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
 * External AJAX services exposed by the tiny_unsplash plugin.
 *
 * @package     tiny_unsplash
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tiny_unsplash_search_images' => [
        'classname'   => 'tiny_unsplash\\external\\search_images',
        'description' => 'Search images on Pexels or Pixabay (server-side proxy).',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'tiny/unsplash:use',
    ],
    'tiny_unsplash_save_image' => [
        'classname'   => 'tiny_unsplash\\external\\save_image',
        'description' => 'Download a stock image into the user draft area.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'tiny/unsplash:use',
    ],
];
