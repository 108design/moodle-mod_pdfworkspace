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
 * Analyse questions in the current workspace.
 *
 * @package    mod_pdfworkspace
 * @copyright  2026 Andreas Giesen <andreas@108design.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace\external;

use core_ai\aiactions\generate_text;
use core_ai\manager;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_pdfworkspace\local\question_analysis;

/** Process one fixed question-analysis action. */
final class analyse_questions extends external_api {
    /** Return the accepted parameters. */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'PDF Workspace course module'),
            'preset' => new external_value(PARAM_ALPHA, 'Fixed analysis action'),
            'document' => new external_value(PARAM_INT, 'Document filter', VALUE_DEFAULT, 0),
            'page' => new external_value(PARAM_INT, 'Page filter', VALUE_DEFAULT, 0),
        ]);
    }

    /** Check access and forward a bounded sample to the configured Moodle provider. */
    public static function execute(int $cmid, string $preset, int $document = 0, int $page = 0): array {
        global $USER;
        $args = self::validate_parameters(self::execute_parameters(), compact('cmid', 'preset', 'document', 'page'));
        if (!in_array($args['preset'], ['topics', 'unanswered'], true) || $args['document'] < 0 || $args['page'] < 0) {
            throw new \invalid_parameter_exception('Invalid question analysis selection');
        }
        $cm = get_coursemodule_from_id('pdfworkspace', $args['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        $manager = question_analysis::available_manager($context);
        if ($manager === null) {
            return ['status' => 'unavailable', 'limited' => false, 'groups' => []];
        }
        if (!manager::get_user_policy_status((int)$USER->id)) {
            require_capability('moodle/ai:acceptpolicy', \context_user::instance($USER->id));
            return ['status' => 'policy', 'limited' => false, 'groups' => []];
        }
        [$sample, $limited] = question_analysis::select($context, $args['preset'], $args['document'], $args['page']);
        if (!$sample) {
            return ['status' => 'empty', 'limited' => false, 'groups' => []];
        }
        $response = $manager->process_action(new generate_text(
            $context->id, (int)$USER->id, question_analysis::prompt($sample, $args['preset'])
        ));
        $content = $response->get_success() ? ($response->get_response_data()['generatedcontent'] ?? '') : '';
        $groups = $content && question_analysis::allowed($context) ? question_analysis::checked_groups($content, $sample, $context) : [];
        return ['status' => $groups ? 'ok' : 'failed', 'limited' => $limited, 'groups' => $groups];
    }

    /** Return plain labels and links resolved by Moodle. */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Outcome'),
            'limited' => new external_value(PARAM_BOOL, 'Sample or source text was limited'),
            'groups' => new external_multiple_structure(new external_single_structure([
                'title' => new external_value(PARAM_TEXT, 'Plain topic label'),
                'sources' => new external_multiple_structure(new external_single_structure([
                    'url' => new external_value(PARAM_URL, 'Original question link'),
                    'text' => new external_value(PARAM_RAW, 'Plain excerpt of the original question'),
                ])),
            ])),
        ]);
    }
}
