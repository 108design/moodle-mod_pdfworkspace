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

namespace mod_pdfworkspace\local;

/**
 * Reuse published PDF files from activities in the same course.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class course_documents {
    /** @var array<int, array{file: \stored_file, cm: \cm_info, section: \section_info, label: string}> Eligible files by ID. */
    private array $files = [];

    /** @var array<int, int> Number of files in each source's content area. */
    private array $sourcecounts = [];

    /** Build the catalogue from Moodle's existing course and file records. */
    public function __construct(int $courseid, int $destinationcmid = 0) {
        $modinfo = get_fast_modinfo($courseid);
        $storage = get_file_storage();
        foreach ($modinfo->get_cms() as $cm) {
            if ((int)$cm->id === $destinationcmid || !in_array($cm->modname, ['resource', 'folder', 'pdfworkspace'], true)
                    || $cm->deletioninprogress
                    || !$cm->visible || !$cm->visibleoncoursepage || !$cm->uservisible || !empty($cm->availability)
                    || !has_capability('mod/' . $cm->modname . ':view', $cm->context)) {
                continue;
            }
            if ($cm->modname === 'pdfworkspace' && !has_capability('moodle/course:managefiles', $cm->context)) {
                continue;
            }
            $section = $cm->get_section_info();
            if (!self::section_is_public($section)) {
                continue;
            }
            $files = $storage->get_area_files($cm->context->id, 'mod_' . $cm->modname, 'content', 0,
                'filepath, filename', false);
            $this->sourcecounts[$cm->id] = count($files);
            foreach ($files as $file) {
                if ($file->get_status() || $file->is_external_file() || $file->get_mimetype() !== 'application/pdf'
                        || strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION)) !== 'pdf') {
                    continue;
                }
                $this->files[$file->get_id()] = ['file' => $file, 'cm' => $cm, 'section' => $section,
                    'label' => ltrim($file->get_filepath() . $file->get_filename(), '/')];
            }
        }
    }

    /** Test the section and any containing subsections even for editors who can view hidden items. */
    private static function section_is_public(\section_info $section): bool {
        while ($section->visible && $section->uservisible && empty($section->availability)) {
            if (!$section->is_delegated()) {
                return true;
            }
            $delegate = $section->get_component_instance();
            if (!$delegate instanceof \core_courseformat\sectiondelegatemodule) {
                return false;
            }
            $parent = $delegate->get_cm();
            if (!$parent->visible || !$parent->visibleoncoursepage || !$parent->uservisible || !empty($parent->availability)) {
                return false;
            }
            $section = $delegate->get_parent_section();
            if (!$section) {
                return false;
            }
        }
        return false;
    }

    /** Native options and the same files grouped by their course sections and source activities. */
    public function options(): array {
        $sections = [];
        $documents = [];
        $groups = [];
        foreach ($this->files as $id => $entry) {
            $section = $entry['section'];
            $cm = $entry['cm'];
            $label = $sections[$section->id] ??= course_get_format($cm->course)->get_section_name($section);
            $name = format_string($cm->name, true, ['context' => $cm->context]);
            $documents[$id] = $label . ' · ' . $name . ' · ' . s($entry['label']);
            $this->include_section($groups, $section, $label);
            $sources = &$groups[$section->id]['sources'];
            if (!isset($sources[$cm->id])) {
                $sources[$cm->id] = ['name' => html_to_text($name, 0), 'type' => get_string('modulename', 'mod_' . $cm->modname),
                    'grouped' => $cm->modname !== 'resource', 'files' => []];
            }
            $sources[$cm->id]['files'][] = ['id' => $id, 'label' => $entry['label']];
            unset($sources);
        }
        $children = [];
        foreach ($groups as $id => $group) {
            $children[$group['parent']][] = $id;
        }
        return [$sections, $documents, self::section_tree($groups, $children, 0)];
    }

    /** Include the containing course sections even when they have no PDFs of their own. */
    private function include_section(array &$groups, \section_info $section, string $label): void {
        if (isset($groups[$section->id])) {
            return;
        }
        $parent = null;
        if ($section->is_delegated()) {
            $delegate = $section->get_component_instance();
            if ($delegate instanceof \core_courseformat\sectiondelegatemodule) {
                $parent = $delegate->get_parent_section();
            }
        }
        if ($parent) {
            $this->include_section($groups, $parent, course_get_format($section->course)->get_section_name($parent));
        }
        $groups[$section->id] = ['id' => $section->id, 'label' => html_to_text($label, 0),
            'parent' => $parent ? $parent->id : 0, 'sources' => []];
    }

    /** Prepare nested lists for the picker template. */
    private static function section_tree(array $groups, array $children, int $parent): array {
        $sections = [];
        foreach ($children[$parent] ?? [] as $id) {
            $section = $groups[$id];
            $section['sources'] = array_values($section['sources']);
            $section['sections'] = self::section_tree($groups, $children, $id);
            $sections[] = $section;
        }
        return $sections;
    }

    /** Resolve submitted IDs using the current catalogue, including overlapping selections only once. */
    public function select(array $sectionids, array $fileids): array {
        $sections = array_fill_keys($sectionids, false);
        $selected = array_fill_keys($fileids, false);
        $files = [];
        foreach ($this->files as $id => $entry) {
            $sectionid = $entry['section']->id;
            if (array_key_exists($sectionid, $sections) || array_key_exists($id, $selected)) {
                $files[$id] = $entry['file'];
                if (array_key_exists($sectionid, $sections)) {
                    $sections[$sectionid] = true;
                }
                if (array_key_exists($id, $selected)) {
                    $selected[$id] = true;
                }
            }
        }
        if (in_array(false, $sections, true) || in_array(false, $selected, true)) {
            throw new \moodle_exception('courseimportunavailable', 'pdfworkspace');
        }
        return $files;
    }

    /** Recheck native visibility authority and the complete source set before hiding it. */
    public function hide_complete_sources(array $files): void {
        require_once(__DIR__ . '/../../../../course/lib.php');
        $selectedbycm = [];
        foreach ($files as $id => $file) {
            $cm = $this->files[$id]['cm'];
            // A workspace contains course activity and discussions besides its PDFs.
            if ($cm->modname === 'pdfworkspace') {
                continue;
            }
            $selectedbycm[$cm->id][$id] = $file->get_contenthash();
        }
        $skipped = false;
        foreach ($selectedbycm as $cmid => $selected) {
            $cm = get_coursemodule_from_id('', $cmid, 0, false, IGNORE_MISSING);
            $source = $this->files[array_key_first($selected)]['cm'];
            if (!$cm || (int)$cm->course !== (int)$source->course || !$cm->visible || !$cm->visibleoncoursepage
                    || !empty($cm->availability) || !empty($cm->deletioninprogress)
                    || !has_capability('moodle/course:activityvisibility', $source->context)
                    || count($selected) !== $this->sourcecounts[$cmid]) {
                $skipped = true;
                continue;
            }
            $current = get_file_storage()->get_area_files($source->context->id, 'mod_' . $source->modname,
                'content', 0, 'id', false);
            $hashes = [];
            foreach ($current as $file) {
                $hashes[$file->get_id()] = $file->get_contenthash();
            }
            if ($selected != $hashes || !set_coursemodule_visible($cmid, 0)) {
                $skipped = true;
                continue;
            }
            \core\event\course_module_updated::create_from_cm($cm, $source->context)->trigger();
        }
        if ($skipped && !(defined('CLI_SCRIPT') && CLI_SCRIPT) && !(defined('WS_SERVER') && WS_SERVER)) {
            \core\notification::info(get_string('courseimporthideskipped', 'pdfworkspace'));
        }
    }
}
