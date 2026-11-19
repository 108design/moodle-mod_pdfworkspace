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
 * Prepare server-rendered images of a restricted PDF.
 *
 * @package mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfworkspace\local;

/** Run a locally configured Poppler executable without a shell or user-supplied arguments. */
final class restricted_renderer {
    /** @var int Maximum number of pages accepted per upload. */
    public const MAX_PAGES = 150;
    /** @var int Maximum source PDF size. */
    public const MAX_BYTES = 25 * 1024 * 1024;

    /** @var int Maximum pixel dimension of a rendered page. */
    private const DIMENSION = 1800;
    /** @var int Maximum wall time in seconds for one conversion. */
    private const TIMEOUT = 60;
    /** @var int Maximum combined image size. */
    private const MAX_OUTPUT = 128 * 1024 * 1024;

    /**
     * Resolve the explicitly configured executable if conversion is available.
     *
     * @return string An absolute, executable path, or an empty string
     */
    public static function executable(): string {
        $path = (string)get_config('mod_pdfworkspace', 'restrictedpdftoppm');
        if ($path === '' || !function_exists('proc_open') || !is_file($path) || !is_executable($path)) {
            return '';
        }
        return realpath($path) ?: '';
    }

    /**
     * Render the source to a bounded set of JPEG pages.
     *
     * @param string $source Local filepath of the copied PDF
     * @param string $directory Private per-request directory
     * @return string[] Image paths keyed by one-based page number
     */
    public static function render(string $source, string $directory): array {
        $executable = self::executable();
        if ($executable === '') {
            throw new \moodle_exception('restrictedrenderersetup', 'mod_pdfworkspace');
        }
        $signature = file_get_contents($source, false, null, 0, 1024);
        if ($signature === false || !str_contains($signature, '%PDF-') || filesize($source) > self::MAX_BYTES) {
            throw new \moodle_exception('restrictedinvalidpdf', 'mod_pdfworkspace');
        }
        $command = [$executable, '-q', '-f', '1', '-l', (string)(self::MAX_PAGES + 1),
            '-scale-to', (string)self::DIMENSION, '-cropbox', '-jpeg', '-jpegopt', 'quality=87,optimize=y',
            $source, $directory . '/page'];
        $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $descriptors = [0 => ['file', $null, 'r'], 1 => ['file', $null, 'w'], 2 => ['file', $null, 'w']];
        \core_php_time_limit::raise(self::TIMEOUT + 15);
        $process = proc_open($command, $descriptors, $pipes, $directory, [], ['bypass_shell' => true]);
        if (!is_resource($process)) {
            throw new \moodle_exception('restrictedrenderfailed', 'mod_pdfworkspace');
        }
        $deadline = microtime(true) + self::TIMEOUT;
        try {
            do {
                $status = proc_get_status($process);
                if (!$status['running']) {
                    break;
                }
                if (microtime(true) >= $deadline || connection_aborted()) {
                    proc_terminate($process, 9);
                    throw new \moodle_exception('restrictedrenderfailed', 'mod_pdfworkspace');
                }
                usleep(50000);
            } while (true);
        } finally {
            $result = proc_close($process);
        }
        if ($status['exitcode'] !== 0 && $result !== 0) {
            throw new \moodle_exception('restrictedrenderfailed', 'mod_pdfworkspace');
        }

        $paths = glob($directory . '/page-*.jpg');
        if (!$paths) {
            throw new \moodle_exception('restrictedrenderfailed', 'mod_pdfworkspace');
        }
        if (count($paths) > self::MAX_PAGES) {
            throw new \moodle_exception('restrictedtoomanypages', 'mod_pdfworkspace', '', self::MAX_PAGES);
        }
        $pages = [];
        $bytes = 0;
        foreach ($paths as $path) {
            $size = getimagesize($path);
            $bytes += filesize($path);
            if (!preg_match('/^page-(\d+)\.jpg$/D', basename($path), $matches) || $size === false
                    || $size[2] !== IMAGETYPE_JPEG || max($size[0], $size[1]) > self::DIMENSION || $bytes > self::MAX_OUTPUT) {
                throw new \moodle_exception('restrictedrenderfailed', 'mod_pdfworkspace');
            }
            $pages[(int)$matches[1]] = $path;
        }
        ksort($pages, SORT_NUMERIC);
        if (array_keys($pages) !== range(1, count($paths))) {
            throw new \moodle_exception('restrictedrenderfailed', 'mod_pdfworkspace');
        }
        return $pages;
    }
}
