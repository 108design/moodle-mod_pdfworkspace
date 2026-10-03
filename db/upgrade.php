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
 * File containing plugin upgrade instructions
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @author   Rabea de Groot, Anna Heynkes, Friederike Schwager
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
defined('MOODLE_INTERNAL') || die;

function xmldb_pdfworkspace_upgrade($oldversion) {

    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2018032600) {

        // Define table pdfworkspace_votes to be created.
        $table = new xmldb_table('pdfworkspace_votes');

        // Adding fields to table pdfworkspace_votes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('commentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('vote', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1');

        // Adding keys to table pdfworkspace_votes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for pdfworkspace_votes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018032600, 'pdfworkspace');
    }

    if ($oldversion < 2018032601) {

        // Define table pdfworkspace_comments_archiv to be created.
        $table = new xmldb_table('pdfworkspace_comments_archiv');

        // Adding fields to table pdfworkspace_comments_archiv.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('annotationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('visibility', XMLDB_TYPE_CHAR, '45', null, XMLDB_NOTNULL, null, 'public');
        $table->add_field('isquestion', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('isdeleted', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('seen', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table pdfworkspace_comments_archiv.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for pdfworkspace_comments_archiv.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018032601, 'pdfworkspace');
    }

    if ($oldversion < 2018043000) {

        // Define field usevotes to be added to pdfworkspace.
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('usevotes', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'introformat');

        // Conditionally launch add field usevotes.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field newsspan to be added to pdfworkspace.
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('newsspan', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '3', 'usevotes');

        // Conditionally launch add field newsspan.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018043000, 'pdfworkspace');
    }

    if ($oldversion < 2018050201) {

        // Define key commentid (foreign) to be added to pdfworkspace_votes.
        $table1 = new xmldb_table('pdfworkspace_votes');
        $key1 = new xmldb_key('commentid', XMLDB_KEY_FOREIGN, array('commentid'), 'comments', array('id'));

        // Launch add key commentid.
        $dbman->add_key($table1, $key1);

        // Define index userid (not unique) to be added to pdfworkspace_votes.
        $index1 = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch add index userid.
        if (!$dbman->index_exists($table1, $index1)) {
            $dbman->add_index($table1, $index1);
        }

        // Define key annotationid (foreign) to be added to pdfworkspace_comments.
        $table2 = new xmldb_table('pdfworkspace_comments');
        $key2 = new xmldb_key('annotationid', XMLDB_KEY_FOREIGN, array('annotationid'), 'annotations', array('id'));

        // Launch add key annotationid.
        $dbman->add_key($table2, $key2);

        // Define index userid (not unique) to be added to pdfworkspace_comments.
        $index2 = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch add index userid.
        if (!$dbman->index_exists($table2, $index2)) {
            $dbman->add_index($table2, $index2);
        }

        // Define key commentid (foreign) to be added to pdfworkspace_reports.
        $table3 = new xmldb_table('pdfworkspace_reports');
        $key3 = new xmldb_key('commentid', XMLDB_KEY_FOREIGN, array('commentid'), 'comments', array('id'));

        // Launch add key commentid.
        $dbman->add_key($table3, $key3);

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018050201, 'pdfworkspace');
    }

    if ($oldversion < 2018050202) {

        // Changing type of field isquestion on table pdfworkspace_comments to int.
        $table1 = new xmldb_table('pdfworkspace_comments');
        $field1 = new xmldb_field('isquestion', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'visibility');
        // Launch change of type for field isquestion.
        $dbman->change_field_type($table1, $field1);

        // Changing type of field isdeleted on table pdfworkspace_comments to int.
        $field2 = new xmldb_field('isdeleted', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'isquestion');
        // Launch change of type for field isdeleted.
        $dbman->change_field_type($table1, $field2);

        // Changing type of field seen on table pdfworkspace_comments to int.
        $field3 = new xmldb_field('seen', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'isdeleted');
        // Launch change of type for field seen.
        $dbman->change_field_type($table1, $field3);

        // Changing type of field seen on table pdfworkspace_reports to int.
        $table2 = new xmldb_table('pdfworkspace_reports');
        $field4 = new xmldb_field('seen', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'timecreated');
        // Launch change of type for field seen.
        $dbman->change_field_type($table2, $field4);

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018050202, 'pdfworkspace');
    }

    if ($oldversion < 2018050400) {

        // Define field use_studenttextbox to be added to pdfworkspace.
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('use_studenttextbox', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'usevotes');

        // Conditionally launch add field use_studenttextbox.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field use_studentdrawing to be added to pdfworkspace.
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('use_studentdrawing', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0',
            'use_studenttextbox');

        // Conditionally launch add field use_studentdrawing.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018050400, 'pdfworkspace');
    }

    if ($oldversion < 2018050402) {

        // Define table pdfworkspace_subscriptions to be created.
        $table = new xmldb_table('pdfworkspace_subscriptions');

        // Adding fields to table pdfworkspace_subscriptions.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('commentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table pdfworkspace_subscriptions.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('commentid', XMLDB_KEY_FOREIGN, array('commentid'), 'comments', array('id'));

        // Conditionally launch create table for pdfworkspace_subscriptions.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018050402, 'pdfworkspace');
    }

    if ($oldversion < 2018060700) {

        // Define key commentid (foreign) to be dropped form pdfworkspace_subscriptions.
        $table = new xmldb_table('pdfworkspace_subscriptions');
        $key = new xmldb_key('commentid', XMLDB_KEY_FOREIGN, array('commentid'), 'comments', array('id'));

        // Launch drop key commentid.
        $dbman->drop_key($table, $key);

        // Rename field commentid on table pdfworkspace_subscriptions to annotationid.
        $table = new xmldb_table('pdfworkspace_subscriptions');
        $field = new xmldb_field('commentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'userid');

        // Launch rename field commentid.
        $dbman->rename_field($table, $field, 'annotationid');

        // Define key annotationid (foreign) to be added to pdfworkspace_subscriptions.
        $table = new xmldb_table('pdfworkspace_subscriptions');
        $key = new xmldb_key('annotationid', XMLDB_KEY_FOREIGN, array('annotationid'), 'annotationsneu', array('id'));

        // Launch add key annotationid.
        $dbman->add_key($table, $key);

        // Update existing records.
        $rs = $DB->get_recordset('pdfworkspace_subscriptions');
        foreach ($rs as $record) {
            $annotationid = $DB->get_field('pdfworkspace_comments', 'annotationid', array('id' => $record->annotationid));
            $record->annotationid = $annotationid;
            $DB->update_record('pdfworkspace_subscriptions', $record);
        }
        $rs->close(); // Don't forget to close the recordse!
        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018060700, 'pdfworkspace');
    }

    if ($oldversion < 2018062700) {

        // Define field pdfworkspaceid to be added to pdfworkspace_comments.
        $table = new xmldb_table('pdfworkspace_comments');
        $field = new xmldb_field('pdfworkspaceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '-1', 'id');

        // Conditionally launch add field pdfworkspaceid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key pdfworkspaceid (foreign) to be added to pdfworkspace_comments.
        $table = new xmldb_table('pdfworkspace_comments');
        $key = new xmldb_key('pdfworkspaceid', XMLDB_KEY_FOREIGN, array('pdfworkspaceid'), 'pdfworkspace', array('id'));

        // Launch add key pdfworkspaceid.
        $dbman->add_key($table, $key);

        // Define field pdfworkspaceid to be added to pdfworkspace_comments_archiv.
        $table = new xmldb_table('pdfworkspace_comments_archiv');
        $field = new xmldb_field('pdfworkspaceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '-1', 'id');

        // Conditionally launch add field pdfworkspaceid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add pdfworkspaceid to old records in comments-table.
        $rs = $DB->get_recordset('pdfworkspace_comments');
        foreach ($rs as $record) {
            $pdfworkspaceid = $DB->get_field('pdfworkspace_annotationsneu', 'pdfworkspaceid', array('id' => $record->annotationid));
            $record->pdfworkspaceid = $pdfworkspaceid;
            $DB->update_record('pdfworkspace_comments', $record);
        }
        $rs->close(); // Don't forget to close the recordset!

        $rs = $DB->get_recordset('pdfworkspace_comments_archiv');
        foreach ($rs as $record) {
            $pdfworkspaceid = $DB->get_field('pdfworkspace_annotationsneu', 'pdfworkspaceid', array('id' => $record->annotationid));
            $record->pdfworkspaceid = $pdfworkspaceid;
            $DB->update_record('pdfworkspace_comments_archiv', $record);
        }
        $rs->close(); // Don't forget to close the recordset!
        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018062700, 'pdfworkspace');
    }

    if ($oldversion < 2018062800) {

        // Define key pdfworkspaceid (foreign) to be added to pdfworkspace_comments_archiv.
        $table = new xmldb_table('pdfworkspace_comments_archiv');
        $key = new xmldb_key('pdfworkspaceid', XMLDB_KEY_FOREIGN, array('pdfworkspaceid'), 'pdfworkspace', array('id'));

        // Launch add key pdfworkspaceid.
        $dbman->add_key($table, $key);

        // Define table pdfworkspace_annotations to be dropped.
        $table = new xmldb_table('pdfworkspace_annotations');

        // Conditionally launch drop table for pdfworkspace_annotations.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table pdfworkspace_annotationsneu to be renamed to NEWNAMEGOESHERE.
        $table = new xmldb_table('pdfworkspace_annotationsneu');

        // Launch rename table for pdfworkspace_annotationsneu.
        $dbman->rename_table($table, 'pdfworkspace_annotations');

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018062800, 'pdfworkspace');
    }

    if ($oldversion < 2018062801) {

        // Define key pdfworkspaceid (foreign) to be added to pdfworkspace_annotations.
        $table = new xmldb_table('pdfworkspace_annotations');
        $key = new xmldb_key('pdfworkspaceid', XMLDB_KEY_FOREIGN, array('pdfworkspaceid'), 'pdfworkspace', array('id'));

        // Launch add key pdfworkspaceid.
        $dbman->add_key($table, $key);

        // Define key annotationtypeid (foreign) to be added to pdfworkspace_annotations.
        $table = new xmldb_table('pdfworkspace_annotations');
        $key = new xmldb_key('annotationtypeid', XMLDB_KEY_FOREIGN, array('annotationtypeid'), 'pdfworkspace_annotationtypes',
            array('id'));

        // Launch add key annotationtypeid.
        $dbman->add_key($table, $key);

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018062801, 'pdfworkspace');
    }

    // Rename field 'page' in table pdfworkspace_reports to 'reason'.
    if ($oldversion < 2018070300) {

        $table = new xmldb_table('pdfworkspace_reports');
        $field = new xmldb_field('page', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'pdfworkspaceid');

        // Conditionally launch add field reason.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Launch rename field 'page'.
        $dbman->rename_field($table, $field, 'reason');

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018070300, 'pdfworkspace');
    }

    if ($oldversion < 2018070301) {

        // Changing nullability of field reason on table pdfworkspace_reports to null.
        $table = new xmldb_table('pdfworkspace_reports');
        $field = new xmldb_field('reason', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'pdfworkspaceid');

        // Launch change of nullability for field reason.
        $dbman->change_field_notnull($table, $field);

        // Launch change of default for field reason.
        $dbman->change_field_default($table, $field);

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018070301, 'pdfworkspace');
    }

    if ($oldversion < 2018070302) {

        // Define field message to be dropped from pdfworkspace_reports.
        $table = new xmldb_table('pdfworkspace_reports');
        $field = new xmldb_field('reason');

        // Conditionally launch drop field message.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018070302, 'pdfworkspace');
    }

    if ($oldversion < 2018082800) {

        // Define field useprint to be added to pdfworkspace.
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('useprint', XMLDB_TYPE_INTEGER, '4', null, null, null, '1', 'usevotes');

        // Conditionally launch add field useprint.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018082800, 'pdfworkspace');
    }

    if ($oldversion < 2018082900) {

        // Changing nullability of field useprint on table pdfworkspace to not null.
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('useprint', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'usevotes');

        // Launch change of nullability for field useprint.
        $dbman->change_field_notnull($table, $field);

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018082900, 'pdfworkspace');
    }

    if ($oldversion < 2018092400) {

        // Define field newsspan to be dropped from pdfworkspace.
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('newsspan');

        // Conditionally launch drop field newsspan.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018092400, 'pdfworkspace');
    }

    if ($oldversion < 2018103000) {

        // Define table pdfworkspace_comments_archiv to be renamed to pdfworkspace_commentsarchive.
        $table = new xmldb_table('pdfworkspace_comments_archiv');

        // Launch rename table for pdfworkspace_comments_archiv.
        $dbman->rename_table($table, 'pdfworkspace_commentsarchive');

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018103000, 'pdfworkspace');
    }

    if ($oldversion < 2018111901) {

        // Changing the default of field useprint on table pdfworkspace to 0.
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('useprint', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'usevotes');

        // Launch change of default for field useprint.
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_default($table, $field);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018111901, 'pdfworkspace');
    }

    if ($oldversion < 2018112100) {

        // Define field modifiedby to be added to pdfworkspace_annotations.
        $table = new xmldb_table('pdfworkspace_annotations');
        $field = new xmldb_field('modifiedby', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field modifiedby.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field modifiedby to be added to pdfworkspace_comments.
        $table = new xmldb_table('pdfworkspace_comments');
        $field = new xmldb_field('modifiedby', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field modifiedby.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field modifiedby to be added to pdfworkspace_commentsarchive.
        $table = new xmldb_table('pdfworkspace_commentsarchive');
        $field = new xmldb_field('modifiedby', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field modifiedby.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018112100, 'pdfworkspace');
    }

    if ($oldversion < 2018112203) {

        // Define field solved to be added to pdfworkspace_comments.
        $table = new xmldb_table('pdfworkspace_comments');
        $field = new xmldb_field('solved', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'seen');

        // Conditionally launch add field solved.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2018112203, 'pdfworkspace');
    }

    if ($oldversion < 2019013000) {

        // Rename field seen on table pdfworkspace_comments to NEWNAMEGOESHERE.
        $table = new xmldb_table('pdfworkspace_comments');
        $field = new xmldb_field('seen', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'isdeleted');

        // Launch rename field seen.
        $dbman->rename_field($table, $field, 'ishidden');

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2019013000, 'pdfworkspace');
    }

    if ($oldversion < 2019030100) {

        // Define field useprintcomments to be added to pdfworkspace.
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('useprintcomments', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'useprint');

        // Conditionally launch add field useprintcomments.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2019030100, 'pdfworkspace');
    }

    if ($oldversion < 2019060300) {

        // Define table pdfworkspace_commentsarchive to be dropped.
        $table = new xmldb_table('pdfworkspace_commentsarchive');

        // Conditionally launch drop table for pdfworkspace_commentsarchive.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2019060300, 'pdfworkspace');
    }

    if ($oldversion < 2019070100) {

        // Define field useprintcomments to be added to pdfworkspace.
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('useprintcomments', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'useprint');

        // Conditionally launch add field useprintcomments.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2019070100, 'pdfworkspace');
    }

    if ($oldversion < 2021032201) {

        // Define field useprivatecomments to be added to pdfworkspace.
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('useprivatecomments', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0',
            'use_studentdrawing');

        // Conditionally launch add field useprivatecomments.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

         // Define field useprotectedcomments to be added to pdfworkspace.
         $table = new xmldb_table('pdfworkspace');
         $field = new xmldb_field('useprotectedcomments', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0',
             'useprivatecomments');

         // Conditionally launch add field useprotectedcomments.
        if (!$dbman->field_exists($table, $field)) {
             $dbman->add_field($table, $field);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2021032201, 'pdfworkspace');
    }

    if ($oldversion < 2022102606) {

        // Define table pdfworkspace_embeddedfiles to be created.
        $table = new xmldb_table('pdfworkspace_embeddedfiles');

        // Adding fields to table pdfworkspace_embeddedfiles.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fileid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('commentid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table pdfworkspace_embeddedfiles.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fileid', XMLDB_KEY_FOREIGN, ['fileid'], 'files', ['id']);
        $table->add_key('commentid', XMLDB_KEY_FOREIGN, ['commentid'], 'comments', ['id']);

        // Adding indexes to table pdfworkspace_embeddedfiles.
        $table->add_index('idandcomment', XMLDB_INDEX_NOTUNIQUE, ['id', 'commentid']);

        // Conditionally launch create table for pdfworkspace_embeddedfiles.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2022102606, 'pdfworkspace');
    }

    if ($oldversion < 2022110200) {

        // Define table pdfworkspace_embeddedfiles to be dropped.
        $table = new xmldb_table('pdfworkspace_embeddedfiles');

        // Conditionally launch drop table for pdfworkspace_embeddedfiles.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Pdfworkspace savepoint reached.
        upgrade_mod_savepoint(true, 2022110200, 'pdfworkspace');
    }

    if ($oldversion < 2026092800) {
        // Preserve the visibility of existing activities and annotations.
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('visibilitymode', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'public',
            'useprotectedcomments');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('pdfworkspace_annotations');
        $field = new xmldb_field('audience', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'public', 'data');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('recipientid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'audience');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026092800, 'pdfworkspace');
    }

    if ($oldversion < 2026092808) {
        // Existing activities retain their allowed audiences (private and scoped).
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('studentaudiences', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '3',
            'visibilitymode');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('staffaudiences', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '3',
            'studentaudiences');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026092808, 'pdfworkspace');
    }

    if ($oldversion < 2026092816) {
        $table = new xmldb_table('pdfworkspace_documents');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('pdfworkspaceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('filepath', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '/');
            $table->add_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('pdfworkspaceid', XMLDB_KEY_FOREIGN, ['pdfworkspaceid'], 'pdfworkspace', ['id']);
            $dbman->create_table($table);
        }
        $annotationtable = new xmldb_table('pdfworkspace_annotations');
        $field = new xmldb_field('documentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'pdfworkspaceid');
        if (!$dbman->field_exists($annotationtable, $field)) {
            $dbman->add_field($annotationtable, $field);
        }
        $key = new xmldb_key('documentid', XMLDB_KEY_FOREIGN, ['documentid'], 'pdfworkspace_documents', ['id']);
        $dbman->add_key($annotationtable, $key);

        $fs = get_file_storage();
        foreach ($DB->get_records('pdfworkspace', null, '', 'id,course') as $activity) {
            $cm = get_coursemodule_from_instance('pdfworkspace', $activity->id, $activity->course);
            if (!$cm) {
                continue;
            }
            $context = context_module::instance($cm->id);
            $files = $fs->get_area_files($context->id, 'mod_pdfworkspace', 'content', 0,
                'sortorder DESC, id ASC', false);
            $firstid = null;
            $order = 0;
            foreach ($files as $file) {
                $doc = (object)[
                    'pdfworkspaceid' => $activity->id,
                    'filepath' => $file->get_filepath(),
                    'filename' => $file->get_filename(),
                    'contenthash' => $file->get_contenthash(),
                    'sortorder' => $order++,
                ];
                $id = $DB->insert_record('pdfworkspace_documents', $doc);
                if ($firstid === null) {
                    $firstid = $id;
                }
            }
            if ($firstid !== null) {
                $DB->set_field('pdfworkspace_annotations', 'documentid', $firstid,
                    ['pdfworkspaceid' => $activity->id]);
            }
        }
        upgrade_mod_savepoint(true, 2026092816, 'pdfworkspace');
    }

    if ($oldversion < 2026092819) {
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('studentdefaultaudience', XMLDB_TYPE_CHAR, '20', null,
            XMLDB_NOTNULL, null, 'protected', 'staffaudiences');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('staffdefaultaudience', XMLDB_TYPE_CHAR, '20', null,
            XMLDB_NOTNULL, null, 'private', 'studentdefaultaudience');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026092819, 'pdfworkspace');
    }

    if ($oldversion < 2026092820) {
        $table = new xmldb_table('pdfworkspace_documents');
        $field = new xmldb_field('displayname', XMLDB_TYPE_CHAR, '255', null,
            null, null, null, 'filename');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026092820, 'pdfworkspace');
    }

    if ($oldversion < 2026092821) {
        $table = new xmldb_table('pdfworkspace');
        $field = new xmldb_field('visibilitymode');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026092821, 'pdfworkspace');
    }

    if ($oldversion < 2026092822) {
        $table = new xmldb_table('pdfworkspace_documents');
        $field = new xmldb_field('displayname', XMLDB_TYPE_CHAR, '255', null,
            null, null, null, 'filename');
        $dbman->change_field_notnull($table, $field);
        upgrade_mod_savepoint(true, 2026092822, 'pdfworkspace');
    }

    if ($oldversion < 2026092911) {
        $table = new xmldb_table('pdfworkspace');
        // Keep the already available combined download enabled for existing activities.
        $field = new xmldb_field('usecombineddownload', XMLDB_TYPE_INTEGER, '2', null,
            XMLDB_NOTNULL, null, '1', 'useprintcomments');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026092911, 'pdfworkspace');
    }

    if ($oldversion < 2026100200) {
        $table = new xmldb_table('pdfworkspace');
        foreach (['useworkspacedownload', 'useworkspacecomments'] as $name) {
            $field = new xmldb_field($name, XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        $table = new xmldb_table('pdfworkspace_documents');
        foreach (['exportincluded' => '2', 'exportorder' => '10'] as $name => $length) {
            $field = new xmldb_field($name, XMLDB_TYPE_INTEGER, $length, null, XMLDB_NOTNULL, null, '0');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        // Start existing workspaces with all documents in their current tab order.
        $DB->execute('UPDATE {pdfworkspace_documents} SET exportincluded = 1, exportorder = sortorder');
        upgrade_mod_savepoint(true, 2026100200, 'pdfworkspace');
    }

    return true;
}
