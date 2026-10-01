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
 * Outcome Mapper analysis_form.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper\form;

use moodleform;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Analysis scope form.
 *
 * @package   local_outcomemapper
 */
class analysis_form extends moodleform {
    /**
     * Define form.
     */
    public function definition(): void {
        $mform = $this->_form;
        $courseid = (int)$this->_customdata['courseid'];
        $sections = $this->_customdata['sections'] ?? [];
        $modules = $this->_customdata['modules'] ?? [];
        $cancompetency = !empty($this->_customdata['cancompetency']);

        $mform->addElement('header', 'analysisconfiguration', get_string('analysisconfiguration', 'local_outcomemapper'));

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('advcheckbox', 'includecompetencies', get_string('includecompetencies', 'local_outcomemapper'));
        $mform->setDefault('includecompetencies', $cancompetency ? 1 : 0);
        if (!$cancompetency) {
            $mform->freeze('includecompetencies');
        }

        $mform->addElement('advcheckbox', 'includeoutcomes', get_string('includeoutcomes', 'local_outcomemapper'));
        $mform->setDefault('includeoutcomes', 1);

        $mform->addElement('advcheckbox', 'includequestions', get_string('includequestions', 'local_outcomemapper'));
        $mform->setDefault('includequestions', 1);

        $mform->addElement('autocomplete', 'sectionids', get_string('sectionids', 'local_outcomemapper'), $sections, [
            'multiple' => true,
        ]);
        $mform->setType('sectionids', PARAM_INT);
        $mform->setDefault('sectionids', array_keys($sections));

        $mform->addElement('autocomplete', 'cmids', get_string('cmids', 'local_outcomemapper'), $modules, [
            'multiple' => true,
        ]);
        $mform->setType('cmids', PARAM_INT);
        $mform->setDefault('cmids', array_keys($modules));

        $mform->addElement('textarea', 'additionalobjectives', get_string('additionalobjectives', 'local_outcomemapper'), [
            'rows' => 6,
            'cols' => 80,
            'placeholder' => get_string('additionalobjectives_placeholder', 'local_outcomemapper'),
        ]);
        $mform->setType('additionalobjectives', PARAM_RAW);
        $mform->addHelpButton('additionalobjectives', 'additionalobjectives', 'local_outcomemapper');

        $this->add_action_buttons(false, get_string('analyse', 'local_outcomemapper'));
    }
}
