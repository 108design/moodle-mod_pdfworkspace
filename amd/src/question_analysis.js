// This file is part of Moodle - https://moodle.org/
// @copyright 2026 Andreas Giesen <andreas@108design.com>
// @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

import {call} from 'core/ajax';
import {get_strings as getStrings} from 'core/str';
import PolicyModal from 'core_ai/policymodal';

const statusKeys = ['working', 'policy', 'unavailable', 'empty', 'failed', 'ok', 'limited'];

/**
 * A preset is sent only when clicked. Render generated headings and source excerpts as plain text.
 */
export const init = () => {
    const panel = document.querySelector('[data-pdfworkspace-analysis]');
    if (!panel) {
        return;
    }
    const status = panel.querySelector('[data-analysis-status]');
    const result = panel.querySelector('[data-analysis-result]');
    const buttons = [...panel.querySelectorAll('button[data-preset]')];
    let strings;
    let busy = false;
    buttons.forEach(button => button.addEventListener('click', async() => {
        if (busy) {
            return;
        }
        busy = true;
        buttons.forEach(action => action.setAttribute('aria-disabled', 'true'));
        buttons.forEach(action => action.setAttribute('aria-pressed', String(action === button)));
        result.replaceChildren();
        result.setAttribute('aria-busy', 'true');
        try {
            if (!strings) {
                const values = await getStrings(statusKeys.map(key => ({
                    key: 'aiquestions_' + key, component: 'mod_pdfworkspace',
                })));
                strings = Object.fromEntries(statusKeys.map((key, index) => [key, values[index]]));
            }
            status.textContent = strings.working;
            const [request] = call([{
                methodname: 'mod_pdfworkspace_analyse_questions',
                args: {
                    cmid: Number(panel.dataset.cmid),
                    preset: button.dataset.preset,
                    document: Number(panel.dataset.document),
                    page: Number(panel.dataset.page),
                },
            }]);
            const output = await request;
            if (output.status === 'policy') {
                await PolicyModal.create({trigger: button});
            }
            status.textContent = strings[output.status === 'ok' && output.limited ? 'limited' : output.status] ?? strings.failed;
            for (const group of output.groups) {
                const heading = document.createElement('h4');
                heading.className = 'h6';
                heading.textContent = group.title;
                const list = document.createElement('ul');
                list.className = 'ps-3 mb-3';
                for (const source of group.sources) {
                    const item = document.createElement('li');
                    const link = document.createElement('a');
                    link.href = source.url;
                    link.textContent = source.text;
                    item.append(link);
                    list.append(item);
                }
                result.append(heading, list);
            }
        } catch {
            status.textContent = strings?.failed || panel.dataset.failed;
        } finally {
            busy = false;
            buttons.forEach(action => action.removeAttribute('aria-disabled'));
            result.removeAttribute('aria-busy');
        }
    }));
};
