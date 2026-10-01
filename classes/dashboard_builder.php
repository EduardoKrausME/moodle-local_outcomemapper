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
 * Outcome Mapper dashboard_builder.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;


use moodle_url;

/**
 * Build Mustache context for the course dashboard.
 *
 * @package   local_outcomemapper
 */
class dashboard_builder {
    /** @var mapping_repository Repository. */
    private mapping_repository $repository;

    /** @var coverage_calculator Coverage calculator. */
    private coverage_calculator $calculator;

    /**
     * Constructor.
     *
     * @param mapping_repository|null $repository Repository.
     * @param coverage_calculator|null $calculator Calculator.
     */
    public function __construct(
        ?mapping_repository $repository = null,
        ?coverage_calculator $calculator = null
    ) {
        $this->repository = $repository ?? new mapping_repository();
        $this->calculator = $calculator ?? new coverage_calculator();
    }

    /**
     * Build dashboard data.
     *
     * @param int $courseid Course id.
     * @param int|null $analysisid Analysis id, or latest when null.
     * @return array
     */
    public function build(int $courseid, ?int $analysisid = null): array {
        global $DB;

        $analysis = $analysisid
            ? $this->repository->get_analysis($analysisid, $courseid)
            : $this->repository->get_latest_analysis($courseid);

        $confirmed = $this->repository->get_confirmed($courseid);
        $data = [
            'courseid' => $courseid,
            'sesskey' => sesskey(),
            'actionurl' => (new moodle_url('/local/outcomemapper/action.php'))->out(false),
            'hasanalysis' => (bool)$analysis,
            'confirmed' => $this->format_confirmed($confirmed),
            'hasconfirmed' => !empty($confirmed),
        ];

        if (!$analysis) {
            return $data;
        }

        $objectives = json_decode((string)$analysis->objectivesjson, true) ?: [];
        $targets = json_decode((string)$analysis->targetsjson, true) ?: [];
        $warnings = json_decode((string)$analysis->warningsjson, true) ?: [];
        $suggestions = $this->repository->get_suggestions((int)$analysis->id);
        $coverageitems = $this->merge_confirmed_for_coverage($suggestions, $confirmed);
        $coverage = $this->calculator->calculate($objectives, $targets, $coverageitems);

        $userlabel = '-';
        if ((int)$analysis->userid > 0) {
            $user = $DB->get_record('user', ['id' => $analysis->userid]);
            if ($user) {
                $userlabel = fullname($user);
            }
        }

        $data['analysis'] = [
            'id' => (int)$analysis->id,
            'date' => userdate((int)$analysis->timecreated),
            'user' => $userlabel,
            'objectivecount' => (int)$analysis->objectivecount,
            'targetcount' => (int)$analysis->targetcount,
            'batches' => (int)$analysis->batches,
            'warnings' => array_map(static fn(string $warning): array => ['text' => $warning], $warnings),
            'haswarnings' => !empty($warnings),
        ];
        $data['matrix'] = $this->format_matrix($coverage['matrix']);
        $data['findings'] = [
            $this->finding('taughtnotassessed', $coverage['taughtnotassessed']),
            $this->finding('assessedwithoutpreparation', $coverage['assessedwithoutpreparation']),
            $this->finding('unmappedactivities', $coverage['unmappedactivities']),
            $this->finding('unmappedassessments', $coverage['unmappedassessments']),
            $this->finding('overrepresented', $coverage['overrepresented']),
            $this->finding('lowcompetencyevidence', $coverage['lowcompetencyevidence']),
        ];
        $data['suggestions'] = $this->format_suggestions($suggestions);
        $data['hassuggestions'] = !empty($data['suggestions']);

        return $data;
    }


    /**
     * Overlay teacher-confirmed mappings on the latest AI suggestions for coverage calculations.
     *
     * @param array $suggestions Current analysis suggestions.
     * @param array $confirmed Persisted teacher-confirmed mappings.
     * @return array
     */
    private function merge_confirmed_for_coverage(array $suggestions, array $confirmed): array {
        $effective = [];
        foreach ($suggestions as $suggestion) {
            $key = $suggestion->objectivekey . "
" . $suggestion->targetkey;
            $effective[$key] = $suggestion;
        }

        foreach ($confirmed as $mapping) {
            $key = $mapping->objectivekey . "
" . $mapping->targetkey;
            $record = clone $mapping;
            $record->status = 'confirmed';
            $effective[$key] = $record;
        }

        return array_values($effective);
    }

    /**
     * Format matrix rows.
     *
     * @param array $matrix Matrix.
     * @return array
     */
    private function format_matrix(array $matrix): array {
        $result = [];
        foreach ($matrix as $row) {
            $source = (string)($row['objective']['source'] ?? 'custom');
            $sourcekey = match ($source) {
                'competency' => 'competency',
                'outcome' => 'outcome',
                default => 'customobjective',
            };

            $result[] = [
                'title' => (string)$row['objective']['title'],
                'description' => (string)($row['objective']['description'] ?? ''),
                'source' => get_string($sourcekey, 'local_outcomemapper'),
                'content' => $this->format_cell($row['content']),
                'activities' => $this->format_cell($row['activities']),
                'assessments' => $this->format_cell($row['assessments']),
                'questions' => $this->format_cell($row['questions']),
            ];
        }
        return $result;
    }

    /**
     * Format a matrix cell.
     *
     * @param array $items Items.
     * @return array
     */
    private function format_cell(array $items): array {
        $formatted = [];
        foreach ($items as $item) {
            $formatted[] = [
                'title' => $item['title'],
                'url' => $item['url'],
                'hasurl' => !empty($item['url']),
                'relation' => get_string($item['relation'], 'local_outcomemapper'),
                'confidence' => (int)$item['confidence'],
                'evidence' => $item['evidence'],
                'explanation' => $item['explanation'],
                'status' => get_string($item['status'], 'local_outcomemapper'),
            ];
        }

        return [
            'hasitems' => !empty($formatted),
            'items' => $formatted,
        ];
    }

    /**
     * Format finding group.
     *
     * @param string $key Language key.
     * @param array $items Items.
     * @return array
     */
    private function finding(string $key, array $items): array {
        return [
            'title' => get_string($key, 'local_outcomemapper'),
            'hasitems' => !empty($items),
            'items' => array_map(static fn(string $text): array => ['text' => $text], $items),
        ];
    }

    /**
     * Format non-none suggestions for review.
     *
     * @param array $suggestions Suggestion records.
     * @return array
     */
    private function format_suggestions(array $suggestions): array {
        $result = [];
        foreach ($suggestions as $suggestion) {
            if ($suggestion->relation === 'none') {
                continue;
            }

            $result[] = [
                'id' => (int)$suggestion->id,
                'objective' => $suggestion->objectivetitle,
                'target' => $suggestion->targettitle,
                'relation' => get_string($suggestion->relation, 'local_outcomemapper'),
                'confidence' => (int)$suggestion->confidence,
                'evidence' => $suggestion->evidence,
                'explanation' => $suggestion->explanation,
                'status' => get_string($suggestion->status, 'local_outcomemapper'),
                'canreview' => $suggestion->status === 'suggested',
            ];
        }
        return $result;
    }

    /**
     * Format confirmed mappings.
     *
     * @param array $confirmed Mapping records.
     * @return array
     */
    private function format_confirmed(array $confirmed): array {
        $result = [];
        foreach ($confirmed as $mapping) {
            $result[] = [
                'objective' => $mapping->objectivetitle,
                'target' => $mapping->targettitle,
                'relation' => get_string($mapping->relation, 'local_outcomemapper'),
                'confidence' => (int)$mapping->confidence,
                'evidence' => $mapping->evidence,
                'explanation' => $mapping->explanation,
            ];
        }
        return $result;
    }
}
