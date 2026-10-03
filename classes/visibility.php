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
 * Central audience rules for annotations and their comment threads.
 *
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace;

defined('MOODLE_INTERNAL') || die();

class visibility {

    /** Audience bits in the activity settings. */
    public const AUDIENCE_PRIVATE = 1;
    public const AUDIENCE_SCOPED = 2;
    public const AUDIENCE_PUBLIC = 4;

    public static function is_staff($context, $userid) {
        return has_capability('mod/pdfworkspace:viewprotectedcomments', $context, $userid);
    }

    /**
     * Return the audiences this activity permits for the current role.
     * Missing settings retain the earlier TN–Dozent policy.
     *
     * @param object $activity
     * @param bool $staff
     * @return string[]
     */
    public static function allowed_audiences($activity, $staff) {
        $field = $staff ? 'staffaudiences' : 'studentaudiences';
        $mask = isset($activity->$field) ? (int)$activity->$field : 3;
        $audiences = [];
        if (!$staff && ($mask & self::AUDIENCE_SCOPED)) {
            $audiences[] = 'protected';
        }
        if ($mask & self::AUDIENCE_PRIVATE) {
            $audiences[] = 'private';
        }
        if ($staff && ($mask & self::AUDIENCE_SCOPED)) {
            $audiences[] = 'targeted';
        }
        if ($mask & self::AUDIENCE_PUBLIC) {
            $audiences[] = 'public';
        }
        return $audiences;
    }

    /** Legacy question visibility follows its annotation's audience. */
    public static function thread_visibility($audience) {
        if ($audience === 'public' || $audience === 'private') {
            return $audience;
        }
        return 'protected';
    }

    /**
     * Decide the audience on the server, ignoring choices a participant may forge.
     *
     * @return array [audience, recipientid]
     */
    public static function new_annotation_audience($activity, $context, $userid, $requested, $recipientid) {
        $staff = self::is_staff($context, $userid);
        $allowed = self::allowed_audiences($activity, $staff);
        if (!$allowed) {
            throw new \invalid_parameter_exception(get_string('error:annotationaudience', 'pdfworkspace'));
        }
        if ($requested === '') {
            $field = $staff ? 'staffdefaultaudience' : 'studentdefaultaudience';
            $requested = isset($activity->$field) && in_array($activity->$field, $allowed, true) ?
                $activity->$field : $allowed[0];
        }
        if (!in_array($requested, $allowed, true)) {
            throw new \invalid_parameter_exception(get_string('error:annotationaudience', 'pdfworkspace'));
        }
        if ($requested !== 'targeted') {
            return [$requested, null];
        }

        $recipientid = (int)$recipientid;
        if ($recipientid <= 0 || $recipientid === (int)$userid || !is_enrolled($context, $recipientid) ||
                !has_capability('mod/pdfworkspace:view', $context, $recipientid) ||
                self::is_staff($context, $recipientid)) {
            throw new \invalid_parameter_exception(get_string('error:annotationrecipient', 'pdfworkspace'));
        }
        return ['targeted', $recipientid];
    }

    public static function can_view_annotation($annotation, $context, $userid) {
        global $DB;

        if (!self::can_view_audience($annotation, $context, $userid)) {
            return false;
        }
        $question = $DB->get_record('pdfworkspace_comments', ['annotationid' => $annotation->id, 'isquestion' => 1]);
        return !$question || self::can_view_question($question, $context, $userid);
    }

    public static function can_view_comment($comment, $context, $userid) {
        global $DB;

        $question = !empty($comment->isquestion) ? $comment :
            $DB->get_record('pdfworkspace_comments', ['annotationid' => $comment->annotationid, 'isquestion' => 1]);
        if (!$question || !self::can_view_question($question, $context, $userid)) {
            return false;
        }
        $annotation = $DB->get_record('pdfworkspace_annotations', ['id' => $comment->annotationid]);
        if (!$annotation) {
            return false;
        }
        // Older conversations can have per-comment restrictions even when the
        // annotation was public. Preserve those restrictions on existing data.
        if (!empty($comment->isquestion) || (isset($annotation->audience) && $annotation->audience !== 'public')) {
            return true;
        }
        if ($comment->visibility === 'private') {
            return (int)$comment->userid === (int)$userid;
        }
        if ($comment->visibility === 'protected') {
            return (int)$comment->userid === (int)$userid || self::is_staff($context, $userid);
        }
        return in_array($comment->visibility, ['public', 'anonymous', 'deleted'], true);
    }

    public static function can_manage_report($reportid, $activityid, $userid) {
        global $DB;

        $report = $DB->get_record('pdfworkspace_reports',
            ['id' => $reportid, 'pdfworkspaceid' => $activityid]);
        if (!$report) {
            return false;
        }
        $activity = $DB->get_record('pdfworkspace', ['id' => $activityid], 'id,course');
        if (!$activity) {
            return false;
        }
        $cm = get_coursemodule_from_instance('pdfworkspace', $activityid, $activity->course);
        if (!$cm) {
            return false;
        }
        $context = \context_module::instance($cm->id);
        $comment = $DB->get_record('pdfworkspace_comments',
            ['id' => $report->commentid, 'pdfworkspaceid' => $report->pdfworkspaceid]);
        return $comment && has_capability('mod/pdfworkspace:viewreports', $context, $userid) &&
            self::can_view_comment($comment, $context, $userid);
    }

    private static function can_view_question($question, $context, $userid) {
        global $DB;

        $annotation = $DB->get_record('pdfworkspace_annotations', ['id' => $question->annotationid]);
        if (!$annotation || !self::can_view_audience($annotation, $context, $userid)) {
            return false;
        }
        if ($question->visibility === 'private') {
            return (int)$question->userid === (int)$userid;
        }
        if ($question->visibility === 'protected') {
            return (int)$question->userid === (int)$userid || self::is_staff($context, $userid) ||
                (isset($annotation->audience) && $annotation->audience === 'targeted' &&
                    (int)$annotation->recipientid === (int)$userid);
        }
        return in_array($question->visibility, ['public', 'anonymous', 'deleted'], true);
    }

    private static function can_view_audience($annotation, $context, $userid) {
        if (!has_capability('mod/pdfworkspace:view', $context, $userid)) {
            return false;
        }
        $audience = isset($annotation->audience) ? $annotation->audience : 'public';
        switch ($audience) {
            case 'public':
                return true;
            case 'private':
                return (int)$annotation->userid === (int)$userid;
            case 'protected':
                return (int)$annotation->userid === (int)$userid || self::is_staff($context, $userid);
            case 'targeted':
                return (int)$annotation->userid === (int)$userid ||
                    (int)$annotation->recipientid === (int)$userid;
            default:
                return false;
        }
    }
}
