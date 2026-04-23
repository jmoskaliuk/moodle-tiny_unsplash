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
 * External web service: download a stock image into the user's draft area.
 *
 * @package     tiny_unsplash
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tiny_unsplash\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use moodle_exception;
use tiny_unsplash\api\pixabay_client;
use tiny_unsplash\local\file_storage;
use tiny_unsplash\local\image_result;

defined('MOODLE_INTERNAL') || die();

/**
 * Server-side: refresh provider URL (Pixabay URLs expire after 24h), download
 * the image and store it via the Moodle File API in the user's draft area.
 *
 * Returns a draftfile URL the editor can embed. Required for Pixabay
 * (no hotlinking) and optional for Pexels.
 */
class save_image extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'provider'    => new external_value(PARAM_ALPHA, 'Provider key'),
            'id'          => new external_value(PARAM_RAW_TRIMMED, 'Provider asset id'),
            'contextid'   => new external_value(PARAM_INT, 'Editor context id'),
            'draftitemid' => new external_value(PARAM_INT, 'Optional existing draft itemid', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the download.
     *
     * @param string $provider
     * @param string $id
     * @param int $contextid
     * @param int $draftitemid
     * @return array
     */
    public static function execute(string $provider, string $id, int $contextid, int $draftitemid = 0): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'provider'    => $provider,
            'id'          => $id,
            'contextid'   => $contextid,
            'draftitemid' => $draftitemid,
        ]);

        $context = \context::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_capability('tiny/unsplash:use', $context);

        try {
            $image = self::resolve_image($params['provider'], $params['id']);
            if ($image === null) {
                return ['url' => '', 'draftitemid' => 0, 'error' => 'Image not found'];
            }
            $stored = file_storage::download_to_draft(
                $image,
                $params['contextid'],
                $params['draftitemid'] ?: null
            );
        } catch (moodle_exception $e) {
            return ['url' => '', 'draftitemid' => 0, 'error' => $e->getMessage()];
        }

        return [
            'url'         => $stored['url']->out(false),
            'draftitemid' => $stored['draftitemid'],
            'authorname'  => $image->authorname,
            'authorurl'   => $image->authorurl,
            'sourceurl'   => $image->sourceurl,
            'license'     => $image->license,
            'provider'    => $image->provider,
            'error'       => '',
        ];
    }

    /**
     * Resolve a provider id back to an {@see image_result} (re-fetch — URLs may have expired).
     *
     * @param string $provider
     * @param string $id
     * @return image_result|null
     */
    protected static function resolve_image(string $provider, string $id): ?image_result {
        switch ($provider) {
            case 'pixabay':
                return (new pixabay_client())->get_by_id($id);
            // Pexels per-id endpoint can be added here when needed.
            default:
                throw new moodle_exception('error_api', 'tiny_unsplash', '', 'Provider does not support save');
        }
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'url'         => new external_value(PARAM_URL, 'Draftfile URL for embedding'),
            'draftitemid' => new external_value(PARAM_INT, 'Draft itemid (reuse for follow-up uploads)'),
            'authorname'  => new external_value(PARAM_TEXT, 'Author', VALUE_OPTIONAL, ''),
            'authorurl'   => new external_value(PARAM_URL, 'Author URL', VALUE_OPTIONAL, ''),
            'sourceurl'   => new external_value(PARAM_URL, 'Source page URL', VALUE_OPTIONAL, ''),
            'license'     => new external_value(PARAM_ALPHANUMEXT, 'License id', VALUE_OPTIONAL, ''),
            'provider'    => new external_value(PARAM_ALPHA, 'Provider', VALUE_OPTIONAL, ''),
            'error'       => new external_value(PARAM_TEXT, 'Empty if successful'),
        ]);
    }
}
