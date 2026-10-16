<?php
defined('MOODLE_INTERNAL') || die();

$callbacks = [];

// Decorative product identity is restricted to the native administration surfaces.
$callbacks[] = [
    'hook' => \core\hook\output\before_standard_head_html_generation::class,
    'callback' => [\mod_pdfworkspace\local\admin_branding::class, 'head'],
];
$callbacks[] = [
    'hook' => \core\hook\output\before_http_headers::class,
    'callback' => [\mod_pdfworkspace\local\admin_branding::class, 'prepare'],
];

$callbacks[] = [
    'hook' => \core\hook\output\before_http_headers::class,
    'callback' => [\mod_pdfworkspace\local\admin_help::class, 'prepare'],
];
$callbacks[] = [
    'hook' => \core\hook\output\before_standard_footer_html_generation::class,
    'callback' => [\mod_pdfworkspace\local\admin_help::class, 'footer'],
];
