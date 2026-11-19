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
 * Moodle AI manager scoped to an explicitly chosen instance.
 *
 * @package    mod_pdfworkspace
 * @copyright  2026 Andreas Giesen <andreas@108design.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace\local;

use core_ai\manager;

defined('MOODLE_INTERNAL') || die();

/** Limit Core's existing selection and request processing to one configured instance. */
final class question_provider_manager extends manager {
    /**
     * Construct a manager for the selected provider.
     *
     * @param \moodle_database $db Moodle database
     * @param int $selection ID of the selected provider instance
     */
    public function __construct(\moodle_database $db, private readonly int $selection) {
        parent::__construct($db);
    }

    /** Return only the configured instance from the current Core order. */
    public function get_sorted_providers(): array {
        $providers = parent::get_sorted_providers();
        return array_filter($providers, fn($instance): bool => (int)$instance->id === $this->selection);
    }
}
