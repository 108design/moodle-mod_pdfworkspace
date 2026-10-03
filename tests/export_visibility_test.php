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
 * Exported discussions must follow the viewer's audience and moderation rights.
 *
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pdfworkspace/model/pdfworkspace.php');

class export_visibility_test extends \advanced_testcase {
    public function test_hidden_comment_content_is_masked_in_export(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $teacher = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'teacher');
        $activity = $generator->create_module('pdfworkspace', ['course' => $course->id]);
        $context = \context_module::instance($activity->cmid);
        $typeid = $DB->get_field('pdfworkspace_annotationtypes', 'id', ['name' => 'pin'], MUST_EXIST);
        $annotationid = $DB->insert_record('pdfworkspace_annotations', (object)[
            'pdfworkspaceid' => $activity->id,
            'page' => 1,
            'userid' => $student->id,
            'annotationtypeid' => $typeid,
            'data' => '{}',
            'audience' => 'public',
            'timecreated' => time(),
        ]);
        $DB->insert_record('pdfworkspace_comments', (object)[
            'pdfworkspaceid' => $activity->id,
            'annotationid' => $annotationid,
            'userid' => $student->id,
            'content' => 'Question secret',
            'timecreated' => time(),
            'timemodified' => time(),
            'visibility' => 'public',
            'isquestion' => 1,
            'ishidden' => 1,
        ]);
        $DB->insert_record('pdfworkspace_comments', (object)[
            'pdfworkspaceid' => $activity->id,
            'annotationid' => $annotationid,
            'userid' => $teacher->id,
            'content' => 'Answer secret',
            'timecreated' => time(),
            'timemodified' => time(),
            'visibility' => 'public',
            'isquestion' => 0,
            'ishidden' => 1,
        ]);

        $this->setUser($student);
        $posts = \pdfworkspace_instance::get_conversations($activity->id, $context);
        $this->assertCount(1, $posts);
        $this->assertSame(get_string('hiddenComment', 'pdfworkspace'), $posts[0]->answeredquestion);
        $this->assertSame(get_string('hiddenComment', 'pdfworkspace'), reset($posts[0]->answers)->answer);
        $this->assertStringNotContainsString('secret', json_encode($posts));

        $this->setAdminUser();
        $posts = \pdfworkspace_instance::get_conversations($activity->id, $context);
        $this->assertSame('Question secret', $posts[0]->answeredquestion);
        $this->assertSame('Answer secret', reset($posts[0]->answers)->answer);
    }
}
