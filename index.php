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
 * Outcome Mapper index.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../../config.php');

use core\notification;
use core_competency\api;
use local_outcomemapper\analysis_service;
use local_outcomemapper\dashboard_builder;
use local_outcomemapper\form\analysis_form;
use local_outcomemapper\selection_helper;

$courseid = required_param('courseid', PARAM_INT);
$analysisid = optional_param('analysisid', 0, PARAM_INT);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/outcomemapper:analyse', $context);

$url = new moodle_url('/local/outcomemapper/index.php', ['courseid' => $courseid]);
if ($analysisid) {
    $url->param('analysisid', $analysisid);
}

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('pluginname', 'local_outcomemapper'));
$PAGE->set_heading(format_string($course->fullname));

$sections = selection_helper::section_options($courseid);
$modules = selection_helper::module_options($courseid);

$cancompetency = false;
if (class_exists(api::class) && api::is_enabled()) {
    $cancompetency = has_any_capability([
        'moodle/competency:coursecompetencyview',
        'moodle/competency:coursecompetencymanage',
    ], $context);
}

$form = new analysis_form(null, [
    'courseid' => $courseid,
    'sections' => $sections,
    'modules' => $modules,
    'cancompetency' => $cancompetency,
]);

if ($data = $form->get_data()) {
    $service = new analysis_service();

    try {
        $newanalysisid = $service->run($courseid, [
            'sectionids' => (array)($data->sectionids ?? []),
            'cmids' => (array)($data->cmids ?? []),
            'includecompetencies' => !empty($data->includecompetencies),
            'includeoutcomes' => !empty($data->includeoutcomes),
            'includequestions' => !empty($data->includequestions),
            'additionalobjectives' => (string)($data->additionalobjectives ?? ''),
        ]);

        redirect(
            new moodle_url('/local/outcomemapper/index.php', [
                'courseid' => $courseid,
                'analysisid' => $newanalysisid,
            ]),
            get_string('analysiscomplete', 'local_outcomemapper'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (Throwable $e) {
        notification::error($e->getMessage());
    }
}

$builder = new dashboard_builder();
$dashboard = $builder->build($courseid, $analysisid ?: null);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_outcomemapper'));

$form->display();

echo $OUTPUT->render_from_template('local_outcomemapper/dashboard', $dashboard);
echo $OUTPUT->footer();
