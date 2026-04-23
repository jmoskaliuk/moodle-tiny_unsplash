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
 * No API keys are forwarded to the browser — all outbound HTTP runs through
 * the Moodle web service proxy.
 *
 * @module      tiny_unsplash/options
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['editor_tiny/options', 'tiny_unsplash/common'], function(Options, Common) {

    var perpageOption = Options.getPluginOptionName(Common.pluginName, 'perpage');
    var contextidOption = Options.getPluginOptionName(Common.pluginName, 'contextid');
    var providersOption = Options.getPluginOptionName(Common.pluginName, 'providers');
    var showAttribOption = Options.getPluginOptionName(Common.pluginName, 'showattribution');

    var register = function(editor) {
        editor.options.register(perpageOption, {processor: 'number'});
        editor.options.register(contextidOption, {processor: 'number'});
        editor.options.register(providersOption, {processor: 'object'});
        editor.options.register(showAttribOption, {processor: 'boolean'});
    };

    var getPerPage = function(editor) {
        return editor.options.get(perpageOption) || 12;
    };
    var getContextId = function(editor) {
        return editor.options.get(contextidOption) || 0;
    };
    var getProviders = function(editor) {
        return editor.options.get(providersOption) || {unsplash: false, pexels: false, pixabay: false};
    };
    var getShowAttribution = function(editor) {
        return editor.options.get(showAttribOption) !== false;
    };

    return {
        register: register,
        getPerPage: getPerPage,
        getContextId: getContextId,
        getProviders: getProviders,
        getShowAttribution: getShowAttribution,
    };
});
