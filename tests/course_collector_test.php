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
use core_competency\api;

/**
 * Tests for deterministic Moodle data collection.
 *
 * @package   local_outcomemapper
 */
final class course_collector_test extends advanced_testcase {
    /**
     * Selected course modules are collected and normalized without student data.
     */
    public function test_collects_selected_course_module(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Architecture page',
            'intro' => '<p>Introduction</p>',
            'content' => '<h2>Dependency inversion</h2><p>Prefer abstractions at boundaries.</p>',
        ]);

        $collector = new course_collector($course->id, 3000);
        $catalog = $collector->collect([], [$page->cmid], false, false, false, 'Explain dependency inversion');

        $this->assertCount(1, $catalog['objectives']);
        $this->assertCount(1, $catalog['targets']);
        $this->assertSame('cm:' . $page->cmid, $catalog['targets'][0]['id']);
        $this->assertStringContainsString('Dependency inversion', $catalog['targets'][0]['text']);
        $this->assertContains('content', $catalog['targets'][0]['roles']);
    }

    /**
     * Course competencies are read through the core competency subsystem.
     */
    public function test_collects_course_competencies(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        set_config('enabled', 1, 'core_competency');
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_competency');
        $framework = $generator->create_framework();
        $competency = $generator->create_competency([
            'competencyframeworkid' => $framework->get('id'),
            'shortname' => 'Design APIs',
            'description' => 'Design cohesive application interfaces.',
        ]);
        api::add_competency_to_course($course->id, $competency->get('id'));

        $collector = new course_collector($course->id, 3000);
        $catalog = $collector->collect([], [], true, false, false, '');

        $this->assertCount(1, $catalog['objectives']);
        $this->assertSame('competency:' . $competency->get('id'), $catalog['objectives'][0]['id']);
        $this->assertStringContainsString('Design APIs', $catalog['objectives'][0]['title']);
    }

    /**
     * Quiz questions are collected only as assessment definitions, never attempts or responses.
     */
    public function test_collects_quiz_questions(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quizsettings = $quizgenerator->create_test_quiz([
            ['Architecture question', 1, 'truefalse'],
        ], [
            'course' => $course->id,
            'name' => 'Architecture quiz',
        ]);

        $collector = new course_collector($course->id, 3000);
        $catalog = $collector->collect([], [$quizsettings->get_cmid()], false, false, true, 'Architecture objective');
        $questions = array_values(array_filter($catalog['targets'], static function (array $target): bool {
            return $target['kind'] === 'question';
        }));

        $this->assertCount(1, $questions);
        $this->assertContains('assessment', $questions[0]['roles']);
        $this->assertContains('question', $questions[0]['roles']);
        $this->assertStringContainsString('Architecture question', $questions[0]['title']);
    }

    /**
     * Missing textual content is retained as a target and surfaced as a warning.
     */
    public function test_missing_content_is_reported(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $url = $this->getDataGenerator()->create_module('url', [
            'course' => $course->id,
            'name' => 'External reference',
            'intro' => '',
            'externalurl' => 'https://example.invalid/reference',
        ]);

        $collector = new course_collector($course->id, 3000);
        $catalog = $collector->collect([], [$url->cmid], false, false, false, 'Reference objective');

        $this->assertCount(1, $catalog['targets']);
        $this->assertSame('', $catalog['targets'][0]['text']);
        $this->assertNotEmpty($catalog['warnings']);
    }
}
