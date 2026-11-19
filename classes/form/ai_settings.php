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
 * Course and workspace settings for question analysis.
 *
 * @package    mod_pdfworkspace
 * @copyright  2026 Andreas Giesen <andreas@108design.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/** Edit the settings available to the current user. */
final class ai_settings extends \moodleform {
    /** Define the controls for the caller's permitted scopes. */
    public function definition(): void {
        $form = $this->_form;
        $form->addElement('hidden', 'id', $this->_customdata['cmid']);
        $form->setType('id', PARAM_INT);
        if ($this->_customdata['courseeditable']) {
            $form->addElement('advcheckbox', 'courseoptin', get_string('aiquestions_course', 'mod_pdfworkspace'));
            $form->addHelpButton('courseoptin', 'aiquestions_course', 'mod_pdfworkspace');
        }
        if ($this->_customdata['workspaceeditable']) {
            $form->addElement('advcheckbox', 'workspaceoptin', get_string('aiquestions_workspace', 'mod_pdfworkspace'));
            $form->addHelpButton('workspaceoptin', 'aiquestions_workspace', 'mod_pdfworkspace');
        }
        if ($this->_customdata['canmanageworkspace'] && !$this->_customdata['courseenabled']) {
            $label = $this->_customdata['workspaceeditable'] ? '' : get_string('aiquestions_workspace', 'mod_pdfworkspace');
            $form->addElement('static', 'workspacestatus', $label, get_string('aiquestions_course_required', 'mod_pdfworkspace'));
        }
        if ($this->_customdata['courseeditable'] || $this->_customdata['workspaceeditable']) {
            $this->add_action_buttons(true);
        }
    }
}
