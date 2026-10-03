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

}
/**
 * Table with all questions that are yet (marked as) unsolved.
 */
class questionstable extends overviewtable {

    private $id = 'mod-pdfworkspace-questions';

    public function __construct($url, $showdropdown, $usevotes) {
        parent::__construct($this->id);
        global $OUTPUT;
        // $this->collapsible(true); // Concerns the tables columns.
        $this->define_baseurl($url);
        $columns = ['col0', 'col1'];
        if ($usevotes) {
            $columns[] = 'col2';
        }
        array_push($columns, 'col3', 'col5');
        if ($showdropdown) {
            $columns[] = 'col6'; // Action dropdown menu.
            $this->no_sorting('col6');
        }
        $this->define_columns($columns);
        $this->attributes['id'] = $this->id;
        $this->attributes['class'] = 'generaltable flexible table table-striped table-hover pdfworkspace-overview-table pdfworkspace-questions-table' .
            ($usevotes ? ' pdfworkspace-has-votes' : '') . ($showdropdown ? ' pdfworkspace-has-actions' : '');
        $question = get_string('question', 'pdfworkspace');
        // $OUTPUT->pix_icon('i/unlock', '') . self::wrap(get_string('question', 'pdfworkspace'));
        $whoasked = get_string('by', 'pdfworkspace') . ' ' . get_string('on', 'pdfworkspace');
        // $OUTPUT->pix_icon('i/user', '') . self::wrap(get_string('by', 'pdfworkspace')) . ' ' .
        // $OUTPUT->pix_icon('e/insert_time', '') . self::wrap(get_string('on', 'pdfworkspace'));
        $votes = get_string('votes', 'pdfworkspace');
        // "<i class='icon fa fa-chevron-up fa-lg' style='float:left'></i>" . self::wrap(get_string('votes', 'pdfworkspace')) .
        // ' ' . $OUTPUT->help_icon('voteshelpicon', 'pdfworkspace');
        $answers = get_string('answers', 'pdfworkspace');
        // $OUTPUT->pix_icon('t/message', '') . ' ' . self::wrap(get_string('answers', 'pdfworkspace'));
        $lastanswered = get_string('lastanswered', 'pdfworkspace');
        // $OUTPUT->pix_icon('e/insert_time', '') . self::wrap(get_string('lastanswered', 'pdfworkspace'));
        $document = get_string('pdfworkspacecolumn', 'pdfworkspace');
        // "<i class='icon fa fa-book fa-fw'></i>" . self::wrap(get_string('pdfworkspacecolumn', 'pdfworkspace'));

        $headers = [$question, get_string('author', 'pdfworkspace')];
        if ($usevotes) {
            $headers[] = $votes;
        }
        array_push($headers, $answers, $document);
        if ($showdropdown) {
            $actionmenu = get_string('overviewactioncolumn', 'pdfworkspace');
            $headers[] = $actionmenu;
        }

        $this->define_headers($headers);
        $this->no_sorting('col0');
        $this->no_sorting('col1');
        $this->sortable(true, 'col5', SORT_ASC);
        $this->sortable(true, 'col3', SORT_ASC);
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
        global $OUTPUT;
        // $this->collapsible(true); // Concerns the tables columns.
        $this->define_baseurl($url);
        $this->define_columns(array('col3', 'col0', 'col4', 'col5'));
        $this->attributes['id'] = $this->id;
        $this->attributes['class'] = 'generaltable flexible table table-striped table-hover ' .
            'pdfworkspace-overview-table pdfworkspace-answers-table pdfworkspace-has-actions';
        $answer = get_string('answer', 'pdfworkspace');
        // $OUTPUT->pix_icon('t/message', '') . self::wrap(get_string('answer', 'pdfworkspace'));
        $iscorrect = get_string('correct', 'pdfworkspace');
        // . get_string('correct', 'pdfworkspace');
        $whoanswered = get_string('by', 'pdfworkspace') . ' ' . get_string('on', 'pdfworkspace');
        // $OUTPUT->pix_icon('i/user', '') . self::wrap(get_string('by', 'pdfworkspace')) . ' ' .
        // $OUTPUT->pix_icon('e/insert_time', '') . self::wrap(get_string('on', 'pdfworkspace'));
        $question = get_string('myquestion', 'pdfworkspace');
        // $OUTPUT->pix_icon('i/email', '') . self::wrap(get_string('myquestion', 'pdfworkspace'));
        $document = get_string('pdfworkspacecolumn', 'pdfworkspace');
        // "<i class='icon fa fa-book fa-fw'></i>" . self::wrap(get_string('pdfworkspacecolumn', 'pdfworkspace'));
        $actionmenu = get_string('overviewactioncolumn', 'pdfworkspace');
        // $OUTPUT->pix_icon('i/settings', '') . self::wrap(get_string('overviewactioncolumn', 'pdfworkspace'));
        $this->define_headers(array($question, $answer, $document, $actionmenu));
        $this->no_sorting('col0');
        $this->no_sorting('col5');
        $this->sortable(true, 'col3', SORT_ASC);
        $this->sortable(true, 'col4', SORT_DESC);
    }
}
/**
 * Table with all posts of the current user.
 */
class userspoststable extends overviewtable {

    private $id = 'mod-pdfworkspace-ownposts';

    public function __construct($url, $usevotes) {
        parent::__construct($this->id);
        global $OUTPUT;
        // $this->collapsible(true); // Concerns the tables columns.
        $this->define_baseurl($url);
        $columns = $usevotes ? ['col0', 'col1', 'col2', 'col3'] : ['col0', 'col1', 'col3'];
        $this->define_columns($columns);
        $this->attributes['id'] = $this->id;
        $this->attributes['class'] = 'generaltable flexible table table-striped table-hover ' .
            'pdfworkspace-overview-table pdfworkspace-posts-table' . ($usevotes ? ' pdfworkspace-has-votes' : '');
        $mypost = get_string('mypost', 'pdfworkspace');
        // $OUTPUT->pix_icon('t/message', '') . self::wrap(get_string('mypost', 'pdfworkspace'));
        $lastedited = get_string('lastedited', 'pdfworkspace');
        // $OUTPUT->pix_icon('e/insert_time', '') . self::wrap(get_string('lastedited', 'pdfworkspace'));
        $votes = get_string('votes', 'pdfworkspace');
        // "<i class='icon fa fa-chevron-up fa-lg' style='float:left'></i>" . self::wrap(get_string('votes', 'pdfworkspace')). ' ' .
        // $OUTPUT->help_icon('voteshelpicon', 'pdfworkspace');
        $document = get_string('pdfworkspacecolumn', 'pdfworkspace');
        // "<i class='icon fa fa-book fa-fw'></i>" . self::wrap(get_string('pdfworkspacecolumn', 'pdfworkspace'));
        $headers = $usevotes ? [$mypost, $lastedited, $votes, $document] : [$mypost, $lastedited, $document];
        $this->define_headers($headers);
        $this->no_sorting('col0');
        if ($usevotes) {
            $this->sortable(true, 'col2', SORT_ASC);
        }
        $this->sortable(true, 'col3', SORT_DESC);
        $this->sortable(true, 'col1', SORT_DESC);
    }
}
/**
 * Table with reported comments.
 */
class reportstable extends overviewtable {

    private $id = 'mod-pdfworkspace-reports';

    public function __construct($url) {
        parent::__construct($this->id);
        global $OUTPUT;
        $this->define_baseurl($url);
        $this->define_columns(array('col0', 'col2', 'col3', 'col4'));
        $this->attributes['id'] = $this->id;
        $this->attributes['class'] = 'generaltable flexible table table-striped table-hover ' .
            'pdfworkspace-overview-table pdfworkspace-reports-table pdfworkspace-has-actions';
        $report = get_string('report', 'pdfworkspace');
        // $OUTPUT->pix_icon('i/email', '') . self::wrap(get_string('report', 'pdfworkspace'));
        $reportedby = get_string('by', 'pdfworkspace'). ' '. get_string('on', 'pdfworkspace');
        // $OUTPUT->pix_icon('i/user', '') . self::wrap(get_string('by', 'pdfworkspace')) . ' ' .
        // $OUTPUT->pix_icon('e/insert_time', '') . self::wrap(get_string('on', 'pdfworkspace'));
        $reportedcomment = get_string('reportedcomment', 'pdfworkspace');
        // $OUTPUT->pix_icon('i/flagged', '') . self::wrap(get_string('reportedcomment', 'pdfworkspace'));
        $writtenby = get_string('by', 'pdfworkspace') . ' ' . get_string('on', 'pdfworkspace');
        // $OUTPUT->pix_icon('i/user', '') . self::wrap(get_string('by', 'pdfworkspace')) . ' ' .
        // $OUTPUT->pix_icon('e/insert_time', '') . self::wrap(get_string('on', 'pdfworkspace'));
        $actionmenu = get_string('overviewactioncolumn', 'pdfworkspace');
        // $OUTPUT->pix_icon('i/settings', '') . self::wrap(get_string('overviewactioncolumn', 'pdfworkspace'));
        $this->define_headers(array($report, $reportedcomment,
            get_string('pdfworkspacecolumn', 'pdfworkspace'), $actionmenu));
        $this->no_sorting('col0');
        $this->no_sorting('col2');
        $this->no_sorting('col3');
        $this->no_sorting('col4');
    }
}
