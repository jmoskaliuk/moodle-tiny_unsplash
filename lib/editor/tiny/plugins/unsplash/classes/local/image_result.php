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
 * Provider-agnostic image result.
 *
 * @package     tiny_unsplash
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tiny_unsplash\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalised representation of a stock image — provider-agnostic shape that the
 * UI layer and File API helper both consume.
 */
class image_result {

    /** @var string Provider key: 'unsplash' | 'pexels' | 'pixabay'. */
    public string $provider;

    /** @var string Provider-internal image id (string for cross-provider safety). */
    public string $id;

    /** @var string Thumbnail URL (gallery preview). */
    public string $thumbnailurl;

    /** @var string Small/preview URL. */
    public string $previewurl;

    /** @var string Full URL suitable for embedding. */
    public string $fullurl;

    /** @var string|null Larger original (if provider exposes one). */
    public ?string $originalurl;

    /** @var string Author / photographer name. */
    public string $authorname;

    /** @var string Profile / author URL on the source site. */
    public string $authorurl;

    /** @var string Public page URL of the asset on the source site. */
    public string $sourceurl;

    /** @var string Suggested alt text (provider description, may be empty). */
    public string $alttext;

    /** @var int Width in pixels. */
    public int $width;

    /** @var int Height in pixels. */
    public int $height;

    /** @var string License identifier (e.g. 'pexels-license', 'pixabay-content-license'). */
    public string $license;

    /**
     * Convert to a plain array for transport (web service responses, JS).
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'provider'     => $this->provider,
            'id'           => $this->id,
            'thumbnailurl' => $this->thumbnailurl,
            'previewurl'   => $this->previewurl,
            'fullurl'      => $this->fullurl,
            'originalurl'  => $this->originalurl,
            'authorname'   => $this->authorname,
            'authorurl'    => $this->authorurl,
            'sourceurl'    => $this->sourceurl,
            'alttext'      => $this->alttext,
            'width'        => $this->width,
            'height'       => $this->height,
            'license'      => $this->license,
        ];
    }
}
