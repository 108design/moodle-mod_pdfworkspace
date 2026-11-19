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
 * Store restricted PDF originals separately from their viewable pages.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace\local;

/** Authorisation and storage shared by the viewer, upload and download entry points. */
final class restricted_documents {
    /** @var string Capability for creating and removing restricted documents. */
    public const MANAGE = 'mod/pdfworkspace:managerestricteddocuments';
    /** @var string Capability for receiving the original PDF. */
    public const DOWNLOAD = 'mod/pdfworkspace:downloadrestricteddocuments';
    /** @var string Moodle file area. Only the original download endpoint reads it. */
    public const ORIGINAL = 'restrictedoriginal';
    /** @var string Moodle file area containing only rendered JPEG pages. */
    public const PAGE = 'restrictedpage';
    /** @var string XMLDB table for restricted documents. */
    public const TABLE = 'pdfworkspace_restricted';

    /**
     * Fetch records only within the supplied workspace.
     *
     * @param int $activityid Workspace instance ID
     * @return \stdClass[]
     */
    public static function listing(int $activityid): array {
        global $DB;
        return $DB->get_records(self::TABLE, ['pdfworkspaceid' => $activityid], 'id ASC');
    }

    /**
     * Store a rendered upload. Both storage areas become visible together on commit.
     *
     * @param \context_module $context Workspace context
     * @param string $title Plain-text name displayed to viewers
     * @param \stored_file $upload Current user's Moodle draft PDF
     * @return int New document ID
     */
    public static function add(\context_module $context, string $title, \stored_file $upload): int {
        global $DB, $USER;
        require_capability('mod/pdfworkspace:view', $context);
        require_capability(self::MANAGE, $context);
        $title = trim(clean_param($title, PARAM_TEXT));
        if ($title === '' || \core_text::strlen($title) > 160) {
            throw new \moodle_exception('restrictedinvalidtitle', 'mod_pdfworkspace');
        }
        if ($upload->get_contextid() != \context_user::instance($USER->id)->id
                || $upload->get_component() !== 'user' || $upload->get_filearea() !== 'draft'
                || $upload->is_external_file() || $upload->is_directory()
                || $upload->get_filesize() > restricted_renderer::MAX_BYTES
                || !str_ends_with(strtolower($upload->get_filename()), '.pdf')) {
            throw new \moodle_exception('restrictedinvalidpdf', 'mod_pdfworkspace');
        }
        $workspace = get_coursemodule_from_id('pdfworkspace', $context->instanceid, 0, false, MUST_EXIST);
        $directory = make_request_directory();
        $source = $directory . '/upload.pdf';
        if (!$upload->copy_content_to($source)) {
            throw new \moodle_exception('restrictedinvalidpdf', 'mod_pdfworkspace');
        }
        $pages = restricted_renderer::render($source, $directory);
        $storage = get_file_storage();
        $transaction = $DB->start_delegated_transaction();
        try {
            $id = $DB->insert_record(self::TABLE, [
                'pdfworkspaceid' => $workspace->instance, 'title' => $title, 'pagecount' => count($pages),
            ]);
            $common = ['contextid' => $context->id, 'component' => 'mod_pdfworkspace', 'itemid' => $id, 'filepath' => '/'];
            $original = $storage->create_file_from_pathname($common + [
                'filearea' => self::ORIGINAL, 'filename' => $upload->get_filename(), 'mimetype' => 'application/pdf',
            ], $source);
            self::register_in_viewer($workspace->instance, $id, $title, $original);
            foreach ($pages as $number => $path) {
                $storage->create_file_from_pathname($common + [
                    'filearea' => self::PAGE, 'filename' => "page-$number.jpg", 'mimetype' => 'image/jpeg',
                ], $path);
            }
            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            if (!$transaction->is_disposed()) {
                $transaction->rollback($exception);
            }
            throw $exception;
        }
        return $id;
    }

    /**
     * List the authorised image endpoints for the common viewer.
     *
     * @param \context_module $context Workspace context
     * @param int $activityid Workspace instance ID
     * @param int $id Protected document ID
     * @return string[]
     */
    public static function page_urls(\context_module $context, int $activityid, int $id): array {
        global $DB;
        require_capability('mod/pdfworkspace:view', $context);
        $document = $DB->get_record(self::TABLE, ['id' => $id, 'pdfworkspaceid' => $activityid], '*', MUST_EXIST);
        $urls = [];
        for ($page = 1; $page <= $document->pagecount; $page++) {
            $urls[] = \moodle_url::make_pluginfile_url($context->id, 'mod_pdfworkspace', self::PAGE, $id,
                '/', "page-$page.jpg")->out(false);
        }
        return $urls;
    }

    /**
     * Register an existing protected file in the document list without copying its original.
     *
     * @param int $activityid Workspace instance ID
     * @param int $id Protected document ID
     * @param string $title Display name
     * @param \stored_file $original Its private file
     */
    public static function register_in_viewer(int $activityid, int $id, string $title, \stored_file $original): void {
        global $DB;
        $previous = $DB->get_field_sql('SELECT MAX(sortorder) FROM {pdfworkspace_documents} WHERE pdfworkspaceid = ?',
            [$activityid]);
        $DB->insert_record('pdfworkspace_documents', [
            'pdfworkspaceid' => $activityid, 'restrictedid' => $id, 'filepath' => '/',
            'filename' => $original->get_filename(), 'displayname' => $title,
            'contenthash' => $original->get_contenthash(), 'sortorder' => (int)$previous + 1,
            'exportincluded' => 0,
        ]);
    }

    /**
     * Return a single JPEG for a document belonging to this activity.
     *
     * @param \context_module $context Workspace context
     * @param int $activityid Workspace instance ID from the resolved course module
     * @param int $id Restricted document ID
     * @param string $filename Requested filename inside the JPEG area
     * @return \stored_file|false
     */
    public static function page_file(\context_module $context, int $activityid, int $id, string $filename): \stored_file|false {
        global $DB;
        if (!has_capability('mod/pdfworkspace:view', $context)
                || !preg_match('/^page-([1-9][0-9]*)\.jpg$/D', $filename, $matches)) {
            return false;
        }
        $document = $DB->get_record(self::TABLE, ['pdfworkspaceid' => $activityid, 'id' => $id], 'id,pagecount');
        if (!$document || (int)$matches[1] > $document->pagecount) {
            return false;
        }
        return get_file_storage()->get_file($context->id, 'mod_pdfworkspace', self::PAGE, $id, '/', $filename);
    }

    /**
     * Read a restricted PDF from its private file area after a separate capability check.
     *
     * @param \context_module $context Workspace context
     * @param int $id Restricted document ID
     * @return \stored_file
     */
    public static function original(\context_module $context, int $id): \stored_file {
        require_capability('mod/pdfworkspace:view', $context);
        require_capability(self::DOWNLOAD, $context);
        self::require_document($context, $id);
        $files = get_file_storage()->get_area_files($context->id, 'mod_pdfworkspace', self::ORIGINAL, $id, 'id', false);
        if (count($files) !== 1) {
            throw new \moodle_exception('filenotfound', 'pdfworkspace');
        }
        return reset($files);
    }

    /**
     * Remove one document and its files after the controller's POST confirmation.
     *
     * @param \context_module $context Workspace context
     * @param int $id Restricted document ID
     */
    public static function remove(\context_module $context, int $id): void {
        global $DB;
        require_capability('mod/pdfworkspace:view', $context);
        require_capability(self::MANAGE, $context);
        $lock = self::lock($id);
        try {
            $document = self::require_document($context, $id);
            $storage = get_file_storage();
            $transaction = $DB->start_delegated_transaction();
            foreach ([self::ORIGINAL, self::PAGE] as $area) {
                $storage->delete_area_files($context->id, 'mod_pdfworkspace', $area, $document->id);
            }
            if ($docid = $DB->get_field('pdfworkspace_documents',
                    ['restrictedid' => $id, 'pdfworkspaceid' => $document->pdfworkspaceid], 'id')) {
                document_removal::delete_discussion($context, $document->pdfworkspaceid, $docid);
                $DB->delete_records('pdfworkspace_documents', ['id' => $docid, 'restrictedid' => $id]);
            }
            restricted_markings::delete_document($document->id);
            $DB->delete_records(self::TABLE, ['id' => $document->id, 'pdfworkspaceid' => $document->pdfworkspaceid]);
            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            if (isset($transaction) && !$transaction->is_disposed()) {
                $transaction->rollback($exception);
            }
            throw $exception;
        } finally {
            $lock->release();
        }
    }

    /**
     * Serialise writes to a protected document and its markings.
     *
     * @param int $documentid Restricted document ID
     * @return \core\lock\lock
     */
    public static function lock(int $documentid): \core\lock\lock {
        $factory = \core\lock\lock_config::get_lock_factory('mod_pdfworkspace');
        $lock = $factory->get_lock('restricted-document-' . $documentid, 5);
        if (!$lock) {
            throw new \moodle_exception('locktimeout');
        }
        return $lock;
    }

    /**
     * Resolve a restricted document using the activity from the authenticated context.
     *
     * @param \context_module $context Workspace context
     * @param int $id Restricted document ID
     * @return \stdClass
     */
    private static function require_document(\context_module $context, int $id): \stdClass {
        global $DB;
        $cm = get_coursemodule_from_id('pdfworkspace', $context->instanceid, 0, false, MUST_EXIST);
        return $DB->get_record(self::TABLE, ['pdfworkspaceid' => $cm->instance, 'id' => $id], '*', MUST_EXIST);
    }
}
