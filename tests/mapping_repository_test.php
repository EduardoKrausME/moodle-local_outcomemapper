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

/**
 * Tests for suggestion review and confirmed mappings.
 *
 * @covers \local_outcomemapper\mapping_repository
 *
 * @package   local_outcomemapper
 */
final class mapping_repository_test extends advanced_testcase {
    /**
     * Confirming a suggestion persists a separate confirmed mapping.
     */
    public function test_confirm_mapping_is_persisted_separately(): void {
        global $DB, $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $repository = new mapping_repository();
        $analysisid = $repository->create_analysis($course->id, $USER->id, $this->catalog());
        $repository->complete_analysis($analysisid, $course->id, [$this->row('strong')], 1);

        $suggestion = $repository->get_suggestions($analysisid)[0];
        $repository->review((int)$suggestion->id, $course->id, 'confirm', $USER->id);

        $suggestion = $DB->get_record('local_outcomemapper_suggest', ['id' => $suggestion->id], '*', MUST_EXIST);
        $this->assertSame('confirmed', $suggestion->status);
        $mapping = $DB->get_record('local_outcomemapper_map', [
            'courseid' => $course->id,
            'objectivekey' => 'custom:o1',
            'targetkey' => 'cm:10',
        ], '*', MUST_EXIST);
        $this->assertSame('strong', $mapping->relation);
        $this->assertSame($analysisid, (int)$mapping->sourceanalysisid);
    }

    /**
     * Rejecting a suggestion changes review state but never creates a mapping.
     */
    public function test_reject_mapping_does_not_create_confirmed_record(): void {
        global $DB, $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $repository = new mapping_repository();
        $analysisid = $repository->create_analysis($course->id, $USER->id, $this->catalog());
        $repository->complete_analysis($analysisid, $course->id, [$this->row('weak')], 1);

        $suggestion = $repository->get_suggestions($analysisid)[0];
        $repository->review((int)$suggestion->id, $course->id, 'reject', $USER->id);

        $suggestion = $DB->get_record('local_outcomemapper_suggest', ['id' => $suggestion->id], '*', MUST_EXIST);
        $this->assertSame('rejected', $suggestion->status);
        $this->assertFalse($DB->record_exists('local_outcomemapper_map', [
            'courseid' => $course->id,
            'objectivekey' => 'custom:o1',
            'targetkey' => 'cm:10',
        ]));
    }

    /**
     * Test catalog.
     *
     * @return array
     */
    private function catalog(): array {
        return [
            'objectives' => [[
                'id' => 'custom:o1',
                'source' => 'custom',
                'title' => 'Objective one',
                'description' => '',
            ]],
            'targets' => [[
                'id' => 'cm:10',
                'kind' => 'quiz',
                'roles' => ['activity', 'assessment'],
                'title' => 'Quiz',
                'text' => 'Assessment text',
                'url' => '',
            ]],
            'warnings' => [],
        ];
    }

    /**
     * Test mapping row.
     *
     * @param string $relation Relation.
     * @return array
     */
    private function row(string $relation): array {
        return [
            'objectivekey' => 'custom:o1',
            'objectivetitle' => 'Objective one',
            'targetkey' => 'cm:10',
            'targettitle' => 'Quiz',
            'targetroles' => 'activity,assessment',
            'relation' => $relation,
            'confidence' => 80,
            'evidence' => 'Evidence.',
            'explanation' => 'Explanation.',
        ];
    }
}
