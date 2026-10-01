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
 * Outcome Mapper coverage tests.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;

use advanced_testcase;

/**
 * Tests deterministic coverage findings.
 *
 * @package   local_outcomemapper
 */
final class coverage_calculator_test extends advanced_testcase {
    /**
     * Content without an assessment is surfaced as a review signal.
     */
    public function test_taught_but_not_assessed(): void {
        $objectives = [[
            'id' => 'custom:o1',
            'source' => 'custom',
            'title' => 'Explain transactions',
            'description' => '',
        ]];
        $targets = [[
            'id' => 'cm:1',
            'kind' => 'page',
            'roles' => ['activity', 'content'],
            'title' => 'Transaction content',
            'text' => '',
            'url' => '',
        ]];
        $suggestion = (object)[
            'id' => 1,
            'objectivekey' => 'custom:o1',
            'targetkey' => 'cm:1',
            'targettitle' => 'Transaction content',
            'targetroles' => 'activity,content',
            'relation' => 'strong',
            'confidence' => 90,
            'evidence' => 'Direct content.',
            'explanation' => 'Direct relation.',
            'status' => 'suggested',
        ];

        $result = (new coverage_calculator())->calculate($objectives, $targets, [$suggestion]);

        $this->assertSame(['Explain transactions'], $result['taughtnotassessed']);
        $this->assertSame([], $result['assessedwithoutpreparation']);
    }
}
