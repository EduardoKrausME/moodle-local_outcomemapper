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
 * Course deletion lifecycle callbacks.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;

use core_course\hook\before_course_deleted;

/**
 * Keep local mapping data consistent with course deletion.
 */
final class hook_callbacks {
    /**
     * Erase both analyses and reviewed mappings before course context is removed.
     *
     * @param before_course_deleted $hook Course deletion hook.
     */
    public static function before_course_deleted(before_course_deleted $hook): void {
        global $DB;

        $courseid = (int)$hook->course->id;
        if ($courseid <= 0) {
            return;
        }

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('local_outcomemapper_suggest', ['courseid' => $courseid]);
        $DB->delete_records('local_outcomemapper_map', ['courseid' => $courseid]);
        $DB->delete_records('local_outcomemapper_analysis', ['courseid' => $courseid]);
        $transaction->allow_commit();
    }
}
