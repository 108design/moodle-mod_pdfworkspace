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
 * Shared PDF export: source lookup, viewer-filtered notes and isolated Python execution.
 *
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace;

defined('MOODLE_INTERNAL') || die();

class pdf_export {
    /** Locate the immutable source belonging to this activity and document. */
    public static function source($document, $context) {
        $file = get_file_storage()->get_file($context->id, 'mod_pdfworkspace', 'content', 0,
            $document->filepath, $document->filename);
        if (!$file || $file->get_contenthash() !== $document->contenthash) {
            throw new \moodle_exception('filenotfound', 'pdfworkspace');
        }
        return $file;
    }

    /** Return only marks and comments the requesting user may see. */
    public static function annotations($activity, $context, $requestedid, $userid) {
        global $DB;
        $types = $DB->get_records('pdfworkspace_annotationtypes');
        $seen = [];
        $export = [];
        $records = $DB->get_records('pdfworkspace_annotations',
            ['pdfworkspaceid' => $activity->id, 'documentid' => $requestedid], 'page ASC, id ASC');
        foreach ($records as $record) {
            if (!\mod_pdfworkspace\visibility::can_view_annotation($record, $context, $userid)) {
                continue;
            }
            $data = json_decode($record->data, true);
            if (!is_array($data) || !isset($types[$record->annotationtypeid])) {
                continue;
            }
            $type = $types[$record->annotationtypeid]->name;
            if (!in_array($type, ['point', 'pin', 'area', 'highlight', 'strikeout', 'textbox', 'drawing'], true)) {
                continue;
            }
            $seen[$record->id] = count($export);
            $export[] = ['page' => (int)$record->page, 'type' => $type, 'data' => $data, 'comments' => []];
        }

        $seehidden = has_capability('mod/pdfworkspace:seehiddencomments', $context, $userid);
        $users = [];
        $comments = $DB->get_records('pdfworkspace_comments', ['pdfworkspaceid' => $activity->id],
            'timecreated ASC, id ASC');
        foreach ($comments as $comment) {
            if (!isset($seen[$comment->annotationid]) ||
                    !\mod_pdfworkspace\visibility::can_view_comment($comment, $context, $userid)) {
                continue;
            }
            $content = $comment->isdeleted ? get_string('deletedComment', 'pdfworkspace') :
                ($comment->ishidden && !$seehidden ? get_string('hiddenComment', 'pdfworkspace') : $comment->content);
            $content = preg_replace('~<\s*br\s*/?\s*>|</\s*(?:p|div|li)\s*>~i', "\n", $content);
            $content = trim(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($comment->visibility === 'anonymous') {
                $author = get_string('anonymous', 'pdfworkspace');
            } else {
                if (!isset($users[$comment->userid])) {
                    $users[$comment->userid] = $DB->get_record('user', ['id' => $comment->userid]);
                }
                $author = $users[$comment->userid] ? fullname($users[$comment->userid]) : '';
            }
            $export[$seen[$comment->annotationid]]['comments'][] = [
                'content' => $content,
                'author' => $author,
                'timecreated' => (int)$comment->timecreated,
            ];
        }

        return $export;
    }

    /** Build a temporary PDF from already-authorized sources and filtered content. */
    public static function run(array $documents) {
        $python = get_config('mod_pdfworkspace', 'exportpython');
        if (!$python || !is_file($python) || !is_executable($python) || !function_exists('proc_open')) {
            throw new \moodle_exception('combinedexportunavailable', 'pdfworkspace');
        }
        if (!$documents) {
            throw new \moodle_exception('workspaceempty', 'pdfworkspace');
        }
        $tempdir = make_request_directory();
        register_shutdown_function(static function() use ($tempdir) {
            fulldelete($tempdir);
        });
        $manifest = [];
        foreach ($documents as $index => $document) {
            $inputpath = $tempdir . '/source-' . $index . '.pdf';
            if (!$document['file']->copy_content_to($inputpath)) {
                throw new \moodle_exception('combinedexportfailed', 'pdfworkspace');
            }
            $manifest[] = ['source' => $inputpath, 'title' => $document['title'] ?? '',
                'annotations' => $document['annotations'] ?? []];
        }
        $datapath = $tempdir . '/documents.json';
        $outputpath = $tempdir . '/workspace.pdf';
        file_put_contents($datapath, json_encode(['documents' => $manifest], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $command = [$python, dirname(__DIR__) . '/export/combined_pdf.py', $manifest[0]['source'], $datapath, $outputpath];
        $pipes = [];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            throw new \moodle_exception('combinedexportfailed', 'pdfworkspace');
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $deadline = microtime(true) + 120;
        $error = '';
        $timedout = false;
        do {
            $error .= substr((string)stream_get_contents($pipes[2]), 0, 8192 - strlen($error));
            stream_get_contents($pipes[1]);
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            if (microtime(true) > $deadline) {
                proc_terminate($process);
                $error = 'PDF export timed out';
                $timedout = true;
                break;
            }
            usleep(100000);
        } while (true);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        if ($timedout || ($status['exitcode'] ?? -1) !== 0 ||
                !is_file($outputpath) || filesize($outputpath) < 10) {
            error_log('mod_pdfworkspace combined export failed: ' . substr($error, 0, 2000));
            throw new \moodle_exception('combinedexportfailed', 'pdfworkspace');
        }
        return $outputpath;
    }
}
