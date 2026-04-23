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

        $unsplashkey = (string) get_config('tiny_unsplash', 'apikey');
        $appname     = (string) get_config('tiny_unsplash', 'appname');
        $perpage     = (int) get_config('tiny_unsplash', 'perpage');
        $showattr    = (int) get_config('tiny_unsplash', 'showattribution');

        // Boolean availability flags only — keys stay server-side for Pexels / Pixabay.
        $hasspexels  = (string) get_config('tiny_unsplash', 'pexels_apikey') !== '';
        $haspixabay  = (string) get_config('tiny_unsplash', 'pixabay_apikey') !== '';

        // No provider configured at all? Disable the button.
        if ($unsplashkey === '' && !$hasspexels && !$haspixabay) {
            return [];
        }

        if ($appname === '') {
            $appname = 'moodle_unsplash';
        }
        if ($perpage < 1 || $perpage > 30) {
            $perpage = 12;
        }

        return [
            'apikey'          => $unsplashkey,
            'appname'         => $appname,
            'perpage'         => $perpage,
            'contextid'       => $context->id,
            'showattribution' => $showattr ? true : false,
            'providers'       => [
                'unsplash' => $unsplashkey !== '',
                'pexels'   => $hasspexels,
                'pixabay'  => $haspixabay,
            ],
        ];
    }
}
