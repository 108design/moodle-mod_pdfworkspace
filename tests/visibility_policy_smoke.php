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
 * PDF Workspace visibility policy smoke.
 *
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Standalone smoke check for the role audience policy; no Moodle bootstrap required.
define('MOODLE_INTERNAL', true);
class invalid_parameter_exception extends Exception {}
function get_string($key, $component) {
    return $key;
}
function has_capability($capability, $context, $userid) {
    return $capability !== 'mod/pdfworkspace:viewprotectedcomments' || (int)$userid === 3;
}
function is_enrolled($context, $userid) {
    return in_array((int)$userid, [1, 2, 3], true);
}
function check($actual, $expected) {
    if ($actual !== $expected) {
        throw new RuntimeException('Audience policy mismatch: ' .
            var_export($actual, true) . ' != ' . var_export($expected, true));
    }
}
require_once(__DIR__ . '/../classes/visibility.php');

use mod_pdfworkspace\visibility;

$context = new stdClass();
$activity = (object)['studentaudiences' => 3, 'staffaudiences' => 3,
    'studentdefaultaudience' => 'protected', 'staffdefaultaudience' => 'private'];
check(visibility::allowed_audiences($activity, false), ['protected', 'private']);
check(visibility::allowed_audiences($activity, true), ['private', 'targeted']);
check(visibility::new_annotation_audience($activity, $context, 1, '', 0), ['protected', null]);
try {
    visibility::new_annotation_audience($activity, $context, 1, 'public', 0);
    throw new RuntimeException('Disallowed participant public audience accepted');
} catch (invalid_parameter_exception $expected) {
}
$activity->studentaudiences = 7;
$activity->staffaudiences = 7;
check(visibility::new_annotation_audience($activity, $context, 1, 'public', 0), ['public', null]);
check(visibility::new_annotation_audience($activity, $context, 3, 'public', 0), ['public', null]);
check(visibility::new_annotation_audience($activity, $context, 3, 'targeted', 2), ['targeted', 2]);
check(visibility::thread_visibility('public'), 'public');
check(visibility::thread_visibility('targeted'), 'protected');
check(visibility::thread_visibility('private'), 'private');
echo "Audience policy smoke check passed\n";
