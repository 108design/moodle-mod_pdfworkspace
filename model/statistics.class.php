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
 * Activity-scoped statistics for PDF Workspace.
 *
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class pdfworkspace_statistics {
    private $annotatorid;
    private $userid;
    private $isteacher;

    public function __construct($courseid, $annotatorid, $userid, $isteacher = false) {
        $this->annotatorid = (int)$annotatorid;
        $this->userid = (int)$userid;
        $this->isteacher = $isteacher;
    }

    /** Count visible, undeleted comments in this activity by author, kind and audience. */
    private function counts() {
        global $DB;
        $sql = "SELECT c.id, c.userid, c.isquestion, a.audience
                  FROM {pdfworkspace_comments} c
                  JOIN {pdfworkspace_annotations} a ON a.id = c.annotationid
                 WHERE a.pdfworkspaceid = ? AND c.isdeleted = 0";
        $counts = [];
        foreach ($DB->get_records_sql($sql, [$this->annotatorid]) as $comment) {
            $kind = (int)$comment->isquestion;
            $audience = $comment->audience === 'targeted' ? 'protected' : $comment->audience;
            if (!in_array($audience, ['private', 'protected', 'public'], true)) {
                continue;
            }
            $owner = (int)$comment->userid === $this->userid ? 'mine' : 'other';
            $counts[$kind][$owner][$audience] = ($counts[$kind][$owner][$audience] ?? 0) + 1;
            $counts[$kind]['authors'][$comment->userid] = true;
        }
        return $counts;
    }

    private static function value($counts, $kind, $owner, $audience) {
        return $counts[$kind][$owner][$audience] ?? 0;
    }

    private static function total($counts, $kind, $owner = null) {
        $sum = 0;
        foreach (($owner ? [$owner] : ['mine', 'other']) as $group) {
            foreach (['private', 'protected', 'public'] as $audience) {
                $sum += self::value($counts, $kind, $group, $audience);
            }
        }
        return $sum;
    }

    private static function average($counts, $kind) {
        $authors = count($counts[$kind]['authors'] ?? []);
        return $authors ? round(self::total($counts, $kind) / $authors, 2) : 0;
    }

    public function get_tabledata() {
        global $DB;
        $counts = $this->counts();
        $rows = [
            ['all_questions', self::total($counts, 1)],
            ['myquestions', self::total($counts, 1, 'mine')],
            ['average_questions', self::average($counts, 1)],
            ['all_answers', self::total($counts, 0)],
            ['myanswers', self::total($counts, 0, 'mine')],
            ['average_answers', self::average($counts, 0)],
            ['private_comments', self::value($counts, 1, 'mine', 'private') +
                self::value($counts, 1, 'other', 'private') +
                self::value($counts, 0, 'mine', 'private') +
                self::value($counts, 0, 'other', 'private')],
            ['protected_comments', self::value($counts, 1, 'mine', 'protected') +
                self::value($counts, 1, 'other', 'protected') +
                self::value($counts, 0, 'mine', 'protected') +
                self::value($counts, 0, 'other', 'protected')],
        ];
        if ($this->isteacher) {
            $rows[] = ['reports', $DB->count_records('pdfworkspace_reports',
                ['pdfworkspaceid' => $this->annotatorid])];
        }
        return array_map(static function($row) {
            return ['row' => [get_string($row[0], 'pdfworkspace'), $row[1]]];
        }, $rows);
    }

    /** Keep the existing chart contract, now with exactly one activity label. */
    public function get_chartdata() {
        global $DB;
        $counts = $this->counts();
        $name = $DB->get_field('pdfworkspace', 'name', ['id' => $this->annotatorid], MUST_EXIST);
        $v = static function($kind, $owner, $audience) use ($counts) {
            return [self::value($counts, $kind, $owner, $audience)];
        };
        return [
            [format_string($name)],
            $v(1, 'other', 'public'), $v(1, 'mine', 'public'),
            $v(0, 'other', 'public'), $v(0, 'mine', 'public'),
            [self::value($counts, 1, 'other', 'private') + self::value($counts, 0, 'other', 'private')],
            [self::value($counts, 1, 'mine', 'private') + self::value($counts, 0, 'mine', 'private')],
            $v(1, 'other', 'protected'), $v(1, 'mine', 'protected'),
            $v(0, 'other', 'protected'), $v(0, 'mine', 'protected'),
        ];
    }
}
