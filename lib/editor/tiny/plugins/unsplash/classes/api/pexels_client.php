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
 * Pexels API client.
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
 * Pexels API client. Hotlinking is permitted, attribution is recommended.
 *
 * Docs: https://www.pexels.com/api/documentation/
 */
class pexels_client extends base_client {

    /** @var string */
    private const BASE_URL = 'https://api.pexels.com/v1';

    /** @var string */
    private const VIDEO_URL = 'https://api.pexels.com/videos';

    public function get_provider(): string {
        return 'pexels';
    }

    protected function auth_mode(): string {
        return 'header';
    }

    protected function auth_header_value(): string {
        return 'Authorization: ' . $this->get_api_key();
    }

    protected function get_api_key(): string {
        $key = (string) get_config('tiny_unsplash', 'pexels_apikey');
        if ($key === '') {
            throw new moodle_exception('error_no_apikey_pexels', 'tiny_unsplash');
        }
        return $key;
    }

    protected function allowed_query_keys(): array {
        return ['query', 'per_page', 'page', 'orientation', 'size', 'color', 'locale'];
    }

    /**
     * Search photos.
     *
     * @param string $query Search term (must be non-empty).
     * @param int $page 1-based.
     * @param int $perpage Max 80 per Pexels docs.
     * @param string $orientation '' | 'landscape' | 'portrait' | 'square'.
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
            'per_page' => max(1, min(80, $perpage)),
        ];
        if ($orientation !== '' && $orientation !== 'all') {
            $params['orientation'] = $orientation;
        }

        $data = $this->get(self::BASE_URL . '/search', $params);

        $results = [];
        foreach ($data['photos'] ?? [] as $photo) {
            $results[] = $this->map_photo($photo);
        }

        return [
            'results' => $results,
            'total'   => (int) ($data['total_results'] ?? 0),
            'page'    => (int) ($data['page'] ?? $page),
            'perpage' => (int) ($data['per_page'] ?? $perpage),
        ];
    }

    /**
     * Curated photos (default landing).
     *
     * @param int $page
     * @param int $perpage
     * @return array
     */
    public function curated(int $page = 1, int $perpage = 12): array {
        $data = $this->get(self::BASE_URL . '/curated', [
            'page'     => max(1, $page),
            'per_page' => max(1, min(80, $perpage)),
        ]);

        $results = [];
        foreach ($data['photos'] ?? [] as $photo) {
            $results[] = $this->map_photo($photo);
        }

        return [
            'results' => $results,
            // Curated has no total, signal "unknown" by returning -1.
            'total'   => -1,
            'page'    => (int) ($data['page'] ?? $page),
            'perpage' => (int) ($data['per_page'] ?? $perpage),
        ];
    }

    /**
     * Map a Pexels photo payload to the normalised DTO.
     *
     * @param array $photo
     * @return image_result
     */
    protected function map_photo(array $photo): image_result {
        $r = new image_result();
        $r->provider     = $this->get_provider();
        $r->id           = (string) ($photo['id'] ?? '');
        $r->thumbnailurl = (string) ($photo['src']['tiny']    ?? '');
        $r->previewurl   = (string) ($photo['src']['medium']  ?? '');
        $r->fullurl      = (string) ($photo['src']['large2x'] ?? $photo['src']['large'] ?? '');
        $r->originalurl  = (string) ($photo['src']['original'] ?? '') ?: null;
        $r->authorname   = (string) ($photo['photographer'] ?? '');
        $r->authorurl    = (string) ($photo['photographer_url'] ?? '');
        $r->sourceurl    = (string) ($photo['url'] ?? '');
        $r->alttext      = (string) ($photo['alt'] ?? '');
        $r->width        = (int) ($photo['width']  ?? 0);
        $r->height       = (int) ($photo['height'] ?? 0);
        $r->license      = 'pexels-license';
        return $r;
    }
}
