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
 * Download the teacher-selected workspace PDFs in their configured order.
 *
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once(__DIR__ . '/locallib.php');

$id = required_param('id', PARAM_INT);
$withcomments = optional_param('comments', false, PARAM_BOOL);
$cm = get_coursemodule_from_id('pdfworkspace', $id, 0, false, MUST_EXIST);
$activity = $DB->get_record('pdfworkspace', ['id' => $cm->instance], '*', MUST_EXIST);
$course = get_course($cm->course);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pdfworkspace:view', $context);
if (!pdfworkspace_can_download_workspace($activity, $context, $withcomments)) {
    $capability = $withcomments ? 'downloadworkspacecomments' : 'downloadworkspace';
    throw new required_capability_exception($context, 'mod/pdfworkspace:' . $capability, 'nopermissions', '');
}

$documents = pdfworkspace_workspace_documents($activity->id);
if (!$documents) {
    throw new moodle_exception('workspaceempty', 'pdfworkspace');
}
$sources = [];
foreach ($documents as $document) {
    $sources[] = [
        'file' => \mod_pdfworkspace\pdf_export::source($document, $context),
        'title' => $document->displayname ?: $document->filename,
        'annotations' => $withcomments ? \mod_pdfworkspace\pdf_export::annotations(
            $activity, $context, $document->id, $USER->id) : [],
    ];
}
$outputpath = \mod_pdfworkspace\pdf_export::run($sources);
$name = clean_param(strip_tags(format_string($activity->name, true, ['context' => $context])), PARAM_FILE);
$name = ($name ?: 'Workspace') . ($withcomments ? '_comments' : '') . '.pdf';
send_file($outputpath, $name, 0, 0, false, true, 'application/pdf');
