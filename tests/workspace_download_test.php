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
 * Independent workspace download rights and document selection.
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_pdfworkspace;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/pdfworkspace/locallib.php');

class workspace_download_test extends \advanced_testcase {
    public function test_participant_flags_and_capabilities_are_independent(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $activity = $this->getDataGenerator()->create_module('pdfworkspace', ['course' => $course->id]);
        $context = \context_module::instance($activity->cmid);
        $this->setUser($student);
        $activity->useworkspacedownload = 1;
        $activity->useworkspacecomments = 0;
        $this->assertTrue(pdfworkspace_can_download_workspace($activity, $context));
        $this->assertFalse(pdfworkspace_can_download_workspace($activity, $context, true));
        $activity->useworkspacecomments = 1;
        $activity->useworkspacedownload = 0;
        $this->assertFalse(pdfworkspace_can_download_workspace($activity, $context));
        $this->assertTrue(pdfworkspace_can_download_workspace($activity, $context, true));
        global $DB;
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        assign_capability('mod/pdfworkspace:downloadworkspacecomments', CAP_PROHIBIT, $roleid, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->assertFalse(pdfworkspace_can_download_workspace($activity, $context, true));
        $this->setAdminUser();
        $activity->useworkspacecomments = 0;
        $this->assertTrue(pdfworkspace_can_download_workspace($activity, $context, true));
    }

    public function test_selection_keeps_tab_order_and_ignores_other_activity_ids(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('pdfworkspace', ['course' => $course->id]);
        $other = $this->getDataGenerator()->create_module('pdfworkspace', ['course' => $course->id]);
        $ids = [];
        foreach ([$activity->id, $activity->id, $activity->id, $other->id] as $index => $activityid) {
            $ids[] = $DB->insert_record('pdfworkspace_documents', (object)[
                'pdfworkspaceid' => $activityid, 'filepath' => '/', 'filename' => 'test-' . $index . '.pdf',
                'contenthash' => str_repeat('0', 40), 'sortorder' => $index,
                'exportorder' => $index, 'exportincluded' => 0,
            ]);
        }
        $data = (object)['id' => $activity->id, 'coursemodule' => $activity->cmid];
        foreach ($ids as $index => $id) {
            $data->{'exportorder_' . $id} = 4 - $index;
            $data->{'exportincluded_' . $id} = $index !== 1;
        }
        pdfworkspace_save_workspace_selection($data);
        $this->assertSame([$ids[2], $ids[0]], array_keys(pdfworkspace_workspace_documents($activity->id)));
        $this->assertEquals(0, $DB->get_field('pdfworkspace_documents', 'exportincluded', ['id' => $ids[3]]));
        $this->assertEquals(0, $DB->get_field('pdfworkspace_documents', 'sortorder', ['id' => $ids[0]]));
        $this->assertEquals(2, $DB->get_field('pdfworkspace_documents', 'sortorder', ['id' => $ids[2]]));
    }
}
