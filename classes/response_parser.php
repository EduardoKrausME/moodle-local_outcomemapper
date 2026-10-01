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
 * Outcome Mapper response_parser.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;

use JsonException;
use moodle_exception;

/**
 * Strict parser for AI mapping JSON.
 *
 * @package   local_outcomemapper
 */
class response_parser {
    /** @var array Allowed relation strengths. */
    private const RELATIONS = ['strong', 'probable', 'weak', 'none'];

    /**
     * Parse and validate a batch response.
     *
     * @param string $raw Raw AI response.
     * @param array $objectives Objectives sent in this batch.
     * @param array $targets Targets sent in this batch.
     * @return array Flattened relation rows.
     */
    public function parse(string $raw, array $objectives, array $targets): array {
        $json = trim($raw);
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $json, $matches)) {
            $json = trim($matches[1]);
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new moodle_exception('error_invalidjson', 'local_outcomemapper', '', null, $e->getMessage());
        }

        if (!is_array($decoded) || !isset($decoded['targets']) || !is_array($decoded['targets'])) {
            throw new moodle_exception('error_invalidshape', 'local_outcomemapper');
        }

        $objectivebyid = [];
        foreach ($objectives as $objective) {
            $objectivebyid[(string)$objective['id']] = $objective;
        }
        $targetbyid = [];
        foreach ($targets as $target) {
            $targetbyid[(string)$target['id']] = $target;
        }

        $returnedtargets = [];
        $rows = [];
        foreach ($decoded['targets'] as $targetresult) {
            if (!is_array($targetresult) || !isset($targetresult['target_id'])) {
                throw new moodle_exception('error_invalidshape', 'local_outcomemapper');
            }

            $targetid = (string)$targetresult['target_id'];
            if (!isset($targetbyid[$targetid])) {
                throw new moodle_exception('error_unknowntarget', 'local_outcomemapper', '', $targetid);
            }
            if (isset($returnedtargets[$targetid])) {
                throw new moodle_exception('error_invalidshape', 'local_outcomemapper');
            }
            $returnedtargets[$targetid] = true;

            $seenobjectives = [];
            $relations = $targetresult['relations'] ?? [];
            if (!is_array($relations)) {
                throw new moodle_exception('error_invalidshape', 'local_outcomemapper');
            }

            foreach ($relations as $relation) {
                if (!is_array($relation) || !isset($relation['objective_id'], $relation['relation'])) {
                    throw new moodle_exception('error_invalidshape', 'local_outcomemapper');
                }

                $objectiveid = (string)$relation['objective_id'];
                $strength = (string)$relation['relation'];
                if ($strength === 'none') {
                    throw new moodle_exception('error_invalidshape', 'local_outcomemapper');
                }
                $rows[] = $this->make_row(
                    $objectiveid,
                    $targetid,
                    $strength,
                    $relation,
                    $objectivebyid,
                    $targetbyid,
                    $seenobjectives
                );
            }

            $none = $targetresult['none'] ?? null;
            if ($none !== null) {
                if (!is_array($none) || ($none['relation'] ?? '') !== 'none' ||
                    !isset($none['objective_ids']) || !is_array($none['objective_ids'])) {
                    throw new moodle_exception('error_invalidshape', 'local_outcomemapper');
                }

                foreach ($none['objective_ids'] as $objectiveid) {
                    $rows[] = $this->make_row(
                        (string)$objectiveid,
                        $targetid,
                        'none',
                        $none,
                        $objectivebyid,
                        $targetbyid,
                        $seenobjectives
                    );
                }
            }

            $missing = array_diff(array_keys($objectivebyid), array_keys($seenobjectives));
            if ($missing) {
                throw new moodle_exception('error_missingobjective', 'local_outcomemapper', '', $targetid);
            }
        }

        $missingtargets = array_diff(array_keys($targetbyid), array_keys($returnedtargets));
        if ($missingtargets) {
            throw new moodle_exception('error_unknowntarget', 'local_outcomemapper', '', reset($missingtargets));
        }

        return $rows;
    }

    /**
     * Create one validated relation row.
     *
     * @param string $objectiveid Objective id.
     * @param string $targetid Target id.
     * @param string $strength Relation.
     * @param array $data Relation data.
     * @param array $objectivebyid Objective catalog.
     * @param array $targetbyid Target catalog.
     * @param array $seenobjectives Seen ids.
     * @return array
     */
    private function make_row(
        string $objectiveid,
        string $targetid,
        string $strength,
        array $data,
        array $objectivebyid,
        array $targetbyid,
        array  &$seenobjectives
    ): array {
        if (!isset($objectivebyid[$objectiveid])) {
            throw new moodle_exception('error_unknownobjective', 'local_outcomemapper', '', $objectiveid);
        }
        if (isset($seenobjectives[$objectiveid])) {
            throw new moodle_exception('error_duplicateobjective', 'local_outcomemapper', '', $targetid);
        }
        if (!in_array($strength, self::RELATIONS, true)) {
            throw new moodle_exception('error_invalidrelation', 'local_outcomemapper', '', $strength);
        }
        if (!array_key_exists('confidence', $data) || !array_key_exists('evidence', $data) ||
            !array_key_exists('explanation', $data) || !is_numeric($data['confidence']) ||
            !is_string($data['evidence']) || !is_string($data['explanation'])) {
            throw new moodle_exception('error_invalidshape', 'local_outcomemapper');
        }
        $seenobjectives[$objectiveid] = true;

        $confidence = (float)$data['confidence'];
        if ($confidence >= 0 && $confidence <= 1) {
            $confidence *= 100;
        }
        if ($confidence < 0 || $confidence > 100) {
            throw new moodle_exception('error_invalidshape', 'local_outcomemapper');
        }
        $confidence = (int)round($confidence);

        return [
            'objectivekey' => $objectiveid,
            'objectivetitle' => content_normalizer::plain_text($objectivebyid[$objectiveid]['title'] ?? '', 255),
            'targetkey' => $targetid,
            'targettitle' => content_normalizer::plain_text($targetbyid[$targetid]['title'] ?? '', 255),
            'targetroles' => implode(',', array_map('strval', $targetbyid[$targetid]['roles'] ?? [])),
            'relation' => $strength,
            'confidence' => $confidence,
            'evidence' => content_normalizer::plain_text((string)($data['evidence'] ?? ''), 2000),
            'explanation' => content_normalizer::plain_text((string)($data['explanation'] ?? ''), 3000),
        ];
    }
}
