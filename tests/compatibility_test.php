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

namespace mod_pdfworkspace;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/pdfworkspace/locallib.php');
require_once($CFG->libdir . '/upgradelib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Native Moodle integration across the supported version and database matrix.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class compatibility_test extends \advanced_testcase {
    /** Exercise native renderers, action menus and all overview table classes. */
    public function test_viewer_and_overview_render_with_native_moodle_components(): void {
        global $DB, $PAGE, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('pdfworkspace', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('pdfworkspace', $activity->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $PAGE->set_course($course);
        $PAGE->set_cm($cm, $course);
        $PAGE->set_context($context);
        $PAGE->set_url(new \moodle_url('/mod/pdfworkspace/view.php', ['id' => $cm->id]));
        $file = get_file_storage()->create_file_from_string(['contextid' => $context->id,
            'component' => 'mod_pdfworkspace', 'filearea' => 'content', 'itemid' => 0,
            'filepath' => '/', 'filename' => 'render.pdf'], '%PDF-1.4 synthetic render fixture');
        $activity->currentdocumentid = $DB->insert_record('pdfworkspace_documents', (object)[
            'pdfworkspaceid' => $activity->id, 'filepath' => '/', 'filename' => 'render.pdf',
            'contenthash' => $file->get_contenthash()]);
        $DB->insert_record('pdfworkspace_documents', (object)['pdfworkspaceid' => $activity->id,
            'filepath' => '/', 'filename' => 'second.pdf', 'contenthash' => $file->get_contenthash(), 'sortorder' => 1]);
        $activity->documents = $DB->get_records('pdfworkspace_documents', ['pdfworkspaceid' => $activity->id]);
        $capabilities = (object)['usetextbox' => true, 'usedrawing' => true, 'useprint' => true,
            'useprintcomments' => true];
        $renderer = $PAGE->get_renderer('mod_pdfworkspace');
        $index = new \mod_pdfworkspace\output\index($activity, $capabilities, $file);
        $html = $renderer->render_index($index);
        $this->assertStringContainsString('toolbarContent', $html);
        $this->assertStringContainsString(get_string('downloadmenu', 'pdfworkspace'), $html);
        require_once($CFG->dirroot . '/mod/pdfworkspace/model/overviewtable.php');
        $url = new \moodle_url('/mod/pdfworkspace/view.php', ['id' => $cm->id, 'action' => 'overview']);
        foreach ([new \questionstable($url, true, false), new \answerstable($url),
                  new \userspoststable($url, false), new \reportstable($url)] as $table) {
            $table->setup();
            ob_start();
            $table->print_nothing_to_display();
            $output = ob_get_clean();
            $this->assertIsString($output);
            $this->assertNotSame('', $output);
        }
    }

    /** Fresh installation and the workspace-selection upgrade must produce the declared schema. */
    public function test_schema_and_selection_upgrade_preserve_existing_data(): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('pdfworkspace', ['course' => $course->id]);
        $id = $DB->insert_record('pdfworkspace_documents', (object)['pdfworkspaceid' => $activity->id,
            'filepath' => '/', 'filename' => 'existing.pdf', 'displayname' => 'Existing title',
            'contenthash' => str_repeat('0', 40), 'sortorder' => 7]);
        $schema = new \xmldb_file($CFG->dirroot . '/mod/pdfworkspace/db/install.xml');
        $this->assertTrue($schema->loadXMLStructure());
        $manager = $DB->get_manager();
        $this->assertSame([], $manager->check_database_schema($schema->getStructure(), ['extratables' => false]));
        require_once($CFG->dirroot . '/mod/pdfworkspace/db/upgrade.php');
        foreach (['pdfworkspace' => ['useworkspacedownload', 'useworkspacecomments'],
                  'pdfworkspace_documents' => ['exportincluded', 'exportorder']] as $table => $fields) {
            foreach ($fields as $name) {
                $manager->drop_field(new \xmldb_table($table), new \xmldb_field($name));
            }
        }
        set_config('version', 2026100102, 'mod_pdfworkspace');
        $this->assertTrue(xmldb_pdfworkspace_upgrade(2026100102));
        $document = $DB->get_record('pdfworkspace_documents', ['id' => $id], '*', MUST_EXIST);
        $this->assertSame('Existing title', $document->displayname);
        $this->assertEquals(1, $document->exportincluded);
        $this->assertEquals(7, $document->exportorder);
        $this->assertEquals(0, $DB->get_field('pdfworkspace', 'useworkspacecomments', ['id' => $activity->id]));
        $this->assertSame([], $manager->check_database_schema($schema->getStructure(), ['extratables' => false]));
    }

    /** Privacy deletion must use the actual activity context and stay within it. */
    public function test_privacy_deletion_is_scoped_to_the_activity(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $activities = [];
        foreach ([0, 1] as $index) {
            $activity = $this->getDataGenerator()->create_module('pdfworkspace', ['course' => $course->id]);
            $activities[] = $activity;
            $typeid = $DB->get_field('pdfworkspace_annotationtypes', 'id', ['name' => 'pin'], MUST_EXIST);
            $annotation = $DB->insert_record('pdfworkspace_annotations', (object)['pdfworkspaceid' => $activity->id,
                'annotationtypeid' => $typeid, 'userid' => $user->id, 'page' => 1, 'data' => '{}',
                'timecreated' => time()]);
            $comment = $DB->insert_record('pdfworkspace_comments', (object)['pdfworkspaceid' => $activity->id,
                'annotationid' => $annotation, 'userid' => $user->id, 'content' => 'Synthetic private data',
                'isquestion' => 1, 'timecreated' => time(), 'timemodified' => time()]);
            $DB->insert_record('pdfworkspace_subscriptions', (object)['annotationid' => $annotation, 'userid' => $user->id]);
            $DB->insert_record('pdfworkspace_votes', (object)['commentid' => $comment, 'userid' => $user->id]);
            $context = \context_module::instance($activity->cmid);
            get_file_storage()->create_file_from_string(['contextid' => $context->id, 'component' => 'mod_pdfworkspace',
                'filearea' => 'post', 'itemid' => $comment, 'filepath' => '/', 'filename' => 'attachment.txt',
                'userid' => $user->id], 'Synthetic attachment');
        }
        $targeted = $DB->insert_record('pdfworkspace_annotations', (object)['pdfworkspaceid' => $activities[0]->id,
            'annotationtypeid' => $typeid, 'userid' => $other->id, 'page' => 1, 'data' => '{}',
            'audience' => 'targeted', 'recipientid' => $user->id, 'timecreated' => time()]);
        $context = \context_module::instance($activities[0]->cmid);
        $approved = new \core_privacy\local\request\approved_userlist($context, 'mod_pdfworkspace', [$user->id]);
        \mod_pdfworkspace\privacy\provider::delete_data_for_users($approved);
        $this->assertFalse($DB->record_exists('pdfworkspace_comments', ['pdfworkspaceid' => $activities[0]->id,
            'userid' => $user->id]));
        $this->assertFalse($DB->record_exists('pdfworkspace_annotations', ['pdfworkspaceid' => $activities[0]->id,
            'userid' => $user->id]));
        $this->assertSame('private', $DB->get_field('pdfworkspace_annotations', 'audience', ['id' => $targeted]));
        $this->assertNull($DB->get_field('pdfworkspace_annotations', 'recipientid', ['id' => $targeted]));
        $this->assertCount(0, get_file_storage()->get_area_files($context->id, 'mod_pdfworkspace', 'post', false, 'id', false));
        $this->assertTrue($DB->record_exists('pdfworkspace_comments', ['pdfworkspaceid' => $activities[1]->id,
            'userid' => $user->id]));
        $this->assertEquals(1, $DB->count_records('pdfworkspace_subscriptions', ['userid' => $user->id]));
        $this->assertEquals(1, $DB->count_records('pdfworkspace_votes', ['userid' => $user->id]));
    }

    /** Document settings and source files must survive native backup and restore. */
    public function test_backup_restore_preserves_documents_and_export_selection(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $target = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('pdfworkspace', ['course' => $course->id,
            'useworkspacecomments' => 1, 'usecombineddownload' => 1]);
        $context = \context_module::instance($activity->cmid);
        foreach ([['first.pdf', 'First PDF', 1, 2], ['second.pdf', 'Second PDF', 0, 1]] as $index => $row) {
            [$filename, $title, $included, $order] = $row;
            $file = get_file_storage()->create_file_from_string(['contextid' => $context->id,
                'component' => 'mod_pdfworkspace', 'filearea' => 'content', 'itemid' => 0,
                'filepath' => '/', 'filename' => $filename], '%PDF-1.4 synthetic backup fixture ' . $filename);
            $DB->insert_record('pdfworkspace_documents', (object)['pdfworkspaceid' => $activity->id,
                'filepath' => '/', 'filename' => $filename, 'displayname' => $title,
                'contenthash' => $file->get_contenthash(), 'sortorder' => $index,
                'exportincluded' => $included, 'exportorder' => $order]);
        }
        $backup = new \backup_controller(\backup::TYPE_1ACTIVITY, $activity->cmid, \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO, \backup::MODE_IMPORT, $USER->id);
        $backupid = $backup->get_backupid();
        $backup->execute_plan();
        $backup->destroy();
        $restore = new \restore_controller($backupid, $target->id, \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT, $USER->id, \backup::TARGET_CURRENT_ADDING);
        $this->assertTrue($restore->execute_precheck());
        $restore->execute_plan();
        $restore->destroy();
        $restored = $DB->get_record('pdfworkspace', ['course' => $target->id], '*', MUST_EXIST);
        $this->assertEquals(1, $restored->useworkspacecomments);
        $this->assertEquals(1, $restored->usecombineddownload);
        $documents = array_values($DB->get_records('pdfworkspace_documents',
            ['pdfworkspaceid' => $restored->id], 'sortorder ASC'));
        $this->assertCount(2, $documents);
        $this->assertSame(['First PDF', 'Second PDF'], array_column($documents, 'displayname'));
        $this->assertSame([1, 0], array_map('intval', array_column($documents, 'exportincluded')));
        $this->assertSame([2, 1], array_map('intval', array_column($documents, 'exportorder')));
        $cm = get_coursemodule_from_instance('pdfworkspace', $restored->id, $target->id, false, MUST_EXIST);
        $restoredcontext = \context_module::instance($cm->id);
        foreach ($documents as $document) {
            $this->assertSame($document->contenthash, pdf_export::source($document, $restoredcontext)->get_contenthash());
        }
    }

    /** Exercise the server callback, including the non-editing teacher boundary. */
    public function test_inline_title_requires_manageactivities(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('pdfworkspace', ['course' => $course->id]);
        $id = $DB->insert_record('pdfworkspace_documents', (object)['pdfworkspaceid' => $activity->id,
            'filepath' => '/', 'filename' => 'fixture.pdf', 'contenthash' => str_repeat('0', 40)]);
        $editor = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($editor->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'teacher');
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($editor);
        $editable = pdfworkspace_inplace_editable('documenttitle', $id, 'Updated title');
        $this->assertInstanceOf(\core\output\inplace_editable::class, $editable);
        $this->assertSame('Updated title', $DB->get_field('pdfworkspace_documents', 'displayname', ['id' => $id]));
        foreach ([$teacher, $student] as $user) {
            $this->setUser($user);
            try {
                pdfworkspace_inplace_editable('documenttitle', $id, 'Forbidden');
                $this->fail('Title update must be denied');
            } catch (\required_capability_exception $exception) {
                $this->assertSame('Updated title', $DB->get_field('pdfworkspace_documents', 'displayname', ['id' => $id]));
            }
        }
    }

    /** Run the actual PHP-to-Python service against Moodle-owned synthetic source files. */
    public function test_python_service_and_missing_runtime(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        try {
            pdf_export::run([]);
            $this->fail('An unconfigured runtime must be rejected');
        } catch (\moodle_exception $exception) {
            $this->assertSame('combinedexportunavailable', $exception->errorcode);
        }
        $python = getenv('PDFWORKSPACE_QA_PYTHON');
        $fixtures = getenv('PDFWORKSPACE_QA_FIXTURES');
        if (!$python || !$fixtures) {
            $this->markTestSkipped('Synthetic Python runtime fixtures are configured by the local workbench');
        }
        set_config('exportpython', $python, 'mod_pdfworkspace');
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('pdfworkspace', ['course' => $course->id]);
        $context = \context_module::instance($activity->cmid);
        $documents = [];
        foreach (['plain.pdf', 'empty-password.pdf'] as $filename) {
            $file = get_file_storage()->create_file_from_pathname(['contextid' => $context->id,
                'component' => 'mod_pdfworkspace', 'filearea' => 'content', 'itemid' => 0,
                'filepath' => '/', 'filename' => $filename], $fixtures . '/' . $filename);
            $documents[] = ['file' => $file, 'title' => $filename, 'annotations' => [['page' => 1,
                'type' => 'pin', 'data' => ['x' => 40, 'y' => 50], 'comments' => [['content' => 'Synthetic note']]]]];
        }
        $output = pdf_export::run($documents);
        $this->assertFileExists($output);
        $this->assertSame('%PDF-', file_get_contents($output, false, null, 0, 5));
        $this->assertGreaterThan(1000, filesize($output));
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('workspaceempty', 'pdfworkspace'));
        pdf_export::run([]);
    }
}
