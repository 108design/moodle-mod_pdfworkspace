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
 * PDF Workspace data generator class
 *
 * @package    mod_pdfworkspace
 * @category   test
 * @copyright  2023 Mikhail Golenkov <mikhailgolenkov@catalyst-au.net>
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_pdfworkspace_generator extends testing_module_generator {

    /**
     * Create a new instance of the PDF Workspace activity.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, array $options = null) {
        if (!isset($record['files'])) {
            $record['files'] = 0;
        }

        return parent::create_instance($record, $options);
    }
}
