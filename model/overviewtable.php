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
 * Defining class for comments
 * @package   mod_pdfworkspace
 * @copyright 2019 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @author    Anna Heynkes
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/tablelib.php');
/**
 * A base class for several table classes that are to be displayed on the overview page.
 */
class overviewtable extends flexible_table {

    public function __construct($id) {
        parent::__construct($id);
    }

    public function setup() {
        ($this->set_control_variables(array(
            TABLE_VAR_SORT    => 'sort',
            TABLE_VAR_HIDE    => 'hide',
            TABLE_VAR_SHOW    => 'show',
            TABLE_VAR_PAGE    => 'page',  // This is used for pagination in the tables.
            TABLE_VAR_RESET   => 'treset'
            )));
        parent::setup();
    }

    /** The filter bar owns the single visible Reset action. */
    protected function render_reset_button() {
        return '';
    }
    /**
     * Function wraps text elements with a text class for identification by media queries /
     * selective display/hiding.
     *
     * @param type $string
     * @return type
     */
    public static function wrap($string) {
        return "<span class='text'>$string</span>";
    }

    /** Accessible compact header, also usable by Moodle's sort controls. */
    public static function icon_header($icon, $label) {
        return html_writer::tag('span', html_writer::tag('i', '',
            ['class' => 'fa fa-' . $icon, 'aria-hidden' => 'true']) .
            html_writer::tag('span', s($label), ['class' => 'sr-only visually-hidden']), ['title' => $label]);
    }

    /** Keep Moodle's accessible sort label plain when the visible heading is an icon. */
    protected function sort_link($text, $column, $isprimary, $order) {
        $label = trim(strip_tags($text));
        $link = parent::sort_link($label, $column, $isprimary, $order);
        return str_replace('>' . $label . '</a>', '>' . $text . '</a>', $link);
    }

}
/**
 * Table with all questions that are yet (marked as) unsolved.
 */
class questionstable extends overviewtable {

    private $id = 'mod-pdfworkspace-questions';

    public function __construct($url, $showdropdown, $usevotes) {
        parent::__construct($this->id);
        $this->define_baseurl($url);
        $columns = ['col5', 'col0', 'col1'];
        if ($usevotes) {
            $columns[] = 'col2';
        }
        array_push($columns, 'col3', 'col4');
        if ($showdropdown) {
            $columns[] = 'col6';
            $this->no_sorting('col6');
        }
        $this->define_columns($columns);
        $this->attributes['id'] = $this->id;
        $this->attributes['class'] = 'generaltable flexible table table-sm table-hover pdfworkspace-overview-table pdfworkspace-questions-table' .
            ($usevotes ? ' pdfworkspace-has-votes' : '') . ($showdropdown ? ' pdfworkspace-has-actions' : '');
        $question = get_string('question', 'pdfworkspace');
        $votes = self::icon_header('thumbs-up', get_string('votes', 'pdfworkspace'));
        $answers = self::icon_header('comments', get_string('answers', 'pdfworkspace'));
        $lastanswered = get_string('lastanswered', 'pdfworkspace');
        $document = get_string('pdfworkspacecolumn', 'pdfworkspace');

        $headers = [$document, $question, get_string('author', 'pdfworkspace')];
        if ($usevotes) {
            $headers[] = $votes;
        }
        array_push($headers, $answers, $lastanswered);
        if ($showdropdown) {
            $actionmenu = get_string('overviewactioncolumn', 'pdfworkspace');
            $headers[] = $actionmenu;
        }

        $this->define_headers($headers);
        $this->no_sorting('col0');
        $this->no_sorting('col1');
        $this->sortable(true, 'col5', SORT_ASC);
        $this->sortable(true, 'col3', SORT_ASC);
        $this->column_class('col5', 'pdfworkspace-col-document');
        $this->column_class('col0', 'pdfworkspace-col-content');
        $this->column_class('col1', 'pdfworkspace-col-person');
        $this->column_class('col2', 'pdfworkspace-col-count');
        $this->column_class('col3', 'pdfworkspace-col-count');
        $this->column_class('col4', 'pdfworkspace-col-person');
        $this->column_class('col6', 'pdfworkspace-col-actions');
        $this->no_sorting('col4');
        if ($usevotes) {
            $this->sortable(true, 'col2', SORT_DESC);
        }
    }
}
/**
 * Table with all answers to questions that the current user subscribed to.
 * Note: Users are automatically subscribed to their own questions on posting them.
 * They can, however, unsubscribe from any question including their own.
 */
class answerstable extends overviewtable {

    private $id = 'mod-pdfworkspace-answers';

    public function __construct($url) {
        parent::__construct($this->id);
        $this->define_baseurl($url);
        $this->define_columns(array('col4', 'col3', 'col0', 'col2', 'col5'));
        $this->attributes['id'] = $this->id;
        $this->attributes['class'] = 'generaltable flexible table table-sm table-hover ' .
            'pdfworkspace-overview-table pdfworkspace-answers-table pdfworkspace-has-actions';
        $answer = get_string('answer', 'pdfworkspace');
        $question = get_string('question', 'pdfworkspace');
        $document = get_string('pdfworkspacecolumn', 'pdfworkspace');
        $actionmenu = get_string('overviewactioncolumn', 'pdfworkspace');
        $this->define_headers(array($document, $question, $answer, get_string('author', 'pdfworkspace'), $actionmenu));
        $this->no_sorting('col0');
        $this->no_sorting('col5');
        $this->sortable(true, 'col3', SORT_ASC);
        $this->sortable(true, 'col4', SORT_DESC);
        $this->column_class('col4', 'pdfworkspace-col-document');
        $this->column_class('col3', 'pdfworkspace-col-content');
        $this->column_class('col0', 'pdfworkspace-col-content');
        $this->column_class('col2', 'pdfworkspace-col-person');
        $this->column_class('col5', 'pdfworkspace-col-actions');
        $this->no_sorting('col2');
    }
}
/**
 * Table with all posts of the current user.
 */
class userspoststable extends overviewtable {

    private $id = 'mod-pdfworkspace-ownposts';

    public function __construct($url, $usevotes) {
        parent::__construct($this->id);
        $this->define_baseurl($url);
        $columns = $usevotes ? ['col3', 'col0', 'col1', 'col2'] : ['col3', 'col0', 'col1'];
        $this->define_columns($columns);
        $this->attributes['id'] = $this->id;
        $this->attributes['class'] = 'generaltable flexible table table-sm table-hover ' .
            'pdfworkspace-overview-table pdfworkspace-posts-table' . ($usevotes ? ' pdfworkspace-has-votes' : '');
        $mypost = get_string('mypost', 'pdfworkspace');
        $lastedited = get_string('lastedited', 'pdfworkspace');
        $votes = self::icon_header('thumbs-up', get_string('votes', 'pdfworkspace'));
        $document = get_string('pdfworkspacecolumn', 'pdfworkspace');
        $headers = $usevotes ? [$document, $mypost, $lastedited, $votes] : [$document, $mypost, $lastedited];
        $this->define_headers($headers);
        $this->no_sorting('col0');
        if ($usevotes) {
            $this->sortable(true, 'col2', SORT_ASC);
        }
        $this->sortable(true, 'col3', SORT_DESC);
        $this->sortable(true, 'col1', SORT_DESC);
        $this->column_class('col3', 'pdfworkspace-col-document');
        $this->column_class('col0', 'pdfworkspace-col-content');
        $this->column_class('col1', 'pdfworkspace-col-person');
        $this->column_class('col2', 'pdfworkspace-col-count');
    }
}
/**
 * Table with reported comments.
 */
class reportstable extends overviewtable {

    private $id = 'mod-pdfworkspace-reports';

    public function __construct($url) {
        parent::__construct($this->id);
        $this->define_baseurl($url);
        $this->define_columns(array('col3', 'col0', 'col1', 'col2', 'col5', 'col4'));
        $this->attributes['id'] = $this->id;
        $this->attributes['class'] = 'generaltable flexible table table-sm table-hover ' .
            'pdfworkspace-overview-table pdfworkspace-reports-table pdfworkspace-has-actions';
        $report = get_string('overviewreport', 'pdfworkspace');
        $reportedcomment = get_string('reportedcomment', 'pdfworkspace');
        $actionmenu = get_string('overviewactioncolumn', 'pdfworkspace');
        $this->define_headers(array(get_string('pdfworkspacecolumn', 'pdfworkspace'), $report,
            get_string('overviewreporter', 'pdfworkspace'), $reportedcomment, get_string('author', 'pdfworkspace'), $actionmenu));
        $this->no_sorting('col0');
        $this->no_sorting('col2');
        $this->no_sorting('col3');
        $this->no_sorting('col4');
        $this->no_sorting('col1');
        $this->no_sorting('col5');
        $this->column_class('col3', 'pdfworkspace-col-document');
        $this->column_class('col0', 'pdfworkspace-col-content');
        $this->column_class('col1', 'pdfworkspace-col-person');
        $this->column_class('col2', 'pdfworkspace-col-content');
        $this->column_class('col5', 'pdfworkspace-col-person');
        $this->column_class('col4', 'pdfworkspace-col-actions');
    }
}
