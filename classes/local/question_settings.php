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
 * Add the question-analysis opt-ins to the native Moodle forms.
 *
 * @package    mod_pdfworkspace
 * @copyright  2026 Andreas Giesen <andreas@108design.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace\local;

use core_course\hook\after_form_definition;
use core_course\hook\after_form_submission;
use core\lock\lock_config;

defined('MOODLE_INTERNAL') || die();

/** Render and persist the two scoped opt-in controls. */
final class question_settings {
    /** Field name used only in the native course settings form. */
    private const COURSE_FIELD = 'pdfworkspace_aicourse';
    /** Field name used only in the native activity settings form. */
    private const ACTIVITY_FIELD = 'pdfworkspace_aiactivity';

    /**
     * Insert a section in the native course edit form.
     *
     * @param after_form_definition $hook Moodle course form event
     */
    public static function course_form(after_form_definition $hook): void {
        $context = $hook->formwrapper->get_context();
        if (!$context instanceof \context_course || !question_analysis::site_enabled()
                || !has_capability('moodle/course:update', $context)) {
            return;
        }
        $form = $hook->mform;
        self::heading($form);
        self::checkbox($form, self::COURSE_FIELD, 'aiquestions_course',
            question_analysis::course_enabled($context->instanceid), linktopic: false);
    }

    /**
     * Save only the value supplied by an authorized editing form.
     *
     * @param after_form_submission $hook Validated Moodle course form submission
     */
    public static function save_course_form(after_form_submission $hook): void {
        $data = $hook->get_data();
        if ($hook->isnewcourse || !isset($data->{self::COURSE_FIELD}) || !question_analysis::site_enabled()) {
            return;
        }
        $context = \context_course::instance((int)$data->id);
        if (!has_capability('moodle/course:update', $context)) {
            return;
        }
        $courseid = (int)$context->instanceid;
        $lock = lock_config::get_lock_factory('mod_pdfworkspace')->get_lock('ai-course-' . $courseid, 5);
        if (!$lock) {
            throw new \moodle_exception('locktimeout');
        }
        try {
            question_analysis::write_course_opt_in($courseid, (bool)$data->{self::COURSE_FIELD});
        } finally {
            $lock->release();
        }
    }

    /**
     * Insert a section in the settings of an existing PDF Workspace.
     *
     * @param \MoodleQuickForm $form Native activity edit form
     * @param \context_module $context Activity context
     * @param int $workspaceid Existing activity instance
     */
    public static function activity_form(\MoodleQuickForm $form, \context_module $context, int $workspaceid): void {
        global $DB;
        if (!question_analysis::site_enabled() || !has_capability('moodle/course:manageactivities', $context)) {
            return;
        }
        $course = $context->get_course_context();
        $courseenabled = question_analysis::course_enabled($course->instanceid);
        $activityenabled = (bool)$DB->get_field('pdfworkspace', 'aiquestions', ['id' => $workspaceid], MUST_EXIST);
        self::heading($form);
        if ($courseenabled || $activityenabled) {
            self::checkbox($form, self::ACTIVITY_FIELD, 'aiquestions_workspace', $activityenabled);
        }
        if (!$courseenabled) {
            $message = get_string('aiquestions_course_required', 'mod_pdfworkspace');
            if (has_capability('moodle/course:update', $course)) {
                $url = new \moodle_url('/course/edit.php', ['id' => $course->instanceid], 'id_pdfworkspace_aiheading');
                $message .= ' ' . \html_writer::link($url, get_string('aiquestions_courselink', 'mod_pdfworkspace'));
            }
            $form->addElement('static', 'pdfworkspace_aistatus', '', $message);
        }
    }

    /**
     * Map the submitted value to the activity record after validating its parent opt-in and permission.
     *
     * @param \stdClass $data Validated or programmatic module update
     */
    public static function prepare_activity_update(\stdClass $data): void {
        $selection = $data->{self::ACTIVITY_FIELD} ?? null;
        unset($data->{self::ACTIVITY_FIELD}, $data->aiquestions);
        if ($selection === null || !question_analysis::site_enabled()) {
            return;
        }
        $cm = get_coursemodule_from_instance('pdfworkspace', (int)$data->instance, 0, false, MUST_EXIST);
        if (!has_capability('moodle/course:manageactivities', \context_module::instance($cm->id))) {
            return;
        }
        if (!$selection || question_analysis::course_enabled((int)$cm->course)) {
            $data->aiquestions = (int)(bool)$selection;
        }
    }

    /**
     * Expose the same section title in course and activity settings.
     *
     * @param \MoodleQuickForm $form Native Moodle form
     */
    private static function heading(\MoodleQuickForm $form): void {
        $form->addElement('header', 'pdfworkspace_aiheading', get_string('aiquestions', 'mod_pdfworkspace'));
        $form->setExpanded('pdfworkspace_aiheading', true);
    }

    /**
     * Render the explicit opt-in, its field help and a link to the full topic.
     *
     * @param \MoodleQuickForm $form Native Moodle form
     * @param string $field Submitted field
     * @param string $label Moodle string key
     * @param bool $enabled Current saved choice
     * @param bool $linktopic Link to the activity help panel, which is available on PDF Workspace pages
     */
    private static function checkbox(
        \MoodleQuickForm $form,
        string $field,
        string $label,
        bool $enabled,
        bool $linktopic = true,
    ): void {
        $form->addElement('advcheckbox', $field, get_string($label, 'mod_pdfworkspace'), null, null, [0, 1]);
        $form->setType($field, PARAM_BOOL);
        $form->setDefault($field, (int)$enabled);
        $form->addHelpButton($field, $label, 'mod_pdfworkspace');
        if ($linktopic) {
            $form->addElement('static', $field . '_help', '', admin_help::field_icon($field, 'aipilot'));
        }
    }
}
