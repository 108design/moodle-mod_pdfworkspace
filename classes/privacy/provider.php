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
 * Privacy class for requesting user data.
 *
 * @package   mod_pdfworkspace
 * @category  privacy
 * @copyright 2018 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @author    Anna Heynkes
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;

/**
 * Description of provider
 *
 * @author Admin
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * This function implements the \core_privacy\local\metadata\provider interface.
     *
     * It describes what kind of data is stored by the pdfworkspace, including:
     *
     * 1. Items stored in a Moodle subsystem - for example files, and ratings
     * 2. Items stored in the Moodle database
     * 3. User preferences stored site-wide within Moodle for the pdfworkspace
     * 4. Data being exported to an external location
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {

        // 1. Indicating that you store content in a Moodle subsystem.
        // 1.1 Files uploaded by users are saved.
        $collection->add_subsystem_link(
                'core_files', [], 'privacy:metadata:core_files'
        );

        // 2. Describing data stored in database tables.
        // 2.1 A user's annotations in the pdf are stored.
        $collection->add_database_table(
                'pdfworkspace_annotations', [
            'userid' => 'privacy:metadata:pdfworkspace_annotations:userid',
            'recipientid' => 'privacy:metadata:pdfworkspace_annotations:recipientid',
            'documentid' => 'privacy:metadata:pdfworkspace_annotations:documentid',
            'id' => 'privacy:metadata:pdfworkspace_annotations:annotationid',
                ], 'privacy:metadata:pdfworkspace_annotations'
        );
        // 2.2 A user's comments are stored.
        $collection->add_database_table(
                'pdfworkspace_comments', [
            'userid' => 'privacy:metadata:pdfworkspace_comments:userid',
            'annotationid' => 'privacy:metadata:pdfworkspace_comments:annotationid',
            'content' => 'privacy:metadata:pdfworkspace_comments:content',
                ], 'privacy:metadata:pdfworkspace_comments'
        );
        // 2.3 Users can report other users' comments as inappropriate. These reports stored.
        $collection->add_database_table(
                'pdfworkspace_reports', [
            'commentid' => 'privacy:metadata:pdfworkspace_reports:commentid',
            'message' => 'privacy:metadata:pdfworkspace_reports:message',
            'userid' => 'privacy:metadata:pdfworkspace_reports:userid',
                ], 'privacy:metadata:pdfworkspace_reports'
        );
        // 2.4 A user's subscriptions are stored.
        $collection->add_database_table(
                'pdfworkspace_subscriptions', [
            'annotationid' => 'privacy:metadata:pdfworkspace_subscriptions:annotationid',
            'userid' => 'privacy:metadata:pdfworkspace_subscriptions:userid',
                ], 'privacy:metadata:pdfworkspace_subscriptions'
        );
        // 2.5 Votes are stored.
        $collection->add_database_table(
                'pdfworkspace_votes', [
            'commentid' => 'privacy:metadata:pdfworkspace_votes:commentid',
            'userid' => 'privacy:metadata:pdfworkspace_votes:userid',
                ], 'privacy:metadata:pdfworkspace_votes'
        );

        // 3. There are no site-wide user preferences stored at present.
        // 4. No data is exported to an external location at present.

        return $collection;
    }

    /**
     * This function implements the core_privacy\local\request\plugin\provider interface.
     * It retursn a list of contexts that contain user information for the specified user.
     *
     * @param   int           $userid       The user to search.
     * @return  contextlist   $contextlist  The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {

        $contextlist = new \core_privacy\local\request\contextlist();

        $params = [
            'modname' => 'pdfworkspace',
            'contextlevel' => CONTEXT_MODULE,
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
            'userid5' => $userid,
            'userid6' => $userid,
            'userid7' => $userid,
        ];

        $sql = "SELECT DISTINCT c.id
                FROM {context} c
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                INNER JOIN {pdfworkspace} p ON p.id = cm.instance
                LEFT JOIN {pdfworkspace_annotations} a ON a.pdfworkspaceid = p.id
                LEFT JOIN {pdfworkspace_subscriptions} s ON s.annotationid = a.id
                LEFT JOIN {pdfworkspace_comments} k ON k.annotationid = a.id
                LEFT JOIN {pdfworkspace_reports} r ON r.commentid = k.id
                LEFT JOIN {pdfworkspace_votes} v ON v.commentid = k.id
                    WHERE (
                    a.userid        = :userid1 OR
                    s.userid        = :userid2 OR
                    k.userid        = :userid3 OR
                    r.userid        = :userid5 OR
                    v.userid        = :userid6
                    OR a.recipientid = :userid7
                    )
                ";

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {

        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/pdfworkspace/locallib.php');

        if (empty($contextlist)) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT
                    c.id AS contextid,
                    cm.id AS cmid,
                    p.id AS id,
                    p.name AS pdfworkspacename
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {pdfworkspace} p ON p.id = cm.instance
                  WHERE (
                       c.id {$contextsql}
                   )
        ";

        // Keep a mapping of pdfworkspaceid to contextid.
        $mappings = [];

        $pdfworkspaces = $DB->get_recordset_sql($sql, $contextparams);
        foreach ($pdfworkspaces as $pdfworkspace) {

            $mappings[$pdfworkspace->id] = $pdfworkspace->contextid;

            $context = \context::instance_by_id($mappings[$pdfworkspace->id]);

            // Get all questions asked by this user.
            $sql1 = "SELECT c.content, c.timecreated, c.visibility, d.filename AS documentname
                    FROM {pdfworkspace_comments} c
                    JOIN {pdfworkspace_annotations} a ON a.id = c.annotationid
                    LEFT JOIN {pdfworkspace_documents} d ON d.id = a.documentid
                    WHERE c.isquestion = 1 AND c.userid = :userid AND c.pdfworkspaceid = :pdfworkspace";
            $myquestions = $DB->get_records_sql($sql1, array('userid' => $userid, 'pdfworkspace' => $pdfworkspace->id));

            foreach ($myquestions as $myquestion) {
                $myquestion->timecreated = pdfworkspace_get_user_datetime($myquestion->timecreated);
            }

            // Get all non-question comments by this user, together with the question.
            $sql2 = "SELECT c.content, c.timecreated, c.visibility, q.content as questioncontent,
                    d.filename AS documentname "
                    . "FROM {pdfworkspace_comments} c "
                    . "JOIN {pdfworkspace_annotations} a ON c.annotationid = a.id "
                    . "JOIN {pdfworkspace_comments} q ON q.annotationid = c.annotationid "
                    . "LEFT JOIN {pdfworkspace_documents} d ON d.id = a.documentid "
                    . "WHERE q.isquestion = :question AND c.isquestion = :normalcomment AND c.userid = :userid AND a.pdfworkspaceid = :pdfworkspace";
            $mycomments = $DB->get_records_sql($sql2, array('question' => 1, 'normalcomment' => 0, 'userid' => $userid, 'pdfworkspace' => $pdfworkspace->id));

            foreach ($mycomments as $mycomment) {
                $mycomment->timecreated = pdfworkspace_get_user_datetime($mycomment->timecreated);
            }

            // Get all subscriptions of this user (exluding their own questions which they're automatically subscribed to).
            $sql3 = "SELECT c.content
                    FROM {pdfworkspace_subscriptions} s JOIN {pdfworkspace_annotations} a ON s.annotationid = a.id JOIN {pdfworkspace_comments} c ON c.annotationid = a.id
                    WHERE c.isquestion = 1 AND s.userid = :userid AND a.pdfworkspaceid = :pdfworkspace AND NOT a.userid = :u";
            $mysubscriptions = $DB->get_records_sql($sql3, array('userid' => $userid, 'pdfworkspace' => $pdfworkspace->id, 'u' => $userid));

            // Get all comments this user voted for in this annotator.
            $sql4 = "SELECT c.content
                    FROM {pdfworkspace_comments} c JOIN {pdfworkspace_votes} v on v.commentid = c.id
                    WHERE v.userid = :userid AND c.pdfworkspaceid = :pdfworkspace";
            $myvotes = $DB->get_records_sql($sql4, array('userid' => $userid, 'pdfworkspace' => $pdfworkspace->id));

            // Get all reports this user wrote.
            $sql6 = "SELECT r.message
                    FROM {pdfworkspace_reports} r JOIN {pdfworkspace_comments} c ON c.id = r.commentid
                    WHERE r.userid = :userid AND r.pdfworkspaceid = :pdfworkspace";
            $myreportmessages = $DB->get_records_sql($sql6, array('userid' => $userid, 'pdfworkspace' => $pdfworkspace->id));

            // Get all drawings and textboxes this user made in this annotator.
            $sql7 = "SELECT a.data, a.timecreated, d.filename AS documentname
                    FROM {pdfworkspace_annotations} a JOIN {pdfworkspace_annotationtypes} t ON a.annotationtypeid = t.id
                    LEFT JOIN {pdfworkspace_documents} d ON d.id = a.documentid
                    WHERE t.name IN (:type1, :type2) AND a.userid = :userid AND a.pdfworkspaceid = :pdfworkspace";
            $mydrawingsandtextboxes = $DB->get_records_sql($sql7, array('type1' => 'drawing', 'type2' => 'textbox', 'userid' => $userid, 'pdfworkspace' => $pdfworkspace->id));

            $mytargetedannotations = $DB->get_records('pdfworkspace_annotations',
                ['pdfworkspaceid' => $pdfworkspace->id, 'recipientid' => $userid], '',
                'id, documentid, page, userid, timecreated');

            foreach ($mydrawingsandtextboxes as $mydrawingortextbox) {
                $mydrawingortextbox->timecreated = pdfworkspace_get_user_datetime($mydrawingortextbox->timecreated);
            }

            $pdfworkspace->myquestions = $myquestions;
            $pdfworkspace->mycomments = $mycomments;
            $pdfworkspace->mysubscriptions = $mysubscriptions;
            $pdfworkspace->myvotes = $myvotes;
            $pdfworkspace->myreportmessages = $myreportmessages;
            $pdfworkspace->mydrawingsandtextboxes = $mydrawingsandtextboxes;
            $pdfworkspace->mytargetedannotations = $mytargetedannotations;

            writer::with_context($context)->export_data([], $pdfworkspace);
        }
        $pdfworkspaces->close();
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $instanceid = $context->instanceid;

        $cm = get_coursemodule_from_id('pdfworkspace', $instanceid);
        if (!$cm) {
            return;
        }

        // 1. Delete all reports of comments in this annotator.
        $DB->delete_records('pdfworkspace_reports', ['pdfworkspaceid' => $instanceid]);

        // 2. Delete all votes in this annotator.
        $sql = "SELECT v.id FROM {pdfworkspace_votes} v WHERE v.commentid IN "
                . "(SELECT c.id FROM {pdfworkspace_comments} c "
                . "JOIN {pdfworkspace_annotations} a ON c.annotationid = a.id WHERE a.pdfworkspaceid = ?)";
        $votes = $DB->get_records_sql($sql, array($instanceid));
        foreach ($votes as $vote) {
            $DB->delete_records('pdfworkspace_votes', array("id" => $vote->id));
        }

        // 3. Delete all subscriptions in this annotator.
        $sql = "SELECT s.id FROM {pdfworkspace_subscriptions} s "
                . "WHERE s.annotationid IN (SELECT a.id FROM {pdfworkspace_annotations} a WHERE a.pdfworkspaceid = ?)";
        $subscriptions = $DB->get_records_sql($sql, array($instanceid));
        foreach ($subscriptions as $subscription) {
            $DB->delete_records('pdfworkspace_subscriptions', array("id" => $subscription->id));
        }

        // 4. Delete all comments in this annotator.
        $sql = "SELECT c.id FROM {pdfworkspace_comments} c WHERE c.annotationid IN (SELECT a.id FROM {pdfworkspace_annotations} a WHERE a.pdfworkspaceid = ?)";
        $comments = $DB->get_records_sql($sql, array($instanceid));
        foreach ($comments as $comment) {
            $DB->delete_records('pdfworkspace_comments', array("id" => $comment->id));
        }

        // 5. Delete all annotations in this annotator.
        $annotations = $DB->get_fieldset_select('pdfworkspace_annotations', 'id', "pdfworkspaceid = ?", array($instanceid));
        foreach ($annotations as $annotationid) {
            $DB->delete_records('pdfworkspace_annotations', array("id" => $annotationid));
        }
    }

    /**
     *
     * Delete personal data for the user in a list of contexts.
     *
     * @param \mod_pdfworkspace\privacy\approved_contextlist $contextlist List of contexts to delete data from.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {

        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {

            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
            $DB->set_field('pdfworkspace_annotations', 'audience', 'private',
                ['pdfworkspaceid' => $instanceid, 'recipientid' => $userid]);
            $DB->set_field('pdfworkspace_annotations', 'recipientid', null,
                ['pdfworkspaceid' => $instanceid, 'recipientid' => $userid]);

            // 1. Delete all reports this user made in this annotator.
            $DB->delete_records(
                'pdfworkspace_reports',
                ['pdfworkspaceid' => $instanceid, 'userid' => $userid]
            );

            // 2. Delete all votes this user made in this annotator.
            $sql = "SELECT v.id
                    FROM {pdfworkspace_votes} v
                    WHERE v.userid = ? AND v.commentid IN
                        (SELECT c.id
                        FROM {pdfworkspace_comments} c
                        WHERE c.pdfworkspaceid = ?)";
            $votes = $DB->get_records_sql($sql , array($userid, $instanceid));
            foreach ($votes as $vote) {
                $DB->delete_records('pdfworkspace_votes', array("id" => $vote->id));
            }

            // 3. Delete all subscriptions this user made in this annotator.
            $sql = "SELECT s.id
                    FROM {pdfworkspace_subscriptions} s
                    WHERE s.userid = ? AND s.annotationid IN
                        (SELECT a.id
                        FROM {pdfworkspace_annotations} a
                        WHERE a.pdfworkspaceid = ?)";
            $subscriptions = $DB->get_records_sql($sql, array($userid, $instanceid));
            foreach ($subscriptions as $subscription) {
                $DB->delete_records('pdfworkspace_subscriptions', array("id" => $subscription->id));
            }

            // 4. Select all comments this user made in this annotator.
            $sql = "SELECT c.*
                    FROM {pdfworkspace_comments} c
                    WHERE c.pdfworkspaceid = ? AND c.userid = ?";
            $comments = $DB->get_records_sql($sql, array($instanceid, $userid));
            foreach ($comments as $comment) {
                // Delete question comments, their underlying annotation as well as all answers and subscriptions.
                if ($comment->isquestion) {
                    self::delete_annotation($comment->annotationid);
                    continue;
                }
                // Empty or delete all other comments.
                self::empty_or_delete_comment($comment);
            }

            // 5. Select the IDs of all annotations that were made by this user in this annotator. Then call the function to delete the annotation and any adjacent comments.
            $annotations = $DB->get_fieldset_select('pdfworkspace_annotations', 'id', "pdfworkspaceid = ? AND userid = ?", array($instanceid, $userid));
            foreach ($annotations as $annotationid) {
                self::delete_annotation($annotationid);
            }
        }
    }

    // Status quo:
    // Deleting the initial or final comment of a 'thread' will remove it from the comments table.
    // Deleting any other comment will merely set the field isdeleted of the comments table to 1, so that the comment will be displayed as deleted within the 'thread'.

    /**
     * Function deletes an annotation and all comments and subscriptions attached to it.
     *
     * @param int $annotationid One annotationid (int) to delete
     */
    public static function delete_annotation($annotationid) {

        global $DB;

        // 1. Get all comments on this annotation and prepare them for deletion.
        // 1.1 Retrieve comments from DB.
        $comments = $DB->get_records('pdfworkspace_comments', array("annotationid" => $annotationid));

        foreach ($comments as $comment) {

            // 1.2 Delete any votes for these comments.
            $DB->delete_records('pdfworkspace_votes', array("commentid" => $comment->id));

            // Delete any pictures of the comment.
            $DB->delete_records('files', array("component" => "mod_pdfworkspace", "filearea" => "post", "itemid" => $comment->id));
        }

        // 1.3 Now delete all comments.
        $DB->delete_records('pdfworkspace_comments', array("annotationid" => $annotationid));

        // 2. Delete subscriptions to the question.
        $DB->delete_records('pdfworkspace_subscriptions', array('annotationid' => $annotationid));

        // 3. Delete the annotation itself.
        $DB->delete_records('pdfworkspace_annotations', array("id" => $annotationid));
    }

    /**
     * Function empties or deletes a comment.
     *
     * @param \mod_pdfworkspace\output\comment $comment comment to be emptied or deleted
     *
     * @throws \dml_exception
     */
    public static function empty_or_delete_comment($comment) {

        global $DB;

        $select = "annotationid = ? AND timecreated > ? AND isdeleted = ?";
        $wasanswered = $DB->record_exists_select('pdfworkspace_comments', $select, array($comment->annotationid, $comment->timecreated, 0));

        // If the comment was answered, empty it and mark it as deleted for a special display.
        if ($wasanswered) {
            $DB->update_record('pdfworkspace_comments', array("id" => $comment->id, "content" => "", "isdeleted" => 1));
            // If not, just delete it.
        } else {

            // But first: Check if the predecessor was already marked as deleted, too and if so, delete it completely.
            $sql = "SELECT id, isdeleted from {pdfworkspace_comments} WHERE annotationid = ? AND isquestion = ? AND timecreated < ? ORDER BY id DESC";
            $params = array($comment->annotationid, 0, $comment->timecreated);

            $predecessors = $DB->get_records_sql($sql, $params);

            foreach ($predecessors as $predecessor) {
                if ($predecessor->isdeleted) {
                    $DB->delete_records('pdfworkspace_comments', array("id" => $predecessor->id));
                } else {
                    break;
                }
            }

            // Now delete the selected comment.
            $DB->delete_records('pdfworkspace_comments', array("id" => $comment->id));
        }
    }

    /**
     * Finds the users in the given userlists's context.
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $params = [
            'contextid'    => $context->instanceid,
            'modulename'    => 'pdfworkspace',
        ];

        // Comments.
        $sql = "SELECT ac.userid
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                JOIN {pdfworkspace} a ON a.id = cm.instance
                JOIN {pdfworkspace_comments} ac ON ac.pdfworkspaceid = a.id
            WHERE cm.id = :contextid";
        $userlist->add_from_sql('userid', $sql, $params);

        // A selected recipient is also part of this activity's personal data.
        $sql = "SELECT ats.recipientid AS userid
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                JOIN {pdfworkspace} a ON a.id = cm.instance
                JOIN {pdfworkspace_annotations} ats ON ats.pdfworkspaceid = a.id
                WHERE cm.id = :contextid AND ats.recipientid IS NOT NULL";
        $userlist->add_from_sql('userid', $sql, $params);

        // Reports.
        $sql = "SELECT ar.userid
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                JOIN {pdfworkspace} a ON a.id = cm.instance
                JOIN {pdfworkspace_reports} ar ON ar.pdfworkspaceid = a.id
            WHERE cm.id = :contextid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Annotations.
        $sql = "SELECT ats.userid
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                JOIN {pdfworkspace} a ON a.id = cm.instance
                JOIN {pdfworkspace_annotations} ats ON ats.pdfworkspaceid = a.id
            WHERE cm.id = :contextid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Votes.
        $sql = "SELECT v.userid
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                JOIN {pdfworkspace} a ON a.id = cm.instance
                JOIN {pdfworkspace_comments} ac ON ac.pdfworkspaceid = a.id
                JOIN {pdfworkspace_votes} v ON v.commentid = ac.id
            WHERE cm.id = :contextid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Subscriptions
        $sql = "SELECT asub.userid
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                JOIN {pdfworkspace} a ON a.id = cm.instance
                JOIN {pdfworkspace_annotations} ats ON ats.pdfworkspaceid = a.id
                JOIN {pdfworkspace_subscriptions} asub ON a.id = asub.annotationid
            WHERE cm.id = :contextid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Deletes data for users in given userlist's context.
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        if (!$userlist->get_userids()) {
            return;
        }

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);

        // Find the instance.
        $annotationinstance = get_coursemodule_from_id('pdfworkspace', $context->instanceid);
        $annotatorid = $annotationinstance->instance;

        // Combine instance + user sql.
        $params = array_merge(['pdfworkspaceid' => $annotatorid], $userinparams);
        $DB->execute("UPDATE {pdfworkspace_annotations}
                         SET audience = 'private', recipientid = NULL
                       WHERE pdfworkspaceid = :pdfworkspaceid AND recipientid {$userinsql}", $params);
        $sql = "pdfworkspaceid = :pdfworkspaceid AND userid {$userinsql}";

        // Delete subscriptions.
        $annotations = $DB->get_records('pdfworkspace_annotations', ['pdfworkspaceid' => $annotatorid]);
        $annotationids = array_column($annotations, 'id');
        if ($annotationids) {
            list($subinsql, $subinparams) = $DB->get_in_or_equal($annotationids, SQL_PARAMS_NAMED, 'annotation');
            $DB->delete_records_select('pdfworkspace_subscriptions',
                "userid {$userinsql} AND annotationid {$subinsql}", array_merge($userinparams, $subinparams));
        }

        // Delete votes.
        $comments = $DB->get_records('pdfworkspace_comments', ['pdfworkspaceid' => $annotatorid]);
        $commentsids = array_column($comments, 'id');
        if ($commentsids) {
            list($commentinsql, $commentinparams) = $DB->get_in_or_equal($commentsids, SQL_PARAMS_NAMED, 'comment');
            $DB->delete_records_select('pdfworkspace_votes',
                "userid {$userinsql} AND commentid {$commentinsql}", array_merge($userinparams, $commentinparams));
        }

        // Delete rest of data.
        $DB->delete_records_select('pdfworkspace_annotations', $sql, $params);
        $DB->delete_records_select('pdfworkspace_reports', $sql, $params);
        $DB->delete_records_select('pdfworkspace_comments', $sql, $params);

        // Delete pictures in comments.
        $files = get_file_storage()->get_area_files($context->id, 'mod_pdfworkspace', 'post', false, 'id', false);
        foreach ($files as $file) {
            if (in_array($file->get_userid(), $userlist->get_userids()) && in_array($file->get_itemid(), $commentsids)) {
                $file->delete();
            }
        }
    }
}
