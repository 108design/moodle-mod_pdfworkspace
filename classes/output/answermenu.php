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
 * Dropdown menu in answerstable on overview tab.
 *
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @author    Friederike Schwager
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace\output;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

class answermenu implements \renderable, \templatable {

    private $url;
    private $iconclass;
    private $label;
    private $buttonclass;

    /**
     * Constructor of renderable for dropdown menu in answerstable.
     * @param int $annotationid Id of the annotation the answer belongs to
     * @param bool $issubscribed Is the user subscribed to the question?
     * @param int $cmid Course module id
     * @param int $currentpage Page of the table on overviewpage
     * @param int $itemsperpage Number of entries on every page in the table
     * @param int $answerfilter Value of the filter for the answerstable
     */
    public function __construct($annotationid, $issubscribed, $cmid, $currentpage, $itemsperpage, $answerfilter) {

        global $CFG;
        if ($answerfilter == 0 && empty($issubscribed)) { // Show all answers and this answer is not subscribed.
            // No one size fits all.
            $urlparams = array('action' => 'subscribeQuestion');
            $iconclass = "icon fa fa-bell fa-fw";
            $label = get_string('subscribeQuestion', 'pdfworkspace');
            $buttonclass = 'comment-subscribe subscribe';
        } else { // Show answers to subscribed questions.
            $urlparams = array('action' => 'unsubscribeQuestion');
            $iconclass = "icon fa fa-bell-slash fa-fw";
            $label = get_string('unsubscribeQuestion', 'pdfworkspace');
            $buttonclass = 'comment-subscribe unsubscribe';
        }
        $urlparams['fromoverview'] = '1';
        $urlparams['id'] = $cmid;
        if ($answerfilter == 0) {
            $urlparams['page'] = $currentpage;
        } else {
            $urlparams['page'] = '0';
        }
        $urlparams['annotationid'] = $annotationid;
        $urlparams['itemsperpage'] = $itemsperpage;
        $urlparams['answerfilter'] = $answerfilter;
        $urlparams['sesskey'] = sesskey();
        $url = new moodle_url($CFG->wwwroot . '/mod/pdfworkspace/view.php', $urlparams);

        $this->url = $url;
        $this->iconclass = $iconclass;
        $this->label = $label;
        $this->buttonclass = $buttonclass;
    }

    /**
     * This function is required by any renderer to retrieve the data structure
     * passed into the template.
     * @param \renderer_base $output
     * @return type
     */
    public function export_for_template(\renderer_base $output) {
        $data = [];
        $data['actions'] = [[
            'url' => $this->url->out(),
            'iconclass' => $this->iconclass,
            'label' => $this->label,
            'buttonclass' => $this->buttonclass,
        ]];
        return $data;
    }

}
