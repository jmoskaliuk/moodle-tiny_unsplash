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
 * Unsplash API client.
 *
 * @package     tiny_unsplash
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tiny_unsplash\api;

use curl;
use moodle_exception;
use tiny_unsplash\local\image_result;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

/**
 * Unsplash API client. All calls are server-side; no key reaches the browser.
 *
 * Docs: https://unsplash.com/documentation
 */
class unsplash_client extends base_client {

    /** @var string */
    private const BASE_URL = 'https://api.unsplash.com';

    public function get_provider(): string {
        return 'unsplash';
    }

    protected function auth_mode(): string {
        return 'header';
    }

    protected function auth_header_value(): string {
        return 'Authorization: Client-ID ' . $this->get_api_key();
    }

    protected function get_api_key(): string {
        $key = (string) get_config('tiny_unsplash', 'apikey');
        if ($key === '') {
            throw new moodle_exception('error_no_apikey_unsplash', 'tiny_unsplash');
        }
        return $key;
    }

    protected function allowed_query_keys(): array {
        return ['query', 'page', 'per_page', 'orientation', 'order_by', 'color', 'content_filter'];
    }

    /**
     * Search photos.
     *
     * @param string $query
     * @param int $page
     * @param int $perpage
     * @param string $orientation '' | 'landscape' | 'portrait' | 'squarish'
     * @return array{results: image_result[], total: int, page: int, perpage: int}
     */
    public function search_photos(string $query, int $page = 1, int $perpage = 12, string $orientation = ''): array {
        $query = trim($query);
        if ($query === '') {
            return ['results' => [], 'total' => 0, 'page' => $page, 'perpage' => $perpage];
        }

        $params = [
            'query'    => $query,
            'page'     => max(1, $page),
            'per_page' => max(1, min(30, $perpage)),
        ];
        if ($orientation !== '' && $orientation !== 'all') {
            $params['orientation'] = $orientation;
        }

        $data = $this->get(self::BASE_URL . '/search/photos', $params);

        $results = [];
        foreach ($data['results'] ?? [] as $photo) {
            $results[] = $this->map_photo($photo);
        }

        return [
            'results' => $results,
            'total'   => (int) ($data['total'] ?? 0),
            'page'    => $page,
            'perpage' => $perpage,
        ];
    }

    /**
     * Fetch a single photo by id (used right before download to obtain a fresh
     * download_location for the attribution-tracking call).
     *
     * @param string $id
     * @return image_result|null
     */
    public function get_by_id(string $id): ?image_result {
        if ($id === '' || preg_match('/[^A-Za-z0-9_\-]/', $id)) {
            return null;
        }
        try {
            $data = $this->get(self::BASE_URL . '/photos/' . rawurlencode($id), []);
        } catch (moodle_exception $e) {
            return null;
        }
        return empty($data['id']) ? null : $this->map_photo($data);
    }

    /**
     * Trigger Unsplash's download-tracking endpoint as required by their API
     * guidelines whenever a user actually inserts (i.e. "downloads") a photo.
     *
     * Best-effort — failures are swallowed.
     *
     * @param string $downloadlocation Absolute URL from photo.links.download_location
     */
    public function trigger_download(string $downloadlocation): void {
        if ($downloadlocation === '') {
            return;
        }
        try {
            $curl = new curl();
            $curl->setopt([
                'CURLOPT_TIMEOUT'        => 5,
                'CURLOPT_CONNECTTIMEOUT' => 3,
                'CURLOPT_RETURNTRANSFER' => true,
            ]);
            $curl->setHeader([
                'Authorization: Client-ID ' . $this->get_api_key(),
                'Accept-Version: v1',
            ]);
            $curl->get($downloadlocation);
        } catch (\Throwable $e) {
            debugging('Unsplash download tracking failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Map a Unsplash photo payload to the normalised DTO. Author / source
     * URLs carry the UTM suffix mandated by Unsplash branding guidelines.
     *
     * @param array $photo
     * @return image_result
     */
    protected function map_photo(array $photo): image_result {
        $appname = (string) get_config('tiny_unsplash', 'appname');
        if ($appname === '') {
            $appname = 'moodle_unsplash';
        }
        $utm = '?utm_source=' . rawurlencode($appname) . '&utm_medium=referral';

        $r = new image_result();
        $r->provider     = $this->get_provider();
        $r->id           = (string) ($photo['id'] ?? '');
        $r->thumbnailurl = (string) ($photo['urls']['small'] ?? '');
        $r->previewurl   = (string) ($photo['urls']['small'] ?? '');
        $r->fullurl      = (string) ($photo['urls']['regular'] ?? '');
        $r->originalurl  = !empty($photo['urls']['full']) ? (string) $photo['urls']['full'] : null;
        $r->authorname   = (string) ($photo['user']['name'] ?? '');
        $authorhtml      = (string) ($photo['user']['links']['html'] ?? '');
        $r->authorurl    = $authorhtml !== '' ? $authorhtml . $utm : '';
        $sourcehtml      = (string) ($photo['links']['html'] ?? '');
        $r->sourceurl    = $sourcehtml !== '' ? $sourcehtml . $utm : '';
        $r->alttext      = (string) ($photo['alt_description'] ?? $photo['description'] ?? '');
        $r->width        = (int) ($photo['width']  ?? 0);
        $r->height       = (int) ($photo['height'] ?? 0);
        $r->license      = 'unsplash-license';
        return $r;
    }

    /**
     * Convenience: download_location from a freshly-fetched photo (needed for
     * trigger_download). Not stored on image_result because it's Unsplash-only.
     *
     * @param string $id
     * @return string|null
     */
    public function get_download_location(string $id): ?string {
        if ($id === '' || preg_match('/[^A-Za-z0-9_\-]/', $id)) {
            return null;
        }
        try {
            $data = $this->get(self::BASE_URL . '/photos/' . rawurlencode($id), []);
        } catch (moodle_exception $e) {
            return null;
        }
        $loc = $data['links']['download_location'] ?? null;
        return $loc ? (string) $loc : null;
    }
}
