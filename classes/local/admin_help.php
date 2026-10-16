<?php
// Copyright (C) 2026 Andreas Giesen. See LICENSE.
namespace mod_pdfworkspace\local;

defined('MOODLE_INTERNAL') || die();

/** Contextual settings help, following the standalone Frontpage panel contract. */
final class admin_help {
    private const TOPICS = ['overview', 'exports'];
    private static bool $prepared = false;

    /** Register only the permitted settings page before its header is rendered. */
    public static function prepare(\core\hook\output\before_http_headers $hook): void {
        global $CFG, $PAGE;
        if (self::$prepared || admin_branding::page_capability() === null) {
            return;
        }
        $PAGE->add_body_class('mod-pdfworkspace-admin-help-page');
        self::$prepared = true;
        $PAGE->requires->css(new \moodle_url('/mod/pdfworkspace/styles/admin_help.css', [
            'rev' => get_config('mod_pdfworkspace', 'version'),
        ]));
        $module = (int)$CFG->version < 2025041400 ? 'admin_help_legacy' : 'admin_help';
        $PAGE->requires->js_call_amd('mod_pdfworkspace/admin_help_form', 'init');
        $PAGE->requires->js_call_amd('mod_pdfworkspace/' . $module, 'init', [[
            'topic' => optional_param('pdfworkspacehelp', '', PARAM_ALPHA),
        ]]);
    }

    /** Native popover with concise guidance and a direct link to the extended topic. */
    public static function icon(string $key): string {
        global $CFG, $PAGE;
        if (!in_array($key, self::TOPICS, true)) {
            throw new \coding_exception('Unknown Administration help topic.');
        }
        $url = new \moodle_url($PAGE->url);
        $url->param('pdfworkspacehelp', $key);
        $help = new \help_icon($key, 'mod_pdfworkspace', $url->out(false));
        $renderer = $PAGE->get_renderer('core');
        $data = (array)$help->export_for_template($renderer);
        $data['completedoclink'] = \html_writer::link($url, get_string('adminhelpmore', 'mod_pdfworkspace'));
        $data['legacy'] = (int)$CFG->version < 2025041400;
        $data['infoicon'] = (int)$CFG->version < 2026100500 ? 'i/info' : 'i/circleinfo';
        return $renderer->render_from_template('mod_pdfworkspace/settings_help_icon', $data);
    }

    /** Always available in the native Topnav user area on the registered page. */
    public static function navbar(\renderer_base $renderer): string {
        global $CFG;
        if (!self::$prepared) {
            return '';
        }
        $label = get_string('adminhelp', 'mod_pdfworkspace');
        return \html_writer::tag('button', $renderer->pix_icon(
            (int)$CFG->version < 2026100500 ? 'i/info' : 'i/circleinfo', ''), [
                'type' => 'button', 'class' => 'nav-link icon-no-margin mod-pdfworkspace-help-navbar',
                'data-pdfworkspace-help-open' => '', 'aria-label' => $label, 'title' => $label,
                'aria-controls' => 'mod-pdfworkspace-context-help', 'aria-expanded' => 'false',
            ]);
    }

    /** Same header, topic chooser and article layout as the Frontpage reference. */
    public static function render(): string {
        global $PAGE;
        if (!self::$prepared) {
            return '';
        }
        $title = get_string('adminhelp', 'mod_pdfworkspace');
        $close = \html_writer::tag('button', \html_writer::span('&#215;', '', ['aria-hidden' => 'true']), [
            'type' => 'button', 'class' => 'btn btn-secondary btn-sm mod-pdfworkspace-help-close',
            'aria-label' => get_string('closebuttontitle', 'moodle'), 'data-pdfworkspace-help-close' => '1',
        ]);
        $options = '';
        $articles = '';
        foreach (self::TOPICS as $topic) {
            $label = get_string('adminhelp_' . $topic, 'mod_pdfworkspace');
            $options .= \html_writer::tag('option', $label, ['value' => $topic]);
            $articles .= \html_writer::tag('article', \html_writer::tag('h3', $label, ['class' => 'h5', 'tabindex' => '-1'])
                . get_string('adminhelp_' . $topic . '_article', 'mod_pdfworkspace'), [
                    'data-pdfworkspace-help-article' => $topic, 'hidden' => 'hidden',
                ]);
        }
        $choice = \html_writer::tag('label', get_string('adminhelptopics', 'mod_pdfworkspace'), [
            'for' => 'mod-pdfworkspace-help-topic', 'class' => 'form-label',
        ]) . \html_writer::tag('select', $options, [
            'id' => 'mod-pdfworkspace-help-topic', 'class' => 'form-select custom-select',
        ]);
        $header = \html_writer::tag('header', \html_writer::tag('h2', $title, [
            'id' => 'mod-pdfworkspace-context-help-title', 'class' => 'sr-only visually-hidden', 'tabindex' => '-1',
        ]) . \html_writer::div($choice, 'mod-pdfworkspace-help-choices') . $close,
            ['class' => 'mod-pdfworkspace-help-header']);
        return \html_writer::tag('aside', $header
            . \html_writer::div($articles, 'mod-pdfworkspace-help-content', ['tabindex' => '0']), [
                'id' => 'mod-pdfworkspace-context-help', 'class' => 'mod-pdfworkspace-context-help',
                'aria-labelledby' => 'mod-pdfworkspace-context-help-title', 'hidden' => 'hidden',
                'data-pdfworkspace-help-url' => $PAGE->url->out(false),
            ]);
    }

    /** Append the registered panel without changing the page's controls. */
    public static function footer(\core\hook\output\before_standard_footer_html_generation $hook): void {
        $hook->add_html(self::render());
    }
}
