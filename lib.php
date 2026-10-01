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
 * Outcome Mapper lib.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Library callbacks for local_outcomemapper.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add Outcome Mapper to course navigation.
 *
 * @param navigation_node $navigation Course navigation.
 * @param stdClass $course Course.
 * @param context_course $context Course context.
 */
function local_outcomemapper_extend_navigation_course(
    navigation_node $navigation,
    stdClass $course,
    context_course $context
): void {
    if (!has_capability('local/outcomemapper:analyse', $context)) {
        return;
    }

    $url = new moodle_url('/local/outcomemapper/index.php', ['courseid' => $course->id]);
    $navigation->add(
        get_string('pluginname', 'local_outcomemapper'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_outcomemapper',
        new pix_icon('i/report', '')
    );
}
