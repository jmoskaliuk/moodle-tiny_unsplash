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
 */
class plugininfo extends plugin implements plugin_with_buttons, plugin_with_configuration {
    /**
     * Get the list of available buttons provided by this plugin.
     *
     * @return array
     */
    public static function get_available_buttons(): array {
        return [
            'tiny_unsplash/unsplash_btn',
        ];
    }

    /**
     * Get plugin configuration for the given context.
     *
     * This is where we pass PHP-side settings (API key, app name, per-page)
     * down to the AMD modules in the browser.
     *
     * @param context $context The context
     * @param array $options Editor options
     * @param array $fpoptions File picker options
     * @param \editor_tiny\editor|null $editor The editor instance
     * @return array Configuration array passed to JS options
     */
    public static function get_plugin_configuration_for_context(
        \context $context,
        array $options,
        array $fpoptions,
        ?\editor_tiny\editor $editor = null
    ): array {
        // Capability check — return empty config to disable the plugin for this user.
        if (!has_capability('tiny/unsplash:use', $context)) {
            return [];
        }

        $apikey  = get_config('tiny_unsplash', 'apikey');
        $appname = get_config('tiny_unsplash', 'appname');
        $perpage = (int) get_config('tiny_unsplash', 'perpage');

        if (empty($apikey)) {
            return [];
        }

        if (empty($appname)) {
            $appname = 'moodle_unsplash';
        }
        if ($perpage < 1 || $perpage > 30) {
            $perpage = 12;
        }

        return [
            'apikey'  => $apikey,
            'appname' => $appname,
            'perpage' => $perpage,
        ];
    }
}
