<?php
/**
 * This file is part of Moodle - https://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <https://www.gnu.org/licenses/>.
 *
 */
// This file is part of Moodle - https://moodle.org/
// Moodle is free software under the GNU GPL v3 or later.
// See <https://www.gnu.org/licenses/> for the full licence and warranty disclaimer.

namespace mod_pdfworkspace;

/**
 * Plain textbox content is validated after decoding its JSON wrapper.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class security_text_test extends \basic_testcase {
    public function test_decoded_markup_is_removed_but_unicode_text_is_preserved(): void {
        $data = json_decode('{"content":"\\u003cb\\u003eGrüße & plain\\u003c/b\\u003e"}', true);
        $this->assertSame('Grüße & plain', \mod_pdfworkspace\local\comment_content::textbox($data['content']));
        $this->assertSame('', \mod_pdfworkspace\local\comment_content::textbox(['unexpected']));
    }
}
