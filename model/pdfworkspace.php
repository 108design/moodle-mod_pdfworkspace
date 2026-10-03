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
 * Getting instances details
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @author    Rabea de Groot and Anna Heynkes
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/pdfworkspace/locallib.php');
require_once($CFG->dirroot . '/mod/pdfworkspace/renderable.php');

/**
 * This class represents an instance of the pdfworkspace module.
 */
class pdfworkspace_instance {

    private $id;
    private $coursemodule;
    private $name;
    private $answers; // Questions asked by the current users.
    private $unsolvedquestions;
    private $reports;
    private $userposts;
    private $hiddenanswers;
    private $hiddenreports;

    public function __construct($dbrecord) {
        $this->id = $dbrecord->id;
        $this->coursemodule = $dbrecord->coursemodule;
        $this->name = $dbrecord->name;
        $this->answers = array();
        $this->reports = array();
        $this->unsolvedquestions = array();
        $this->userposts = array();
        $this->hiddenanswers = array();
        $this->hiddenreports = array();
    }

    /*     * **************************** static methods ***************************** */

    /**
     * This method returns an array containing one pdfworkspace_instance object
     * for each annotator in the specified course.
     *
     * @param type $courseid
     * @param type $beginwith optional parameter that specifies the (current) pdfworkspace that should come first in the list
     * @return \pdfworkspace_instance: array of pdfworkspace_instance objects
     */
    public static function get_pdfworkspace_instances($courseid, $beginwith = null) {

        global $DB;

        $course = get_course($courseid);
        $result = get_all_instances_in_course('pdfworkspace', $course);

        $pdfworkspacelist = array();

        foreach ($result as $pdfworkspace) {
            $pdfworkspacelist[] = new pdfworkspace_instance($pdfworkspace);
        }

        if ($beginwith) {
            foreach ($pdfworkspacelist as $index => $annotator) {
                if ($annotator->get_id() == $beginwith && $index != 0) {
                    $temp = $pdfworkspacelist[0];
                    $pdfworkspacelist[0] = $annotator;
                    $pdfworkspacelist[$index] = $temp;
                    break;
                }
            }
        }

        return $pdfworkspacelist;
    }

    public static function get_cm_info($courseid) {
        global $USER;
        $info = array();

        $userid = $USER->id;
        $course = get_course($courseid);
        $instances = get_all_instances_in_course('pdfworkspace', $course, $userid);
        $modinfo = get_fast_modinfo($course);

        foreach ($instances as $instance) {
            $cmid = $instance->coursemodule;
            $cm = $modinfo->get_cm($cmid);
            $cminfo = array();
            $cminfo['visible'] = $cm->visible;
            $cminfo['availableinfo'] = $cm->availableinfo;
            $info[$cmid] = $cminfo;
        }
        return $info;
    }

    public static function use_votes($documentid) {
        global $DB;
        return $DB->record_exists('pdfworkspace', array('id' => $documentid, 'usevotes' => '1'));
    }

    /*     * **************************** (attribute) getter methods ***************************** */

    public function get_id() {
        return $this->id;
    }

    public function get_name() {
        return $this->name;
    }

    public static function get_conversations($pdfworkspaceid, $context, $pdfdocid = null) {

        global $DB;

        $sql = "SELECT q.id, q.content AS answeredquestion, q.timemodified, q.userid, q.visibility, q.ishidden, q.annotationid,"
                . " a.id AS annoid, a.page, a.annotationtypeid, q.isquestion "
                . "FROM {pdfworkspace_annotations} a "
                . "JOIN {pdfworkspace_comments} q ON q.annotationid = a.id "
                  . "WHERE q.isquestion = 1 AND a.pdfworkspaceid = ? "
                  . ($pdfdocid === null ? '' : 'AND a.documentid = ? ')
                  . "AND NOT q.isdeleted = 1 "
                . "ORDER BY a.page ASC";

        try {
              $params = [$pdfworkspaceid];
              if ($pdfdocid !== null) {
                  $params[] = $pdfdocid;
              }
              $questions = $DB->get_records_sql($sql, $params);
        } catch (Exception $ex) {
            return -1;
        }

        $res = [];
        $seehidden = has_capability('mod/pdfworkspace:seehiddencomments', $context);

        foreach ($questions as $question) {

            if (!pdfworkspace_can_see_comment($question, $context)) {
                continue;
            }

            if ($question->ishidden && !$seehidden) {
                $question->answeredquestion = get_string('hiddenComment', 'pdfworkspace');
            }
            $question->answeredquestion = html_entity_decode($question->answeredquestion);
            $question->timemodified = pdfworkspace_get_user_datetime($question->timemodified);
            $question->answeredquestion = pdfworkspace_get_relativelink($question->answeredquestion, $question->id, $context);
            if ($question->visibility === 'anonymous') {
                $question->author = get_string('anonymous', 'pdfworkspace');
            } else {
                $question->author = pdfworkspace_get_username($question->userid);
            }

            $sql = "SELECT c.id, c.content AS answer, c.userid, c.timemodified, c.visibility, c.ishidden, c.annotationid, c.isquestion FROM {pdfworkspace_comments} c "
                . "WHERE c.pdfworkspaceid = ? AND c.annotationid = ? AND NOT c.isquestion = 1 AND NOT c.isdeleted = 1";

            try {
                $answers = $DB->get_records_sql($sql, array($pdfworkspaceid, $question->annoid));
            } catch (Exception $ex) {
                return -1;
            }

            foreach ($answers as $answer) {
                if (!pdfworkspace_can_see_comment($answer, $context)) {
                    unset($answers[$answer->id]);
                    continue;
                }
                if ($answer->ishidden && !$seehidden) {
                    $answer->answer = get_string('hiddenComment', 'pdfworkspace');
                }
                $answer->answer = pdfworkspace_get_relativelink($answer->answer, $answer->id, $context);
                $answer->answer = html_entity_decode($answer->answer);
                $answer->timemodified = pdfworkspace_get_user_datetime($answer->timemodified);
                if ($answer->visibility === 'anonymous') {
                    $answer->author = get_string('anonymous', 'pdfworkspace');
                } else {
                    $answer->author = pdfworkspace_get_username($answer->userid);
                }
                unset($answer->visibility);
                unset($answer->ishidden);
                unset($answer->userid);
            }
            unset($question->visibility);
            unset($question->ishidden);
            unset($question->userid);
            unset($question->annoid);

            $question->answers = $answers;
            $res[] = $question;
        }
        return $res;
    }
}
