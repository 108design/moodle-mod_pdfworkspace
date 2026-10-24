<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// Moodle is distributed without any warranty. See <https://www.gnu.org/licenses/>.

namespace mod_pdfworkspace\local;

defined('MOODLE_INTERNAL') || die();

/**
 * The common output boundary for stored comment HTML and plain annotations.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class comment_content {
    /** Clean rich text independently of the author's role or trusted-content setting. */
    public static function clean(string $html): string {
        return clean_text($html, FORMAT_HTML);
    }

    /** Format existing and newly saved comments through the same safe output boundary. */
    public static function format(string $html, int $commentid, \context $context): string {
        $html = self::clean($html);
        $html = file_rewrite_pluginfile_urls($html, 'pluginfile.php', $context->id,
            'mod_pdfworkspace', 'post', $commentid);
        return format_text($html, FORMAT_HTML, [
            'context' => $context, 'para' => false, 'filter' => true,
            'trusted' => false, 'noclean' => false,
        ]);
    }

    /** Apply moderation before any content is included in a JSON question response. */
    public static function question(\stdClass $question, \context $context): string {
        if (!empty($question->isdeleted)) {
            return '<em>' . s(get_string('deletedComment', 'pdfworkspace')) . '</em>';
        }
        if (!empty($question->ishidden) && !has_capability('mod/pdfworkspace:seehiddencomments', $context)) {
            return s(get_string('hiddenComment', 'pdfworkspace'));
        }
        return self::format((string)$question->content, (int)$question->id, $context);
    }

    /** JSON must be decoded before applying plain-text validation. */
    public static function textbox($text): string {
        return clean_param(is_string($text) ? $text : '', PARAM_TEXT);
    }
}
