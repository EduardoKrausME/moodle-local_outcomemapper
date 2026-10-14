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
 * Restore of teacher-confirmed Outcome Mapper mappings.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/backup/moodle2/restore_local_plugin.class.php');

/**
 * Reconstruct reviewed mappings once all Moodle ID mappings are available.
 */
class restore_local_outcomemapper_plugin extends restore_local_plugin {
    /** @var array Incoming rows held until all modules and questions have been restored. */
    private array $pending = [];

    /**
     * define_course_plugin_structure
     *
     * @return restore_path_element[]
     */
    protected function define_course_plugin_structure() {
        return [
            new restore_path_element(
                'local_outcomemapper_mapping',
                $this->get_pathfor('/mappings/mapping')
            ),
        ];
    }

    /**
     * Defer inserts, as dependent course modules might not exist yet.
     *
     * @param array $data Backup XML mapping data.
     */
    public function process_local_outcomemapper_mapping($data): void {
        $this->pending[] = (object)$data;
    }

    /**
     * Restore references after course sections and activity modules have been mapped.
     */
    public function after_restore_course(): void {
        global $DB;

        $courseid = (int)$this->task->get_courseid();
        $lookup = function (string $itemname, int $oldid): int {
            return (int)$this->get_mappingid($itemname, $oldid, 0);
        };

        foreach ($this->pending as $item) {
            $keys = \local_outcomemapper\restore_key_mapper::remap(
                (string)$item->objectivekey,
                (string)$item->targetkey,
                $lookup
            );
            if ($keys === null) {
                // A partial restore cannot safely reuse source course object IDs.
                continue;
            }
            [$objectivekey, $targetkey] = $keys;
            $conditions = [
                'courseid' => $courseid,
                'objectivekey' => $objectivekey,
                'targetkey' => $targetkey,
            ];
            if ($DB->record_exists('local_outcomemapper_map', $conditions)) {
                // Importing a backup must not overwrite the teacher's current review.
                continue;
            }

            $confirmedby = !empty($item->confirmedby)
                ? (int)$this->get_mappingid('user', (int)$item->confirmedby, 0)
                : 0;

            $DB->insert_record('local_outcomemapper_map', (object)[
                'courseid' => $courseid,
                'objectivekey' => $objectivekey,
                'objectivetitle' => (string)$item->objectivetitle,
                'targetkey' => $targetkey,
                'targettitle' => (string)$item->targettitle,
                'targetroles' => (string)$item->targetroles,
                'relation' => (string)$item->relation,
                'confidence' => (int)$item->confidence,
                'evidence' => (string)($item->evidence ?? ''),
                'explanation' => (string)($item->explanation ?? ''),
                'sourceanalysisid' => 0,
                'confirmedby' => $confirmedby,
                'timecreated' => (int)($item->timecreated ?? time()),
                'timemodified' => (int)($item->timemodified ?? time()),
            ]);
        }

        $this->pending = [];
    }
}
