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
 * Prepare print fragments without resolving any user-supplied network location.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class print_images {
    /** Split safe HTML into plain text and images from this comment's own file area. */
    public static function split(string $html, int $itemid, \context $context): array {
        $parts = preg_split('/(<img\b[^>]*>)/i', comment_content::clean($html), -1, PREG_SPLIT_DELIM_CAPTURE);
        $files = get_file_storage()->get_area_files($context->id, 'mod_pdfworkspace', 'post', $itemid, 'id', false);
        $result = [];
        foreach ($parts as $part) {
            if (!preg_match('/^<img\b/i', $part)) {
                $result[] = html_to_text($part, 0, false);
                continue;
            }
            // The input has already passed Moodle's HTML cleaner. DOM parsing never loads image URLs.
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $part, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            $image = $dom->getElementsByTagName('img')->item(0);
            if (!$image) {
                continue;
            }
            $src = $image->getAttribute('src');
            $match = null;
            foreach ($files as $file) {
                $url = \moodle_url::make_pluginfile_url($context->id, 'mod_pdfworkspace', 'post',
                    $itemid, $file->get_filepath(), $file->get_filename())->out(false);
                $placeholder = '@@PLUGINFILE@@' . $file->get_filepath() . $file->get_filename();
                if ($src === $url || $src === $placeholder) {
                    $match = $file;
                    break;
                }
            }
            if (!$match || !in_array($match->get_mimetype(), ['image/png', 'image/jpeg', 'image/gif'], true)) {
                // Unsupported/local foreign images never become a file read or a network request.
                $result[] = preg_match('~^https?://~i', $src) ? $src : $image->getAttribute('alt');
                continue;
            }
            $info = $match->get_imageinfo();
            if (!$info || empty($info['width']) || empty($info['height'])
                    || ($info['mimetype'] ?? null) !== $match->get_mimetype()) {
                $result[] = $image->getAttribute('alt');
                continue;
            }
            $width = (int)$image->getAttribute('width');
            $height = (int)$image->getAttribute('height');
            $result[] = [
                'image' => 'data:' . $match->get_mimetype() . ';base64,' . base64_encode($match->get_content()),
                'format' => $match->get_mimetype(), 'imagestorage' => 'intern',
                'fileid' => $match->get_id(), 'filename' => $match->get_filename(),
                'filepath' => $match->get_filepath(), 'filesize' => $match->get_filesize(),
                'imagewidth' => $width > 0 ? $width : $info['width'],
                'imageheight' => $height > 0 ? $height : $info['height'],
            ];
        }
        return $result;
    }
}
