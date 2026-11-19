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
 * AJAX endpoint for protected page drawings.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_pdfworkspace\local\restricted_markings;

/** Save one marking using the logged-in user's course module context. */
final class restricted_marking extends external_api {
    /** @return external_function_parameters AJAX request schema. */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'PDF Workspace course module'),
            'document' => new external_value(PARAM_INT, 'Restricted document'),
            'page' => new external_value(PARAM_INT, 'Page number'),
            'tool' => new external_value(PARAM_ALPHA, 'Pen or marker', VALUE_DEFAULT, ''),
            'points' => new external_value(PARAM_RAW, 'Normalised coordinates as JSON', VALUE_DEFAULT, ''),
            'audience' => new external_value(PARAM_ALPHA, 'Drawing audience', VALUE_DEFAULT, ''),
            'recipient' => new external_value(PARAM_INT, 'Selected recipient', VALUE_DEFAULT, 0),
            'removeid' => new external_value(PARAM_INT, 'Marking to remove', VALUE_DEFAULT, 0),
            'label' => new external_value(PARAM_TEXT, 'Text box content', VALUE_DEFAULT, ''),
            'threadid' => new external_value(PARAM_INT, 'Selected marking discussion', VALUE_DEFAULT, 0),
            'comment' => new external_value(PARAM_RAW_TRIMMED, 'Plain comment', VALUE_DEFAULT, ''),
            'removecommentid' => new external_value(PARAM_INT, 'Own comment to remove', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Apply one change to a protected page.
     *
     * @param int $cmid PDF Workspace course module
     * @param int $document Restricted document ID
     * @param int $page One-based page number
     * @param string $tool Drawing tool
     * @param string $points JSON-encoded coordinates
     * @param string $audience Visibility choice
     * @param int $recipient Recipient for targeted drawing
     * @param int $removeid Optional existing marking ID to remove
     * @param string $label Text box content
     * @param int $threadid Selected discussion
     * @param string $comment New discussion comment
     * @param int $removecommentid Own comment to remove
     * @return array Affected ID and authorised comments
     */
    public static function execute(int $cmid, int $document, int $page, string $tool = '', string $points = '',
            string $audience = '', int $recipient = 0, int $removeid = 0, string $label = '', int $threadid = 0,
            string $comment = '', int $removecommentid = 0): array {
        global $DB;
        $args = self::validate_parameters(self::execute_parameters(),
            compact('cmid', 'document', 'page', 'tool', 'points', 'audience', 'recipient', 'removeid', 'label',
                'threadid', 'comment', 'removecommentid'));
        $cm = get_coursemodule_from_id('pdfworkspace', $args['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        $activity = $DB->get_record('pdfworkspace', ['id' => $cm->instance], '*', MUST_EXIST);
        if ($args['threadid'] > 0) {
            return ['id' => $args['threadid'], 'comments' => restricted_markings::thread(
                $activity, $context, $args['document'], $args['page'], $args['threadid'], $args['comment'], $args['removecommentid'])];
        }
        return ['id' => restricted_markings::save($activity, $context, $args['document'], $args['page'], $args['tool'],
            $args['points'], $args['audience'], $args['recipient'], $args['removeid'], $args['label']), 'comments' => []];
    }

    /** @return external_single_structure Marking ID and comment data. */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Affected marking ID'),
            'comments' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Comment ID'),
                'author' => new external_value(PARAM_RAW, 'Author display name'),
                'content' => new external_value(PARAM_RAW, 'Plain comment'),
                'date' => new external_value(PARAM_TEXT, 'Formatted creation date'),
                'own' => new external_value(PARAM_BOOL, 'Comment belongs to the current user'),
            ])),
        ]);
    }
}
