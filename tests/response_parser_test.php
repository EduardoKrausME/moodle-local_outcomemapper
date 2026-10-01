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
use moodle_exception;

/**
 * Tests for strict JSON response parsing.
 *
 * @package   local_outcomemapper
 */
final class response_parser_test extends advanced_testcase {
    /**
     * Explicit positive relations and grouped none relations are expanded deterministically.
     */
    public function test_parse_expands_complete_matrix(): void {
        $objectives = [
            ['id' => 'custom:o1', 'title' => 'Objective one'],
            ['id' => 'custom:o2', 'title' => 'Objective two'],
        ];
        $targets = [[
            'id' => 'cm:10',
            'title' => 'Assessment',
            'roles' => ['activity', 'assessment'],
        ]];
        $json = json_encode([
            'targets' => [[
                'target_id' => 'cm:10',
                'relations' => [[
                    'objective_id' => 'custom:o1',
                    'relation' => 'strong',
                    'confidence' => 0.92,
                    'evidence' => 'The task explicitly requires the stated skill.',
                    'explanation' => 'Direct semantic alignment.',
                ]],
                'none' => [
                    'relation' => 'none',
                    'objective_ids' => ['custom:o2'],
                    'confidence' => 88,
                    'evidence' => 'No relevant concept appears.',
                    'explanation' => 'No apparent relationship in the supplied text.',
                ],
            ]],
        ], JSON_THROW_ON_ERROR);

        $rows = (new response_parser())->parse($json, $objectives, $targets);

        $this->assertCount(2, $rows);
        $this->assertSame('strong', $rows[0]['relation']);
        $this->assertSame(92, $rows[0]['confidence']);
        $this->assertSame('none', $rows[1]['relation']);
        $this->assertSame('custom:o2', $rows[1]['objectivekey']);
        $this->assertSame('activity,assessment', $rows[0]['targetroles']);
    }

    /**
     * A response is rejected when one objective is missing for a target.
     */
    public function test_parse_rejects_incomplete_matrix(): void {
        $objectives = [
            ['id' => 'custom:o1', 'title' => 'Objective one'],
            ['id' => 'custom:o2', 'title' => 'Objective two'],
        ];
        $targets = [[
            'id' => 'cm:10',
            'title' => 'Assessment',
            'roles' => ['assessment'],
        ]];
        $json = json_encode([
            'targets' => [[
                'target_id' => 'cm:10',
                'relations' => [[
                    'objective_id' => 'custom:o1',
                    'relation' => 'probable',
                    'confidence' => 70,
                    'evidence' => 'Some evidence.',
                    'explanation' => 'Some explanation.',
                ]],
                'none' => [
                    'relation' => 'none',
                    'objective_ids' => [],
                    'confidence' => 90,
                    'evidence' => 'No evidence.',
                    'explanation' => 'No relationship.',
                ],
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->expectException(moodle_exception::class);
        (new response_parser())->parse($json, $objectives, $targets);
    }

    /**
     * Markdown fenced output is accepted only after the fenced JSON itself passes validation.
     */
    public function test_parse_accepts_json_fence_defensively(): void {
        $objectives = [['id' => 'custom:o1', 'title' => 'Objective one']];
        $targets = [['id' => 'cm:10', 'title' => 'Page', 'roles' => ['content']]];
        $json = "```json\n" . json_encode([
                'targets' => [[
                    'target_id' => 'cm:10',
                    'relations' => [],
                    'none' => [
                        'relation' => 'none',
                        'objective_ids' => ['custom:o1'],
                        'confidence' => 90,
                        'evidence' => 'No evidence.',
                        'explanation' => 'No relationship.',
                    ],
                ]],
            ], JSON_THROW_ON_ERROR) . "\n```";

        $rows = (new response_parser())->parse($json, $objectives, $targets);
        $this->assertCount(1, $rows);
        $this->assertSame('none', $rows[0]['relation']);
    }

    /**
     * Every relation must include confidence, evidence and explanation.
     */
    public function test_parse_rejects_relation_without_required_explanation_fields(): void {
        $objectives = [['id' => 'custom:o1', 'title' => 'Objective one']];
        $targets = [['id' => 'cm:10', 'title' => 'Page', 'roles' => ['content']]];
        $json = json_encode([
            'targets' => [[
                'target_id' => 'cm:10',
                'relations' => [[
                    'objective_id' => 'custom:o1',
                    'relation' => 'weak',
                    'confidence' => 40,
                    'explanation' => 'A weak relationship.',
                ]],
                'none' => [
                    'relation' => 'none',
                    'objective_ids' => [],
                    'confidence' => 90,
                    'evidence' => 'No additional objectives.',
                    'explanation' => 'Complete.',
                ],
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->expectException(moodle_exception::class);
        (new response_parser())->parse($json, $objectives, $targets);
    }

}
