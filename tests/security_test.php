<?php
/**
 * This file is part of Moodle - https://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <https://www.gnu.org/licenses/>.
 *
 */
// This file is part of Moodle - https://moodle.org/
// Moodle is free software under the GNU GPL v3 or later.
// See <https://www.gnu.org/licenses/> for the full licence and warranty disclaimer.

namespace mod_pdfworkspace;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/pdfworkspace/model/comment.class.php');

/**
 * Real comment responses, persistence and local print files at the content boundary.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class security_test extends \advanced_testcase {
    /** Build a public question and two synthetic course participants. */
    private function fixture(): array {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $other = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($other->id, $course->id, 'student');
        $activity = $generator->create_module('pdfworkspace', ['course' => $course->id]);
        $context = \context_module::instance($activity->cmid);
        $annotationid = $DB->insert_record('pdfworkspace_annotations', (object)[
            'pdfworkspaceid' => $activity->id, 'documentid' => 0, 'page' => 1,
            'userid' => $student->id,
            'annotationtypeid' => $DB->get_field('pdfworkspace_annotationtypes', 'id', ['name' => 'pin'], MUST_EXIST),
            'data' => '{}', 'audience' => 'public', 'timecreated' => time(),
        ]);
        $question = (object)[
            'pdfworkspaceid' => $activity->id, 'annotationid' => $annotationid, 'userid' => $student->id,
            'content' => '<strong>Visible question</strong>', 'visibility' => 'public', 'isquestion' => 1,
            'timecreated' => time(), 'timemodified' => time(), 'ishidden' => 0, 'isdeleted' => 0,
        ];
        $question->id = $DB->insert_record('pdfworkspace_comments', $question);
        return [$activity, $context, $student, $other, $question];
    }

    public function test_all_questions_masks_moderated_text_and_keeps_privileged_access(): void {
        global $DB;
        [$activity, $context, $student, $other, $question] = $this->fixture();
        $DB->set_field('pdfworkspace_comments', 'ishidden', 1, ['id' => $question->id]);
        $this->setUser($other);
        $all = \pdfworkspace_comment::get_all_questions($activity->id, $context, 0);
        $this->assertStringNotContainsString('Visible question', json_encode($all));
        $this->assertSame(get_string('hiddenComment', 'pdfworkspace'), $all[1][0]->content);
        $thread = \pdfworkspace_comment::read($activity->id, $question->annotationid, $context);
        $this->assertStringNotContainsString('Visible question', json_encode($thread));
        $this->setAdminUser();
        $all = \pdfworkspace_comment::get_all_questions($activity->id, $context, 0);
        $this->assertStringContainsString('Visible question', $all[1][0]->content);
        $DB->set_field('pdfworkspace_comments', 'isdeleted', 1, ['id' => $question->id]);
        $all = \pdfworkspace_comment::get_all_questions($activity->id, $context, 0);
        $this->assertStringNotContainsString('Visible question', json_encode($all));
        $this->assertStringContainsString(get_string('deletedComment', 'pdfworkspace'), $all[1][0]->content);
    }

    public function test_every_question_response_cleans_stored_legacy_html(): void {
        global $DB;
        [$activity, $context, $student, $other, $question] = $this->fixture();
        $DB->set_field('pdfworkspace_comments', 'content', '<b onclick="return false">Visible question</b>',
            ['id' => $question->id]);
        $this->setUser($other);
        $responses = [
            \pdfworkspace_comment::get_questions($activity->id, 1, $context, 0),
            \pdfworkspace_comment::get_all_questions($activity->id, $context, 0),
            \pdfworkspace_comment::get_questions_search($activity->id, 'Visible', $context, 0),
            \pdfworkspace_comment::read($activity->id, $question->annotationid, $context),
            \pdfworkspace_instance::get_conversations($activity->id, $context, 0),
        ];
        foreach ($responses as $response) {
            $json = json_encode($response);
            $this->assertStringContainsString('Visible question', $json);
            $this->assertStringNotContainsString('onclick', $json);
        }
    }

    public function test_create_and_update_clean_before_storing_comment_html(): void {
        global $DB;
        [$activity, $context, $student, $other, $question] = $this->fixture();
        $this->setUser($student);
        $cm = get_coursemodule_from_id('pdfworkspace', $activity->cmid);
        $_POST['pdfworkspace_addcomment_editoritemid'] = file_get_unused_draft_itemid();
        $_POST['pdfworkspace_editcomment_editoritemid'] = file_get_unused_draft_itemid();
        try {
            $created = \pdfworkspace_comment::create($activity->id, $question->annotationid,
                '<em onclick="return false">Saved reply</em>', 'public', 0, $cm, $context);
            $this->assertIsObject($created);
            $stored = $DB->get_field('pdfworkspace_comments', 'content', ['id' => $created->id]);
            $this->assertStringContainsString('Saved reply', $stored);
            $this->assertStringNotContainsString('onclick', $stored);
            $result = \pdfworkspace_comment::update($created->id,
                '<strong onclick="return false">Edited reply</strong>', false, $context);
            $this->assertSame('success', $result['status']);
            $stored = $DB->get_field('pdfworkspace_comments', 'content', ['id' => $created->id]);
            $this->assertStringContainsString('Edited reply', $stored);
            $this->assertStringNotContainsString('onclick', $stored);
        } finally {
            unset($_POST['pdfworkspace_addcomment_editoritemid'], $_POST['pdfworkspace_editcomment_editoritemid']);
        }
    }

    public function test_comment_formatting_preserves_enabled_moodle_filters(): void {
        [$activity, $context] = $this->fixture();
        filter_set_global_state('multilang', TEXTFILTER_ON);
        $text = '<span lang="en" class="multilang">English sample</span>'
            . '<span lang="de" class="multilang">Deutscher Beispieltext</span>';
        $formatted = \mod_pdfworkspace\local\comment_content::format($text, 0, $context);
        $this->assertStringContainsString('English sample', $formatted);
        $this->assertStringNotContainsString('Deutscher Beispieltext', $formatted);
    }

    public function test_print_embeds_only_the_exact_comments_own_file(): void {
        [$activity, $context, $student, $other, $question] = $this->fixture();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6G9sAAAAASUVORK5CYII=');
        $file = get_file_storage()->create_file_from_string([
            'contextid' => $context->id, 'component' => 'mod_pdfworkspace', 'filearea' => 'post',
            'itemid' => $question->id, 'filepath' => '/nested/', 'filename' => 'print.png', 'mimetype' => 'image/png',
        ], $png);
        $url = \moodle_url::make_pluginfile_url($context->id, 'mod_pdfworkspace', 'post',
            $question->id, '/nested/', 'print.png')->out(false);
        $fragments = \pdfworkspace_extract_images('<p>Before</p><img src="' . s($url) . '"><p>After</p>',
            $question->id, $context);
        $images = array_values(array_filter($fragments, 'is_array'));
        $this->assertCount(1, $images);
        $this->assertEquals($file->get_id(), $images[0]['fileid']);
        $this->assertSame($png, base64_decode(explode(',', $images[0]['image'], 2)[1]));
        $this->assertEquals(1, $images[0]['imagewidth']);
        $this->assertEquals(1, $images[0]['imageheight']);
        $spoof = get_file_storage()->create_file_from_string([
            'contextid' => $context->id, 'component' => 'mod_pdfworkspace', 'filearea' => 'post',
            'itemid' => $question->id, 'filepath' => '/', 'filename' => 'mismatch.jpg', 'mimetype' => 'image/jpeg',
        ], $png);
        $spoofurl = \moodle_url::make_pluginfile_url($context->id, 'mod_pdfworkspace', 'post',
            $question->id, '/', 'mismatch.jpg')->out(false);
        $spooffragments = \pdfworkspace_extract_images('<img src="' . s($spoofurl) . '" alt="Sample image">',
            $question->id, $context);
        $this->assertSame([], array_values(array_filter($spooffragments, 'is_array')));
        $this->assertContains('Sample image', $spooffragments);
        $foreign = \pdfworkspace_extract_images('<img src="' . s($url) . '">', $question->id + 1, $context);
        $this->assertSame([], array_values(array_filter($foreign, 'is_array')));
        $external = \pdfworkspace_extract_images('<img src="https://example.invalid/print.png">', $question->id, $context);
        $this->assertSame([], array_values(array_filter($external, 'is_array')));
        $this->assertContains('https://example.invalid/print.png', $external);
    }
}
