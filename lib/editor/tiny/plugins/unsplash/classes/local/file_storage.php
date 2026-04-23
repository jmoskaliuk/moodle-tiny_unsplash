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
 * File API integration for downloaded stock images.
 *
 * @package     tiny_unsplash
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tiny_unsplash\local;

use context;
use curl;
use moodle_exception;
use stored_file;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

/**
 * Downloads a remote stock image and stores it via the Moodle File API.
 *
 * Used for Pixabay (mandatory: no hotlinking) and optional for Pexels when the
 * caller chooses "download & insert" rather than "hotlink".
 *
 * Persisted metadata per file:
 *  - source (provider key)
 *  - author
 *  - license
 *  - source URL (canonical page on the provider site)
 *
 * The caller is responsible for capability checks on the target context.
 */
class file_storage {

    /** @var string File area name reserved for stock images. */
    public const FILEAREA = 'stockimage';

    /** @var string Component for stored files. */
    public const COMPONENT = 'tiny_unsplash';

    /**
     * Download and save an image into the user's draft area.
     *
     * @param image_result $image Normalised image from a provider client.
     * @param int $contextid Target context (typically user context for the editor's draft area).
     * @param int|null $draftitemid Optional existing draft itemid; new one created if null.
     * @return array{file: stored_file, url: \moodle_url, draftitemid: int}
     * @throws moodle_exception
     */
    public static function download_to_draft(image_result $image, int $contextid, ?int $draftitemid = null): array {
        global $USER;

        if (empty($image->fullurl)) {
            throw new moodle_exception('error_api', self::COMPONENT, '', 'Missing source URL');
        }

        // Use the user context for draft files (Moodle convention).
        $usercontext = \context_user::instance($USER->id);
        $itemid = $draftitemid ?: file_get_unused_draft_itemid();

        $filename = self::build_filename($image);

        $fs = get_file_storage();
        // Avoid duplicate downloads in the same draft area.
        $existing = $fs->get_file($usercontext->id, 'user', 'draft', $itemid, '/', $filename);
        if ($existing) {
            return [
                'file'        => $existing,
                'url'         => self::draft_url($existing),
                'draftitemid' => $itemid,
            ];
        }

        // Download via Moodle's curl wrapper into a temp path. Avoids loading the full
        // body in memory for large originals.
        $tmpfile = make_request_directory() . '/' . $filename;
        $curl = new curl();
        $curl->setopt([
            'CURLOPT_TIMEOUT'        => 30,
            'CURLOPT_CONNECTTIMEOUT' => 5,
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_MAXREDIRS'      => 3,
        ]);
        $fp = fopen($tmpfile, 'wb');
        if (!$fp) {
            throw new moodle_exception('error_api', self::COMPONENT, '', 'Cannot open temp file');
        }
        $curl->download_one($image->fullurl, [], ['file' => $fp]);
        fclose($fp);

        $info = $curl->get_info();
        $httpcode = (int) ($info['http_code'] ?? 0);
        if ($httpcode < 200 || $httpcode >= 300 || !file_exists($tmpfile) || filesize($tmpfile) === 0) {
            throw new moodle_exception('error_api', self::COMPONENT, '', "Download failed (HTTP {$httpcode})");
        }

        $filerecord = [
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $itemid,
            'filepath'  => '/',
            'filename'  => $filename,
            'author'    => $image->authorname,
            'license'   => $image->license,
            // 'source' is stored as a serialized stdClass per Moodle convention.
            'source'    => serialize((object) [
                'source'   => $image->sourceurl,
                'author'   => $image->authorname,
                'provider' => $image->provider,
            ]),
        ];

        $stored = $fs->create_file_from_pathname($filerecord, $tmpfile);

        return [
            'file'        => $stored,
            'url'         => self::draft_url($stored),
            'draftitemid' => $itemid,
        ];
    }

    /**
     * Build a safe filename from the provider + id, preserving the original extension.
     *
     * @param image_result $image
     * @return string
     */
    protected static function build_filename(image_result $image): string {
        $ext = pathinfo(parse_url($image->fullurl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $ext = 'jpg';
        }
        return clean_filename($image->provider . '-' . $image->id . '.' . $ext);
    }

    /**
     * Build the draftfile URL for an embeddable <img src>.
     *
     * @param stored_file $file
     * @return \moodle_url
     */
    protected static function draft_url(stored_file $file): \moodle_url {
        return \moodle_url::make_draftfile_url(
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
    }
}
