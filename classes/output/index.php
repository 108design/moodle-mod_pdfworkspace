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
 * The purpose of this script is to collect the output data for the index.mustache template
 * and make it available to the renderer. The data is collected via the pdfworkspace model
 * and then processed. Therefore, class teacheroverview can be seen as a view controller.
 *
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @author    Anna Heynkes
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Description of index
 *
 * @author degroot
 */

namespace mod_pdfworkspace\output;

use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class index implements \renderable, \templatable {
    // Class should be placed elsewhere.

    private $usestudenttextbox;
    private $usestudentdrawing;
    private $useprint;
    private $useprintcomments;
    private $cancombineddownload;
    private $printurl;
    private $currentdocumentid;
    private $documenttabs;
    private $showaudience;
    private $staff;
    private $recipients;
    private $audiences;
    private $allowtargeted;
    private $downloadmenuhtml = '';

    public function __construct($pdfworkspace, $capabilities, $file) {
        global $USER, $PAGE, $OUTPUT;
        $this->usestudenttextbox = ($pdfworkspace->use_studenttextbox || $capabilities->usetextbox);
        $this->usestudentdrawing = ($pdfworkspace->use_studentdrawing || $capabilities->usedrawing);
        $this->useprint = ($pdfworkspace->useprint || $capabilities->useprint);
        $this->useprintcomments = ($pdfworkspace->useprintcomments || $capabilities->useprintcomments);
        $this->cancombineddownload = pdfworkspace_can_download_combined($pdfworkspace, $PAGE->context);
        $this->currentdocumentid = $pdfworkspace->currentdocumentid;
        $this->printurl = moodle_url::make_pluginfile_url(
            $file->get_contextid(), $file->get_component(), $file->get_filearea(),
            $file->get_itemid(), $file->get_filepath(), $file->get_filename(), true)->out(false);
        $python = get_config('mod_pdfworkspace', 'exportpython');
        $exportavailable = $python && is_file($python) && is_executable($python) && function_exists('proc_open');
        $menu = new \action_menu();
        $menu->set_menu_trigger(\html_writer::tag('i', '', ['class' => 'fa fa-download', 'aria-hidden' => 'true']) .
            ' ' . s(get_string('downloadmenu', 'pdfworkspace')));
        $hascurrent = $this->useprint || $this->useprintcomments || ($this->cancombineddownload && $exportavailable);
        if ($hascurrent) {
            $menu->add_secondary_action(\html_writer::tag('h6', get_string('downloadcurrentpdf', 'pdfworkspace'),
                ['class' => 'dropdown-header']));
        }
        if ($this->useprint) {
            $menu->add(new \action_menu_link_secondary(new moodle_url($this->printurl), new \pix_icon('f/pdf', ''),
                get_string('print', 'pdfworkspace')));
        }
        if ($this->useprintcomments) {
            $menu->add(new \action_menu_link_secondary(new moodle_url('#'), new \pix_icon('f/pdf', ''),
                get_string('export_comments_pdf_tooltip', 'pdfworkspace'), ['data-pdfworkspace-download' => 'comments-pdf']));
            $menu->add(new \action_menu_link_secondary(new moodle_url('#'), new \pix_icon('file-csv', '', 'mod_pdfworkspace'),
                get_string('export_comments_csv_tooltip', 'pdfworkspace'), ['data-pdfworkspace-download' => 'comments-csv']));
        }
        if ($this->cancombineddownload && $exportavailable) {
            $menu->add(new \action_menu_link_secondary(new moodle_url('/mod/pdfworkspace/download_annotated.php',
                ['id' => $PAGE->cm->id, 'doc' => $this->currentdocumentid]), new \pix_icon('f/pdf', ''),
                get_string('combinedexport', 'pdfworkspace')));
        }
        $hasworkspace = $exportavailable && pdfworkspace_workspace_documents($pdfworkspace->id) &&
            (pdfworkspace_can_download_workspace($pdfworkspace, $PAGE->context) ||
                pdfworkspace_can_download_workspace($pdfworkspace, $PAGE->context, true));
        if ($hasworkspace) {
            if ($hascurrent) {
                $menu->add_secondary_action(\html_writer::div('', 'dropdown-divider', ['role' => 'separator']));
            }
            $menu->add_secondary_action(\html_writer::tag('h6', get_string('downloadwholeworkspace', 'pdfworkspace'),
                ['class' => 'dropdown-header']));
            foreach ([false => 'workspacewithoutcomments', true => 'workspacewithcomments'] as $comments => $label) {
                if (pdfworkspace_can_download_workspace($pdfworkspace, $PAGE->context, (bool)$comments)) {
                    $menu->add(new \action_menu_link_secondary(new moodle_url('/mod/pdfworkspace/download_workspace.php',
                        ['id' => $PAGE->cm->id, 'comments' => $comments]), new \pix_icon('f/pdf', ''),
                        get_string($label, 'pdfworkspace')));
                }
            }
        }
        if ($hascurrent || $hasworkspace) {
            $this->downloadmenuhtml = $OUTPUT->render($menu);
        }
        $this->documenttabs = [];
        if (count($pdfworkspace->documents) > 1) {
            foreach ($pdfworkspace->documents as $document) {
                $editable = pdfworkspace_document_title_editable($document, $PAGE->cm->id,
                    has_capability('moodle/course:manageactivities', $PAGE->context));
                $this->documenttabs[] = [
                    'editablehtml' => $OUTPUT->render($editable),
                    'active' => (int)$document->id === (int)$this->currentdocumentid,
                ];
            }
        }
        $context = \context::instance_by_id($file->get_contextid());
        $this->staff = \mod_pdfworkspace\visibility::is_staff($context, $USER->id);
        $allowed = \mod_pdfworkspace\visibility::allowed_audiences($pdfworkspace, $this->staff);
        $role = $this->staff ? 'staff' : 'student';
        $defaultfield = $this->staff ? 'staffdefaultaudience' : 'studentdefaultaudience';
        $defaultaudience = isset($pdfworkspace->$defaultfield) &&
            in_array($pdfworkspace->$defaultfield, $allowed, true) ? $pdfworkspace->$defaultfield : reset($allowed);
        $this->showaudience = !empty($allowed);
        $this->audiences = [];
        foreach ($allowed as $audience) {
            $this->audiences[] = ['value' => $audience,
                'label' => get_string('audience_' . $role . '_' . $audience, 'pdfworkspace'),
                'selected' => $audience === $defaultaudience];
        }
        $this->allowtargeted = in_array('targeted', $allowed, true);
        $this->recipients = [];
        if ($this->staff && $this->allowtargeted) {
            foreach (get_enrolled_users($context, 'mod/pdfworkspace:view') as $user) {
                if (!\mod_pdfworkspace\visibility::is_staff($context, $user->id)) {
                    $this->recipients[] = ['id' => $user->id, 'name' => fullname($user)];
                }
            }
        }

    }

    public function export_for_template(\renderer_base $output) {
        global $OUTPUT, $PAGE;
        $url = $PAGE->url;
        $data = new stdClass();
        $data->usestudenttextbox = $this->usestudenttextbox;
        $data->hasdocumenttabs = !empty($this->documenttabs);
        $data->documenttabs = $this->documenttabs;
        $data->downloadmenuhtml = $this->downloadmenuhtml;
        $data->usestudentdrawing = $this->usestudentdrawing;
        $data->pixopenbook = $OUTPUT->image_url('openbook', 'mod_pdfworkspace');
        $data->pixsinglefile = $OUTPUT->image_url('/e/new_document');
        $data->showaudience = $this->showaudience;
        $data->staff = $this->staff;
        $data->recipients = $this->recipients;
        $data->audiences = $this->audiences;
        $data->allowtargeted = $this->allowtargeted;


        return $data;
    }
}
