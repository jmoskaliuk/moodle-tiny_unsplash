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
use tiny_unsplash\api\pexels_client;
use tiny_unsplash\api\pixabay_client;
use tiny_unsplash\api\unsplash_client;
use tiny_unsplash\local\file_storage;
use tiny_unsplash\local\image_result;

defined('MOODLE_INTERNAL') || die();

/**
 * Server-side: refresh provider URL (Pixabay URLs expire after 24h, Unsplash
 * needs a fresh download_location for tracking), download the image and store
 * it via the Moodle File API in the user's draft area.
 *
 * Returns a draftfile URL the editor embeds. Used for all providers — there is
 * no hotlink path anymore.
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
            [$image, $unsplashclient] = self::resolve_image($params['provider'], $params['id']);
            if ($image === null) {
                return self::error_response('Image not found');
            }
            $stored = file_storage::download_to_draft(
                $image,
                $params['contextid'],
                $params['draftitemid'] ?: null
            );

            // Per Unsplash API guidelines: hit download_location only when the
            // user actually inserts the photo. We've now downloaded it.
            if ($unsplashclient instanceof unsplash_client) {
                $loc = $unsplashclient->get_download_location($params['id']);
                if ($loc !== null) {
                    $unsplashclient->trigger_download($loc);
                }
            }
        } catch (moodle_exception $e) {
            return self::error_response($e->getMessage());
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
     * Resolve a provider id to a fresh image_result. Returns the client too so
     * the caller can perform provider-specific follow-up calls (Unsplash
     * download tracking).
     *
     * @param string $provider
     * @param string $id
     * @return array{0: image_result|null, 1: object|null}
     */
    protected static function resolve_image(string $provider, string $id): array {
        switch ($provider) {
            case 'unsplash':
                $client = new unsplash_client();
                return [$client->get_by_id($id), $client];
            case 'pexels':
                $client = new pexels_client();
                return [$client->get_by_id($id), null];
            case 'pixabay':
                $client = new pixabay_client();
                return [$client->get_by_id($id), null];
            default:
                throw new moodle_exception('error_api', 'tiny_unsplash', '', 'Unknown provider');
        }
    }

    /**
     * Build a uniform error response shape.
     *
     * @param string $message
     * @return array
     */
    protected static function error_response(string $message): array {
        return [
            'url'         => '',
            'draftitemid' => 0,
            'authorname'  => '',
            'authorurl'   => '',
            'sourceurl'   => '',
            'license'     => '',
            'provider'    => '',
            'error'       => $message,
        ];
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
