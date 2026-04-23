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
 * Tiny Unsplash plugin info class.
 *
 * @package     tiny_unsplash
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tiny_unsplash;

use context;
use editor_tiny\plugin;
use editor_tiny\plugin_with_buttons;
use editor_tiny\plugin_with_configuration;

/**
 * Tiny Unsplash plugin info — registers buttons and passes config to the JS side.
 *
 * Note on security: only the (legacy) Unsplash key is forwarded to JS. Pexels
 * and Pixabay keys NEVER leave the server; the JS calls a Moodle web service
 * proxy, which performs the outbound HTTP request.
 */
class plugininfo extends plugin implements plugin_with_buttons, plugin_with_configuration {

    public static function get_available_buttons(): array {
        return [
            'tiny_unsplash/unsplash_btn',
        ];
    }

    /**
     * Get plugin configuration for the given context.
     *
     * Only boolean availability flags + per-page + context id are forwarded to
     * JS. API keys for all three providers stay server-side; the editor calls
     * the `tiny_unsplash_search_images` / `tiny_unsplash_save_image` web
     * services which proxy the outbound HTTP request.
     *
     * @param context $context
     * @param array $options
     * @param array $fpoptions
     * @param \editor_tiny\editor|null $editor
     * @return array
     */
    public static function get_plugin_configuration_for_context(
        \context $context,
        array $options,
        array $fpoptions,
        ?\editor_tiny\editor $editor = null
    ): array {
        if (!has_capability('tiny/unsplash:use', $context)) {
            return [];
        }

        $perpage  = (int) get_config('tiny_unsplash', 'perpage');
        $showattr = (int) get_config('tiny_unsplash', 'showattribution');

        $hasunsplash = (string) get_config('tiny_unsplash', 'apikey') !== '';
        $haspexels   = (string) get_config('tiny_unsplash', 'pexels_apikey') !== '';
        $haspixabay  = (string) get_config('tiny_unsplash', 'pixabay_apikey') !== '';

        if (!$hasunsplash && !$haspexels && !$haspixabay) {
            return [];
        }

        if ($perpage < 1 || $perpage > 30) {
            $perpage = 12;
        }

        return [
            'perpage'         => $perpage,
            'contextid'       => $context->id,
            'showattribution' => $showattr ? true : false,
            'providers'       => [
                'unsplash' => $hasunsplash,
                'pexels'   => $haspexels,
                'pixabay'  => $haspixabay,
            ],
        ];
    }
}
