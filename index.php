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
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen (see README.md)
 * @author    Ahmad Obeid
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//require_once('../../config.php');
require_once('locallib.php');

$id = required_param('id', PARAM_INT);           // Course ID.

// Ensure that the course specified is valid.
if (!$course = $DB->get_record('course', array('id' => $id))) {
    throw new moodle_exception('Course ID is incorrect');
}
// $course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

$params = array(
    'context' => context_course::instance($course->id),
);
$context = $params['context'];
$event = \mod_pdfworkspace\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strpdfworkspace     = get_string('modulename', 'pdfworkspace');
$strpdfworkspaces    = get_string('modulenameplural', 'pdfworkspace');
$strsectionname  = get_string('sectionname', 'format_'.$course->format);
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');
$strsubscribe    = get_string('subscribe', 'pdfworkspace');
$strunsubscribe  = get_string('unsubscribe', 'pdfworkspace');
$strsubscribed   = get_string('subscribed', 'pdfworkspace');
$stryes          = get_string('yes');
$strno           = get_string('no');

$PAGE->set_url('/mod/pdfworkspace/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strpdfworkspaces);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strpdfworkspaces);
echo $OUTPUT->header();
echo $OUTPUT->heading($strpdfworkspaces);

if (!$pdfworkspaces = get_all_instances_in_course('pdfworkspace', $course)) {
    notice(get_string('thereareno', 'moodle', $strpdfworkspaces), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head  = array ($strsectionname, $strname, $strlastmodified, $strintro);
    $table->align = array ('center', '', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);

$currentsection = '';

foreach ($pdfworkspaces as $pdfworkspace) {
    $cm = $modinfo->cms[$pdfworkspace->coursemodule];
    $infor = pdfworkspace_get_number_of_new_activities($pdfworkspace->id);
    if ($usesections) {
        $printsection = '';
        if ($pdfworkspace->section !== $currentsection) {
            if ($pdfworkspace->section) {
                $printsection = get_section_name($course, $pdfworkspace->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $pdfworkspace->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($pdfworkspace->timemodified)."</span>";
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon = '<img src="'.$cm->get_icon_url().'" class="activityicon" alt="'.$cm->get_module_type_name().'" /> ';
    $visible = $pdfworkspace->visible;
    $class = $visible ? '' : 'class="dimmed"'; // Hidden modules are dimmed.
    $newinfo = " ";
    $actions = array();
    // Settings if user have capabilty.
    $manageurl = new \moodle_url('/course/mod.php', array('sesskey' => \sesskey()));
    // Edit.
    $hascapability = has_capability('mod/pdfworkspace:administrateuserinput', $context);
    if ($hascapability) {
        $actions['edit'] = array(
            'url' => new \moodle_url('/course/modedit.php', array('update' => $cm->id)),
            'icon' => new \pix_icon('t/edit', new \lang_string('edit')),
            'string' => new \lang_string('edit'),
        );
        // Show/Hide.
        if ($visible) {
            $actions['hide'] = array(
                'url' => new \moodle_url($manageurl, array('hide' => $cm->id)),
                'icon' => new \pix_icon('t/hide', new \lang_string('hide')),
                'string' => new \lang_string('hide'),
            );
        } else {
            $actions['show'] = array(
                'url' => new \moodle_url($manageurl, array('show' => $cm->id)),
                'icon' => new \pix_icon('t/show', new \lang_string('show')),
                'string' => new \lang_string('show'),
            );
        }
    }
    $setting = pdfworkspace_render_listitem_actions($actions);
    $lastmodified = pdfworkspace_get_datetime_of_last_modification($pdfworkspace->id);
    if ($infor > 0) {
        $newinfo = "<img src=\"pix/new.png\">($infor)</img>";
    } else if ($lastmodified >= strtotime("-1 day")) {
            $newinfo = "<img src=\"pix/new.gif\"></img>";
    }
    $table->data[] = array (
        $printsection,
        "<div style=\"float:left\"><a $class $extra href=\"view.php?id=$cm->id\">".$icon.format_string($pdfworkspace->name)
        .$newinfo."</a></div><div style=\"float:right\">".$setting."</div>" , userdate($lastmodified),
        format_module_intro('pdfworkspace', $pdfworkspace, $cm->id));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
