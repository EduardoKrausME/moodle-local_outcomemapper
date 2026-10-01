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
 * Outcome Mapper coverage_calculator.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;

/**
 * Derive descriptive coverage findings from validated mappings.
 *
 * The calculator intentionally treats AI relations as suggestions and uses
 * transparent heuristics instead of presenting them as facts.
 *
 * @package   local_outcomemapper
 */
class coverage_calculator {
    /**
     * Calculate matrix and findings.
     *
     * @param array $objectives Objective catalog.
     * @param array $targets Target catalog.
     * @param array $suggestions Suggestion records.
     * @return array
     */
    public function calculate(array $objectives, array $targets, array $suggestions): array {
        $objectivebyid = [];
        foreach ($objectives as $objective) {
            $objectivebyid[$objective['id']] = $objective;
        }

        $targetbyid = [];
        foreach ($targets as $target) {
            $targetbyid[$target['id']] = $target;
        }

        $matrix = [];
        $positivebytarget = [];
        $counts = [];

        foreach ($objectives as $objective) {
            $matrix[$objective['id']] = [
                'objective' => $objective,
                'content' => [],
                'activities' => [],
                'assessments' => [],
                'questions' => [],
            ];
            $counts[$objective['id']] = 0;
        }

        foreach ($suggestions as $suggestion) {
            if ($suggestion->status === 'rejected' || $suggestion->relation === 'none') {
                continue;
            }
            if (!isset($matrix[$suggestion->objectivekey], $targetbyid[$suggestion->targetkey])) {
                continue;
            }

            $target = $targetbyid[$suggestion->targetkey];
            $roles = array_filter(explode(',', (string)$suggestion->targetroles));
            $item = [
                'id' => (int)$suggestion->id,
                'targetkey' => (string)$suggestion->targetkey,
                'title' => $suggestion->targettitle,
                'url' => $target['url'] ?? '',
                'relation' => $suggestion->relation,
                'confidence' => (int)$suggestion->confidence,
                'evidence' => $suggestion->evidence,
                'explanation' => $suggestion->explanation,
                'status' => $suggestion->status,
            ];

            foreach ($roles as $role) {
                if ($role === 'content') {
                    $matrix[$suggestion->objectivekey]['content'][] = $item;
                } else if ($role === 'activity') {
                    $matrix[$suggestion->objectivekey]['activities'][] = $item;
                } else if ($role === 'assessment') {
                    $matrix[$suggestion->objectivekey]['assessments'][] = $item;
                } else if ($role === 'question') {
                    $matrix[$suggestion->objectivekey]['questions'][] = $item;
                }
            }

            $positivebytarget[$suggestion->targetkey] = true;
            $counts[$suggestion->objectivekey]++;
        }

        $taughtnotassessed = [];
        $assessedwithoutpreparation = [];
        $lowcompetencyevidence = [];

        foreach ($matrix as $row) {
            $preparatorykeys = [];
            foreach ($row['content'] as $item) {
                $preparatorykeys[$item['targetkey']] = true;
            }
            foreach ($row['activities'] as $item) {
                $target = $targetbyid[$item['targetkey']] ?? null;
                if ($target && !in_array('assessment', $target['roles'] ?? [], true)) {
                    $preparatorykeys[$item['targetkey']] = true;
                }
            }

            $assessmentkeys = [];
            foreach (array_merge($row['assessments'], $row['questions']) as $item) {
                $assessmentkeys[$item['targetkey']] = true;
            }

            if ($preparatorykeys && !$assessmentkeys) {
                $taughtnotassessed[] = $row['objective']['title'];
            }
            if ($assessmentkeys && !$preparatorykeys) {
                $assessedwithoutpreparation[] = $row['objective']['title'];
            }

            if (($row['objective']['source'] ?? '') === 'competency') {
                $meaningfulkeys = [];
                foreach (array_merge($row['content'], $row['activities'], $row['assessments'], $row['questions']) as $item) {
                    if (in_array($item['relation'], ['strong', 'probable'], true)) {
                        $meaningfulkeys[$item['targetkey']] = true;
                    }
                }
                if (count($meaningfulkeys) < 2) {
                    $lowcompetencyevidence[] = $row['objective']['title'];
                }
            }
        }

        $unmappedactivities = [];
        $unmappedassessments = [];
        foreach ($targets as $target) {
            if (isset($positivebytarget[$target['id']])) {
                continue;
            }
            $roles = $target['roles'] ?? [];
            if (in_array('assessment', $roles, true)) {
                $unmappedassessments[] = $target['title'];
            } else if (in_array('activity', $roles, true)) {
                $unmappedactivities[] = $target['title'];
            }
        }

        $overrepresented = [];
        if ($counts) {
            $average = array_sum($counts) / count($counts);
            $threshold = max(3, (int)ceil($average * 1.75));
            foreach ($counts as $objectiveid => $count) {
                if ($count >= $threshold && $count > $average) {
                    $overrepresented[] = $objectivebyid[$objectiveid]['title'];
                }
            }
        }

        return [
            'matrix' => array_values($matrix),
            'taughtnotassessed' => $taughtnotassessed,
            'assessedwithoutpreparation' => $assessedwithoutpreparation,
            'unmappedactivities' => $unmappedactivities,
            'unmappedassessments' => $unmappedassessments,
            'overrepresented' => $overrepresented,
            'lowcompetencyevidence' => $lowcompetencyevidence,
        ];
    }
}
