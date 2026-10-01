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
 * Outcome Mapper tests.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;

use advanced_testcase;
use context_course;

/**
 * Tests for course-level access control.
 *
 * @coversNothing
 *
 * @package   local_outcomemapper
 */
final class capability_test extends advanced_testcase {
    /**
     * Editing teachers receive analyse capability while students do not.
     */
    public function test_default_role_capabilities(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $context = context_course::instance($course->id);

        $this->assertTrue(has_capability('local/outcomemapper:analyse', $context, $teacher->id));
        $this->assertFalse(has_capability('local/outcomemapper:analyse', $context, $student->id));
    }

    /**
     * Module-specific view capabilities are respected in addition to course access.
     */
    public function test_module_view_capability_is_respected(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Restricted page',
            'content' => 'Restricted content',
        ]);
        $teacher = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);
        $coursecontext = context_course::instance($course->id);
        assign_capability('mod/page:view', CAP_PROHIBIT, $teacherrole->id, $coursecontext->id, true);

        $this->setUser($teacher);
        get_fast_modinfo($course, 0, true);
        $cm = get_fast_modinfo($course)->get_cm($page->cmid);

        $this->assertFalse(access_helper::can_access_cm($cm));
    }
}
