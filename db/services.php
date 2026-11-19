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
 * AJAX methods for PDF Workspace.
 *
 * @package    mod_pdfworkspace
 * @copyright  2026 Andreas Giesen <andreas@108design.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_pdfworkspace_analyse_questions' => [
        'classname' => \mod_pdfworkspace\external\analyse_questions::class,
        'description' => 'Group authorised open questions using the configured Moodle text provider.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/pdfworkspace:view,mod/pdfworkspace:viewquestions,mod/pdfworkspace:viewprotectedcomments',
    ],
];
