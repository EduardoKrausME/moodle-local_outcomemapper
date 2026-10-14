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
 * Course backup and restore key mapping tests.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;

use advanced_testcase;

/**
 * Class restore_key_mapper_test
 *
 * @covers \local_outcomemapper\restore_key_mapper
 */
final class restore_key_mapper_test extends advanced_testcase {
    /**
     * Restore must use the destination IDs and avoid stale source references.
     */
    public function test_rewrites_mapped_references(): void {
        $ids = [
            'outcome:4' => 34,
            'competency:7' => 37,
            'course_module:15' => 115,
            'course_section:18' => 118,
            'question:5' => 205,
            'quiz:6' => 206,
        ];
        $lookup = static function (string $type, int $oldid) use ($ids): int {
            return $ids[$type . ':' . $oldid] ?? 0;
        };

        $this->assertSame(
            ['custom:abcd0123', 'cm:115'],
            restore_key_mapper::remap('custom:abcd0123', 'cm:15', $lookup)
        );
        $this->assertSame(
            ['outcome:34', 'section:118'],
            restore_key_mapper::remap('outcome:4', 'section:18', $lookup)
        );
        $this->assertSame(
            ['competency:37', 'question:205:quiz:206'],
            restore_key_mapper::remap('competency:7', 'question:5:quiz:6', $lookup)
        );
    }

    /**
     * Missing mappings may result from importing only part of a course.
     */
    public function test_skips_unmapped_or_unknown_references(): void {
        $lookup = static function (): int {
            return 0;
        };
        $this->assertNull(restore_key_mapper::remap('competency:9', 'cm:4', $lookup));
        $this->assertNull(restore_key_mapper::remap('custom:a', 'question:5:quiz:6', $lookup));
        $this->assertNull(restore_key_mapper::remap('custom:a', 'unsupported:99', $lookup));
    }
}
