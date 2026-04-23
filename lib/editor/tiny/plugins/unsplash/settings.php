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
 * Plugin administration pages are defined here.
 *
 * @package     tiny_unsplash
 * @category    admin
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading(
            'tiny_unsplash/pluginname',
            get_string('pluginname', 'tiny_unsplash'),
            get_string('pluginname_desc', 'tiny_unsplash')
        ));

        // ---------------------------------------------------------------------
        // Unsplash.
        // ---------------------------------------------------------------------
        $settings->add(new admin_setting_heading(
            'tiny_unsplash/unsplash_heading',
            get_string('settings_unsplash', 'tiny_unsplash'),
            ''
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'tiny_unsplash/apikey',
            get_string('apikey', 'tiny_unsplash'),
            get_string('apikey_desc', 'tiny_unsplash'),
            ''
        ));

        $settings->add(new admin_setting_configtext(
            'tiny_unsplash/appname',
            get_string('appname', 'tiny_unsplash'),
            get_string('appname_desc', 'tiny_unsplash'),
            'moodle_unsplash',
            PARAM_ALPHANUMEXT
        ));

        // ---------------------------------------------------------------------
        // Pexels.
        // ---------------------------------------------------------------------
        $settings->add(new admin_setting_heading(
            'tiny_unsplash/pexels_heading',
            get_string('settings_pexels', 'tiny_unsplash'),
            get_string('settings_pexels_desc', 'tiny_unsplash')
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'tiny_unsplash/pexels_apikey',
            get_string('pexels_apikey', 'tiny_unsplash'),
            get_string('pexels_apikey_desc', 'tiny_unsplash'),
            ''
        ));

        // ---------------------------------------------------------------------
        // Pixabay.
        // ---------------------------------------------------------------------
        $settings->add(new admin_setting_heading(
            'tiny_unsplash/pixabay_heading',
            get_string('settings_pixabay', 'tiny_unsplash'),
            get_string('settings_pixabay_desc', 'tiny_unsplash')
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'tiny_unsplash/pixabay_apikey',
            get_string('pixabay_apikey', 'tiny_unsplash'),
            get_string('pixabay_apikey_desc', 'tiny_unsplash'),
            ''
        ));

        // ---------------------------------------------------------------------
        // Common.
        // ---------------------------------------------------------------------
        $settings->add(new admin_setting_heading(
            'tiny_unsplash/common_heading',
            get_string('settings_common', 'tiny_unsplash'),
            ''
        ));

        $settings->add(new admin_setting_configtext(
            'tiny_unsplash/perpage',
            get_string('perpage', 'tiny_unsplash'),
            get_string('perpage_desc', 'tiny_unsplash'),
            '12',
            PARAM_INT
        ));

        // Pixabay requires a minimum cache of 24 hours; we enforce that floor in code.
        $settings->add(new admin_setting_configduration(
            'tiny_unsplash/cachettl',
            get_string('cachettl', 'tiny_unsplash'),
            get_string('cachettl_desc', 'tiny_unsplash'),
            DAYSECS,
            HOURSECS
        ));

        $settings->add(new admin_setting_configcheckbox(
            'tiny_unsplash/showattribution',
            get_string('showattribution', 'tiny_unsplash'),
            get_string('showattribution_desc', 'tiny_unsplash'),
            1
        ));
    }
}
