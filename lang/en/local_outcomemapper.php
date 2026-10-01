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
 * Outcome Mapper local_outcomemapper.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


$string['activities'] = 'Activities';
$string['activity'] = 'Activity';
$string['additionalobjectives'] = 'Additional learning objectives';
$string['additionalobjectives_help'] = 'Enter one objective per line. You can use "Title | Description". These objectives are used only for this analysis and do not change Moodle competencies or grade outcomes.';
$string['additionalobjectives_placeholder'] = 'Explain the authentication flow | The learner can explain session creation, renewal and invalidation.
Design a secure API';
$string['aicaveat'] = 'AI relationships are suggestions. Confidence is model-reported and must not be treated as a verified probability.';
$string['analyse'] = 'Analyse course mapping';
$string['analysisby'] = 'Analysed by';
$string['analysiscomplete'] = 'Outcome mapping analysis completed.';
$string['analysisconfiguration'] = 'Analysis scope';
$string['analysisdate'] = 'Created';
$string['analysisfailed'] = 'Analysis failed.';
$string['analysisid'] = 'Analysis ID';
$string['assessedwithoutpreparation'] = 'Assessed without apparent preparatory evidence';
$string['assessment'] = 'Assessment';
$string['assessments'] = 'Assessments';
$string['batches'] = 'AI batches';
$string['bridgeerror'] = 'AI Bridge could not complete the analysis: {$a}';
$string['cmids'] = 'Activities and resources to include';
$string['competency'] = 'Competency';
$string['confidence'] = 'Confidence';
$string['confirm'] = 'Confirm';
$string['confirmed'] = 'Confirmed by teacher';
$string['confirmedmappings'] = 'Confirmed mappings';
$string['content'] = 'Content';
$string['coursecontent'] = 'Course content';
$string['customobjective'] = 'Additional objective';
$string['error_analysismissing'] = 'The requested analysis does not exist in this course.';
$string['error_cannotconfirmnone'] = 'A none relation cannot be persisted as a confirmed mapping.';
$string['error_duplicateobjective'] = 'AI returned the same objective more than once for target {$a}.';
$string['error_invalidjson'] = 'The AI response was not valid JSON.';
$string['error_invalidrelation'] = 'AI returned an unsupported relation: {$a}';
$string['error_invalidshape'] = 'The AI response JSON does not match the required schema.';
$string['error_missingobjective'] = 'AI did not classify every objective for target {$a}.';
$string['error_suggestionmissing'] = 'The requested suggestion does not exist in this course.';
$string['error_unknownobjective'] = 'AI returned an unknown objective ID: {$a}';
$string['error_unknowntarget'] = 'AI returned an unknown target ID: {$a}';
$string['evidence'] = 'Evidence';
$string['explanation'] = 'Explanation';
$string['findings'] = 'Coverage findings';
$string['heuristicnote'] = 'Coverage findings are heuristics built from AI-suggested relationships. They are not facts and should be reviewed by a teacher.';
$string['includecompetencies'] = 'Include Moodle competencies';
$string['includeoutcomes'] = 'Include grade outcomes';
$string['includequestions'] = 'Include quiz questions';
$string['invalidaction'] = 'Invalid review action.';
$string['invalidairesponse'] = 'AI Bridge returned an invalid mapping response. Nothing from that response was saved.';
$string['latestanalysis'] = 'Latest analysis';
$string['lowcompetencyevidence'] = 'Competencies with limited mapped evidence';
$string['mappingconfirmed'] = 'Mapping confirmed.';
$string['mappingrejected'] = 'Mapping rejected.';
$string['matrix'] = 'Objective × course evidence matrix';
$string['maxitemchars'] = 'Maximum characters per course object';
$string['maxitemchars_desc'] = 'Maximum normalized text sent to AI for each objective or course object. Values below 500 are raised to 500 and values above 12000 are capped at 12000.';
$string['noconfirmedmappings'] = 'No mappings have been confirmed yet.';
$string['nomapping'] = 'No apparent relation';
$string['none'] = 'None';
$string['noneavailable'] = 'No selectable items are available.';
$string['noobjectives'] = 'No learning objectives were available for analysis.';
$string['nosuggestions'] = 'No suggestions are available for this analysis.';
$string['notargets'] = 'No course content, activities, assessments or questions were available for analysis.';
$string['objective'] = 'Objective';
$string['objectivebatchsize'] = 'Objectives per AI batch';
$string['objectivebatchsize_desc'] = 'Number of objectives sent in each AI request. The effective range is 5 to 80.';
$string['objectives'] = 'Objectives';
$string['outcome'] = 'Outcome';
$string['outcomemapper:analyse'] = 'Analyse learning outcomes and course evidence';
$string['overrepresented'] = 'Possibly over-represented objectives';
$string['pluginname'] = 'Outcome Mapper';
$string['privacy:metadata:analysis'] = 'Stores course analysis run metadata and deterministic course-object catalogs.';
$string['privacy:metadata:analysis:userid'] = 'The user who started the analysis.';
$string['privacy:metadata:map'] = 'Stores mappings explicitly confirmed by a teacher.';
$string['privacy:metadata:map:confirmedby'] = 'The user who confirmed the mapping.';
$string['privacy:metadata:suggest'] = 'Stores AI suggestions and teacher review state.';
$string['privacy:metadata:suggest:reviewedby'] = 'The user who reviewed a suggestion.';
$string['privacy:path:analysis'] = 'Outcome Mapper analyses';
$string['privacy:path:confirmed'] = 'Outcome Mapper confirmed mappings';
$string['privacy:path:reviews'] = 'Outcome Mapper reviews';
$string['probable'] = 'Probable';
$string['purposeid'] = 'AI Bridge purpose';
$string['purposeid_desc'] = 'This plugin always calls local_ai_bridge with purpose idnumber outcomemapper-map.';
$string['question'] = 'Question';
$string['questions'] = 'Questions';
$string['reject'] = 'Reject';
$string['rejected'] = 'Rejected by teacher';
$string['reviewmapping'] = 'Review mapping';
$string['section'] = 'Section';
$string['sectionids'] = 'Sections to include';
$string['source'] = 'Source';
$string['status'] = 'Status';
$string['strong'] = 'Strong';
$string['suggested'] = 'Suggested by AI';
$string['targetbatchsize'] = 'Course objects per AI batch';
$string['targetbatchsize_desc'] = 'Number of content/activity/assessment/question objects sent in each AI request. The effective range is 1 to 20.';
$string['targets'] = 'Course objects';
$string['taughtnotassessed'] = 'Taught but not apparently assessed';
$string['unmappedactivities'] = 'Activities without an apparent objective';
$string['unmappedassessments'] = 'Assessments without an apparent objective';
$string['viewactivity'] = 'Open activity';
$string['viewquiz'] = 'Open quiz';
$string['warning_competencynopermission'] = 'Competencies were requested, but the current user does not have permission to view course competencies.';
$string['warning_contentmissing'] = 'No textual content was available for "{$a}". The object was still included using its title and metadata.';
$string['warning_modulemissing'] = 'Course module {$a} could not be loaded.';
$string['warning_quizquestionsnopermission'] = 'Questions from quiz "{$a}" were skipped because the current user cannot preview or manage that quiz.';
$string['warnings'] = 'Collection warnings';
$string['weak'] = 'Weak';
