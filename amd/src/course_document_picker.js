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
 * Present Moodle's native multiselect as a list grouped by course section.
 *
 * @module mod_pdfworkspace/course_document_picker
 * @copyright 2026 Andreas Giesen
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const region = (name) => `[data-region="course-pdf-${name}"]`;
const fileToggle = '[data-action="select-course-pdf"]';

/**
 * Use the native select for form submission and redisplay submitted selections.
 *
 * @param {string} countText Translated selection counter with a {count} placeholder.
 */
export const init = (countText) => {
    const select = document.getElementById('id_courseimportfiles');
    const picker = document.querySelector(region('picker'));
    if (!select || !picker || !picker.classList.contains('d-none')) {
        return;
    }
    const options = new Map([...select.options].map(option => [option.value, option]));
    const sections = [...picker.querySelectorAll('details[data-section]')];
    const sources = [...picker.querySelectorAll(region('source'))];
    const files = [...picker.querySelectorAll(region('file'))].map(file => {
        const names = [file.querySelector('label').textContent, file.closest(region('source')).dataset.sourceName];
        for (let section = file.closest('details[data-section]'); section;
                section = section.parentElement.closest('details[data-section]')) {
            names.push(section.querySelector(`:scope > summary ${region('section-name')}`).textContent);
        }
        return {file, search: names.join(' ').toLocaleLowerCase()};
    });
    const count = picker.querySelector(region('count'));
    const item = picker.closest('.fitem');
    select.after(picker);
    select.classList.add('d-none');
    picker.classList.remove('d-none');
    item?.remove();

    const sync = () => {
        const selectedFiles = new Set([...select.selectedOptions].map(option => option.value));
        for (const {file} of files) {
            const checkbox = file.querySelector(fileToggle);
            checkbox.checked = selectedFiles.has(checkbox.value);
        }
        for (const section of sections) {
            const checkboxes = [...section.querySelectorAll(fileToggle)];
            const selected = checkboxes.filter(checkbox => checkbox.checked).length;
            section.querySelector(`:scope > summary ${region('section-count')}`).textContent = `${selected} / ${checkboxes.length}`;
        }
        count.textContent = countText.replace('{count}', selectedFiles.size);
    };

    picker.addEventListener('change', ({target}) => {
        if (target.matches(fileToggle)) {
            options.get(target.value).selected = target.checked;
        } else {
            return;
        }
        select.dispatchEvent(new Event('change', {bubbles: true}));
    });
    select.addEventListener('change', sync);
    sync();

    const expanded = new Set();
    let filtering = false;
    picker.querySelector('[data-action="filter-course-pdfs"]').addEventListener('input', ({target}) => {
        const query = target.value.trim().toLocaleLowerCase();
        if (query && !filtering) {
            sections.filter(section => section.open).forEach(section => expanded.add(section));
        }
        for (const {file, search} of files) {
            file.classList.toggle('d-none', !search.includes(query));
        }
        const visibleFile = `${region('file')}:not(.d-none)`;
        for (const source of sources) {
            source.classList.toggle('d-none', !source.querySelector(visibleFile));
        }
        let matched = false;
        for (const section of sections) {
            const sectionMatch = Boolean(section.querySelector(visibleFile));
            section.classList.toggle('d-none', !sectionMatch);
            if (query || filtering) {
                section.open = query ? sectionMatch : expanded.has(section);
            }
            matched ||= sectionMatch;
        }
        filtering = Boolean(query);
        if (!filtering) {
            expanded.clear();
        }
        picker.querySelector(region('empty')).classList.toggle('d-none', matched);
    });
};
