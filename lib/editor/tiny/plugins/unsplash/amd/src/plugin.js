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
 * Tiny Unsplash plugin — main entry point.
 *
 * @module      tiny_unsplash/plugin
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'editor_tiny/loader',
    'editor_tiny/utils',
    'tiny_unsplash/common',
    'tiny_unsplash/options',
    'tiny_unsplash/commands',
    'tiny_unsplash/configuration'
], function(
    Loader,
    Utils,
    Common,
    Options,
    Commands,
    Configuration
) {
    return new Promise(function(resolve) {
        Promise.all([
            Loader.getTinyMCE(),
            Utils.getPluginMetadata(Common.component, Common.pluginName),
            Commands.getSetup(),
        ]).then(function(results) {
            var tinyMCE = results[0];
            var pluginMetadata = results[1];
            var setupCommands = results[2];

            tinyMCE.PluginManager.add(Common.pluginName, function(editor) {
                Options.register(editor);
                setupCommands(editor);
                return pluginMetadata;
            });

            return resolve([Common.pluginName, Configuration]);
        }).catch(function(error) {
            window.console.error('[tiny_unsplash] Plugin setup error:', error);
            return resolve([Common.pluginName, Configuration]);
        });
    });
});
