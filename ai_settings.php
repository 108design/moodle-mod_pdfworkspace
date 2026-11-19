<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Edit course and workspace question-analysis opt-ins.
 *
 * @package    mod_pdfworkspace
 * @copyright  2026 Andreas Giesen <andreas@108design.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_pdfworkspace\local\admin_help;
use mod_pdfworkspace\local\question_analysis;

require('../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('pdfworkspace', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pdfworkspace:view', $context);
if (!question_analysis::site_enabled() || !question_analysis::can_configure($context)) {
    throw new moodle_exception('aiquestions_unavailable', 'mod_pdfworkspace');
}
$coursecontext = $context->get_course_context();
$courseeditable = has_capability('moodle/course:update', $coursecontext);
$canmanageworkspace = has_capability('moodle/course:manageactivities', $context);
$courseenabled = question_analysis::course_enabled((int)$course->id);
$workspaceenabled = (bool)$DB->get_field('pdfworkspace', 'aiquestions', ['id' => $cm->instance], MUST_EXIST);
$workspaceeditable = $canmanageworkspace && ($courseenabled || $workspaceenabled);

$url = new moodle_url('/mod/pdfworkspace/ai_settings.php', ['id' => $cm->id]);
$overview = new moodle_url('/mod/pdfworkspace/view.php', ['id' => $cm->id, 'action' => 'overviewquestions']);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('aiquestions_settings', 'mod_pdfworkspace'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('aiquestions_settings', 'mod_pdfworkspace'));
admin_help::register_analysis_settings();

$form = new \mod_pdfworkspace\form\ai_settings($url, [
    'cmid' => $cm->id,
    'courseeditable' => $courseeditable,
    'workspaceeditable' => $workspaceeditable,
    'canmanageworkspace' => $canmanageworkspace,
    'courseenabled' => $courseenabled,
]);
$form->set_data([
    'courseoptin' => $courseenabled,
    'workspaceoptin' => $workspaceenabled,
]);
if ($form->is_cancelled()) {
    redirect($overview);
}
if ($data = $form->get_data()) {
    // Serialise changing the course setting from multiple workspaces in the same course.
    $lock = \core\lock\lock_config::get_lock_factory('mod_pdfworkspace')->get_lock('ai-course-' . $course->id, 5);
    if (!$lock) {
        throw new moodle_exception('locktimeout');
    }
    try {
        $transaction = $DB->start_delegated_transaction();
        if ($courseeditable) {
            question_analysis::write_course_opt_in((int)$course->id, !empty($data->courseoptin));
        }
        if ($workspaceeditable && (empty($data->workspaceoptin) || question_analysis::course_enabled((int)$course->id))) {
            $DB->set_field('pdfworkspace', 'aiquestions', (int)!empty($data->workspaceoptin), ['id' => $cm->instance]);
        }
        $transaction->allow_commit();
    } finally {
        $lock->release();
    }
    redirect($url, get_string('changessaved'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('aiquestions_settings', 'mod_pdfworkspace'));
echo html_writer::div(get_string('aiquestions_settings_intro', 'mod_pdfworkspace') . admin_help::icon('aipilot'), 'mb-3');
echo html_writer::div(html_writer::link($overview, get_string('questionstab', 'mod_pdfworkspace')), 'mb-3');
$form->display();
echo $OUTPUT->footer();
