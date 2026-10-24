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
 * File documentation for amd/src/admin_help.js.
 *
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.
import {publish, subscribe} from 'core/pubsub';
import DrawerEvents from 'core/drawer_events';
import Drawers from 'theme_boost/drawers';
import Popover from 'theme_boost/bootstrap/popover';

/** Nonmodal contextual help; use native exclusive drawer coordination when available. */
export const init = config => {
    const panel = document.getElementById('mod-pdfworkspace-context-help');
    if (!panel || panel.dataset.initialised) return;
    panel.dataset.initialised = '1';
    document.body.append(panel);
    const heading = panel.querySelector('h2');
    const content = panel.querySelector('.mod-pdfworkspace-help-content');
    const choice = panel.querySelector('select');
    const helpUrl = new URL(panel.dataset.pdfworkspaceHelpUrl, location.href);
    const hasTopic = topic => [...choice.options].some(option => option.value === topic);
    let opener = null;
    let activeTopic = '';
    let ownsSpace = false;
    const exclusiveDrawers = Boolean(DrawerEvents.DRAWER_EXCLUSIVE_REQUESTED && DrawerEvents.DRAWER_EXCLUSIVE_RELEASED);
    document.body.classList.toggle('mod-pdfworkspace-help-legacy-drawers', !exclusiveDrawers);

    // A mobile drawer owns a modal focus lock; release it through Core without saving preferences.
    const closeMobileDrawers = () => {
        if (!window.matchMedia('(max-width: 991.98px)').matches) return;
        document.querySelectorAll('.drawer.drawer-left.show, .drawer.drawer-right.show').forEach(node => {
            Drawers.getDrawerInstanceForNode(node).closeDrawer({focusOnOpenButton: false, updatePreferences: false});
        });
    };
    const position = () => {
        const nav = document.querySelector('.navbar.fixed-top');
        const top = nav ? Math.max(0, nav.getBoundingClientRect().bottom) + 12 : 12;
        panel.style.setProperty('--mod-pdfworkspace-help-top', `${top}px`);
    };
    const hide = (returnFocus = true, releaseSpace = true) => {
        if (panel.hidden) return;
        panel.hidden = true;
        document.body.classList.remove('mod-pdfworkspace-help-open');
        document.querySelectorAll('[data-pdfworkspace-help-open]').forEach(button => button.setAttribute('aria-expanded', 'false'));
        if (ownsSpace) {
            ownsSpace = false;
            if (releaseSpace) publish(DrawerEvents.DRAWER_EXCLUSIVE_RELEASED, {region: 'right'});
        }
        if (returnFocus && opener?.isConnected) opener.focus();
    };
    const showTopic = topic => {
        const article = [...panel.querySelectorAll('[data-pdfworkspace-help-article]')]
            .find(node => node.dataset.pdfworkspaceHelpArticle === topic);
        if (!article) return;
        panel.querySelectorAll('article').forEach(node => { node.hidden = node !== article; });
        choice.value = topic;
        if (activeTopic !== topic) content.scrollTop = 0;
        activeTopic = topic;
    };
    const show = (topic, trigger) => {
        if (topic && !hasTopic(topic)) return;
        if (trigger) opener = trigger;
        closeMobileDrawers();
        showTopic(topic || activeTopic || choice.value);
        position();
        if (panel.hidden) {
            if (exclusiveDrawers) {
                publish(DrawerEvents.DRAWER_EXCLUSIVE_REQUESTED, {region: 'right'});
                ownsSpace = true;
            }
            panel.hidden = false;
            document.body.classList.add('mod-pdfworkspace-help-open');
        }
        document.querySelectorAll('[data-pdfworkspace-help-open]').forEach(button => button.setAttribute('aria-expanded', 'true'));
        (panel.querySelector('article:not([hidden]) h3') || heading).focus();
    };
    document.addEventListener('click', event => {
        // Native information controls inside labels/disclosures must not change their host control.
        if (event.target.closest('.mod-pdfworkspace-context-help-icon')?.closest('label, summary')) {
            event.preventDefault();
        }
        const close = event.target.closest('[data-pdfworkspace-help-close]');
        if (close) { hide(); return; }
        const button = event.target.closest('[data-pdfworkspace-help-open]');
        const link = event.target.closest('a[href]');
        let topic = '';
        if (link) {
            const url = new URL(link.href, window.location.href);
            if (url.origin === location.origin && url.pathname === helpUrl.pathname) {
                const requested = url.searchParams.get('pdfworkspacehelp') || '';
                if (hasTopic(requested)) topic = requested;
            }
        }
        if (!button && !topic) return;
        event.preventDefault();
        if (button && !panel.hidden) { hide(); return; }
        const popover = link?.closest('.popover');
        // Moodle adapters may describe the outer popover or its body; match the actual trigger.
        const describedIds = [popover?.id, popover?.querySelector('.popover-body')?.id].filter(Boolean);
        const icon = [...document.querySelectorAll('.help-icon[aria-describedby]')]
            .find(candidate => candidate.getAttribute('aria-describedby').split(/\s+/)
                .some(id => describedIds.includes(id)));
        const trigger = button || icon || document.querySelector('[data-pdfworkspace-help-open]');
        if (topic) {
            document.querySelectorAll('.help-icon').forEach(icon => Popover.getInstance(icon)?.hide());
        }
        show(topic, trigger);
    });
    choice.addEventListener('change', () => showTopic(choice.value));
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || panel.hidden || document.querySelector('.modal.show, dialog[open]')) return;
        event.preventDefault();
        hide();
    });
    // Native tools win when explicitly reopened. Their DOM and input state are never replaced.
    document.addEventListener(Drawers.eventTypes.drawerShow, event => {
        if ((event.target.classList.contains('drawer-right') ||
                (event.target.classList.contains('drawer-left') && window.matchMedia('(max-width: 991.98px)').matches))
                && !panel.hidden) hide(false);
    });
    // A second overlay now owns the native exclusive request; do not release its claim.
    if (exclusiveDrawers) subscribe(DrawerEvents.DRAWER_EXCLUSIVE_REQUESTED, ({region} = {}) => {
        if (region === 'right' && ownsSpace) hide(false, false);
    });
    document.addEventListener('show.bs.modal', () => hide(false));
    window.addEventListener('resize', () => {
        position();
        if (!panel.hidden) closeMobileDrawers();
    });
    if (config.topic) show(config.topic);
};
