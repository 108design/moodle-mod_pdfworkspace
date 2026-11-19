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
 * Drawings on server-rendered restricted document pages.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace\local;

use mod_pdfworkspace\visibility;

/** Bind each stroke to a restricted document, page, author and Moodle audience. */
final class restricted_markings {
    /** @var string Storage for strokes, not shared with exportable PDF annotations. */
    public const TABLE = 'pdfworkspace_rstr_marks';
    /** @var string Comments are kept in the restricted document's own storage path. */
    public const COMMENTS = 'pdfworkspace_rstr_comments';
    /** @var int The normalized width and height of the source image. */
    private const EXTENT = 10000;
    /** @var int Maximum number of points in one stroke. */
    private const MAX_POINTS = 300;

    /**
     * Apply the Workspace's existing drawing setting and capability.
     *
     * @param \stdClass $activity Workspace record
     * @param \context_module $context Workspace context
     * @return bool
     */
    public static function can_draw(\stdClass $activity, \context_module $context): bool {
        return self::can_mark($context)
            && (!empty($activity->use_studentdrawing) || has_capability('mod/pdfworkspace:usedrawing', $context));
    }

    /** Check the common permission behind pins, frames and drawing tools. */
    private static function can_mark(\context_module $context): bool {
        return !isguestuser() && has_capability('mod/pdfworkspace:view', $context)
            && has_capability('mod/pdfworkspace:create', $context);
    }

    /** Apply the existing text box setting and capability. */
    private static function can_add_text(\stdClass $activity, \context_module $context): bool {
        return self::can_mark($context)
            && (!empty($activity->use_studenttextbox) || has_capability('mod/pdfworkspace:usetextbox', $context));
    }

    /**
     * Build the authorised SVG data and, for writers, the activity's audience options.
     *
     * @param \stdClass $activity Workspace record
     * @param \context_module $context Workspace context
     * @param int $document Restricted document ID from the current workspace
     * @param int $page One-based page number
     * @return array
     */
    public static function view_data(\stdClass $activity, \context_module $context, int $document, int $page): array {
        global $DB, $USER;
        $marks = [];
        $own = false;
        foreach ($DB->get_records(self::TABLE, ['documentid' => $document, 'page' => $page], 'id ASC') as $mark) {
            if (!visibility::can_view_audience($mark, $context, $USER->id)) {
                continue;
            }
            $owned = (int)$mark->userid === (int)$USER->id && !isguestuser();
            $marks[] = [
                'id' => (int)$mark->id, 'own' => $owned, 'tool' => $mark->tool,
                'points' => json_decode($mark->points), 'label' => $mark->label,
            ];
            $own = $own || $owned;
        }
        $data = ['markings' => json_encode($marks, JSON_THROW_ON_ERROR), 'marktoolbar' => $own, 'ownmarkings' => $own,
            'cancomment' => self::can_mark($context)];
        if (!self::can_mark($context)) {
            return $data;
        }
        $staff = visibility::is_staff($context, $USER->id);
        $allowed = visibility::allowed_audiences($activity, $staff);
        $recipients = [];
        if (in_array('targeted', $allowed, true)) {
            $fields = 'u.id, u.' . implode(', u.', \core_user\fields::get_name_fields());
            foreach (get_enrolled_users($context, 'mod/pdfworkspace:view', 0, $fields,
                    'u.lastname, u.firstname', 0, 0, true) as $user) {
                if ((int)$user->id !== (int)$USER->id && !visibility::is_staff($context, $user->id)) {
                    $recipients[] = ['id' => $user->id, 'name' => fullname($user)];
                }
            }
            if (!$recipients) {
                $allowed = array_values(array_diff($allowed, ['targeted']));
            }
        }
        if (!$allowed) {
            return $data;
        }
        $role = $staff ? 'staff' : 'student';
        $default = $activity->{$role . 'defaultaudience'} ?? '';
        $default = in_array($default, $allowed, true) ? $default : reset($allowed);
        $audiences = [];
        foreach ($allowed as $audience) {
            $audiences[] = [
                'value' => $audience, 'selected' => $audience === $default,
                'label' => get_string('audience_' . $role . '_' . $audience, 'mod_pdfworkspace'),
            ];
        }
        $data['marktoolbar'] = true;
        return $data + [
            'canmark' => true, 'candraw' => self::can_draw($activity, $context), 'cantext' => self::can_add_text($activity, $context),
            'audiences' => $audiences, 'recipients' => $recipients, 'hasrecipients' => !empty($recipients),
            'targeted' => $default === 'targeted',
        ];
    }

    /**
     * Validate and persist one new stroke, or remove an owned stroke.
     *
     * @param \stdClass $activity Workspace record
     * @param \context_module $context Workspace context
     * @param int $documentid Restricted document ID
     * @param int $page One-based page
     * @param string $tool Drawing tool, or empty when removing
     * @param string $points JSON with pairs of integer coordinates
     * @param string $audience Workspace audience
     * @param int $recipient Target user for a targeted stroke
     * @param int $removeid Owned stroke to remove, or zero to add
     * @param string $label Optional text box content
     * @return int The affected stroke ID
     */
    public static function save(\stdClass $activity, \context_module $context, int $documentid, int $page, string $tool,
            string $points, string $audience, int $recipient, int $removeid, string $label = ''): int {
        global $DB, $USER;
        require_capability('mod/pdfworkspace:view', $context);
        if (isguestuser() || $removeid < 0) {
            throw new \invalid_parameter_exception('Invalid marking action');
        }
        // Share the lock with document removal so drawing cannot leave an orphan.
        $lock = restricted_documents::lock($documentid);
        try {
            $document = $DB->get_record(restricted_documents::TABLE,
                ['id' => $documentid, 'pdfworkspaceid' => $activity->id], 'id,pagecount', MUST_EXIST);
            if ($page < 1 || $page > $document->pagecount) {
                throw new \invalid_parameter_exception('Invalid restricted page');
            }
            $scope = ['documentid' => $document->id, 'page' => $page, 'userid' => $USER->id];
            if ($removeid) {
                $record = $DB->get_record(self::TABLE, $scope + ['id' => $removeid], 'id', MUST_EXIST);
                $transaction = $DB->start_delegated_transaction();
                $DB->delete_records(self::COMMENTS, ['markid' => $record->id]);
                $DB->delete_records(self::TABLE, ['id' => $record->id]);
                $transaction->allow_commit();
                return (int)$record->id;
            }
            require_capability('mod/pdfworkspace:create', $context);
            if ((!self::can_draw($activity, $context) && in_array($tool, ['pen', 'marker'], true))
                    || ($tool === 'text' && !self::can_add_text($activity, $context))) {
                throw new \moodle_exception('restrictedmarkforbidden', 'mod_pdfworkspace');
            }
            if (!in_array($tool, ['pen', 'marker', 'point', 'frame', 'text'], true) || strlen($points) > 6000) {
                throw new \invalid_parameter_exception('Invalid marking data');
            }
            $coordinates = json_decode($points, true, 6);
            if (!is_array($coordinates) || !array_is_list($coordinates)
                    || count($coordinates) < 2 || count($coordinates) > self::MAX_POINTS) {
                throw new \invalid_parameter_exception('Invalid marking coordinates');
            }
            foreach ($coordinates as $point) {
                if (!is_array($point) || !array_is_list($point) || count($point) !== 2
                        || !is_int($point[0]) || !is_int($point[1])
                        || min($point) < 0 || max($point) > self::EXTENT) {
                    throw new \invalid_parameter_exception('Invalid marking point');
                }
            }
            if (in_array($tool, ['point', 'frame', 'text'], true) && count($coordinates) !== 2) {
                throw new \invalid_parameter_exception('Invalid marking geometry');
            }
            $label = trim(clean_param($label, PARAM_TEXT));
            if ($tool === 'text' && ($label === '' || \core_text::strlen($label) > 160)) {
                throw new \moodle_exception('restrictedmarkinvalidtext', 'mod_pdfworkspace');
            }
            // Keep mobile response size and per-user storage bounded on any one page.
            if ($DB->count_records(self::TABLE, $scope) >= 200) {
                throw new \moodle_exception('restrictedmarklimit', 'mod_pdfworkspace');
            }
            [$approved, $recipientid] = visibility::new_annotation_audience($activity, $context, $USER->id, $audience, $recipient);
            return $DB->insert_record(self::TABLE, $scope + [
                'tool' => $tool, 'points' => json_encode($coordinates, JSON_THROW_ON_ERROR),
                'audience' => $approved, 'recipientid' => $recipientid, 'timecreated' => time(),
                'label' => $tool === 'text' ? $label : null,
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Read or comment on a marking whose audience includes the current user.
     *
     * @param \stdClass $activity Workspace record
     * @param \context_module $context Activity context
     * @param int $documentid Restricted document
     * @param int $page Page number
     * @param int $markid Marking ID
     * @param string $comment New text, or empty when listing
     * @param int $removecommentid Own comment to remove, or zero
     * @return array Plain comment records for the current audience
     */
    public static function thread(\stdClass $activity, \context_module $context, int $documentid, int $page, int $markid,
            string $comment, int $removecommentid): array {
        global $DB, $USER;
        require_capability('mod/pdfworkspace:view', $context);
        $lock = restricted_documents::lock($documentid);
        try {
            $DB->get_record(restricted_documents::TABLE, ['id' => $documentid, 'pdfworkspaceid' => $activity->id], 'id', MUST_EXIST);
            $mark = $DB->get_record(self::TABLE, ['id' => $markid, 'documentid' => $documentid, 'page' => $page], '*', MUST_EXIST);
            if (!visibility::can_view_audience($mark, $context, $USER->id)) {
                throw new \moodle_exception('nopermissions', 'error', '', get_string('restrictedmarkthread', 'mod_pdfworkspace'));
            }
            if ($removecommentid) {
                $DB->delete_records(self::COMMENTS, ['id' => $removecommentid, 'markid' => $markid, 'userid' => $USER->id]);
            } else if ($comment !== '') {
                if (!self::can_mark($context)) {
                    throw new \moodle_exception('restrictedmarkforbidden', 'mod_pdfworkspace');
                }
                if (trim($comment) === '' || \core_text::strlen($comment) > 2000) {
                    throw new \moodle_exception('restrictedmarkinvalidcomment', 'mod_pdfworkspace');
                }
                $DB->insert_record(self::COMMENTS, [
                    'markid' => $markid, 'userid' => $USER->id, 'content' => trim($comment), 'timecreated' => time(),
                ]);
            }
            $comments = [];
            $namefields = 'u.' . implode(', u.', \core_user\fields::get_name_fields());
            $records = $DB->get_records_sql("SELECT c.id, c.userid, c.content, c.timecreated, $namefields
                FROM {pdfworkspace_rstr_comments} c JOIN {user} u ON u.id = c.userid
                WHERE c.markid = :markid ORDER BY c.id", ['markid' => $markid]);
            foreach ($records as $entry) {
                $comments[] = [
                    'id' => $entry->id, 'author' => fullname($entry), 'content' => $entry->content,
                    'own' => (int)$entry->userid === (int)$USER->id, 'date' => userdate($entry->timecreated),
                ];
            }
            return $comments;
        } finally {
            $lock->release();
        }
    }

    /**
     * Delete drawings and comments for a removed document.
     *
     * @param int $documentid Restricted document ID
     */
    public static function delete_document(int $documentid): void {
        global $DB;
        $DB->delete_records_select(self::COMMENTS, 'markid IN (SELECT id FROM {pdfworkspace_rstr_marks} WHERE documentid = ?)',
            [$documentid]);
        $DB->delete_records(self::TABLE, ['documentid' => $documentid]);
    }
}
