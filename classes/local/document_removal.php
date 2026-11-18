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

namespace mod_pdfworkspace\local;

/**
 * Remove one document and the Moodle data attached to its annotations.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class document_removal {
    /** Serialize PDF changes with requests that can add related records or files. */
    public static function lock(int $activityid): \core\lock\lock {
        $factory = \core\lock\lock_config::get_lock_factory('mod_pdfworkspace');
        $lock = $factory->get_lock('documents:' . $activityid, 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout');
        }
        return $lock;
    }

    /** Check a confirmation from this session against the actual activity and PDF. */
    public static function require_confirmation(\context_module $context, int $documentid, string $token): void {
        global $SESSION;
        require_capability('moodle/course:manageactivities', $context);
        require_capability('mod/pdfworkspace:deleteannotateddocument', $context);
        $expected = $SESSION->pdfworkspace_document_confirmation[$context->instanceid][$documentid] ?? '';
        if ($expected === '' || !hash_equals($expected, $token)) {
            throw new \moodle_exception('documentdeleteunconfirmed', 'pdfworkspace');
        }
    }

    /** Delete each file and record by the annotation that owns it. */
    public static function delete_discussion(\context_module $context, int $activityid, int $documentid): void {
        global $DB;

        $params = ['workspace' => $activityid, 'document' => $documentid];
        $annotations = 'SELECT id FROM {pdfworkspace_annotations}
                         WHERE pdfworkspaceid = :workspace AND documentid = :document';
        $commentwhere = "annotationid IN ($annotations)";
        $comments = "SELECT id FROM {pdfworkspace_comments} WHERE $commentwhere";
        $storage = get_file_storage();
        foreach ($DB->get_fieldset_select('pdfworkspace_comments', 'id', $commentwhere, $params) as $commentid) {
            $storage->delete_area_files($context->id, 'mod_pdfworkspace', 'post', $commentid);
        }
        $DB->delete_records_select('pdfworkspace_reports', "commentid IN ($comments)", $params);
        $DB->delete_records_select('pdfworkspace_votes', "commentid IN ($comments)", $params);
        $DB->delete_records_select('pdfworkspace_comments', $commentwhere, $params);
        $DB->delete_records_select('pdfworkspace_subscriptions', "annotationid IN ($annotations)", $params);
        $DB->delete_records('pdfworkspace_annotations', ['pdfworkspaceid' => $activityid, 'documentid' => $documentid]);
    }
}
