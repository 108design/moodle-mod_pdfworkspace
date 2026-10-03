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
 * Renderer file
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @author    Rabea de Groot and Anna Heynkes
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
defined('MOODLE_INTERNAL') || die();

class mod_pdfworkspace_renderer extends plugin_renderer_base {

    /**
     *
     * @param type $index
     * @return type
     */
    public function render_index($index) {
        return $this->render_from_template('pdfworkspace/index', $index->export_for_template($this));
    }
    /**
     *
     * @param \templatable $statistic
     * @return type
     */
    public function render_statistic(\templatable $statistic) {
        $data = $statistic->export_for_template($this);
        return $this->render_from_template('mod_pdfworkspace/statistic', $data);
    }

    /**
     * Render the actions available for an overview row without a nested menu.
     * @param \templatable $actions Row actions.
     * @return string
     */
    public function render_overview_actions(\templatable $actions) {
        return $this->render_from_template('mod_pdfworkspace/overview_actions',
            $actions->export_for_template($this));
    }

    /**
     * Render a table containing information about a comment the user wants to report
     *
     * @param pdfworkspace_comment_info $info a renderable
     * @return string
     */
    public function render_pdfworkspace_comment_info(pdfworkspace_comment_info $info) {
        $o = '';
        $o .= $this->output->container_start('appointmentinfotable');
        $o .= $this->output->box_start('boxaligncenter appointmentinfotable');

        $t = new html_table();

        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('slotdatetimelabel', 'pdfworkspace'));
        $cell2 = $info->datetime;
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('author', 'pdfworkspace'));
        $cell2 = new html_table_cell($info->author);
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('comment', 'pdfworkspace'));
        $cell2 = new html_table_cell($info->content);
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        $o .= html_writer::table($t);
        $o .= $this->output->box_end();
        $o .= $this->output->container_end();
        return $o;
    }
    /**
     * Construct a tab header.
     *
     * @param moodle_url $baseurl
     * @param string $what
     * @param string $namekey
     * @param string $subpage
     * @param string $nameargs
     * @return tabobject
     */
    private function pdfworkspace_create_tab(moodle_url $baseurl, $action, $namekey = null, $pdfworkspacename = null,
        $nameargs = null) {
        $taburl = new moodle_url($baseurl, array('action' => $action));
        $tabname = get_string($namekey, 'pdfworkspace', $nameargs);
        if ($pdfworkspacename) {
            strlen($pdfworkspacename) > 20 ? $tabname = substr($pdfworkspacename, 0, 21) . "..." : $tabname = $pdfworkspacename;
        }
        $id = $action;
        $tab = new tabobject($id, $taburl, $tabname);
        return $tab;
    }
    /**
     * Render the tab header hierarchy.
     *
     * @param moodle_url $baseurl
     * @param type $pdfworkspacename
     * @param type $context
     * @param type $selected
     * @param type $inactive
     * @return type
     */
    public function pdfworkspace_render_tabs(moodle_url $baseurl, $pdfworkspacename, $context, $selected = null, $inactive = null) {
        global $DB, $USER;

        $overviewtab = $this->pdfworkspace_create_tab($baseurl, 'overview', 'overview');

        $level1 = array(
            $overviewtab,
            $this->pdfworkspace_create_tab($baseurl, 'view', 'document', $pdfworkspacename),
        );
        $cm = get_coursemodule_from_id('pdfworkspace', $context->instanceid, 0, false, MUST_EXIST);
        if (\mod_pdfworkspace\visibility::is_staff($context, $USER->id)) {
            $level1[] = $this->pdfworkspace_create_tab($baseurl, 'statistic', 'statistic');
        }
        return $this->tabtree($level1, $selected, $inactive);
    }

}
