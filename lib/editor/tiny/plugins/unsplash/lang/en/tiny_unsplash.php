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

$string['pluginname'] = 'Stock images (Unsplash, Pexels, Pixabay)';
$string['pluginname_desc'] = 'Search and insert free, high-quality images from Unsplash, Pexels or Pixabay directly into the TinyMCE editor.';

// Privacy.
$string['privacy:metadata'] = 'The Stock Images plugin does not store personal data. Search queries are sent to the configured providers.';
$string['privacy:metadata:searchquery'] = 'The search term entered by the user.';
$string['privacy:metadata:unsplash'] = 'Search queries are forwarded to the Unsplash API to retrieve image results.';
$string['privacy:metadata:pexels'] = 'Search queries are forwarded to the Pexels API to retrieve image results.';
$string['privacy:metadata:pixabay'] = 'Search queries are forwarded to the Pixabay API to retrieve image results.';

// Capability.
$string['unsplash:use'] = 'Use the stock image picker';

// Buttons / menu.
$string['button_unsplash'] = 'Stock images';
$string['menuitem_unsplash'] = 'Insert stock image';

// Settings — common.
$string['settings'] = 'Stock images plugin settings';
$string['settings_desc'] = 'Configure the stock image picker plugin for TinyMCE.';
$string['settings_unsplash'] = 'Unsplash';
$string['settings_pexels'] = 'Pexels';
$string['settings_pexels_desc'] = 'Attribution is required by Pexels and is rendered automatically below each inserted image.';
$string['settings_pixabay'] = 'Pixabay';
$string['settings_pixabay_desc'] = 'Pixabay terms enforce a minimum API response cache of 24 hours; the plugin honours that floor automatically.';
$string['settings_common'] = 'Common';

// Settings — Unsplash.
$string['apikey'] = 'Unsplash Access Key';
$string['apikey_desc'] = 'Your Unsplash API Access Key. Get one for free at <a href="https://unsplash.com/developers" target="_blank">unsplash.com/developers</a>.';
$string['appname'] = 'Application name';
$string['appname_desc'] = 'The name of your application as registered on Unsplash. Used for UTM attribution links.';

// Settings — Pexels.
$string['pexels_apikey'] = 'Pexels API Key';
$string['pexels_apikey_desc'] = 'Your Pexels API key. Get one at <a href="https://www.pexels.com/api/" target="_blank">pexels.com/api</a>.';

// Settings — Pixabay.
$string['pixabay_apikey'] = 'Pixabay API Key';
$string['pixabay_apikey_desc'] = 'Your Pixabay API key. Get one at <a href="https://pixabay.com/api/docs/" target="_blank">pixabay.com/api/docs</a>.';

// Settings — Common (shared).
$string['perpage'] = 'Results per page';
$string['perpage_desc'] = 'Number of images to show per search page (max 30).';
$string['cachettl'] = 'API cache TTL';
$string['cachettl_desc'] = 'How long search responses are cached. Pixabay terms require a minimum of 24 hours; the plugin enforces this floor automatically.';
$string['showattribution'] = 'Show attribution';
$string['showattribution_desc'] = 'Insert a visible attribution caption (photographer + source) below each inserted image.';

// Dialog.
$string['dialog_title'] = 'Insert image';
$string['provider'] = 'Source';
$string['provider_unsplash'] = 'Unsplash';
$string['provider_pexels'] = 'Pexels';
$string['provider_pixabay'] = 'Pixabay';
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
$string['on_pexels'] = 'on Pexels';
$string['on_pixabay'] = 'on Pixabay';
$string['insert'] = 'Insert image';
$string['prev_page'] = 'Previous';
$string['next_page'] = 'Next';
$string['page_info'] = 'Page {$a->current} of {$a->total}';
$string['alt_text'] = 'Alt text';
$string['error_no_apikey'] = 'No image provider is configured. Please contact your administrator.';
$string['error_no_apikey_unsplash'] = 'Unsplash API key is not configured.';
$string['error_no_apikey_pexels'] = 'Pexels API key is not configured.';
$string['error_no_apikey_pixabay'] = 'Pixabay API key is not configured.';
$string['error_api'] = 'API error: {$a}';
$string['error_download'] = 'Could not download the image. Please try again.';
$string['helplinktext'] = 'Stock images';
