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
 * File documentation for amd/src/document_settings.js.
 *
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Config from 'core/config';
import {deleteCancel, exception} from 'core/notification';

/**
 * Obtain confirmation for this checkbox without changing the activity yet.
 *
 * @param {HTMLInputElement} checkbox The removal checkbox.
 * @param {HTMLInputElement} token The corresponding native form field.
 * @param {object} settings Labels and the course module ID.
 */
const confirmRemoval = async(checkbox, token, settings) => {
    const body = document.createElement('p');
    body.textContent = settings.warning.replace('{filename}', () => checkbox.dataset.documentFilename);
    await deleteCancel(settings.title, body.outerHTML, settings.confirm, async() => {
        checkbox.disabled = true;
        try {
            const response = await fetch(Config.wwwroot + '/mod/pdfworkspace/delete_document.php', {
                method: 'POST',
                body: new URLSearchParams({
                    sesskey: Config.sesskey,
                    id: settings.cmid,
                    document: checkbox.dataset.documentRemovalId,
                }),
            });
            const result = await response.json();
            if (!response.ok || !result.confirmation) {
                throw new Error(result.message || result.error || response.statusText);
            }
            token.value = result.confirmation;
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change', {bubbles: true}));
        } catch (error) {
            exception(error);
        } finally {
            checkbox.disabled = false;
            if (checkbox.checked) {
                checkbox.focus();
            }
        }
    }, null, {triggerElement: checkbox});
};

let initialised = false;
export function init(settings) {
    if (initialised) return;
    initialised = true;
        const region = document.getElementById('id_existingdocuments');
        const labels = region?.querySelector('.pdfworkspace-order-labels');
        if (!region || !labels) { return; }
        const rows = () => Array.from(region.querySelectorAll('.pdfworkspace-document-setting'));
        let dragging = null;
        function update(announce) {
            rows().forEach((row, index, all) => {
                row.querySelector('input[name^="exportorder_"]').value = index + 1;
                row.querySelector('[data-direction="up"]').disabled = index === 0;
                row.querySelector('[data-direction="down"]').disabled = index === all.length - 1;
            });
            if (announce) {
                labels.textContent = labels.dataset.order + ': ' +
                    rows().map(row => row.querySelector('.pdfworkspace-document-filename').textContent).join(' → ');
            }
        }
        for (const input of region.querySelectorAll('input[name^="exportorder_"]')) {
            const row = input.closest('[data-groupname^="documentrow_"]');
            const filename = row?.querySelector('.pdfworkspace-document-filename');
            const group = filename?.parentElement;
            if (!row || !group) { continue; }
            row.classList.add('pdfworkspace-document-setting');
            const holder = input.closest('.fitem') === row ? input : input.closest('.fitem');
            const controls = document.createElement('span');
            controls.className = 'pdfworkspace-order-controls';
            holder.replaceWith(controls);
            input.type = 'hidden';
            controls.append(input);
            for (const [direction, icon, title] of [
                ['up', 'arrow-up', labels.dataset.up], ['down', 'arrow-down', labels.dataset.down],
                ['drag', 'arrows', labels.dataset.drag],
            ]) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'pdfworkspace-order-action' + (direction === 'drag' ? ' pdfworkspace-drag' : '');
                button.title = title;
                button.setAttribute('aria-label', title + ': ' + row.querySelector('.pdfworkspace-document-filename').textContent);
                button.dataset.direction = direction;
                const glyph = document.createElement('i');
                glyph.className = 'fa fa-' + icon;
                glyph.setAttribute('aria-hidden', 'true');
                button.append(glyph);
                controls.append(button);
                if (direction === 'drag') {
                    button.draggable = true;
                    button.addEventListener('dragstart', event => {
                        dragging = row;
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', input.name);
                        row.classList.add('pdfworkspace-document-dragging');
                    });
                    button.addEventListener('dragend', () => {
                        row.classList.remove('pdfworkspace-document-dragging');
                        dragging = null;
                        update(true);
                    });
                } else {
                    button.addEventListener('click', () => {
                        const all = rows();
                        const index = all.indexOf(row);
                        const other = all[index + (direction === 'up' ? -1 : 1)];
                        if (other) {
                            other.parentElement.insertBefore(row, direction === 'up' ? other : other.nextSibling);
                            update(true);
                            const opposite = direction === 'up' ? 'down' : 'up';
                            (button.disabled ? row.querySelector('[data-direction="' + opposite + '"]') : button).focus();
                        }
                    });
                }
            }
            row.addEventListener('dragover', event => {
                if (dragging && dragging !== row) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                }
            });
            row.addEventListener('drop', event => {
                if (!dragging || dragging === row) { return; }
                event.preventDefault();
                const box = row.getBoundingClientRect();
                const after = event.clientY > box.top + box.height / 2;
                row.parentElement.insertBefore(dragging, after ? row.nextSibling : row);
                update(true);
            });
            const remove = row.querySelector('input[type="checkbox"][name^="removedocument_"]');
            const include = row.querySelector('input[type="checkbox"][name^="exportincluded_"]');
            const grid = document.createElement('div');
            grid.className = 'pdfworkspace-document-row';
            group.prepend(grid);
            // Keep Moodle's feedback and hidden values outside the four visible columns.
            const checkboxCell = field => field.closest('label, .form-check') || field;
            grid.append(checkboxCell(remove), filename, checkboxCell(include), controls);
            const token = remove.dataset.documentRemovalId
                ? remove.form.elements.namedItem('confirmremovedocument_' + remove.dataset.documentRemovalId) : null;
            function removalState() {
                row.classList.toggle('pdfworkspace-document-removing', remove.checked);
                include.disabled = remove.checked;
            }
            remove.addEventListener('change', () => {
                if (token && !remove.checked) {
                    token.value = '';
                }
                if (token && remove.checked && !token.value) {
                    remove.checked = false;
                    confirmRemoval(remove, token, settings).catch(exception);
                }
                removalState();
            });
            if (token && !token.value) {
                remove.checked = false;
            }
            removalState();
        }
        if (rows().length) { update(false); }
}
