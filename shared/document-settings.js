/*
 * PDF Workspace document selection and download order.
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
(function () {
    'use strict';
    function init() {
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
            group.classList.add('pdfworkspace-document-row');
            const holder = input.closest('.fitem') === row ? input : input.closest('.fitem');
            const controls = document.createElement('span');
            controls.className = 'pdfworkspace-order-controls';
            holder.replaceWith(controls);
            input.type = 'hidden';
            controls.append(input);
            for (const [direction, icon, title] of [
                ['up', 'arrow-up', labels.dataset.up], ['down', 'arrow-down', labels.dataset.down],
                ['drag', 'bars', labels.dataset.drag],
            ]) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-outline-secondary btn-sm' + (direction === 'drag' ? ' pdfworkspace-drag' : '');
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
            function removalState() {
                row.classList.toggle('pdfworkspace-document-removing', remove.checked);
                include.disabled = remove.checked;
            }
            remove.addEventListener('change', removalState);
            removalState();
        }
        if (rows().length) { update(false); }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, {once: true});
    } else { init(); }
})();
