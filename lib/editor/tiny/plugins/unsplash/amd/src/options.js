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
 * Options registration for the tiny_unsplash plugin.
 *
 * @module      tiny_unsplash/options
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['editor_tiny/options', 'tiny_unsplash/common'], function(Options, Common) {

    var apikeyOption = Options.getPluginOptionName(Common.pluginName, 'apikey');
    var appnameOption = Options.getPluginOptionName(Common.pluginName, 'appname');
    var perpageOption = Options.getPluginOptionName(Common.pluginName, 'perpage');

    /**
     * Register options with the TinyMCE editor.
     *
     * @param {TinyMCE.Editor} editor
     */
    var register = function(editor) {
        editor.options.register(apikeyOption, {
            processor: 'string',
        });
        editor.options.register(appnameOption, {
            processor: 'string',
        });
        editor.options.register(perpageOption, {
            processor: 'number',
        });
    };

    /**
     * Get the Unsplash API key.
     *
     * @param {TinyMCE.Editor} editor
     * @returns {string}
     */
    var getApiKey = function(editor) {
        return editor.options.get(apikeyOption) || '';
    };

    /**
     * Get the application name for UTM links.
     *
     * @param {TinyMCE.Editor} editor
     * @returns {string}
     */
    var getAppName = function(editor) {
        return editor.options.get(appnameOption) || 'moodle_unsplash';
    };

    /**
     * Get results per page.
     *
     * @param {TinyMCE.Editor} editor
     * @returns {number}
     */
    var getPerPage = function(editor) {
        return editor.options.get(perpageOption) || 12;
    };

    return {
        register: register,
        getApiKey: getApiKey,
        getAppName: getAppName,
        getPerPage: getPerPage,
    };
});
