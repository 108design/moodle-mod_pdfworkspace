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
 * File documentation for amd/src/tooltips.js.
 *
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/*
 * PDF Workspace tooltip integration.
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Convert PDF Workspace title hints to Moodle's Bootstrap tooltips, including dynamic sidebar items.
import Tooltip from 'theme_boost/bootstrap/tooltip';
import $ from 'jquery';

let initialised = false;
export const init = () => {
    if (initialised) return;
    initialised = true;
        const selector = '#pdfworkspace_index [title], ' +
            '.path-mod-pdfworkspace #region-main table [title], ' +
            '.path-mod-pdfworkspace .pdfworkspace-overview-action[title], ' +
            '.path-mod-pdfworkspace .pdfworkspace-document-filename[title], ' +
            '.path-mod-pdfworkspace .pdfworkspace-document-remove-icon[title], ' +
            '#id_existingdocuments [title]';
        const owned = new Map();
        const hovered = new Set();
        const focused = new Set();

        function target(event) {
            if (!(event.target instanceof Element)) {
                return null;
            }
            // Bootstrap moves title into data-bs-original-title after the first show.
            for (let ancestor = event.target; ancestor; ancestor = ancestor.parentElement) {
                if (owned.has(ancestor)) {
                    return ancestor;
                }
            }
            // The control owns the hint even when the pointer lands on its icon.
            const control = event.target.closest('button[title], a[title], select[title], input[title]');
            const node = control && control.matches(selector) ? control : event.target.closest(selector);
            if (!node || node.closest('.pdfViewer, .tox, .moodle-dialogue-base')) {
                return null;
            }
            return node;
        }

        function show(node) {
            if (!node || node.disabled || node.getAttribute('aria-disabled') === 'true') {
                return;
            }
            let entry = owned.get(node);
            const updatedTitle = node.getAttribute('title');
            if (entry && updatedTitle && updatedTitle !== entry.title) {
                entry.instance.dispose();
                owned.delete(node);
                node.setAttribute('title', updatedTitle);
                entry = null;
            }
            if (!entry) {
                // Moodle, the theme and other plugins may already own a tooltip.
                if (Tooltip.getInstance?.(node) || $(node).data('bs.tooltip') ||
                        node.hasAttribute('data-original-title') || node.hasAttribute('data-bs-original-title')) {
                    return;
                }
                const title = node.getAttribute('title');
                if (!title || title === 'undefined') {
                    return;
                }
                entry = {
                    title,
                    instance: new Tooltip(node, {
                        title,
                        container: 'body',
                        placement: 'top',
                        trigger: 'manual',
                        html: false,
                        animation: false,
                        template: node.classList.contains('pdfworkspace-overview-preview') ?
                            '<div class="tooltip pdfworkspace-preview-tooltip" role="tooltip">' +
                            '<div class="tooltip-arrow arrow"></div><div class="tooltip-inner"></div></div>' :
                            '<div class="tooltip" role="tooltip"><div class="tooltip-arrow arrow"></div>' +
                            '<div class="tooltip-inner"></div></div>',
                    }),
                };
                owned.set(node, entry);
            }
            entry.instance.show();
        }

        function hide(node) {
            owned.get(node)?.instance.hide();
        }

        document.addEventListener('pointerover', function (event) {
            if (event.pointerType === 'touch') {
                return;
            }
            const node = target(event);
            if (!node || (event.relatedTarget instanceof Node && node.contains(event.relatedTarget))) {
                return;
            }
            hovered.add(node);
            show(node);
        });
        document.addEventListener('pointerout', function (event) {
            const node = target(event);
            if (!node || (event.relatedTarget instanceof Node && node.contains(event.relatedTarget))) {
                return;
            }
            hovered.delete(node);
            if (!focused.has(node)) {
                hide(node);
            }
        });
        document.addEventListener('focusin', function (event) {
            const node = target(event);
            if (node) {
                focused.add(node);
                show(node);
            }
        });
        document.addEventListener('focusout', function (event) {
            const node = target(event);
            if (node) {
                focused.delete(node);
                if (!hovered.has(node)) {
                    hide(node);
                }
            }
        });
        document.addEventListener('click', function () {
            owned.forEach(entry => entry.instance.hide());
        }, true);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                owned.forEach(entry => entry.instance.hide());
            }
        });
        const cleanup = new MutationObserver(function () {
            owned.forEach((entry, node) => {
                if (!node.isConnected) {
                    entry.instance.dispose();
                    owned.delete(node);
                    hovered.delete(node);
                    focused.delete(node);
                }
            });
        });
        cleanup.observe(document.body, {childList: true, subtree: true});
        window.addEventListener('pagehide', function () {
            cleanup.disconnect();
            owned.forEach(entry => entry.instance.dispose());
            owned.clear();
        });
};
