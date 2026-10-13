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
 * Course deletion privacy regression test.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;

use advanced_testcase;

/**
 * @covers \local_outcomemapper\hook_callbacks
 */
final class course_deletion_test extends advanced_testcase {
    /**
     * Deleting a course also erases its private catalogs and confirmed mappings.
     */
    public function test_course_delete_removes_all_plugin_rows(): void {
        global $DB, $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $repository = new mapping_repository();
        $analysisid = $repository->create_analysis($course->id, (int)$USER->id, [
            'objectives' => [['id' => 'custom:o', 'title' => 'Example']],
            'targets' => [['id' => 'section:1', 'title' => 'Example']],
            'warnings' => [],
        ]);
        $DB->insert_record('local_outcomemapper_suggest', (object)[
            'analysisid' => $analysisid,
            'courseid' => $course->id,
            'objectivekey' => 'custom:o',
            'objectivetitle' => 'Example',
            'targetkey' => 'section:1',
            'targettitle' => 'Example',
            'targetroles' => 'content',
        ]);
        $DB->insert_record('local_outcomemapper_map', (object)[
            'courseid' => $course->id,
            'objectivekey' => 'custom:o',
            'objectivetitle' => 'Example',
            'targetkey' => 'section:1',
            'targettitle' => 'Example',
            'targetroles' => 'content',
        ]);

        delete_course($course->id, false);

        foreach (['local_outcomemapper_analysis', 'local_outcomemapper_suggest', 'local_outcomemapper_map'] as $table) {
            $this->assertFalse($DB->record_exists($table, ['courseid' => $course->id]));
        }
    }
}
