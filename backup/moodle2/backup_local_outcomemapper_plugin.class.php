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
 * Course backup support for teacher-confirmed Outcome Mapper mappings.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/backup/moodle2/backup_local_plugin.class.php');

/**
 * Backup only teacher-confirmed course mappings, not regenerable AI analysis.
 */
class backup_local_outcomemapper_plugin extends backup_local_plugin {
    /**
     * Attach mapping records to the course element.
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $mappings = new backup_nested_element('mappings');
        $fields = [
            'objectivekey', 'objectivetitle', 'targetkey', 'targettitle',
            'targetroles', 'relation', 'confidence', 'evidence',
            'explanation', 'timecreated', 'timemodified',
        ];

        // Do not export teacher identity unless user information was requested.
        if ($this->get_setting_value('users')) {
            $fields[] = 'confirmedby';
        }

        $mapping = new backup_nested_element('mapping', ['id'], $fields);
        $plugin->add_child($wrapper);
        $wrapper->add_child($mappings);
        $mappings->add_child($mapping);

        $mapping->set_source_table('local_outcomemapper_map', [
            'courseid' => backup::VAR_COURSEID,
        ]);

        return $plugin;
    }
}
