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
 * Compatibility links and capability-protected original downloads.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_pdfworkspace\local\restricted_documents;

require('../../config.php');
require_once($CFG->libdir . '/filelib.php');

$id = required_param('id', PARAM_INT);
$documentid = optional_param('document', 0, PARAM_INT);
$page = max(1, optional_param('page', 1, PARAM_INT));
$action = optional_param('action', 'view', PARAM_ALPHA);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'pdfworkspace');
require_course_login($course, true, $cm);
$context = context_module::instance($id);
require_capability('mod/pdfworkspace:view', $context);

if ($action === 'download') {
    send_stored_file(restricted_documents::original($context, $documentid), 0, 0, true);
    exit;
}
if (in_array($action, ['add', 'remove'], true)) {
    require_capability(restricted_documents::MANAGE, $context);
    require_capability('moodle/course:manageactivities', $context);
    redirect(new moodle_url('/course/modedit.php', ['update' => $id]));
}
if ($action !== 'view') {
    throw new moodle_exception('invalidaccessparameter');
}
$where = 'pdfworkspaceid = ? AND restrictedid IS NOT NULL';
$params = [$cm->instance];
if ($documentid) {
    $where .= ' AND restrictedid = ?';
    $params[] = $documentid;
}
$documents = $DB->get_records_select('pdfworkspace_documents', $where, $params, 'sortorder ASC, id ASC', 'id', 0, 1);
$url = new moodle_url('/mod/pdfworkspace/view.php', ['id' => $id]);
if ($documents) {
    $url->params(['doc' => reset($documents)->id, 'page' => $page]);
} else if ($documentid) {
    throw new moodle_exception('invaliddocument', 'pdfworkspace', $url);
}
redirect($url);
