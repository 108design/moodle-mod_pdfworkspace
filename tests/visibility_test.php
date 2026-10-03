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
 * Audience isolation for PDF annotations and comments.
 *
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace;

defined('MOODLE_INTERNAL') || die();

class visibility_test extends \advanced_testcase {
    public function test_participant_teacher_and_targeted_audiences(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $otherstudent = $generator->create_user();
        $teacher = $generator->create_user();
        $otherteacher = $generator->create_user();
        foreach ([$student, $otherstudent] as $user) {
            $generator->enrol_user($user->id, $course->id, 'student');
        }
        foreach ([$teacher, $otherteacher] as $user) {
            $generator->enrol_user($user->id, $course->id, 'teacher');
        }
        $activity = $generator->create_module('pdfworkspace', ['course' => $course->id]);
        $context = \context_module::instance($activity->cmid);
        $newaudience = visibility::new_annotation_audience($activity, $context, $student->id,
            '', $otherstudent->id);
        $this->assertSame(['protected', null], $newaudience);
        $this->assertSame(['targeted', (int)$student->id], visibility::new_annotation_audience(
            $activity, $context, $teacher->id, 'targeted', $student->id));
        $this->expectException(\invalid_parameter_exception::class);
        visibility::new_annotation_audience($activity, $context, $teacher->id, 'targeted', $otherteacher->id);
    }

    public function test_activity_controls_audiences_for_each_role(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $teacher = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'teacher');
        $activity = $generator->create_module('pdfworkspace',
            ['course' => $course->id, 'studentaudiences' => 7, 'staffaudiences' => 7]);
        $context = \context_module::instance($activity->cmid);

        $this->assertSame(['protected', 'private', 'public'], visibility::allowed_audiences($activity, false));
        $this->assertSame(['private', 'targeted', 'public'], visibility::allowed_audiences($activity, true));
        $this->assertSame(['public', null], visibility::new_annotation_audience(
            $activity, $context, $student->id, 'public', 0));
        $this->assertSame(['public', null], visibility::new_annotation_audience(
            $activity, $context, $teacher->id, 'public', 0));
        $this->assertSame('public', visibility::thread_visibility('public'));
        $this->assertSame('protected', visibility::thread_visibility('targeted'));

        $activity->studentaudiences = 2;
        $activity->staffaudiences = 2;
        foreach ([$student->id, $teacher->id] as $userid) {
            try {
                visibility::new_annotation_audience($activity, $context, $userid, 'public', 0);
                $this->fail('Disallowed public audience was accepted');
            } catch (\invalid_parameter_exception $ignored) {
                // The activity setting must be enforced independently of the toolbar.
            }
        }
    }

    public function test_comment_and_standalone_annotation_visibility(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $otherstudent = $generator->create_user();
        $teacher = $generator->create_user();
        $otherteacher = $generator->create_user();
        foreach ([$student, $otherstudent] as $user) {
            $generator->enrol_user($user->id, $course->id, 'student');
        }
        foreach ([$teacher, $otherteacher] as $user) {
            $generator->enrol_user($user->id, $course->id, 'teacher');
        }
        $activity = $generator->create_module('pdfworkspace', ['course' => $course->id]);
        $context = \context_module::instance($activity->cmid);
        $typeid = $DB->get_field('pdfworkspace_annotationtypes', 'id', ['name' => 'pin'], MUST_EXIST);
        $insertannotation = function ($owner, $audience, $recipientid = null) use ($DB, $activity, $typeid) {
            $record = (object)[
                'pdfworkspaceid' => $activity->id,
                'page' => 1,
                'userid' => $owner,
                'annotationtypeid' => $typeid,
                'data' => '{}',
                'audience' => $audience,
                'recipientid' => $recipientid,
                'timecreated' => time(),
            ];
            $record->id = $DB->insert_record('pdfworkspace_annotations', $record);
            return $record;
        };

        $studentannotation = $insertannotation($student->id, 'protected');
        $this->assertTrue(visibility::can_view_annotation($studentannotation, $context, $student->id));
        $this->assertTrue(visibility::can_view_annotation($studentannotation, $context, $teacher->id));
        $this->assertFalse(visibility::can_view_annotation($studentannotation, $context, $otherstudent->id));

        $targeted = $insertannotation($teacher->id, 'targeted', $student->id);
        $this->assertTrue(visibility::can_view_annotation($targeted, $context, $teacher->id));
        $this->assertTrue(visibility::can_view_annotation($targeted, $context, $student->id));
        $this->assertFalse(visibility::can_view_annotation($targeted, $context, $otherstudent->id));
        $this->assertFalse(visibility::can_view_annotation($targeted, $context, $otherteacher->id));

        $private = $insertannotation($teacher->id, 'private');
        $this->assertTrue(visibility::can_view_annotation($private, $context, $teacher->id));
        $this->assertFalse(visibility::can_view_annotation($private, $context, $student->id));
        $this->assertFalse(visibility::can_view_annotation($private, $context, $otherteacher->id));

        $question = (object)[
            'pdfworkspaceid' => $activity->id,
            'annotationid' => $studentannotation->id,
            'userid' => $student->id,
            'content' => 'Question',
            'timecreated' => time(),
            'timemodified' => time(),
            'visibility' => 'protected',
            'isquestion' => 1,
        ];
        $question->id = $DB->insert_record('pdfworkspace_comments', $question);
        $this->assertTrue(visibility::can_view_comment($question, $context, $student->id));
        $this->assertTrue(visibility::can_view_comment($question, $context, $teacher->id));
        $this->assertFalse(visibility::can_view_comment($question, $context, $otherstudent->id));

        $targetedquestion = clone $question;
        unset($targetedquestion->id);
        $targetedquestion->annotationid = $targeted->id;
        $targetedquestion->userid = $teacher->id;
        $targetedquestion->id = $DB->insert_record('pdfworkspace_comments', $targetedquestion);
        $this->assertTrue(visibility::can_view_comment($targetedquestion, $context, $student->id));
        $this->assertFalse(visibility::can_view_comment($targetedquestion, $context, $otherteacher->id));

        $public = $insertannotation($student->id, 'public');
        $publicquestion = clone $question;
        unset($publicquestion->id);
        $publicquestion->annotationid = $public->id;
        $publicquestion->visibility = 'public';
        $publicquestion->id = $DB->insert_record('pdfworkspace_comments', $publicquestion);
        $this->assertTrue(visibility::can_view_comment($publicquestion, $context, $otherstudent->id));
        $this->assertTrue(visibility::can_view_comment($publicquestion, $context, $teacher->id));
        $privateanswer = clone $publicquestion;
        unset($privateanswer->id);
        $privateanswer->isquestion = 0;
        $privateanswer->userid = $teacher->id;
        $privateanswer->visibility = 'private';
        $privateanswer->id = $DB->insert_record('pdfworkspace_comments', $privateanswer);
        $this->assertTrue(visibility::can_view_comment($privateanswer, $context, $teacher->id));
        $this->assertFalse(visibility::can_view_comment($privateanswer, $context, $student->id));
    }
}
