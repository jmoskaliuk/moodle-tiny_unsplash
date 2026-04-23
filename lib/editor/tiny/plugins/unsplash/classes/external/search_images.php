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
 * External web service: search images on Pexels / Pixabay.
 *
 * @package     tiny_unsplash
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tiny_unsplash\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use moodle_exception;
use tiny_unsplash\api\pexels_client;
use tiny_unsplash\api\pixabay_client;

defined('MOODLE_INTERNAL') || die();

/**
 * Server-side proxy that performs the actual outbound API call. Keeps API keys
 * on the server and centralises rate-limit handling and caching.
 */
class search_images extends external_api {

    /**
     * Parameter definition.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'provider'    => new external_value(PARAM_ALPHA, 'Provider key: pexels|pixabay'),
            'query'       => new external_value(PARAM_TEXT, 'Search query', VALUE_DEFAULT, ''),
            'page'        => new external_value(PARAM_INT, 'Page (1-based)', VALUE_DEFAULT, 1),
            'perpage'     => new external_value(PARAM_INT, 'Results per page', VALUE_DEFAULT, 12),
            'orientation' => new external_value(PARAM_ALPHA, 'all|landscape|portrait|squarish', VALUE_DEFAULT, 'all'),
            'contextid'   => new external_value(PARAM_INT, 'Editor context id'),
        ]);
    }

    /**
     * Execute the search.
     *
     * @param string $provider
     * @param string $query
     * @param int $page
     * @param int $perpage
     * @param string $orientation
     * @param int $contextid
     * @return array
     */
    public static function execute(
        string $provider,
        string $query,
        int $page,
        int $perpage,
        string $orientation,
        int $contextid
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'provider'    => $provider,
            'query'       => $query,
            'page'        => $page,
            'perpage'     => $perpage,
            'orientation' => $orientation,
            'contextid'   => $contextid,
        ]);

        $context = \context::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_capability('tiny/unsplash:use', $context);

        // Bound the page size — protect against accidental huge requests.
        $perpage = max(1, min(80, (int) $params['perpage']));
        $page = max(1, (int) $params['page']);
        $query = trim($params['query']);
        $orientation = $params['orientation'] ?: 'all';

        try {
            switch ($params['provider']) {
                case 'pexels':
                    $client = new pexels_client();
                    $data = $query === ''
                        ? $client->curated($page, $perpage)
                        : $client->search_photos($query, $page, $perpage, $orientation);
                    break;

                case 'pixabay':
                    $client = new pixabay_client();
                    $data = $client->search_photos($query, $page, $perpage, $orientation);
                    break;

                default:
                    throw new moodle_exception('error_api', 'tiny_unsplash', '', 'Unknown provider');
            }
        } catch (moodle_exception $e) {
            // Surface a controlled error to the client without leaking internals.
            return [
                'results' => [],
                'total'   => 0,
                'page'    => $page,
                'perpage' => $perpage,
                'error'   => $e->getMessage(),
            ];
        }

        $results = array_map(fn($r) => $r->to_array(), $data['results']);

        return [
            'results' => $results,
            'total'   => (int) $data['total'],
            'page'    => (int) $data['page'],
            'perpage' => (int) $data['perpage'],
            'error'   => '',
        ];
    }

    /**
     * Return definition.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'results' => new external_multiple_structure(new external_single_structure([
                'provider'     => new external_value(PARAM_ALPHA, 'Provider key'),
                'id'           => new external_value(PARAM_RAW, 'Provider-internal id'),
                'thumbnailurl' => new external_value(PARAM_URL, 'Thumbnail URL'),
                'previewurl'   => new external_value(PARAM_URL, 'Preview URL'),
                'fullurl'      => new external_value(PARAM_URL, 'Full URL'),
                'originalurl'  => new external_value(PARAM_URL, 'Original URL', VALUE_OPTIONAL, null, NULL_ALLOWED),
                'authorname'   => new external_value(PARAM_TEXT, 'Author display name'),
                'authorurl'    => new external_value(PARAM_URL, 'Author profile URL'),
                'sourceurl'    => new external_value(PARAM_URL, 'Asset page on provider site'),
                'alttext'      => new external_value(PARAM_TEXT, 'Alt text'),
                'width'        => new external_value(PARAM_INT, 'Width in pixels'),
                'height'       => new external_value(PARAM_INT, 'Height in pixels'),
                'license'      => new external_value(PARAM_ALPHANUMEXT, 'License identifier'),
            ])),
            'total'   => new external_value(PARAM_INT, 'Total available results'),
            'page'    => new external_value(PARAM_INT, 'Current page'),
            'perpage' => new external_value(PARAM_INT, 'Per-page'),
            'error'   => new external_value(PARAM_TEXT, 'Empty if successful'),
        ]);
    }
}
