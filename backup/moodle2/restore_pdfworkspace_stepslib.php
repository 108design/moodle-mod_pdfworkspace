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
 * Moodle restores data from course backups by executing so called restore plan.
 * The restore plan consists of a set of restore tasks and finally each restore task consists of one or more restore steps.
 * You as the developer of a plugin will have to implement one restore task that deals with your plugin data.
 * Most plugins have their restore tasks consisting of a single restore step
 * - the one that parses the plugin XML file and puts the data into its tables.
 *
 * @package   mod_pdfworkspace
 * @category  backup
 * @copyright 2018 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @author    Anna Heynkes
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define all the restore steps that will be used by the restore_pdfworkspace_activity_task
 */

/**
 * Structure step to restore one pdfworkspace activity
 */
class restore_pdfworkspace_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines the structure to be restored.
     *
     * @return restore_path_element[].
     */
    protected function define_structure() {

        $paths = array();

        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('pdfworkspace',
            '/activity/pdfworkspace');
        $paths[] = new restore_path_element('pdfworkspace_document',
            '/activity/pdfworkspace/documents/document');

        if ($userinfo) {
            $paths[] = new restore_path_element('pdfworkspace_annotation',
                '/activity/pdfworkspace/annotations/annotation');

            $paths[] = new restore_path_element('pdfworkspace_subscription',
                '/activity/pdfworkspace/annotations/annotation/subscriptions/subscription');
            $paths[] = new restore_path_element('pdfworkspace_comment',
                '/activity/pdfworkspace/annotations/annotation/comments/comment');

            $paths[] = new restore_path_element('pdfworkspace_vote',
                '/activity/pdfworkspace/annotations/annotation/comments/comment/votes/vote');
            $paths[] = new restore_path_element('pdfworkspace_report',
                '/activity/pdfworkspace/annotations/annotation/comments/comment/reports/report');
        }
        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore pdfworkspace.
     *
     * @param object $data data.
     */
    protected function process_pdfworkspace($data) {

        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        unset($data->visibilitymode);
        $data->course = $this->get_courseid();
        $data->timecreated = time();
        $data->timemodified = time();

        $newitemid = $DB->insert_record('pdfworkspace', $data); // Insert the pdfworkspace record.

        $this->apply_activity_instance($newitemid); // Immediately after inserting "activity" record, call this.
    }

    /**
     * Restore annotation.
     *
     * @param object $data data.
     */
    protected function process_pdfworkspace_document($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->pdfworkspaceid = $this->get_new_parentid('pdfworkspace');
        // Older backups predate the curated workspace download.
        $data->exportincluded = $data->exportincluded ?? 1;
        $data->exportorder = $data->exportorder ?? $data->sortorder;
        $newid = $DB->insert_record('pdfworkspace_documents', $data);
        $this->set_mapping('pdfworkspace_document', $oldid, $newid);
    }

    protected function process_pdfworkspace_annotation($data) {

        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->pdfworkspaceid = $this->get_new_parentid('pdfworkspace');
        $data->documentid = !empty($data->documentid) ?
            $this->get_mappingid('pdfworkspace_document', $data->documentid) : null;
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (isset($data->audience) && $data->audience === 'targeted') {
            $data->recipientid = !empty($data->recipientid) ? $this->get_mappingid('user', $data->recipientid) : null;
            if (!$data->recipientid) {
                $data->audience = 'private';
            }
        }

        $newitemid = $DB->insert_record('pdfworkspace_annotations', $data);
        $this->set_mapping('pdfworkspace_annotation', $oldid, $newitemid);

    }

    /**
     * Restore subscription.
     *
     * @param object $data data.
     */
    protected function process_pdfworkspace_subscription($data) {

        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->annotationid = $this->get_new_parentid('pdfworkspace_annotation');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('pdfworkspace_subscriptions', $data);
        $this->set_mapping('pdfworkspace_subscription', $oldid, $newitemid);

    }

    /**
     * Restore comment.
     *
     * @param object $data data.
     */
    protected function process_pdfworkspace_comment($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->annotationid = $this->get_new_parentid('pdfworkspace_annotation');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $data->pdfworkspaceid = $this->get_mappingid('pdfworkspace', $data->pdfworkspaceid);

        $newitemid = $DB->insert_record('pdfworkspace_comments', $data);
        $this->set_mapping('pdfworkspace_comment', $oldid, $newitemid, true);
    }

    /**
     * Restore vote.
     *
     * @param object $data data.
     */
    protected function process_pdfworkspace_vote($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->commentid = $this->get_new_parentid('pdfworkspace_comment');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('pdfworkspace_votes', $data);
        $this->set_mapping('pdfworkspace_vote', $oldid, $newitemid);
    }

    /**
     * Restore report.
     *
     * @param object $data data.
     */
    protected function process_pdfworkspace_report($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->courseid = $this->get_courseid();

        $data->commentid = $this->get_new_parentid('pdfworkspace_comment');
        $data->userid = $this->get_mappingid('user', $data->userid);

        // $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->pdfworkspaceid = $this->get_mappingid('pdfworkspace', $data->pdfworkspaceid);
        // Params: 1. Object class as defined in structure, 2. attribute&/column name.

        $newitemid = $DB->insert_record('pdfworkspace_reports', $data);
        $this->set_mapping('pdfworkspace_report', $oldid, $newitemid);
    }

    /**
     * Defines post-execution actions like restoring files.
     */
    protected function after_execute() {
        // Add pdfworkspace related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_pdfworkspace', 'intro', null);
        $this->add_related_files('mod_pdfworkspace', 'content', null);
        $this->add_related_files('mod_pdfworkspace', 'post', 'pdfworkspace_comment');
    }
}
