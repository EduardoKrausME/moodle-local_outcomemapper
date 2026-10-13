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
 * AI request budget regression tests.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;

use advanced_testcase;
use moodle_exception;

/**
 * @covers \local_outcomemapper\ai_mapper
 */
final class ai_mapper_test extends advanced_testcase {
    /**
     * A request over the budget must fail before invoking AI Bridge.
     */
    public function test_oversized_selection_is_rejected(): void {
        $this->resetAfterTest(true);
        set_config('targetbatchsize', 8, 'local_outcomemapper');
        set_config('objectivebatchsize', 50, 'local_outcomemapper');
        set_config('maxbatches', 8, 'local_outcomemapper');

        $mapper = new ai_mapper();
        $this->assertSame(8, $mapper->validate_workload(array_fill(0, 50, []), array_fill(0, 64, [])));

        $this->expectException(moodle_exception::class);
        $mapper->validate_workload(array_fill(0, 60, []), array_fill(0, 300, []));
    }

    /**
     * The hard cap also applies to manually modified settings.
     */
    public function test_maximum_budget_is_capped_at_twelve(): void {
        $this->resetAfterTest(true);
        set_config('targetbatchsize', 1, 'local_outcomemapper');
        set_config('objectivebatchsize', 5, 'local_outcomemapper');
        set_config('maxbatches', 999, 'local_outcomemapper');

        $mapper = new ai_mapper();
        $this->assertSame(12, $mapper->validate_workload(array_fill(0, 5, []), array_fill(0, 12, [])));
        $this->expectException(moodle_exception::class);
        $mapper->validate_workload(array_fill(0, 5, []), array_fill(0, 13, []));
    }
}
