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
 * Pixabay API client.
 *
 * @package     tiny_unsplash
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tiny_unsplash\api;

use moodle_exception;
use tiny_unsplash\local\image_result;

defined('MOODLE_INTERNAL') || die();

/**
 * Pixabay API client.
 *
 * Compliance highlights enforced or assumed here:
 *  - No hotlinking — callers MUST download via {@see \tiny_unsplash\local\file_storage}.
 *  - Mandatory ≥ 24h cache (enforced via minimum_cache_ttl()).
 *  - URLs from the API expire after 24h, so re-fetch on save if expired.
 *
 * Docs: https://pixabay.com/api/docs/
 */
class pixabay_client extends base_client {

    /** @var string */
    private const BASE_URL = 'https://pixabay.com/api/';

    /** @var string */
    private const VIDEO_URL = 'https://pixabay.com/api/videos/';

    public function get_provider(): string {
        return 'pixabay';
    }

    protected function minimum_cache_ttl(): int {
        // Pixabay terms require caching for at least 24 hours.
        return DAYSECS;
    }

    protected function auth_mode(): string {
        return 'query';
    }

    protected function auth_query_param(): array {
        return ['key', $this->get_api_key()];
    }

    protected function get_api_key(): string {
        $key = (string) get_config('tiny_unsplash', 'pixabay_apikey');
        if ($key === '') {
            throw new moodle_exception('error_no_apikey_pixabay', 'tiny_unsplash');
        }
        return $key;
    }

    protected function allowed_query_keys(): array {
        return [
            'q', 'lang', 'image_type', 'orientation', 'category', 'min_width', 'min_height',
            'colors', 'editors_choice', 'safesearch', 'order', 'page', 'per_page',
        ];
    }

    /**
     * Search images.
     *
     * @param string $query Search term.
     * @param int $page 1-based.
     * @param int $perpage 3..200 per docs (we cap at 80).
     * @param string $orientation '' | 'horizontal' | 'vertical'.
     * @return array{results: image_result[], total: int, page: int, perpage: int}
     */
    public function search_photos(string $query, int $page = 1, int $perpage = 12, string $orientation = ''): array {
        $query = trim($query);

        // Map normalised orientation values to Pixabay vocabulary.
        $maporient = [
            'all'       => '',
            'landscape' => 'horizontal',
            'portrait'  => 'vertical',
            'squarish'  => '', // Pixabay has no native square orientation.
        ];
        $resolved = $maporient[$orientation] ?? $orientation;

        $params = [
            'q'           => $query,
            'page'        => max(1, $page),
            'per_page'    => max(3, min(80, $perpage)),
            'image_type'  => 'photo',
            'safesearch'  => 'true',
        ];
        if ($resolved !== '') {
            $params['orientation'] = $resolved;
        }

        $data = $this->get(self::BASE_URL, $params);

        $results = [];
        foreach ($data['hits'] ?? [] as $hit) {
            $results[] = $this->map_photo($hit);
        }

        return [
            'results' => $results,
            'total'   => (int) ($data['totalHits'] ?? 0),
            'page'    => max(1, $page),
            'perpage' => $perpage,
        ];
    }

    /**
     * Fetch a single hit by id (used right before download to refresh expired URLs).
     *
     * @param string $id
     * @return image_result|null
     */
    public function get_by_id(string $id): ?image_result {
        if (!ctype_digit($id)) {
            return null;
        }
        $data = $this->get(self::BASE_URL, ['id' => $id]);
        $hit = $data['hits'][0] ?? null;
        return $hit ? $this->map_photo($hit) : null;
    }

    /**
     * Map a Pixabay hit to the normalised DTO.
     *
     * Note: largeImageURL / fullHDURL / imageURL are temporary (24h). Callers
     * that need permanent embedding MUST download immediately.
     *
     * @param array $hit
     * @return image_result
     */
    protected function map_photo(array $hit): image_result {
        $r = new image_result();
        $r->provider     = $this->get_provider();
        $r->id           = (string) ($hit['id'] ?? '');
        $r->thumbnailurl = (string) ($hit['previewURL'] ?? '');
        $r->previewurl   = (string) ($hit['webformatURL'] ?? '');
        $r->fullurl      = (string) ($hit['largeImageURL'] ?? $hit['webformatURL'] ?? '');
        $r->originalurl  = !empty($hit['imageURL']) ? (string) $hit['imageURL'] : null;
        $r->authorname   = (string) ($hit['user'] ?? '');
        $userid          = (int) ($hit['user_id'] ?? 0);
        $r->authorurl    = $userid ? "https://pixabay.com/users/{$userid}/" : 'https://pixabay.com/';
        $r->sourceurl    = (string) ($hit['pageURL'] ?? '');
        $r->alttext      = (string) ($hit['tags'] ?? '');
        $r->width        = (int) ($hit['imageWidth']  ?? 0);
        $r->height       = (int) ($hit['imageHeight'] ?? 0);
        $r->license      = 'pixabay-content-license';
        return $r;
    }
}
