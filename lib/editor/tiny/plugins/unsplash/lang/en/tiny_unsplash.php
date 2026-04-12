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
 * Plugin strings are defined here.
 *
 * @package     tiny_unsplash
 * @category    string
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Unsplash Images';
$string['pluginname_desc'] = 'Search and insert free, high-quality images from Unsplash directly into the TinyMCE editor.';
$string['privacy:metadata'] = 'The Unsplash Images plugin does not store any personal data. Search queries are sent to the Unsplash API.';
$string['unsplash:use'] = 'Use Unsplash image picker';
$string['button_unsplash'] = 'Unsplash Images';
$string['menuitem_unsplash'] = 'Insert Unsplash image';
$string['settings'] = 'Unsplash plugin settings';
$string['settings_desc'] = 'Configure the Unsplash image picker plugin for TinyMCE.';
$string['apikey'] = 'Unsplash Access Key';
$string['apikey_desc'] = 'Enter your Unsplash API Access Key. You can get one for free at <a href="https://unsplash.com/developers" target="_blank">unsplash.com/developers</a>.';
$string['appname'] = 'Application name';
$string['appname_desc'] = 'The name of your application as registered on Unsplash. Used for UTM attribution links.';
$string['perpage'] = 'Results per page';
$string['perpage_desc'] = 'Number of images to show per search page (max 30).';
$string['dialog_title'] = 'Insert image from Unsplash';
$string['search_placeholder'] = 'Search free high-resolution photos...';
$string['search_button'] = 'Search';
$string['orientation_all'] = 'All orientations';
$string['orientation_landscape'] = 'Landscape';
$string['orientation_portrait'] = 'Portrait';
$string['orientation_squarish'] = 'Square';
$string['no_results'] = 'No images found. Try a different search term.';
$string['loading'] = 'Loading...';
$string['photo_by'] = 'Photo by';
$string['on_unsplash'] = 'on Unsplash';
$string['insert_hotlink'] = 'Insert (hotlink)';
$string['insert_download'] = 'Download & insert';
$string['insert'] = 'Insert image';
$string['prev_page'] = 'Previous';
$string['next_page'] = 'Next';
$string['page_info'] = 'Page {$a->current} of {$a->total}';
$string['alt_text'] = 'Alt text';
$string['error_no_apikey'] = 'Unsplash API key is not configured. Please contact your administrator.';
$string['error_api'] = 'Unsplash API error: {$a}';
$string['helplinktext'] = 'Unsplash Images';
