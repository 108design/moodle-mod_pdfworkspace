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
 * Upload one restricted PDF without adding it to the regular document library.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace\form;

use mod_pdfworkspace\local\restricted_renderer;

/** Native Moodle form bound to the user-owned upload draft. */
final class restricted_upload extends \moodleform {
    /** Define the title and PDF picker. */
    protected function definition(): void {
        global $CFG;
        $form = $this->_form;
        $form->addElement('text', 'title', get_string('restricteddocumenttitle', 'mod_pdfworkspace'), ['maxlength' => 160]);
        $form->setType('title', PARAM_TEXT);
        $form->addRule('title', get_string('required'), 'required', null, 'client');
        $form->addElement('filepicker', 'documentfile', get_string('restrictedpdf', 'mod_pdfworkspace'), null, [
            'maxbytes' => get_max_upload_file_size($CFG->maxbytes, $this->_customdata['coursemaxbytes'], restricted_renderer::MAX_BYTES),
            'accepted_types' => '.pdf',
            'return_types' => FILE_INTERNAL,
        ]);
        $form->addRule('documentfile', get_string('required'), 'required', null, 'client');
        $form->addHelpButton('documentfile', 'restrictedpdf', 'mod_pdfworkspace');
        $this->add_action_buttons(true, get_string('restrictedadd', 'mod_pdfworkspace'));
    }

    /**
     * Validate the title and the submitted draft before it can be rendered.
     *
     * @param array $data Submitted field values
     * @param array $files Uploaded files as received by Moodle forms
     * @return array Field errors
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $title = trim((string)($data['title'] ?? ''));
        if ($title === '' || \core_text::strlen($title) > 160) {
            $errors['title'] = get_string('restrictedinvalidtitle', 'mod_pdfworkspace');
        }
        if (!$this->selected_file()) {
            $errors['documentfile'] = get_string('restrictedinvalidpdf', 'mod_pdfworkspace');
        }
        return $errors;
    }

    /**
     * Obtain exactly one local file from the current user's upload draft.
     *
     * @return \stored_file|null
     */
    public function selected_file(): ?\stored_file {
        $files = $this->get_draft_files('documentfile');
        if (!$files || count($files) !== 1) {
            return null;
        }
        return reset($files);
    }
}
