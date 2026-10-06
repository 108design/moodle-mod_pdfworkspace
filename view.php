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
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @author    Ahmad Obeid, Anna Heynkes
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->dirroot . '/mod/pdfworkspace/locallib.php'); // Requires lib.php in turn.
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/mod/pdfworkspace/model/pdfworkspace.php');
require_once('renderable.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
$r = optional_param('r', 0, PARAM_INT);  // Pdfworkspace instance ID.
$redirect = optional_param('redirect', 0, PARAM_BOOL);

$page = optional_param('page', 1, PARAM_INT);
$requestedpreview = optional_param('doc', 0, PARAM_INT);
$annoid = optional_param('annoid', null, PARAM_INT);
$commid = optional_param('commid', null, PARAM_INT);

if ($r) {
    if (!$pdfworkspace = $DB->get_record('pdfworkspace', array('id' => $r))) {
        throw new moodle_exception('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('pdfworkspace', $pdfworkspace->id, $pdfworkspace->course, false, MUST_EXIST);
} else {
    if (!$cm = get_coursemodule_from_id('pdfworkspace', $id)) {
        throw new moodle_exception('invalidcoursemodule');
    }
    $pdfworkspace = $DB->get_record('pdfworkspace', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = get_course($cm->course); // Get course by id.
require_course_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/pdfworkspace:view', $context);

// Apply filters, e.g. multilang.
$rawname = $pdfworkspace->name;
$pdfworkspace->name = format_text($pdfworkspace->name, FORMAT_MOODLE, ['para' => false, 'filter' => true]);
$PAGE->set_title(format_string($rawname));

// Completion and trigger events.
pdfworkspace_view($pdfworkspace, $course, $cm, $context);

$fs = get_file_storage();
$documents = pdfworkspace_get_documents($pdfworkspace->id, $context->id);
$files = $fs->get_area_files($context->id, 'mod_pdfworkspace', 'content', 0, 'sortorder DESC, id ASC', false);
if (!$documents || !$files) {
    pdfworkspace_print_filenotfound($pdfworkspace, $cm, $course);
    die;
}
$selectedid = $requestedpreview;
if (!$selectedid && $annoid) {
    $selectedid = (int)$DB->get_field('pdfworkspace_annotations', 'documentid',
        ['id' => $annoid, 'pdfworkspaceid' => $pdfworkspace->id]);
}
if (!$selectedid) {
    $selectedid = (int)reset($documents)->id;
}
if (!isset($documents[$selectedid])) {
    throw new moodle_exception('invalidaccessparameter');
}
$pdfworkspace->documents = $documents;
$pdfworkspace->currentdocumentid = $selectedid;
$selecteddocument = $documents[$selectedid];
$file = null;
foreach ($files as $candidate) {
    if ($candidate->get_filepath() === $selecteddocument->filepath &&
            $candidate->get_filename() === $selecteddocument->filename &&
            $candidate->get_contenthash() === $selecteddocument->contenthash) {
        $file = $candidate;
        break;
    }
}
if ($file === null) {
    pdfworkspace_print_filenotfound($pdfworkspace, $cm, $course);
    die;
}
$PAGE->set_url('/mod/pdfworkspace/view.php', ['id' => $cm->id, 'doc' => $selectedid]);

$pdfworkspace->mainfile = $file->get_filename();

// Set course name for display.
$PAGE->set_heading($course->fullname);

// Display course name, navigation bar at the very top and "Dashboard->...->..." bar.
$PAGE->requires->css(new moodle_url('/mod/pdfworkspace/shared/viewer.css', ['v' => '2026092809.2']));
$PAGE->requires->css(new moodle_url('/mod/pdfworkspace/shared/ui.css', ['v' => '2026100602']));
$PAGE->requires->js(new moodle_url('/mod/pdfworkspace/shared/tooltips.js', ['v' => '2026100600']));
echo $OUTPUT->header();

// Render the activity information.
if ($CFG->version < 2022041900) {
    $modinfo = get_fast_modinfo($course);
    $cminfo = $modinfo->get_cm($cm->id);
    $completiondetails = \core_completion\cm_completion_details::get_instance($cminfo, $USER->id);
    $activitydates = \core\activity_dates::get_dates_for_module($cminfo, $USER->id);
    echo $OUTPUT->activity_information($cminfo, $completiondetails, $activitydates);
}

require_once($CFG->dirroot . '/mod/pdfworkspace/controller.php');

// Display navigation and settings bars on the left as well as the footer.
echo $OUTPUT->footer();
