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
use core_text;

/**
 * Tests for content normalization.
 *
 * @covers \local_outcomemapper\content_normalizer
 *
 * @package   local_outcomemapper
 */
final class content_normalizer_test extends advanced_testcase {
    /**
     * HTML and executable content are removed before text is sent to AI.
     */
    public function test_plain_text_removes_html_and_scripts(): void {
        $input = '<p>Hello <strong>world</strong></p><script>alert("x")</script><p>&amp; Moodle</p>';
        $result = content_normalizer::plain_text($input, 1000);

        $this->assertSame('Hello world & Moodle', $result);
        $this->assertStringNotContainsString('alert', $result);
    }

    /**
     * Text is bounded before it enters an AI request.
     */
    public function test_plain_text_truncates_long_content(): void {
        $result = content_normalizer::plain_text(str_repeat('A', 1000), 100);
        $this->assertLessThanOrEqual(101, core_text::strlen($result));
        $this->assertStringEndsWith('…', $result);
    }
}
