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
 * Download the source PDF with the requesting viewer's visible annotations.
 *
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once(__DIR__ . '/locallib.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('pdfworkspace', $id, 0, false, MUST_EXIST);
$activity = $DB->get_record('pdfworkspace', ['id' => $cm->instance], '*', MUST_EXIST);
$course = get_course($cm->course);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pdfworkspace:view', $context);
if (!pdfworkspace_can_download_combined($activity, $context)) {
    throw new required_capability_exception($context, 'mod/pdfworkspace:printcomments', 'nopermissions', '');
}

$python = get_config('mod_pdfworkspace', 'exportpython');
if (!$python || !is_file($python) || !is_executable($python) || !function_exists('proc_open')) {
    throw new moodle_exception('combinedexportunavailable', 'pdfworkspace');
}

$documents = pdfworkspace_get_documents($activity->id, $context->id);
$requestedid = optional_param('doc', 0, PARAM_INT);
if (!$requestedid && count($documents) === 1) {
    $requestedid = (int)reset($documents)->id;
}
if (!$requestedid || !isset($documents[$requestedid])) {
    throw new moodle_exception('invalidaccessparameter');
}
$selecteddocument = $documents[$requestedid];
$files = get_file_storage()->get_area_files($context->id, 'mod_pdfworkspace', 'content', 0,
    'sortorder DESC, id ASC', false);
if (!$files) {
    throw new moodle_exception('filenotfound', 'pdfworkspace');
}
$source = null;
foreach ($files as $file) {
    if ($file->get_filepath() === $selecteddocument->filepath &&
            $file->get_filename() === $selecteddocument->filename &&
            $file->get_contenthash() === $selecteddocument->contenthash) {
        $source = $file;
        break;
    }
}
if (!$source) {
    throw new moodle_exception('filenotfound', 'pdfworkspace');
}
$export = \mod_pdfworkspace\pdf_export::annotations($activity, $context, $requestedid, $USER->id);
$outputpath = \mod_pdfworkspace\pdf_export::run([['file' => $source, 'annotations' => $export]]);

$name = pathinfo($source->get_filename(), PATHINFO_FILENAME) . '_annotated.pdf';
send_file($outputpath, $name, 0, 0, false, true, 'application/pdf');
