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
 * File documentation for classes/local/admin_help.php.
 *
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.
namespace mod_pdfworkspace\local;

defined('MOODLE_INTERNAL') || die();

/** Contextual settings help, following the standalone Frontpage panel contract. */
final class admin_help {
    private const TOPICS = ['overview', 'exports', 'courseoverview', 'viewer', 'downloads', 'discussion', 'moderation',
        'activityoverview', 'activitysettings', 'questiondashboard', 'recentanswers', 'courseimport', 'aipilot',
        'restricteddocuments', 'restrictedupload', 'restrictedsetup'];
    private static array $topics = ['overview', 'exports'];
    private static string $titlekey = 'adminhelp';
    private static bool $prepared = false;
    private static bool $activitysettings = false;
    private static bool $analysissettings = false;
    private static bool $analysis = false;

    /** Register the pilot settings page after checking access. */
    public static function register_analysis_settings(): void {
        self::$analysissettings = true;
    }

    /** Render the direct panel opener and the settings link on the questions overview. */
    public static function analysis_controls(\context_module $context): string {
        $controls = '';
        if (self::$analysis) {
            $icon = \html_writer::tag('i', '', ['class' => 'fa fa-wand-magic-sparkles me-1', 'aria-hidden' => 'true']);
            $label = get_string('aiquestions_open', 'mod_pdfworkspace');
            $controls .= \html_writer::tag('button', $icon . $label, [
                'type' => 'button', 'class' => 'btn btn-primary',
                'data-pdfworkspace-help-open' => '', 'data-pdfworkspace-help-view' => 'analysis',
                'aria-controls' => 'mod-pdfworkspace-context-help', 'aria-expanded' => 'false',
            ]);
            $controls .= self::icon('aipilot');
        }
        if (in_array('aipilot', self::$topics, true) && question_analysis::can_configure($context)) {
            $url = new \moodle_url('/mod/pdfworkspace/ai_settings.php', ['id' => $context->instanceid]);
            $controls .= \html_writer::link($url, get_string('aiquestions_settings', 'mod_pdfworkspace'),
                ['class' => 'btn btn-link']);
        }
        return $controls === '' ? '' : \html_writer::div($controls, 'd-flex flex-wrap align-items-center gap-2 mb-3');
    }

    /** Called only by this activity's native edit form. */
    public static function register_activity_settings(): void {
        self::$activitysettings = true;
    }

    /** Place an exact-topic icon beside its named native form control. */
    public static function field_icon(string $field, string $topic): string {
        return \html_writer::span(self::icon($topic), '', ['data-pdfworkspace-help-field' => $field]);
    }

    /** Register only the permitted settings page before its header is rendered. */
    public static function prepare(\core\hook\output\before_http_headers $hook): void {
        global $CFG, $PAGE, $USER;
        if (self::$prepared) {
            return;
        }
        // The native course-index controller has already enforced course login.
        $courseindex = (int)$PAGE->context->contextlevel === CONTEXT_COURSE
            && $PAGE->url->get_path() === (new \moodle_url('/mod/pdfworkspace/index.php'))->get_path();
        $activityview = (int)$PAGE->context->contextlevel === CONTEXT_MODULE
            && $PAGE->url->get_path() === (new \moodle_url('/mod/pdfworkspace/view.php'))->get_path()
            && has_capability('mod/pdfworkspace:view', $PAGE->context);
        $activitysettings = self::$activitysettings
            && $PAGE->url->get_path() === (new \moodle_url('/course/modedit.php'))->get_path()
            && has_capability('moodle/course:manageactivities', $PAGE->context);
        if (!$courseindex && !$activityview && !$activitysettings && !self::$analysissettings
                && admin_branding::page_capability() === null) {
            return;
        }
        self::$topics = $courseindex ? ['courseoverview'] : ['overview', 'exports', 'restrictedsetup'];
        if (self::$analysissettings) {
            self::$topics = ['aipilot'];
        }
        if ($activityview) {
            self::$topics = ['viewer', 'activityoverview', 'discussion', 'downloads'];
            $action = \pdfworkspace_request_action();
            if (str_starts_with($action, 'overview')) {
                self::$topics = ['activityoverview', 'viewer', 'discussion', 'downloads'];
            }
            if (has_capability('mod/pdfworkspace:viewquestions', $PAGE->context)
                    && \mod_pdfworkspace\visibility::is_staff($PAGE->context, $USER->id)) {
                self::$topics[] = 'questiondashboard';
            }
            if ($action === 'overviewquestions' && question_analysis::site_enabled()) {
                $enabled = question_analysis::available_manager($PAGE->context) !== null;
                if ($enabled || question_analysis::can_configure($PAGE->context)) {
                    self::$topics[] = 'aipilot';
                }
                if ($enabled) {
                    self::$analysis = true;
                    $PAGE->add_body_class('mod-pdfworkspace-combined-panel');
                    $PAGE->requires->js_call_amd('mod_pdfworkspace/question_analysis', 'init');
                }
            }
            if (has_capability('mod/pdfworkspace:viewanswers', $PAGE->context)) {
                self::$topics[] = 'recentanswers';
            }
            if (has_any_capability(['mod/pdfworkspace:deleteany', 'mod/pdfworkspace:hidecomments',
                    'mod/pdfworkspace:viewreports'], $PAGE->context)) {
                self::$topics[] = 'moderation';
            }
            if (restricted_documents::listing($PAGE->cm->instance)) {
                self::$topics[] = 'restricteddocuments';
            }
        }
        if ($activitysettings) {
            self::$topics = ['activitysettings', 'courseimport', 'discussion', 'downloads'];
            if (has_capability(restricted_documents::MANAGE, $PAGE->context)) {
                self::$topics[] = 'restrictedupload';
                self::$topics[] = 'restrictedsetup';
            }
            if (question_analysis::site_enabled()) {
                self::$topics[] = 'aipilot';
            }
        }
        self::$titlekey = $courseindex || $activityview || $activitysettings || self::$analysissettings
            ? 'activityhelp' : 'adminhelp';
        $PAGE->add_body_class('mod-pdfworkspace-admin-help-page');
        self::$prepared = true;
        $PAGE->requires->css(new \moodle_url('/mod/pdfworkspace/styles/admin_help.css', [
            'rev' => get_config('mod_pdfworkspace', 'version'),
            'theme' => theme_get_revision(),
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
        if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
            return '';
        }
        if (!in_array($key, self::TOPICS, true)) {
            throw new \coding_exception('Unknown Administration help topic.');
        }
        $url = new \moodle_url($PAGE->url);
        if ($PAGE->url->get_path() === (new \moodle_url('/mod/pdfworkspace/view.php'))->get_path()) {
            $action = optional_param('action', '', PARAM_ALPHA);
            if ($action !== '') {
                $url->param('action', $action);
            }
        }
        $url->param('pdfworkspacehelp', $key);
        $help = new \help_icon('adminhelp_' . $key, 'mod_pdfworkspace', $url->out(false));
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
        $label = get_string(self::$titlekey, 'mod_pdfworkspace');
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
        $title = get_string(self::$titlekey, 'mod_pdfworkspace');
        $close = \html_writer::tag('button', '<svg class="design108-panel-close-icon" viewBox="0 0 16 16"'
            . ' width="16" height="16" aria-hidden="true" focusable="false"'
            . ' fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M3 3l10 10M13 3L3 13"/></svg>', [
            'type' => 'button', 'class' => 'btn btn-secondary btn-sm mod-pdfworkspace-help-close',
            'aria-label' => get_string('closebuttontitle', 'moodle'), 'data-pdfworkspace-help-close' => '1',
        ]);
        $options = '';
        $articles = '';
        foreach (self::$topics as $topic) {
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
        // Shared help-panel identity: no link or action; close remains beside the topic selector.
        $brand = \html_writer::tag('span', \html_writer::empty_tag('img', [
            'src' => $PAGE->get_renderer('core')->image_url('helpbrand', 'mod_pdfworkspace')->out(false),
            'alt' => '', 'width' => 25, 'height' => 24,
        ]), [
            'class' => 'mod-pdfworkspace-help-brand', 'role' => 'img', 'aria-label' => '108design',
            'title' => '108design', 'tabindex' => '0', 'data-toggle' => 'tooltip', 'data-bs-toggle' => 'tooltip',
        ]);
        if (self::$analysis) {
            $header = \html_writer::tag('header', \html_writer::tag('h2', get_string('pluginname', 'mod_pdfworkspace'), [
                'id' => 'mod-pdfworkspace-context-help-title', 'class' => 'h6 mb-0', 'tabindex' => '-1',
            ]) . $brand . $close, ['class' => 'mod-pdfworkspace-help-header mod-pdfworkspace-help-header-branded']);
            $tabs = '';
            foreach (['analysis' => 'aiquestions', 'help' => 'adminhelp_helptab'] as $tab => $label) {
                $selected = $tab === 'help';
                $tabs .= \html_writer::tag('button', get_string($label, 'mod_pdfworkspace'), [
                    'type' => 'button', 'id' => 'mod-pdfworkspace-tab-' . $tab,
                    'class' => 'nav-link' . ($selected ? ' active' : ''),
                    'role' => 'tab', 'data-pdfworkspace-panel-tab' => $tab,
                    'aria-controls' => 'mod-pdfworkspace-panel-' . $tab,
                    'aria-selected' => $selected ? 'true' : 'false', 'tabindex' => $selected ? 0 : -1,
                ]);
            }
            $header .= \html_writer::div($tabs, 'nav nav-tabs nav-fill px-3 flex-nowrap', [
                'role' => 'tablist', 'aria-labelledby' => 'mod-pdfworkspace-context-help-title',
            ]);
            $content = \html_writer::div(question_analysis::panel(), 'mod-pdfworkspace-analysis-content', [
                'id' => 'mod-pdfworkspace-panel-analysis', 'role' => 'tabpanel',
                'aria-labelledby' => 'mod-pdfworkspace-tab-analysis', 'tabindex' => '0', 'hidden' => 'hidden',
            ]) . \html_writer::div(\html_writer::div($choice, 'mod-pdfworkspace-help-choices') . $articles,
                'mod-pdfworkspace-help-content', [
                    'id' => 'mod-pdfworkspace-panel-help', 'role' => 'tabpanel',
                    'aria-labelledby' => 'mod-pdfworkspace-tab-help', 'tabindex' => '0',
                ]);
        } else {
            $header = \html_writer::tag('header', \html_writer::tag('h2', $title, [
                'id' => 'mod-pdfworkspace-context-help-title', 'class' => 'sr-only visually-hidden', 'tabindex' => '-1',
            ]) . \html_writer::div($choice, 'mod-pdfworkspace-help-choices') . $brand . $close,
                ['class' => 'mod-pdfworkspace-help-header mod-pdfworkspace-help-header-branded']);
            $content = \html_writer::div($articles, 'mod-pdfworkspace-help-content', ['tabindex' => '0']);
        }
        return \html_writer::tag('aside', $header . $content, [
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
