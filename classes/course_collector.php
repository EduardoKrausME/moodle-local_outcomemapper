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
 * Outcome Mapper course_collector.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;

use context_course;
use context_module;
use core_competency\api as competency_api;
use mod_quiz\quiz_settings;
use moodle_url;
use Throwable;

/**
 * Deterministically collect objectives and course-owned evidence.
 *
 * Student submissions, forum posts, attempts and names are deliberately not collected.
 *
 * @package   local_outcomemapper
 */
class course_collector {
    /** @var int Course id. */
    private int $courseid;

    /** @var context_course Course context. */
    private context_course $context;

    /** @var int Maximum text characters per object. */
    private int $maxchars;

    /**
     * Constructor.
     *
     * @param int $courseid Course id.
     * @param int|null $maxchars Maximum normalized characters.
     */
    public function __construct(int $courseid, ?int $maxchars = null) {
        $this->courseid = $courseid;
        $this->context = context_course::instance($courseid);
        $configured = $maxchars ?? (int)get_config('local_outcomemapper', 'maxitemchars');
        $this->maxchars = max(500, min(12000, $configured ?: 3000));
    }

    /**
     * Collect selected data.
     *
     * @param array $sectionids Section DB ids.
     * @param array $cmids Course module ids.
     * @param bool $includecompetencies Include Moodle competencies.
     * @param bool $includeoutcomes Include grade outcomes.
     * @param bool $includequestions Include quiz questions.
     * @param string $additionalobjectives One per line, optionally Title | Description.
     * @return array
     */
    public function collect(
        array  $sectionids,
        array  $cmids,
        bool   $includecompetencies,
        bool   $includeoutcomes,
        bool   $includequestions,
        string $additionalobjectives
    ): array {
        $warnings = [];
        $objectives = [];

        if ($includecompetencies) {
            $objectives = array_merge($objectives, $this->collect_competencies($warnings));
        }

        if ($includeoutcomes) {
            $objectives = array_merge($objectives, $this->collect_outcomes());
        }

        $objectives = array_merge($objectives, $this->parse_additional_objectives($additionalobjectives));
        $objectives = $this->deduplicate_by_id($objectives);

        $targets = [];
        $targets = array_merge($targets, $this->collect_sections($sectionids));
        $targets = array_merge($targets, $this->collect_modules($cmids, $includequestions, $warnings));
        $targets = $this->deduplicate_by_id($targets);

        return [
            'objectives' => array_values($objectives),
            'targets' => array_values($targets),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * Collect course competencies through the core competency API.
     *
     * @param array $warnings Warnings.
     * @return array
     */
    private function collect_competencies(array &$warnings): array {
        if (!class_exists(competency_api::class) || !competency_api::is_enabled()) {
            return [];
        }

        $capabilities = [
            'moodle/competency:coursecompetencyview',
            'moodle/competency:coursecompetencymanage',
        ];
        if (!has_any_capability($capabilities, $this->context)) {
            $warnings[] = get_string('warning_competencynopermission', 'local_outcomemapper');
            return [];
        }

        $result = [];
        foreach (competency_api::list_course_competencies($this->courseid) as $entry) {
            $competency = $entry['competency'];
            $id = (int)$competency->get('id');
            $title = trim((string)$competency->get('shortname'));
            $idnumber = trim((string)$competency->get('idnumber'));
            if ($idnumber !== '') {
                $title .= ' [' . $idnumber . ']';
            }

            $result[] = [
                'id' => 'competency:' . $id,
                'source' => 'competency',
                'title' => content_normalizer::plain_text($title, 255),
                'description' => content_normalizer::plain_text(
                    (string)$competency->get('description'),
                    $this->maxchars
                ),
            ];
        }

        return $result;
    }

    /**
     * Collect grade outcomes used in the course.
     *
     * @return array
     */
    private function collect_outcomes(): array {
        global $CFG, $DB;

        require_once($CFG->libdir . '/grade/grade_outcome.php');

        $sql = "SELECT DISTINCT go.id, go.shortname, go.fullname, go.description
                  FROM {grade_outcomes} go
             LEFT JOIN {grade_outcomes_courses} goc ON goc.outcomeid = go.id
                 WHERE go.courseid = :courseid1 OR goc.courseid = :courseid2
              ORDER BY go.id";
        $records = $DB->get_records_sql($sql, [
            'courseid1' => $this->courseid,
            'courseid2' => $this->courseid,
        ]);

        $result = [];
        foreach ($records as $record) {
            $title = trim((string)$record->fullname);
            if ($title === '') {
                $title = (string)$record->shortname;
            }

            $result[] = [
                'id' => 'outcome:' . (int)$record->id,
                'source' => 'outcome',
                'title' => content_normalizer::plain_text($title, 255),
                'description' => content_normalizer::plain_text((string)$record->description, $this->maxchars),
            ];
        }

        return $result;
    }

    /**
     * Parse additional objectives.
     *
     * @param string $raw Raw textarea content.
     * @return array
     */
    public function parse_additional_objectives(string $raw): array {
        $result = [];
        $lines = preg_split('/\R/u', trim($raw)) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            [$title, $description] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            if ($title === '') {
                continue;
            }

            $hash = substr(hash('sha256', $title . "\n" . $description), 0, 24);
            $result[] = [
                'id' => 'custom:' . $hash,
                'source' => 'custom',
                'title' => content_normalizer::plain_text($title, 255),
                'description' => content_normalizer::plain_text($description, $this->maxchars),
            ];
        }

        return $result;
    }

    /**
     * Collect selected section summaries.
     *
     * @param array $sectionids Section DB ids.
     * @return array
     */
    private function collect_sections(array $sectionids): array {
        if (!$sectionids) {
            return [];
        }

        $course = get_course($this->courseid);
        $allowed = array_fill_keys(array_map('intval', $sectionids), true);
        $modinfo = get_fast_modinfo($course);
        $result = [];

        foreach ($modinfo->get_section_info_all() as $section) {
            if (!$section || !isset($allowed[(int)$section->id]) || !$section->uservisible) {
                continue;
            }

            $summary = content_normalizer::plain_text((string)$section->summary, $this->maxchars);
            $result[] = [
                'id' => 'section:' . (int)$section->id,
                'kind' => 'section',
                'roles' => ['content'],
                'title' => content_normalizer::plain_text(get_section_name($course, $section), 255),
                'text' => $summary,
                'url' => (new moodle_url('/course/view.php', [
                    'id' => $this->courseid,
                    'section' => (int)$section->section,
                ]))->out(false),
            ];
        }

        return $result;
    }

    /**
     * Collect selected modules and optionally quiz questions.
     *
     * @param array $cmids Course module ids.
     * @param bool $includequestions Include questions.
     * @param array $warnings Warnings.
     * @return array
     */
    private function collect_modules(array $cmids, bool $includequestions, array &$warnings): array {
        global $DB;

        if (!$cmids) {
            return [];
        }

        $course = get_course($this->courseid);
        $modinfo = get_fast_modinfo($course);
        $allowed = array_fill_keys(array_map('intval', $cmids), true);
        $result = [];

        foreach ($modinfo->get_cms() as $cm) {
            if (!isset($allowed[(int)$cm->id])) {
                continue;
            }
            if (!access_helper::can_access_cm($cm)) {
                continue;
            }

            try {
                $modulecontext = context_module::instance((int)$cm->id);
                $record = $DB->get_record($cm->modname, ['id' => $cm->instance]);
                if (!$record) {
                    $warnings[] = get_string('warning_modulemissing', 'local_outcomemapper', $cm->id);
                    continue;
                }

                $roles = ['activity'];
                if (in_array($cm->modname, ['page', 'book', 'label', 'resource', 'url', 'folder'], true)) {
                    $roles[] = 'content';
                }
                if (in_array($cm->modname, ['quiz', 'assign', 'workshop', 'lesson', 'scorm', 'h5pactivity'], true)) {
                    $roles[] = 'assessment';
                }

                $parts = [];
                if (property_exists($record, 'intro')) {
                    $parts[] = (string)$record->intro;
                }
                if ($cm->modname === 'page' && property_exists($record, 'content')) {
                    $parts[] = (string)$record->content;
                }
                if ($cm->modname === 'book') {
                    $chapters = $DB->get_records('book_chapters', [
                        'bookid' => $record->id,
                        'hidden' => 0,
                    ], 'pagenum ASC', 'id,title,content');
                    foreach ($chapters as $chapter) {
                        $parts[] = (string)$chapter->title;
                        $parts[] = (string)$chapter->content;
                    }
                }

                $text = content_normalizer::join($parts, $this->maxchars);
                if ($text === '') {
                    $warnings[] = get_string('warning_contentmissing', 'local_outcomemapper', $cm->name);
                }

                $result[] = [
                    'id' => 'cm:' . (int)$cm->id,
                    'kind' => $cm->modname,
                    'roles' => array_values(array_unique($roles)),
                    'title' => content_normalizer::plain_text((string)$cm->name, 255),
                    'text' => $text,
                    'url' => $cm->url ? $cm->url->out(false) : '',
                ];

                if ($includequestions && $cm->modname === 'quiz') {
                    $result = array_merge(
                        $result,
                        $this->collect_quiz_questions((int)$cm->id, (int)$cm->instance, $modulecontext, $cm->name, $warnings)
                    );
                }
            } catch (Throwable $e) {
                debugging('Outcome Mapper skipped cmid ' . $cm->id . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                $warnings[] = get_string('warning_modulemissing', 'local_outcomemapper', $cm->id);
            }
        }

        return $result;
    }

    /**
     * Collect teacher-visible quiz question text without attempts or student responses.
     *
     * @param int $cmid Course module id.
     * @param int $quizid Quiz id.
     * @param context_module $context Quiz context.
     * @param string $quizname Quiz name.
     * @param array $warnings Warnings.
     * @return array
     */
    private function collect_quiz_questions(
        int            $cmid,
        int            $quizid,
        context_module $context,
        string         $quizname,
        array          &$warnings
    ): array {
        if (!has_any_capability(['mod/quiz:preview', 'mod/quiz:manage'], $context)) {
            $warnings[] = get_string('warning_quizquestionsnopermission', 'local_outcomemapper', $quizname);
            return [];
        }

        try {
            $quizsettings = quiz_settings::create($quizid);
            $quizsettings->preload_questions();
            $quizsettings->load_questions();
            $questions = $quizsettings->get_questions();
        } catch (Throwable $e) {
            debugging('Outcome Mapper could not load questions for quiz ' . $quizid . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            $warnings[] = get_string('warning_quizquestionsnopermission', 'local_outcomemapper', $quizname);
            return [];
        }

        $result = [];
        foreach ($questions as $question) {
            if (empty($question->id) || !is_numeric($question->id)) {
                continue;
            }

            $text = content_normalizer::join([
                (string)($question->name ?? ''),
                (string)($question->questiontext ?? ''),
                (string)($question->generalfeedback ?? ''),
            ], $this->maxchars);

            $result[] = [
                'id' => 'question:' . (int)$question->id . ':quiz:' . $quizid,
                'kind' => 'question',
                'roles' => ['assessment', 'question'],
                'title' => content_normalizer::plain_text(
                    $quizname . ' — ' . (string)($question->name ?? get_string('question', 'local_outcomemapper')),
                    255
                ),
                'text' => $text,
                'url' => (new moodle_url('/mod/quiz/edit.php', ['cmid' => $cmid]))->out(false),
            ];
        }

        return $result;
    }

    /**
     * Deduplicate list by id.
     *
     * @param array $items Items.
     * @return array
     */
    private function deduplicate_by_id(array $items): array {
        $result = [];
        foreach ($items as $item) {
            if (!empty($item['id'])) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }
}
