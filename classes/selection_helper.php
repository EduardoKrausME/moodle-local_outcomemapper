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
 * Outcome Mapper selection_helper.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;


/**
 * Build selectable course scope while respecting Moodle visibility.
 *
 * @package   local_outcomemapper
 */
class selection_helper {
    /**
     * Get selectable course sections.
     *
     * @param int $courseid Course ID.
     * @return array
     */
    public static function section_options(int $courseid): array {
        $course = get_course($courseid);
        $modinfo = get_fast_modinfo($course);
        $options = [];

        foreach ($modinfo->get_section_info_all() as $section) {
            if (!$section || (int)$section->section === 0) {
                continue;
            }
            if (!$section->uservisible) {
                continue;
            }

            $name = get_section_name($course, $section);
            $options[(int)$section->id] = $name;
        }

        return $options;
    }

    /**
     * Get selectable course modules.
     *
     * @param int $courseid Course ID.
     * @return array
     */
    public static function module_options(int $courseid): array {
        $course = get_course($courseid);
        $modinfo = get_fast_modinfo($course);
        $options = [];

        foreach ($modinfo->get_cms() as $cm) {
            if (!access_helper::can_access_cm($cm)) {
                continue;
            }

            $section = $modinfo->get_section_info($cm->sectionnum);
            $sectionname = $section ? get_section_name($course, $section) : '';
            $label = $sectionname !== '' ? $sectionname . ' — ' . $cm->name : $cm->name;
            $options[(int)$cm->id] = $label;
        }

        return $options;
    }
}
