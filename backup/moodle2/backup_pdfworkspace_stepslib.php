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
 * Define all the backup steps that will be used by the backup_pdfworkspace_activity_task
 *
 * Moodle creates backups of courses or their parts by executing a so called backup plan.
 * The backup plan consists of a set of backup tasks and finally each backup task consists of one or more backup steps.
 * This file provides all the backup steps classes.
 *
 * See https://docs.moodle.org/dev/Backup_API and https://docs.moodle.org/dev/Backup_2.0_for_developers for more information.
 *
 * @package   mod_pdfworkspace
 * @category  backup
 * @copyright 2018 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @author    Anna Heynkes
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete pdfworkspace structure for backup, with file and id annotations
 */
class backup_pdfworkspace_activity_structure_step extends backup_activity_structure_step {

    /**
     * There are three main things that the method must do:
     * 1. Create a set of backup_nested_element instances that describe the required data of your plugin
     * 2. Connect these instances into a hierarchy using their add_child() method
     * 3. Set data sources for the elements, using their methods like set_source_table() or set_source_sql()
     * The method must return the root backup_nested_element instance processed by the prepare_activity_structure()
     * method (which just wraps your structures with a common envelope).
     *
     * TODO Adjust after final db structure has been determined
     *
     */
    protected function define_structure() {

        // 1. To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // 2. Define each element separately.
        $pdfworkspace = new backup_nested_element('pdfworkspace', array('id'), array(
            'name', 'intro', 'introformat', 'usevotes', 'useprint', 'useprintcomments', 'usecombineddownload',
            'use_studenttextbox', 'use_studentdrawing', 'useworkspacedownload', 'useworkspacecomments',
            'useprivatecomments', 'useprotectedcomments', 'studentaudiences', 'staffaudiences',
            'studentdefaultaudience', 'staffdefaultaudience',
            'timecreated', 'timemodified'));

            $documents = new backup_nested_element('documents');
            $document = new backup_nested_element('document', array('id'),
                array('filepath', 'filename', 'displayname', 'contenthash', 'sortorder', 'exportincluded', 'exportorder'));
            $annotations = new backup_nested_element('annotations');
            $annotation = new backup_nested_element('annotation', array('id'), array('page', 'userid', 'annotationtypeid',
                'documentid', 'data', 'audience', 'recipientid', 'timecreated', 'timemodified', 'modifiedby'));

                $subscriptions = new backup_nested_element('subscriptions');
                $subscription = new backup_nested_element('subscription', array('id'), array('userid'));

                $comments = new backup_nested_element('comments');
                $c = array('pdfworkspaceid', 'userid', 'content', 'timecreated', 'timemodified', 'modifiedby', 'visibility',
                    'isquestion', 'isdeleted', 'ishidden', 'solved');
                $comment = new backup_nested_element('comment', array('id'), $c);

                    $votes = new backup_nested_element('votes');
                    $vote = new backup_nested_element('vote', array('id'), array('userid', 'annotationid'));

                    $reports = new backup_nested_element('reports');
                    $report = new backup_nested_element('report', array('id'), array('courseid', 'pdfworkspaceid', 'message',
                        'userid', 'timecreated', 'seen'));

        // 3. Build the tree (mind the right order!)
        $pdfworkspace->add_child($documents);
            $documents->add_child($document);
        $pdfworkspace->add_child($annotations);
            $annotations->add_child($annotation);

                $annotation->add_child($subscriptions);
                    $subscriptions->add_child($subscription);

                $annotation->add_child($comments);
                    $comments->add_child($comment);

                        $comment->add_child($votes);
                            $votes->add_child($vote);

                        $comment->add_child($reports);
                            $reports->add_child($report);

        // 4. Define db sources
        // backup::VAR_ACTIVITYID is the 'course module id'.
        $pdfworkspace->set_source_table('pdfworkspace', array('id' => backup::VAR_ACTIVITYID));
        $document->set_source_table('pdfworkspace_documents', ['pdfworkspaceid' => backup::VAR_PARENTID]);

        if ($userinfo) {
            // Add all annotations specific to this annotator instance.
            $annotation->set_source_sql('SELECT a.* FROM {pdfworkspace_annotations} a '
                                        . 'WHERE a.pdfworkspaceid = ?',
                                        array('pdfworkspaceid' => backup::VAR_PARENTID));

                // Add any subscriptions to this annotation.
                $subscription->set_source_table('pdfworkspace_subscriptions', array('annotationid' => backup::VAR_PARENTID));

                // Add any comments of this annotation.
                $comment->set_source_table('pdfworkspace_comments', array('annotationid' => backup::VAR_PARENTID));

                    // Add any votes for this comment.
                    $vote->set_source_table('pdfworkspace_votes', array('commentid' => backup::VAR_PARENTID));

                    // Add any reports of this comment.
                    $report->set_source_table('pdfworkspace_reports', array('commentid' => backup::VAR_PARENTID));
        }

        // 5. Define id annotations (some attributes are foreign keys).
        $annotation->annotate_ids('user', 'userid');
        $annotation->annotate_ids('user', 'recipientid');
        $subscription->annotate_ids('user', 'userid');
        $comment->annotate_ids('user', 'userid');
        $comment->annotate_ids('pdfworkspace', 'pdfworkspaceid');
        $vote->annotate_ids('user', 'userid');
        $report->annotate_ids('user', 'userid');
        $report->annotate_ids('pdfworkspace', 'pdfworkspaceid');

        // 6. Define file annotations (vgl. resource activity).
        $pdfworkspace->annotate_files('mod_pdfworkspace', 'intro', null); // This file area does not have an itemid.
        $pdfworkspace->annotate_files('mod_pdfworkspace', 'content', null); // See above.
        $comment->annotate_files('mod_pdfworkspace', 'post', 'id');

        // 7. Return the root element (pdfworkspace), wrapped into standard activity structure.
        return $this->prepare_activity_structure($pdfworkspace);
    }
}
