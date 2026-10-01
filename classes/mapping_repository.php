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
 * Outcome Mapper mapping_repository.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;

use context_course;
use moodle_exception;
use stdClass;

/**
 * Persistence for analysis runs, suggestions and teacher-confirmed mappings.
 *
 * @package   local_outcomemapper
 */
class mapping_repository {
    /**
     * Create an analysis record.
     *
     * @param int $courseid Course id.
     * @param int $userid User id.
     * @param array $catalog Collected data.
     * @return int Analysis id.
     */
    public function create_analysis(int $courseid, int $userid, array $catalog): int {
        global $DB;

        $now = time();
        $objectivesjson = json_encode($catalog['objectives'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $targetsjson = json_encode($catalog['targets'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $warningsjson = json_encode($catalog['warnings'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $objectivesjson . "\n" . $targetsjson);

        $record = (object)[
            'courseid' => $courseid,
            'userid' => $userid,
            'status' => 'running',
            'objectivecount' => count($catalog['objectives']),
            'targetcount' => count($catalog['targets']),
            'batches' => 0,
            'inputhash' => $hash,
            'objectivesjson' => $objectivesjson,
            'targetsjson' => $targetsjson,
            'warningsjson' => $warningsjson,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        return (int)$DB->insert_record('local_outcomemapper_analysis', $record);
    }

    /**
     * Save validated rows and mark analysis complete.
     *
     * @param int $analysisid Analysis id.
     * @param int $courseid Course id.
     * @param array $rows Rows.
     * @param int $batches Batch count.
     */
    public function complete_analysis(int $analysisid, int $courseid, array $rows, int $batches): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $now = time();

        foreach ($rows as $row) {
            $DB->insert_record('local_outcomemapper_suggest', (object)[
                'analysisid' => $analysisid,
                'courseid' => $courseid,
                'objectivekey' => $row['objectivekey'],
                'objectivetitle' => $row['objectivetitle'],
                'targetkey' => $row['targetkey'],
                'targettitle' => $row['targettitle'],
                'targetroles' => $row['targetroles'],
                'relation' => $row['relation'],
                'confidence' => $row['confidence'],
                'evidence' => $row['evidence'],
                'explanation' => $row['explanation'],
                'status' => 'suggested',
                'reviewedby' => 0,
                'timereviewed' => 0,
                'timecreated' => $now,
            ]);
        }

        $DB->set_field('local_outcomemapper_analysis', 'status', 'complete', ['id' => $analysisid]);
        $DB->set_field('local_outcomemapper_analysis', 'batches', $batches, ['id' => $analysisid]);
        $DB->set_field('local_outcomemapper_analysis', 'timemodified', $now, ['id' => $analysisid]);
        $transaction->allow_commit();
    }

    /**
     * Mark analysis failed without storing unvalidated model output.
     *
     * @param int $analysisid Analysis id.
     */
    public function fail_analysis(int $analysisid): void {
        global $DB;
        $DB->set_field('local_outcomemapper_analysis', 'status', 'failed', ['id' => $analysisid]);
        $DB->set_field('local_outcomemapper_analysis', 'timemodified', time(), ['id' => $analysisid]);
    }

    /**
     * Get analysis constrained to course.
     *
     * @param int $analysisid Analysis id.
     * @param int $courseid Course id.
     * @return stdClass
     */
    public function get_analysis(int $analysisid, int $courseid): stdClass {
        global $DB;
        $record = $DB->get_record('local_outcomemapper_analysis', [
            'id' => $analysisid,
            'courseid' => $courseid,
        ]);
        if (!$record) {
            throw new moodle_exception('error_analysismissing', 'local_outcomemapper');
        }
        return $record;
    }

    /**
     * Get latest completed analysis.
     *
     * @param int $courseid Course id.
     * @return stdClass|null
     */
    public function get_latest_analysis(int $courseid): ?stdClass {
        global $DB;
        $records = $DB->get_records('local_outcomemapper_analysis', [
            'courseid' => $courseid,
            'status' => 'complete',
        ], 'timecreated DESC, id DESC', '*', 0, 1);
        return $records ? reset($records) : null;
    }

    /**
     * Get suggestions for analysis.
     *
     * @param int $analysisid Analysis id.
     * @return array
     */
    public function get_suggestions(int $analysisid): array {
        global $DB;
        return array_values($DB->get_records('local_outcomemapper_suggest', [
            'analysisid' => $analysisid,
        ], 'objectivetitle ASC, targettitle ASC, id ASC'));
    }

    /**
     * Get confirmed mappings in course.
     *
     * @param int $courseid Course id.
     * @return array
     */
    public function get_confirmed(int $courseid): array {
        global $DB;
        return array_values($DB->get_records('local_outcomemapper_map', [
            'courseid' => $courseid,
        ], 'objectivetitle ASC, targettitle ASC, id ASC'));
    }

    /**
     * Confirm or reject an AI suggestion.
     *
     * @param int $suggestionid Suggestion id.
     * @param int $courseid Course id.
     * @param string $action confirm or reject.
     * @param int $userid Reviewer id.
     */
    public function review(int $suggestionid, int $courseid, string $action, int $userid): void {
        global $DB;

        $context = context_course::instance($courseid);
        require_capability('local/outcomemapper:analyse', $context);

        if (!in_array($action, ['confirm', 'reject'], true)) {
            throw new moodle_exception('invalidaction', 'local_outcomemapper');
        }

        $suggestion = $DB->get_record('local_outcomemapper_suggest', [
            'id' => $suggestionid,
            'courseid' => $courseid,
        ]);
        if (!$suggestion) {
            throw new moodle_exception('error_suggestionmissing', 'local_outcomemapper');
        }

        if ($action === 'confirm' && $suggestion->relation === 'none') {
            throw new moodle_exception('error_cannotconfirmnone', 'local_outcomemapper');
        }

        $transaction = $DB->start_delegated_transaction();
        $now = time();

        if ($action === 'confirm') {
            $existing = $DB->get_record('local_outcomemapper_map', [
                'courseid' => $courseid,
                'objectivekey' => $suggestion->objectivekey,
                'targetkey' => $suggestion->targetkey,
            ]);

            $mapping = (object)[
                'courseid' => $courseid,
                'objectivekey' => $suggestion->objectivekey,
                'objectivetitle' => $suggestion->objectivetitle,
                'targetkey' => $suggestion->targetkey,
                'targettitle' => $suggestion->targettitle,
                'targetroles' => $suggestion->targetroles,
                'relation' => $suggestion->relation,
                'confidence' => $suggestion->confidence,
                'evidence' => $suggestion->evidence,
                'explanation' => $suggestion->explanation,
                'sourceanalysisid' => $suggestion->analysisid,
                'confirmedby' => $userid,
                'timemodified' => $now,
            ];

            if ($existing) {
                $mapping->id = $existing->id;
                $mapping->timecreated = $existing->timecreated;
                $DB->update_record('local_outcomemapper_map', $mapping);
            } else {
                $mapping->timecreated = $now;
                $DB->insert_record('local_outcomemapper_map', $mapping);
            }
        }

        $suggestion->status = $action === 'confirm' ? 'confirmed' : 'rejected';
        $suggestion->reviewedby = $userid;
        $suggestion->timereviewed = $now;
        $DB->update_record('local_outcomemapper_suggest', $suggestion);

        $transaction->allow_commit();
    }
}
