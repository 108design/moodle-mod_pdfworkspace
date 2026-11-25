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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_pdfworkspace\task;

use mod_pdfworkspace\local\restricted_documents;
use mod_pdfworkspace\local\restricted_renderer;

/**
 * Generate page images for one protected upload committed by the activity form.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class render_restricted_document extends \core\task\adhoc_task {
    /** @return string The name shown in Moodle's task log. */
    public function get_name(): string {
        return get_string('restrictedrendertask', 'pdfworkspace');
    }

    /** @return int Maximum number of jobs the scheduler should run together. */
    protected function get_default_concurrency_limit(): int {
        return 1;
    }

    /** Perform the work independently of the uploader's draft area and session. */
    public function execute(): void {
        global $DB;
        $documentid = (int)$this->get_custom_data()->documentid;
        $pending = ['id' => $documentid, 'pagecount' => 0, 'rendererror' => null];

        // Enforce the site limit even for manually executed jobs or scheduler overrides.
        $factory = \core\lock\lock_config::get_lock_factory('mod_pdfworkspace');
        $lock = $factory->get_lock('restricted-renderer', 0);
        if (!$lock) {
            throw new \moodle_exception('locktimeout');
        }

        $directory = null;
        try {
            $document = $DB->get_record(restricted_documents::TABLE, $pending);
            if (!$document) {
                return;
            }
            $cm = get_coursemodule_from_instance('pdfworkspace', $document->pdfworkspaceid);
            if (!$cm || !empty($cm->deletioninprogress)) {
                return;
            }
            $context = \context_module::instance($cm->id);
            $files = get_file_storage()->get_area_files($context->id, 'mod_pdfworkspace', restricted_documents::ORIGINAL,
                $documentid, 'id', false);
            $original = count($files) === 1 ? reset($files) : null;
            if (!$original) {
                if (!$DB->record_exists(restricted_documents::TABLE, $pending)) {
                    return;
                }
                throw new \moodle_exception('filenotfound', 'pdfworkspace');
            }

            $directory = make_request_directory();
            $source = $directory . '/upload.pdf';
            if (!$original->copy_content_to($source)) {
                throw new \moodle_exception('filenotfound', 'pdfworkspace');
            }
            $start = microtime(true);
            mtrace("Rendering restricted document {$documentid}");
            try {
                $pages = restricted_renderer::render($source, $directory);
            } catch (\moodle_exception $exception) {
                // File-specific failures need a replacement upload. Infrastructure errors can be retried by Moodle.
                if (!in_array($exception->errorcode, [
                    'restrictedinvalidpdf', 'restrictedtoomanypages', 'restrictedoutputlimit',
                    'restrictedrendertimeout', 'restrictedrenderfailed',
                ], true)) {
                    throw $exception;
                }
                $DB->set_field(restricted_documents::TABLE, 'rendererror', $exception->errorcode, $pending);
                mtrace("Restricted document {$documentid}: {$exception->errorcode}");
                return;
            }
            if (restricted_documents::publish($context, $document, $original, $pages)) {
                $bytes = array_sum(array_map('filesize', $pages));
                mtrace(sprintf('Published restricted document %d: %d pages, %.1f MiB, %.2f s',
                    $documentid, count($pages), $bytes / (1024 * 1024), microtime(true) - $start));
            }
        } catch (\Throwable $exception) {
            if ($this->get_attempts_available() === 1) {
                $DB->set_field(restricted_documents::TABLE, 'rendererror', 'restrictedrenderunavailable', $pending);
            }
            throw $exception;
        } finally {
            try {
                if ($directory !== null) {
                    remove_dir($directory);
                }
            } finally {
                $lock->release();
            }
        }
    }
}
