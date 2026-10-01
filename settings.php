<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Outcome Mapper settings.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Settings for local_outcomemapper.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_outcomemapper', get_string('pluginname', 'local_outcomemapper'));

    $settings->add(new admin_setting_configtext(
        'local_outcomemapper/maxitemchars',
        get_string('maxitemchars', 'local_outcomemapper'),
        get_string('maxitemchars_desc', 'local_outcomemapper'),
        3000,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_outcomemapper/targetbatchsize',
        get_string('targetbatchsize', 'local_outcomemapper'),
        get_string('targetbatchsize_desc', 'local_outcomemapper'),
        8,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_outcomemapper/objectivebatchsize',
        get_string('objectivebatchsize', 'local_outcomemapper'),
        get_string('objectivebatchsize_desc', 'local_outcomemapper'),
        50,
        PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}
