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
 * @author    Anna Heynkes, Friederike Schwager
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_pdfworkspace\output\statistics;

defined('MOODLE_INTERNAL') || die();

$action = optional_param('action', 'view', PARAM_ALPHA); // The default action is 'view'.

$taburl = new moodle_url('/mod/pdfworkspace/view.php', array('id' => $id));

$myrenderer = $PAGE->get_renderer('mod_pdfworkspace');

require_course_login($pdfworkspace->course, true, $cm);

/* * ********************************************** Display overview page *********************************************** */

if ($action === 'overview') {
    // Go to question-overview by default.
    $action = 'overviewquestions';
}

if ($action === 'forwardquestion') {
    require_sesskey();
    require_capability('mod/pdfworkspace:forwardquestions', $context);
    require_once($CFG->dirroot . '/mod/pdfworkspace/forward_form.php');
    global $USER;

    $commentid = required_param('commentid', PARAM_INT);
    $fromoverview = optional_param('fromoverview', 0, PARAM_INT);
    $sql = "SELECT c.*, a.page, cm.id AS cmid "
        . "FROM {pdfworkspace_comments} c "
        . "JOIN {pdfworkspace_annotations} a ON c.annotationid = a.id "
        . "JOIN {pdfworkspace} p ON a.pdfworkspaceid = p.id "
        . "JOIN {course_modules} cm ON p.id = cm.instance "
        . "WHERE c.isdeleted = 0 AND c.id = ? AND cm.id = ?";
    $params = [$commentid, $cm->id];
    $comments = $DB->get_records_sql($sql, $params);
    $error = false;
    if (!$comments) {
        $error = true;
    } else {
        $comment = $comments[$commentid];
        if ((int)$comment->cmid !== (int)$cm->id || !\mod_pdfworkspace\visibility::can_view_comment(
                $comment, $context, $USER->id)) {
            $error = true;
        }
        if (!$error && $comment->ishidden && !has_capability('mod/pdfworkspace:seehiddencomments', $context)) {
            $error = true;
        }
    }

    $recipientslist = $error ? [] : pdfworkspace_forward_recipients($comment, $context, $USER->id);

    if (count($recipientslist) === 0) {
        $error = true;
        $errorinfo = get_string('error:forwardquestionnorecipient', 'pdfworkspace');
    }

    if ($error) { // An error occured e.g. comment doesn't exist.
        if (!isset($errorinfo)) {
            $errorinfo = get_string('error:forwardquestion', 'pdfworkspace'); // Display error notification.
        }
        \core\notification::add($errorinfo, \core\notification::ERROR);
        if ($fromoverview) {
            // If user forwarded question from overview go back to overview.
            $action = 'overviewquestions';
        } else {
            // Else go to document.
            $action = 'view';
        }
    } else {

        $data = new stdClass();
        $data->course = $cm->course;
        $data->pdfworkspaceid = $cm->instance;
        $data->pdfname = format_string($cm->name, true);
        $data->commentid = $commentid;
        $data->id = $cm->id; // Course module id.
        $data->action = 'forwardquestion';
        $data->fromoverview = $fromoverview;

        // Initialise mform and pass on $data-object to it.
        $mform = new pdfworkspace_forward_form(null, ['comment' => $comment, 'recipients' => $recipientslist]);
        $mform->set_data($data);

        if ($mform->is_cancelled()) { // Form was cancelled.
            // Go back to overview or document.
            if ($fromoverview) {
                $action = 'overviewquestions';
            } else {
                $action = 'view';
            }
        } else if ($data = $mform->get_data()) { // Process validated data. $mform->get_data() returns data posted in form.
            $url = (new moodle_url('/mod/pdfworkspace/view.php', array('id' => $comment->cmid,
                'page' => $comment->page, 'annoid' => $comment->annotationid, 'commid' => $comment->id)))->out();

            $params = new stdClass();
            $params->sender = $USER->firstname . ' ' . $USER->lastname;
            $params->questioncontent = $comment->content;
            $params->message = $data->message;
            $params->urltoquestion = $url;

            if (isset($data->recipients)) {
                pdfworkspace_send_forward_message($data->recipients, $params, $course, $cm, $context);
            }
            if ($fromoverview) {
                // If user forwarded question from overview go back to overview.
                $action = 'overviewquestions';
            } else {
                // Else go to document.
                $action = 'view';
            }
        } else { // Executed if the form is submitted but the data doesn't validate and the form should be redisplayed
            // or on the first display of the form.
            echo $OUTPUT->heading(get_string('titleforwardform', 'pdfworkspace'));
            $mform->display(); // Display form.
        }
    }
}
/*
 * This section prints a subpage of overview called 'unsolved questions'.
 */
if ($action === 'overviewquestions') {
    require_capability('mod/pdfworkspace:viewquestions', $context);

    global $OUTPUT, $CFG;

    require_once($CFG->libdir . '/tablelib.php');
    require_once($CFG->dirroot . '/mod/pdfworkspace/locallib.php');


    $currentpage = optional_param('page', 0, PARAM_INT);
    $itemsperpage = optional_param('itemsperpage', 5, PARAM_INT);
    $questionfilter = optional_param('questionfilter', 0, PARAM_INT); // Default 0 means: Display only unsolved/open questions.
    $documentfilter = pdfworkspace_valid_document_filter($pdfworkspace->id, $context->id,
        optional_param('documentfilter', 0, PARAM_INT));

    $thisannotator = $pdfworkspace->id;
    $thiscourse = $pdfworkspace->course;
    $cmid = get_coursemodule_from_instance('pdfworkspace', $thisannotator, $thiscourse, false, MUST_EXIST)->id;

    pdfworkspace_prepare_overviewpage($id, $myrenderer, $taburl, ['tab' => 'overview', 'action' => $action], $pdfworkspace,
        $context);
    echo $OUTPUT->heading(get_string('questionstab', 'pdfworkspace'));
    echo pdfworkspace_render_overview_filters($cmid, $thisannotator, $action, 'questionfilter',
        $questionfilter, [0 => get_string('openquestions', 'pdfworkspace'),
            1 => get_string('closedquestions', 'pdfworkspace'),
            2 => get_string('allquestions', 'pdfworkspace')], $itemsperpage, $documentfilter);

    $questions = pdfworkspace_get_questions($thisannotator, $context, $questionfilter, $documentfilter);

    if (empty($questions)) {
        if ($questionfilter == 1) {
            $info = get_string('noquestionsclosed_overview', 'pdfworkspace');
        } else if ($questionfilter == 2) {
            $info = get_string('noquestions_overview', 'pdfworkspace');
        } else {
            $info = get_string('noquestionsopen_overview', 'pdfworkspace');
        }
        echo "<span class='notification'><div class='alert alert-info alert-block fade in' role='alert'>$info</div></span>";
    } else {
        $urlparams = array('action' => 'overviewquestions', 'id' => $cmid, 'page' => $currentpage, 'itemsperpage' => $itemsperpage,
            'questionfilter' => $questionfilter, 'documentfilter' => $documentfilter);
        pdfworkspace_print_questions($questions, $thiscourse, $urlparams, $currentpage, $itemsperpage, $context);
    }
}
/*
 * This section subscribes the user to a particular question and then rerenders the overview table of
 * all answers.
 */
if ($action === 'subscribeQuestion') {
    require_sesskey();
    require_capability('mod/pdfworkspace:subscribe', $context);

    global $DB;

    $annotationid = required_param('annotationid', PARAM_INT);

    $annotatorid = $DB->get_field('pdfworkspace_annotations', 'pdfworkspaceid', ['id' => $annotationid], $strictness = MUST_EXIST);
    $annotation = $DB->get_record('pdfworkspace_annotations',
        ['id' => $annotationid, 'pdfworkspaceid' => $cm->instance], '*', MUST_EXIST);
    if (!\mod_pdfworkspace\visibility::can_view_annotation($annotation, $context, $USER->id)) {
        throw new required_capability_exception($context, 'mod/pdfworkspace:view', 'nopermissions', '');
    }

    $subscriptionid = pdfworkspace_comment::insert_subscription($annotationid, $context);

    if (!empty($subscriptionid)) {
        $info = get_string('successfullySubscribed', 'pdfworkspace');
        \core\notification::add($info, \core\notification::SUCCESS);
    }

    $action = 'overviewanswers';
}
/*
 * This section unsubscribes the user from a particular question and then rerenders the overview table of
 * answers to questions to which the user is subscribed.
 */
if ($action === 'unsubscribeQuestion') {
    require_sesskey();
    require_capability('mod/pdfworkspace:subscribe', $context);

    global $DB;

    $annotationid = required_param('annotationid', PARAM_INT);
    $answerfilter = optional_param('answerfilter', 1, PARAM_INT);

    $annotatorid = $DB->get_field('pdfworkspace_annotations', 'pdfworkspaceid', ['id' => $annotationid], $strictness = MUST_EXIST);
    $annotation = $DB->get_record('pdfworkspace_annotations',
        ['id' => $annotationid, 'pdfworkspaceid' => $cm->instance], '*', MUST_EXIST);
    if (!\mod_pdfworkspace\visibility::can_view_annotation($annotation, $context, $USER->id)) {
        throw new required_capability_exception($context, 'mod/pdfworkspace:view', 'nopermissions', '');
    }

    $entrycount = pdfworkspace_comment::delete_subscription($annotationid);

    if (!empty($entrycount) && ($answerfilter == 1)) {
        if ($entrycount == 1) {
            $info = get_string('successfullyUnsubscribedSingular', 'pdfworkspace', $entrycount);
        } else if ($entrycount == 2) {
            $info = get_string('successfullyUnsubscribedTwo', 'pdfworkspace', $entrycount);
        } else {
            $info = get_string('successfullyUnsubscribedPlural', 'pdfworkspace', $entrycount);
        }
    } else {
        $info = get_string('successfullyUnsubscribed', 'pdfworkspace', $entrycount);
    }
    \core\notification::add($info, \core\notification::SUCCESS);

    $action = 'overviewanswers';
}
/*
 * This section prints a subpage of overview called 'answers'. It lists all answers to questions the current
 * user asked or subscribed to.
 */
if ($action === 'overviewanswers') {
    require_capability('mod/pdfworkspace:viewanswers', $context);

    require_once($CFG->libdir . '/tablelib.php');
    require_once($CFG->dirroot . '/mod/pdfworkspace/locallib.php');

    global $CFG, $OUTPUT, $DB;

    $currentpage = optional_param('page', 0, PARAM_INT);

    $itemsperpage = optional_param('itemsperpage', 5, PARAM_INT);
    $answerfilter = optional_param('answerfilter', 1, PARAM_INT);
    $documentfilter = pdfworkspace_valid_document_filter($pdfworkspace->id, $context->id,
        optional_param('documentfilter', 0, PARAM_INT));

    $thisannotator = $pdfworkspace->id;
    $thiscourse = $pdfworkspace->course;
    $cmid = get_coursemodule_from_instance('pdfworkspace', $thisannotator, $thiscourse, false, MUST_EXIST)->id;

    pdfworkspace_prepare_overviewpage($id, $myrenderer, $taburl, ['tab' => 'overview', 'action' => $action], $pdfworkspace,
        $context);
    echo $OUTPUT->heading(get_string('answerstab', 'pdfworkspace'));
    echo pdfworkspace_render_overview_filters($cmid, $thisannotator, $action, 'answerfilter',
        $answerfilter, [0 => get_string('allanswers', 'pdfworkspace'),
            1 => get_string('subscribedanswers', 'pdfworkspace')], $itemsperpage, $documentfilter);

    $data = pdfworkspace_get_answers_for_this_user($thisannotator, $context, $answerfilter, $documentfilter);

    if (empty($data)) {
        if ($answerfilter == 1) {
            $info = get_string('noanswerssubscribed', 'pdfworkspace');
        } else {
            $info = get_string('noanswers', 'pdfworkspace');
        }
        echo "<span class='notification'><div class='alert alert-info alert-block fade in' role='alert'>$info</div></span>";
    } else {
        $urlparams = array('action' => 'overviewanswers', 'id' => $cmid, 'page' => $currentpage, 'itemsperpage' => $itemsperpage,
            'answerfilter' => $answerfilter, 'documentfilter' => $documentfilter);
        $url = new moodle_url($CFG->wwwroot . '/mod/pdfworkspace/view.php', $urlparams);
        pdfworkspace_print_answers($data, $thiscourse, $url, $currentpage, $itemsperpage, $cmid, $answerfilter, $context);
    }
}
/*
 * This section prints a subpage of overview called "My posts".
 */
if ($action === 'overviewownposts') {
    require_capability('mod/pdfworkspace:viewposts', $context);

    require_once($CFG->libdir . '/tablelib.php');
    require_once($CFG->dirroot . '/mod/pdfworkspace/locallib.php');

    global $CFG, $OUTPUT;

    $currentpage = optional_param('page', 0, PARAM_INT);
    $itemsperpage = optional_param('itemsperpage', 5, PARAM_INT);
    $documentfilter = pdfworkspace_valid_document_filter($pdfworkspace->id, $context->id,
        optional_param('documentfilter', 0, PARAM_INT));

    $thisannotator = $pdfworkspace->id;
    $thiscourse = $pdfworkspace->course;
    $cmid = get_coursemodule_from_instance('pdfworkspace', $thisannotator, $thiscourse, false, MUST_EXIST)->id;

    pdfworkspace_prepare_overviewpage($id, $myrenderer, $taburl, ['tab' => 'overview', 'action' => $action], $pdfworkspace,
        $context);
    echo $OUTPUT->heading(get_string('ownpoststab', 'pdfworkspace'));
    echo pdfworkspace_render_overview_filters($cmid, $thisannotator, $action, null,
        null, [], $itemsperpage, $documentfilter);

    $posts = pdfworkspace_get_posts_by_this_user($thisannotator, $context, $documentfilter);

    if (empty($posts)) {
        $info = get_string('nomyposts', 'pdfworkspace');
        echo "<span class='notification'><div class='alert alert-info alert-block fade in' role='alert'>$info</div></span>";
    } else {
        $urlparams = array('action' => 'overviewownposts', 'id' => $cmid, 'page' => $currentpage,
            'itemsperpage' => $itemsperpage, 'documentfilter' => $documentfilter);
        $url = new moodle_url($CFG->wwwroot . '/mod/pdfworkspace/view.php', $urlparams);
        pdfworkspace_print_this_users_posts($posts, $thiscourse, $url, $currentpage, $itemsperpage);
    }
}
/*
 * This section marks a report as read and then rerenders the overview table of reports
 * (either unread reports (reportfiler == 0) or all reports (reportfilter == 2)).
 */
if ($action === 'markreportasread') { // XXX Rename key and move it into $action === 'overviewreports'.
    require_sesskey();
    require_capability('mod/pdfworkspace:viewreports', $context);

    global $DB;

    $reportid = required_param('reportid', PARAM_INT);
    if (!\mod_pdfworkspace\visibility::can_manage_report($reportid, $cm->instance, $USER->id)) {
        throw new required_capability_exception($context, 'mod/pdfworkspace:viewreports', 'nopermissions', '');
    }
    $itemsperpage = optional_param('itemsperpage', 5, PARAM_INT);
    $reportfilter = optional_param('reportfilter', 0, PARAM_INT);

    $success = $DB->update_record('pdfworkspace_reports', array("id" => $reportid, "seen" => 1), $bulk = false);

    // Give feedback to the user.
    if ($success) {
        switch ($reportfilter) {
            case 0:// Filter is currently set to show read reports only.
                $info = get_string('successfullymarkedasreadandnolongerdisplayed', 'pdfworkspace');
                break;
            case 2: // Filter is currently set to show all reports in this course.
                $info = get_string('successfullymarkedasread', 'pdfworkspace');
                break;
            default:
                $info = get_string('successfullymarkedasread', 'pdfworkspace');
        }
        \core\notification::add($info, \core\notification::SUCCESS);
    } else {
        $info = get_string('error:markasread', 'pdfworkspace');
        \core\notification::add($info, \core\notification::ERROR);
    }

    $action = 'overviewreports'; // This will do the actual rerendering of the page (see below).
}
/*
 * This section marks a report as read and then rerenders the overview table of reports
 * (either unread reports (reportfiler == 0) or all reports (reportfilter == 2)).
 */
if ($action === 'markreportasunread') { // XXX Rename key and move it into $action === 'overviewreports'.
    require_sesskey();
    require_capability('mod/pdfworkspace:viewreports', $context);

    global $DB;

    $reportid = required_param('reportid', PARAM_INT);
    if (!\mod_pdfworkspace\visibility::can_manage_report($reportid, $cm->instance, $USER->id)) {
        throw new required_capability_exception($context, 'mod/pdfworkspace:viewreports', 'nopermissions', '');
    }
    $itemsperpage = optional_param('itemsperpage', 5, PARAM_INT);
    $reportfilter = optional_param('reportfilter', 2, PARAM_INT);

    $success = $DB->update_record('pdfworkspace_reports', array("id" => $reportid, "seen" => 0), $bulk = false);

    // Give feedback to the user.
    if ($success) {
        switch ($reportfilter) {
            case 1: // I.e.: Filter is currently set to show unread reports only.
                $info = get_string('successfullymarkedasunreadandnolongerdisplayed', 'pdfworkspace');
                break;
            case 2: // I.e.: Filter is currently set to show all reports in this course.
                $info = get_string('successfullymarkedasunread', 'pdfworkspace');
                break;
            default:
                $info = get_string('successfullymarkedasunread', 'pdfworkspace');
        }
        \core\notification::add($info, \core\notification::SUCCESS);
    } else {
        $info = get_string('error:markasunread', 'pdfworkspace');
        \core\notification::add($info, \core\notification::ERROR);
    }

    $action = 'overviewreports'; // This will do the actual rerendering of the page (see below).
}
/*
 * This section prints a subpage of overview called "Reports" were comments that were reported as inappropriate are listed.
 */
if ($action === 'overviewreports') {
    require_capability('mod/pdfworkspace:viewreports', $context);

    require_once($CFG->libdir . '/tablelib.php');
    require_once($CFG->dirroot . '/mod/pdfworkspace/locallib.php');

    global $CFG, $OUTPUT;

    $currentpage = optional_param('page', 0, PARAM_INT);
    $itemsperpage = optional_param('itemsperpage', 5, PARAM_INT);
    $reportfilter = optional_param('reportfilter', 0, PARAM_INT);
    $documentfilter = pdfworkspace_valid_document_filter($pdfworkspace->id, $context->id,
        optional_param('documentfilter', 0, PARAM_INT));

    $thisannotator = $pdfworkspace->id;
    $thiscourse = $pdfworkspace->course;
    $cmid = get_coursemodule_from_instance('pdfworkspace', $thisannotator, $thiscourse, false, MUST_EXIST)->id;

    pdfworkspace_prepare_overviewpage($id, $myrenderer, $taburl, ['tab' => 'overview', 'action' => $action], $pdfworkspace,
        $context);
    echo $OUTPUT->heading(get_string('reportstab', 'pdfworkspace'));
    echo pdfworkspace_render_overview_filters($cmid, $thisannotator, $action, 'reportfilter',
        $reportfilter, [0 => get_string('unseenreports', 'pdfworkspace'),
            1 => get_string('seenreports', 'pdfworkspace'),
            2 => get_string('allreports', 'pdfworkspace')], $itemsperpage, $documentfilter);

    $reports = pdfworkspace_get_reports($thisannotator, $context, $reportfilter, $documentfilter);

    if (empty($reports)) {
        switch ($reportfilter) {
            case 0:
                $info = get_string('nounreadreports', 'pdfworkspace');
                break;
            case 1:
                $info = get_string('noreadreports', 'pdfworkspace');
                break;
            case 2:
                $info = get_string('noreports', 'pdfworkspace');
                break;
        }
        echo "<span class='notification'><div class='alert alert-info alert-block fade in' role='alert'>$info</div></span>";
    } else {
        $urlparams = array('action' => 'overviewreports', 'id' => $cmid, 'page' => $currentpage, 'itemsperpage' => $itemsperpage,
            'reportfilter' => $reportfilter, 'documentfilter' => $documentfilter);
        $url = new moodle_url($CFG->wwwroot . '/mod/pdfworkspace/view.php', $urlparams);
        pdfworkspace_print_reports($reports, $thiscourse, $url, $currentpage, $itemsperpage, $cmid, $reportfilter, $context);
    }
}

/* * ********************************** Display the pdf in its editor (default action) *************************************** */

if ($action === 'view') { // Default.
    echo $myrenderer->pdfworkspace_render_tabs($taburl, $pdfworkspace->name, $context, $action);

    pdfworkspace_display_embed($pdfworkspace, $cm, $course, $file, $page, $annoid, $commid);
}

/* * ********************************************** Display statistics *********************************************** */

if ($action === 'statistic') {

    pdfworkspace_require_statistics_access($context);

    require_once($CFG->dirroot . '/mod/pdfworkspace/model/statistics.class.php');

    echo $myrenderer->pdfworkspace_render_tabs($taburl, $pdfworkspace->name, $context, $action);
    echo $OUTPUT->heading(get_string('statistic', 'pdfworkspace'));

    // Give javascript access to the language string repository.
    $stringman = get_string_manager();
    $strings = $stringman->load_component_strings('pdfworkspace', 'en'); // Method gets the strings of the language files.
    $PAGE->requires->strings_for_js(array_keys($strings), 'pdfworkspace'); // Method to use the language-strings in javascript.
    $PAGE->requires->js(new moodle_url("/mod/pdfworkspace/shared/statistic.js?ver=0004"));
    $myrenderer = $PAGE->get_renderer('mod_pdfworkspace');
    $capabilities = new stdClass();
    $capabilities->viewquestions = has_capability('mod/pdfworkspace:viewquestions', $context);
    $capabilities->viewanswers = has_capability('mod/pdfworkspace:viewanswers', $context);
    $capabilities->viewposts = has_capability('mod/pdfworkspace:viewposts', $context);
    $capabilities->viewreports = has_capability('mod/pdfworkspace:viewreports', $context);
    $capabilities->viewteacherstatistics = has_capability('mod/pdfworkspace:viewteacherstatistics', $context);

    echo $myrenderer->render_statistic(new statistics($cm->instance, $course->id, $capabilities, $id));
}

/* * ***************************************** Display form for reporting a comment  ******************************************** */

if ($action === 'report') {

    require_once($CFG->dirroot . '/mod/pdfworkspace/reportform.php');
    require_once($CFG->dirroot . '/mod/pdfworkspace/model/comment.class.php');

    global $DB;

    // Get comment id.
    $commentid = optional_param('commentid', 0, PARAM_INT);
    $reportedcomment = $DB->get_record('pdfworkspace_comments',
        ['id' => $commentid, 'pdfworkspaceid' => $cm->instance], '*', MUST_EXIST);
    if (!\mod_pdfworkspace\visibility::can_view_comment($reportedcomment, $context, $USER->id)) {
        throw new required_capability_exception($context, 'mod/pdfworkspace:view', 'nopermissions', '');
    }

    // Contextual data to pass on to the report form.
    $data = new stdClass();
    $data->course = $cm->course;
    $data->pdfworkspaceid = $cm->instance;
    $data->pdfname = format_string($cm->name, true);
    $data->commentid = $commentid;
    $data->id = $id; // Course module id.
    $data->action = 'report';

    // Initialise mform and pass on $data-object to it.
    $mform = new pdfworkspace_reportform();
    $mform->set_data($data);

    /*     * ******************* Form processing and displaying is done here ************************ */
    if ($mform->is_cancelled()) {
        $action = 'view';
        echo $myrenderer->pdfworkspace_render_tabs($taburl, $pdfworkspace->name, $context, $action);
        pdfworkspace_display_embed($pdfworkspace, $cm, $course, $file);
    } else if ($report = $mform->get_data()) { // Process validated data. $mform->get_data() returns data posted in form.
        require_sesskey();
        global $USER;
        if ((int)$report->commentid !== (int)$reportedcomment->id) {
            throw new invalid_parameter_exception('Invalid comment for report');
        }

        // 1. Notify course manager(s).
        $recipients = get_enrolled_users($context, 'mod/pdfworkspace:viewreports');
        $name = 'newreport';
        $report->reportinguser = fullname($USER);
        $report->url = $CFG->wwwroot . '/mod/pdfworkspace/view.php?id=' . $cm->id . '&action=overviewreports';
        $messagetext = new stdClass();
        $modulename = format_string($cm->name, true);
        $messagetext->text = pdfworkspace_format_notification_message_text($course, $cm, $context,
            get_string('modulename', 'pdfworkspace'), $modulename, $report, 'reportadded');
        $messagetext->url = $report->url;
        try {
            foreach ($recipients as $recipient) {
                if (!\mod_pdfworkspace\visibility::can_view_comment($reportedcomment, $context, $recipient->id)) {
                    continue;
                }
                $messagetext->html = pdfworkspace_format_notification_message_html($course, $cm, $context,
                    get_string('modulename', 'pdfworkspace'), $modulename, $report, 'reportadded', $recipient->id);
                $messageid = pdfworkspace_notify_manager($recipient, $course, $cm, $name, $messagetext);
            }
            // 2. Notify the reporting user that their report has been sent off (display blue toast box at top of page).
            \core\notification::info(get_string('reportwassentoff', 'pdfworkspace'));
        } catch (Exception $ex) {
            $info = $ex->getMessage();
            \core\notification::error($info);
        }

        // 3. Save report in db.
        $record = new stdClass();
        $record->commentid = $report->commentid;
        $record->courseid = $cm->course;
        $record->pdfworkspaceid = $cm->instance;
        $record->message = $report->introduction;
        $record->userid = $USER->id;
        $record->timecreated = time();
        $record->seen = 0;

        $reportid = $DB->insert_record('pdfworkspace_reports', $record, $returnid = true, $bulk = false);
        if (empty($reportid)) {
            \core\notification::error(get_string('error:reportComment', 'pdfworkspace'));
        }

        $action = 'view';
        echo $myrenderer->pdfworkspace_render_tabs($taburl, $pdfworkspace->name, $context, $action);
        pdfworkspace_display_embed($pdfworkspace, $cm, $course, $file);
    } else { // This branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
        // or on the first display of the form.
        $PAGE->set_title("reportform");
        echo $OUTPUT->heading(get_string('titleforreportcommentform', 'pdfworkspace'));

        // Get information about the comment to be reported.
        $comment = $reportedcomment;
        $comment->content = pdfworkspace_get_relativelink($comment->content, $comment->id, $context);
        $info = pdfworkspace_comment_info::make_from_comment($comment);

        // Display it in a table.
        $myrenderer = $PAGE->get_renderer('mod_pdfworkspace');
        echo $myrenderer->render_pdfworkspace_comment_info($info);

        // Now display the complaint form itself.
        $mform->display();
    }
    return;
}
