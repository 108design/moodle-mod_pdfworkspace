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
 * Defining elements of the creation form for plugin
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/pdfworkspace/lib.php');
require_once($CFG->libdir . '/filelib.php');

class mod_pdfworkspace_mod_form extends moodleform_mod {

    public function definition() {

        global $CFG, $USER, $COURSE, $DB, $PAGE;
        $mform =& $this->_form;
        $config = get_config('mod_pdfworkspace');
        $PAGE->requires->css(new moodle_url('/mod/pdfworkspace/shared/form.css', ['v' => '2026100200-2']));
        $PAGE->requires->js(new moodle_url('/mod/pdfworkspace/shared/document-settings.js', ['v' => '2026100200-1']));
        $PAGE->requires->js(new moodle_url('/mod/pdfworkspace/shared/tooltips.js', ['v' => '2026092912']));

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->setType('general', PARAM_TEXT);
        $mform->addElement('text', 'name', get_string('setting_alternative_name', 'pdfworkspace'), array('size' => '48'));
        $mform->addHelpButton('name', 'setting_alternative_name', 'pdfworkspace');
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Description.
        $this->standard_intro_elements();

        $element = $mform->getElement('introeditor');
        $attributes = $element->getAttributes();
        $attributes['rows'] = 5;
        $element->setAttributes($attributes);

        // Existing PDFs stay outside the editable draft area. A locked PDF has
        // no clickable filemanager row or delete action.
        if ($this->current->instance) {
            require_once(__DIR__ . '/locallib.php');
            $documents = pdfworkspace_get_documents($this->current->instance, $this->context->id);
            uasort($documents, static function($a, $b) {
                return [$a->exportorder, $a->sortorder, $a->id] <=> [$b->exportorder, $b->sortorder, $b->id];
            });
            $mform->addElement('header', 'existingdocuments', get_string('documentsupload', 'pdfworkspace'));
            $trash = html_writer::tag('span', '', ['class' => 'fa fa-trash', 'aria-hidden' => 'true']);
            $header = html_writer::div($trash, '', ['title' => get_string('removedocument', 'pdfworkspace'),
                'aria-label' => get_string('removedocument', 'pdfworkspace')]);
            foreach (['document', 'workspaceinclude', 'workspaceorder'] as $key) {
                $header .= html_writer::div(get_string($key, 'pdfworkspace'));
            }
            $mform->addElement('static', 'documentcolumns', '',
                html_writer::div($header, 'pdfworkspace-document-columns', ['aria-hidden' => 'true']));
            $position = 0;
            foreach ($documents as $document) {
                $hasannotations = $DB->record_exists('pdfworkspace_annotations', ['documentid' => $document->id]);
                $title = $document->displayname ?: $document->filename;
                $tooltip = get_string($hasannotations ? 'documentlockedshort' : 'removedocument', 'pdfworkspace');
                $attributes = ['title' => $tooltip, 'aria-label' => $tooltip . ': ' . $title];
                if ($hasannotations) {
                    $attributes['disabled'] = 'disabled';
                }
                $remove = 'removedocument_' . $document->id;
                $include = 'exportincluded_' . $document->id;
                $order = 'exportorder_' . $document->id;
                $elements = [
                    $mform->createElement('advcheckbox', $remove, '', '', $attributes, [0, 1]),
                    $mform->createElement('static', 'documentname_' . $document->id, '',
                        html_writer::tag('span', s($title), ['class' => 'pdfworkspace-document-filename',
                            'title' => $document->filename])),
                    $mform->createElement('advcheckbox', $include, '', '',
                        ['title' => get_string('workspaceinclude', 'pdfworkspace'),
                            'aria-label' => get_string('workspaceinclude', 'pdfworkspace') . ': ' . $title], [0, 1]),
                    $mform->createElement('text', $order, '', ['type' => 'number', 'min' => 1,
                        'max' => count($documents), 'size' => 3,
                        'aria-label' => get_string('workspaceorder', 'pdfworkspace') . ': ' . $title]),
                ];
                $mform->addGroup($elements, 'documentrow_' . $document->id, '', '', false);
                $mform->setType($remove, PARAM_BOOL);
                $mform->setType($include, PARAM_BOOL);
                $mform->setType($order, PARAM_INT);
                $mform->setDefault($include, (int)$document->exportincluded);
                $mform->setDefault($order, ++$position);
            }
            $mform->addElement('static', 'workspaceexplanation', '', get_string('workspacefiles_help', 'pdfworkspace'));
            $mform->addElement('static', 'workspaceorderlabels', '', html_writer::div('', 'pdfworkspace-order-labels', [
                'data-up' => get_string('moveup'), 'data-down' => get_string('movedown'),
                'data-drag' => get_string('workspacemove', 'pdfworkspace'),
                'data-order' => get_string('workspaceorder', 'pdfworkspace'), 'aria-live' => 'polite',
            ]));
        }

        // Add a filemanager for new uploads only when editing.
        // $fileoptions = array('subdirs' => 0, 'maxbytes' => 0, 'areamaxbytes' => 10485760, 'maxfiles' => 1,
        // 'accepted_types' => '.pdf', 'return_types' => 1 | 2);
        // FILE_INTERNAL | FILE_EXTERNAL was replaced by 1|2, because moodle doesnt't identify FILE_INTERNAL, FILE_EXTERNAL here.
        $filemanageroptions = array();
        $filemanageroptions['accepted_types'] = '.pdf';
        $filemanageroptions['subdirs'] = 0;
        $filemanageroptions['maxbytes'] = 0;
        $filemanageroptions['maxfiles'] = -1;
        $filemanageroptions['mainfile'] = false;

        $mform->addElement('filemanager', 'files', get_string($this->current->instance ?
            'documentsadd' : 'documentsupload', 'pdfworkspace'), null,
            $filemanageroptions); // Params: 1. type of the element, 2. (html) elementname, 3. label.
        $mform->addHelpButton('files', 'documentsupload', 'pdfworkspace');

        $mform->addElement('header', 'audiencepermissionheader',
            get_string('audiencepermissions', 'pdfworkspace'));
        $mform->addElement('static', 'audienceexplanation', '',
            get_string('audiencepermissions_help', 'pdfworkspace'));
        $audienceoptions = [
            'audience_student_private' => 1,
            'audience_student_protected' => 1,
            'audience_student_public' => 0,
            'audience_staff_private' => 1,
            'audience_staff_targeted' => 1,
            'audience_staff_public' => 1,
        ];
        foreach ($audienceoptions as $name => $default) {
            $mform->addElement('advcheckbox', $name, get_string($name, 'pdfworkspace'), null, null, [0, 1]);
            $mform->setType($name, PARAM_BOOL);
            $mform->setDefault($name, $default);
        }
        $mform->addElement('select', 'studentdefaultaudience',
            get_string('audiencedefault_student', 'pdfworkspace'), [
                'private' => get_string('audience_student_private', 'pdfworkspace'),
                'protected' => get_string('audience_student_protected', 'pdfworkspace'),
                'public' => get_string('audience_student_public', 'pdfworkspace'),
            ]);
        $mform->setType('studentdefaultaudience', PARAM_ALPHA);
        $mform->setDefault('studentdefaultaudience', 'protected');
        $mform->addElement('select', 'staffdefaultaudience',
            get_string('audiencedefault_staff', 'pdfworkspace'), [
                'private' => get_string('audience_staff_private', 'pdfworkspace'),
                'targeted' => get_string('audience_staff_targeted', 'pdfworkspace'),
                'public' => get_string('audience_staff_public', 'pdfworkspace'),
            ]);
        $mform->setType('staffdefaultaudience', PARAM_ALPHA);
        $mform->setDefault('staffdefaultaudience', 'private');

        $mform->addElement('header', 'featureheader', get_string('annotationfeatures', 'pdfworkspace'));

        $mform->addElement('advcheckbox', 'usevotes', get_string('setting_usevotes', 'pdfworkspace'),
            get_string('usevotes', 'pdfworkspace'), null, array(0, 1));
        $mform->setType('usevotes', PARAM_BOOL);
        $mform->setDefault('usevotes', $config->usevotes);
        $mform->addHelpButton('usevotes', 'setting_usevotes', 'pdfworkspace');

        $mform->addElement('advcheckbox', 'use_studenttextbox', get_string('setting_use_studenttextbox', 'pdfworkspace'),
                get_string('use_studenttextbox', 'pdfworkspace'), null, array(0, 1));
        $mform->setType('use_studenttextbox', PARAM_BOOL);
        $mform->setDefault('use_studenttextbox', $config->use_studenttextbox);
        $mform->addHelpButton('use_studenttextbox', 'setting_use_studenttextbox', 'pdfworkspace');

        $mform->addElement('advcheckbox', 'use_studentdrawing', get_string('setting_use_studentdrawing', 'pdfworkspace'),
                get_string('use_studentdrawing', 'pdfworkspace'), null, array(0, 1));
        $mform->setType('use_studentdrawing', PARAM_BOOL);
        $mform->setDefault('use_studentdrawing', $config->use_studentdrawing);
        $mform->addHelpButton('use_studentdrawing', 'setting_use_studentdrawing', 'pdfworkspace');

        // XXX second checkbox or change to select.
        $mform->addElement('advcheckbox', 'useprint', get_string('setting_useprint_document', 'pdfworkspace'),
            get_string('useprint', 'pdfworkspace'), null, array(0, 1));
        $mform->setType('useprint', PARAM_BOOL);
        $mform->setDefault('useprint', $config->useprint);
        $mform->addHelpButton('useprint', 'setting_useprint_document', 'pdfworkspace');

        $mform->addElement('advcheckbox', 'useprintcomments', get_string('setting_useprint_comments', 'pdfworkspace'),
            get_string('useprint_comments', 'pdfworkspace'), null, array(0, 1));
        $mform->setType('useprintcomments', PARAM_BOOL);
        $mform->setDefault('useprintcomments', $config->useprintcomments);
        $mform->addHelpButton('useprintcomments', 'setting_useprint_comments', 'pdfworkspace');

        $mform->addElement('advcheckbox', 'usecombineddownload',
            get_string('setting_usecombineddownload', 'pdfworkspace'),
            get_string('usecombineddownload', 'pdfworkspace'), null, [0, 1]);
        $mform->setType('usecombineddownload', PARAM_BOOL);
        $mform->setDefault('usecombineddownload', $config->usecombineddownload ?? 1);
        $mform->addHelpButton('usecombineddownload', 'setting_usecombineddownload', 'pdfworkspace');

        foreach (['useworkspacedownload', 'useworkspacecomments'] as $setting) {
            $mform->addElement('advcheckbox', $setting, get_string('setting_' . $setting, 'pdfworkspace'),
                get_string($setting, 'pdfworkspace'), null, [0, 1]);
            $mform->setType($setting, PARAM_BOOL);
            $mform->setDefault($setting, 0);
            $mform->addHelpButton($setting, 'setting_' . $setting, 'pdfworkspace');
        }

        // Add legacy files flag only if used.
        if (isset($this->current->legacyfiles) && $this->current->legacyfiles != RESOURCELIB_LEGACYFILES_NO) {
            $options = array(RESOURCELIB_LEGACYFILES_DONE => get_string('legacyfilesdone', 'pdfworkspace'),
                RESOURCELIB_LEGACYFILES_ACTIVE => get_string('legacyfilesactive', 'pdfworkspace'));
            $mform->addElement('select', 'legacyfiles', get_string('legacyfiles', 'pdfworkspace'), $options);
        }
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
        // -------------------------------------------------------
        $mform->addElement('hidden', 'revision'); // Hard-coded as 1; should be changed if version becomes important.
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
    }

    // Loads the old file in the filemanager.
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            $studentmask = isset($defaultvalues['studentaudiences']) ? (int)$defaultvalues['studentaudiences'] : 3;
            $staffmask = isset($defaultvalues['staffaudiences']) ? (int)$defaultvalues['staffaudiences'] : 3;
            foreach (['private' => 1, 'protected' => 2, 'public' => 4] as $name => $bit) {
                $defaultvalues['audience_student_' . $name] = (int)(bool)($studentmask & $bit);
            }
            foreach (['private' => 1, 'targeted' => 2, 'public' => 4] as $name => $bit) {
                $defaultvalues['audience_staff_' . $name] = (int)(bool)($staffmask & $bit);
            }
        }
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('files');
            $defaultvalues['files'] = $draftitemid;
        }
    }

    public function validation($data, $files) {
        global $USER, $DB;

        $errors = parent::validation($data, $files);

        foreach (['student' => ['private', 'protected', 'public'],
            'staff' => ['private', 'targeted', 'public']] as $role => $audiences) {
            $enabled = false;
            foreach ($audiences as $audience) {
                $enabled = $enabled || !empty($data['audience_' . $role . '_' . $audience]);
            }
            if (!$enabled) {
                $errors['audience_' . $role . '_' . $audiences[0]] =
                    get_string('error:audiencenone', 'pdfworkspace');
            }
            if (empty($data['audience_' . $role . '_' . ($data[$role . 'defaultaudience'] ?? '')])) {
                $errors[$role . 'defaultaudience'] = get_string('error:audiencedefault', 'pdfworkspace');
            }
        }

        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $data['files'], 'sortorder, id', false);
        $files = $draftfiles ?: [];
        if ($this->current->instance) {
            require_once(__DIR__ . '/locallib.php');
            $documents = pdfworkspace_get_documents($this->current->instance, $this->context->id);
            $kept = 0;
            $names = [];
            foreach ($documents as $document) {
                $order = $data['exportorder_' . $document->id] ?? 0;
                if ($order < 1 || $order > count($documents)) {
                    $errors['documentrow_' . $document->id] = get_string('error:workspaceorder', 'pdfworkspace');
                }
                $remove = !empty($data['removedocument_' . $document->id]);
                if ($remove && $DB->record_exists('pdfworkspace_annotations', ['documentid' => $document->id])) {
                    $errors['files'] = get_string('documentannotatedimmutable', 'pdfworkspace', $document->filename);
                }
                if (!$remove) {
                    $kept++;
                    $names[$document->filepath . $document->filename] = true;
                }
            }
            foreach ($files as $file) {
                if (isset($names[$file->get_filepath() . $file->get_filename()])) {
                    $errors['files'] = get_string('documentalreadyexists', 'pdfworkspace', $file->get_filename());
                }
            }
            if (!$kept && !$files) {
                $errors['files'] = get_string('required');
            }
        } else if (!$files) {
            $errors['files'] = get_string('required');
            return $errors;
        }
        if (count($files) == 1) {
            // No need to select main file if only one picked.
            return $errors;
        } else if (count($files) > 1) {
            $mainfile = false;
            foreach ($files as $file) {
                if ($file->get_sortorder() == 1) {
                    $mainfile = true;
                    break;
                }
            }
            // Set a default main file.
            if (!$mainfile) {
                $file = reset($files);
                file_set_sortorder($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(),
                                   $file->get_filepath(), $file->get_filename(), 1);
            }
        }
        return $errors;
    }

}
