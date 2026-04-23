<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Abstract base HTTP client for image stock APIs.
 *
 * @package     tiny_unsplash
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tiny_unsplash\api;

use cache;
use curl;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

/**
 * Shared HTTP plumbing: caching, retry/backoff and error normalisation.
 *
 * Concrete subclasses provide the base URL, auth headers / query params and
 * the response → {@see \tiny_unsplash\local\image_result} mapping.
 */
abstract class base_client {

    /** @var int Maximum retry attempts for transient errors. */
    protected const MAX_RETRIES = 3;

    /** @var int Base backoff in milliseconds (doubled on each retry). */
    protected const BACKOFF_BASE_MS = 500;

    /** @var int Default network/connect timeout in seconds. */
    protected const TIMEOUT = 15;

    /** @var int Cache TTL in seconds. */
    protected $cachettl;

    /**
     * @param int|null $cachettl Override TTL (seconds). Subclasses enforce a minimum if required.
     */
    public function __construct(?int $cachettl = null) {
        $configured = (int) get_config('tiny_unsplash', 'cachettl');
        if ($configured <= 0) {
            $configured = DAYSECS;
        }
        $this->cachettl = max($cachettl ?? $configured, $this->minimum_cache_ttl());
    }

    /**
     * Provider identifier — used for cache namespacing and result attribution.
     */
    abstract public function get_provider(): string;

    /**
     * Minimum required cache TTL for this provider (seconds).
     *
     * Pixabay's terms require ≥ 24h. Pexels has no such requirement, but caching
     * is still recommended for rate-limit hygiene.
     */
    protected function minimum_cache_ttl(): int {
        return 0;
    }

    /**
     * Authentication strategy: 'header' or 'query'.
     */
    abstract protected function auth_mode(): string;

    /**
     * Auth header value (only used when auth_mode() === 'header').
     */
    protected function auth_header_value(): string {
        return '';
    }

    /**
     * Auth query parameter name + value (only used when auth_mode() === 'query').
     *
     * @return array{0:string,1:string}
     */
    protected function auth_query_param(): array {
        return ['', ''];
    }

    /**
     * Resolve the API key from plugin config. Throws if missing.
     */
    abstract protected function get_api_key(): string;

    /**
     * Perform a GET request against the provider, with caching, retry and backoff.
     *
     * @param string $url Absolute URL (without auth params).
     * @param array $params Query parameters (will be url-encoded; auth is added automatically).
     * @return array Decoded JSON response.
     * @throws moodle_exception On non-recoverable error.
     */
    protected function get(string $url, array $params = []): array {
        // Strip any accidentally-passed identifying data — defence in depth.
        $params = $this->sanitise_params($params);

        // Cache key is provider + url + params (excluding auth).
        $cachekey = sha1($this->get_provider() . '|' . $url . '|' . json_encode($params));
        $cache = cache::make('tiny_unsplash', 'apiresults');
        $cached = $cache->get($cachekey);
        if ($cached !== false && is_array($cached)) {
            if (!empty($cached['expires']) && $cached['expires'] > time()) {
                return $cached['payload'];
            }
        }

        // Append auth.
        $headers = ['Accept: application/json'];
        if ($this->auth_mode() === 'header') {
            $headers[] = $this->auth_header_value();
        } else if ($this->auth_mode() === 'query') {
            [$name, $value] = $this->auth_query_param();
            $params[$name] = $value;
        }

        $finalurl = $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($params);

        $attempt = 0;
        $lasterror = null;
        while ($attempt <= self::MAX_RETRIES) {
            $curl = new curl();
            $curl->setopt([
                'CURLOPT_TIMEOUT'        => self::TIMEOUT,
                'CURLOPT_CONNECTTIMEOUT' => 5,
                'CURLOPT_RETURNTRANSFER' => true,
                'CURLOPT_FOLLOWLOCATION' => true,
                'CURLOPT_MAXREDIRS'      => 3,
            ]);
            $curl->setHeader($headers);

            $body = $curl->get($finalurl);
            $info = $curl->get_info();
            $httpcode = (int) ($info['http_code'] ?? 0);

            if ($httpcode >= 200 && $httpcode < 300) {
                $decoded = json_decode($body, true);
                if (!is_array($decoded)) {
                    throw new moodle_exception('error_api', 'tiny_unsplash', '', 'Invalid JSON');
                }
                $cache->set($cachekey, [
                    'expires' => time() + $this->cachettl,
                    'payload' => $decoded,
                ]);
                return $decoded;
            }

            // Decide whether to retry.
            if ($httpcode === 429 || $httpcode >= 500) {
                $lasterror = "HTTP {$httpcode}";
                $sleepms = self::BACKOFF_BASE_MS * (2 ** $attempt);
                // Honour Retry-After if provided (header parsing best-effort).
                $headerblob = $curl->get_raw_response();
                if (is_array($headerblob)) {
                    foreach ($headerblob as $line) {
                        if (stripos($line, 'Retry-After:') === 0) {
                            $hint = (int) trim(substr($line, strlen('Retry-After:')));
                            if ($hint > 0) {
                                $sleepms = max($sleepms, $hint * 1000);
                            }
                            break;
                        }
                    }
                }
                usleep($sleepms * 1000);
                $attempt++;
                continue;
            }

            // Non-retryable.
            throw new moodle_exception('error_api', 'tiny_unsplash', '', "HTTP {$httpcode}");
        }

        throw new moodle_exception('error_api', 'tiny_unsplash', '',
            $lasterror ?? 'Request failed after retries');
    }

    /**
     * Strip any keys that could leak personal data into provider logs.
     *
     * Whitelist-only: only documented search params survive this filter.
     *
     * @param array $params
     * @return array
     */
    protected function sanitise_params(array $params): array {
        $allowed = $this->allowed_query_keys();
        $clean = [];
        foreach ($params as $k => $v) {
            if (in_array($k, $allowed, true) && $v !== null && $v !== '') {
                $clean[$k] = is_string($v) ? trim($v) : $v;
            }
        }
        return $clean;
    }

    /**
     * Whitelist of permitted query keys (subclasses override).
     *
     * @return string[]
     */
    protected function allowed_query_keys(): array {
        return [];
    }
}
