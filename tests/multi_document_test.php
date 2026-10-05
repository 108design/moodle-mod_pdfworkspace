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
 * PDF Workspace multi document test.
 *
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/mod/pdfworkspace/locallib.php');
require_once($CFG->dirroot . '/mod/pdfworkspace/model/comment.class.php');

class multi_document_test extends \advanced_testcase {
    public function test_annotations_and_questions_stay_on_their_pdf(): void {
        global $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('pdfworkspace', ['course' => $course->id]);
        $context = \context_module::instance($activity->cmid);
        $storage = get_file_storage();
        foreach (['first.pdf', 'second.pdf'] as $name) {
            $storage->create_file_from_string([
                'contextid' => $context->id,
                'component' => 'mod_pdfworkspace',
                'filearea' => 'content',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $name,
            ], '%PDF-1.4 ' . $name);
        }
        $documents = array_values(pdfworkspace_get_documents($activity->id, $context->id));
        $this->assertCount(2, $documents);
        $typeid = $DB->get_field('pdfworkspace_annotationtypes', 'id', ['name' => 'pin'], MUST_EXIST);
        foreach ($documents as $document) {
            $annotationid = $DB->insert_record('pdfworkspace_annotations', (object)[
                'pdfworkspaceid' => $activity->id,
                'documentid' => $document->id,
                'page' => 1,
                'userid' => $USER->id,
                'annotationtypeid' => $typeid,
                'data' => '{}',
                'audience' => 'public',
                'timecreated' => time(),
            ]);
            $DB->insert_record('pdfworkspace_comments', (object)[
                'pdfworkspaceid' => $activity->id,
                'annotationid' => $annotationid,
                'userid' => $USER->id,
                'content' => $document->filename,
                'timecreated' => time(),
                'timemodified' => time(),
                'visibility' => 'public',
                'isquestion' => 1,
            ]);
        }
        foreach ($documents as $document) {
            $questions = \pdfworkspace_comment::get_questions($activity->id, 1, $context, $document->id);
            $this->assertCount(1, $questions);
            $this->assertSame($document->filename, reset($questions)->content);
        }
        $this->assertSame($documents[0]->filename,
            pdfworkspace_validate_draft_documents($activity->id, $context->id, []));
    }
}
