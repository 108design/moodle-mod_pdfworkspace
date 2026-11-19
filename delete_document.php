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
 * Record confirmation for removal of a PDF on the next activity settings save.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

$cmid = required_param('id', PARAM_INT);
$documentid = required_param('document', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'pdfworkspace');
require_login($course, true, $cm);
$context = context_module::instance($cmid);
require_capability('moodle/course:manageactivities', $context);
require_capability('mod/pdfworkspace:deleteannotateddocument', $context);
if (!data_submitted()) {
    throw new moodle_exception('invalidrequest');
}
require_sesskey();
$document = $DB->get_record('pdfworkspace_documents', ['id' => $documentid, 'pdfworkspaceid' => $cm->instance],
    'id,restrictedid');
if ($document && $document->restrictedid) {
    require_capability(\mod_pdfworkspace\local\restricted_documents::MANAGE, $context);
}
if (!$document || !$DB->record_exists('pdfworkspace_annotations', ['pdfworkspaceid' => $cm->instance, 'documentid' => $documentid])) {
    throw new moodle_exception('invaliddocument', 'pdfworkspace');
}
$confirmation = random_string(32);
$SESSION->pdfworkspace_document_confirmation[$cmid][$documentid] = $confirmation;
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['confirmation' => $confirmation], JSON_THROW_ON_ERROR);
