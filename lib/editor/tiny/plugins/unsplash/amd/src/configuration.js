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
 * Toolbar / menu configuration for tiny_unsplash.
 *
 * @module      tiny_unsplash/configuration
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['tiny_unsplash/common', 'editor_tiny/utils'], function(Common, Utils) {

    /**
     * Get the toolbar configuration — add our button to the "content" section.
     *
     * @param {object} instanceConfig
     * @returns {object}
     */
    var getToolbarConfiguration = function(instanceConfig) {
        var toolbar = instanceConfig.toolbar;
        toolbar = Utils.addToolbarButtons(toolbar, 'content', [
            Common.pluginButtonName,
        ]);
        return toolbar;
    };

    /**
     * Get the menu configuration — add our menu item to the "insert" menu.
     *
     * @param {object} instanceConfig
     * @returns {object}
     */
    var getMenuConfiguration = function(instanceConfig) {
        var menu = instanceConfig.menu;
        menu = Utils.addMenubarItem(menu, 'insert', [
            Common.pluginMenuItem,
        ].join(' '));
        return menu;
    };

    /**
     * Configure the editor instance.
     *
     * @param {object} instanceConfig
     * @returns {object}
     */
    var configure = function(instanceConfig) {
        return {
            toolbar: getToolbarConfiguration(instanceConfig),
            menu: getMenuConfiguration(instanceConfig),
        };
    };

    return {
        configure: configure,
    };
});
