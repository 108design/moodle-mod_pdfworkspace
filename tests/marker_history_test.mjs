/*
 * Undo and redo behavior tests.
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {runInNewContext} from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../shared/index.js', import.meta.url), 'utf8').replace(/\r\n/g, '\n');
const start = source.indexOf('annotationHistory = (function () {');
const end = source.indexOf('\n\n                // Render stuff', start);
assert.ok(start >= 0 && end > start);
const historySource = source.slice(start, end);

test('bundled annotation renderer is exported to the UI as a function', () => {
    const appendModule = source.slice(source.indexOf('/* 11 */'), source.indexOf('/* 12 */'));
    const uiModule = source.slice(source.indexOf('/* 28 */'), source.indexOf('/* 29 */'));
    assert.match(appendModule, /exports\.default = appendChild/);
    assert.match(appendModule, /module\.exports = exports\['default'\]/);
    assert.match(uiModule, /appendAnnotation: _appendAnnotation\s*,/);
});

function setup() {
    const operations = [];
    const controls = {
        'pdfworkspace-undo': {disabled: true, addEventListener(event, fn) { this[event] = fn; }},
        'pdfworkspace-redo': {disabled: true, addEventListener(event, fn) { this[event] = fn; }},
    };
    let nextId = 100;
    let failDelete = false;
    const adapter = {
        async addAnnotation(id, page, value) {
            const saved = {...value, uuid: nextId++, status: 'success', owner: true};
            operations.push(['add', saved.uuid, page]);
            return saved;
        },
        async deleteAnnotation(id, uuid, info, safe) {
            operations.push(['delete', uuid, safe]);
            return failDelete ? {status: 'error'} : {status: 'success'};
        },
        async editAnnotation(id, page, uuid) {
            operations.push(['edit', uuid, page]);
            return {status: 'success'};
        },
    };
    const context = {
        annotationHistory: null,
        documentId: 7,
        document: {
            getElementById: id => controls[id] || null,
            querySelectorAll: () => [],
            addEventListener: () => {},
        },
        _2: {default: {getStoreAdapter: () => adapter}},
        UI: {addEventListener: () => {}, appendAnnotation: () => {}},
        notification: {addNotification: () => {}},
        M: {util: {get_string: () => 'history error'}},
        console: {error: () => {}},
    };
    const history = runInNewContext(historySource + '\nannotationHistory', context);
    async function click(id) {
        controls[id].click({stopPropagation() {}});
        await new Promise(resolve => setImmediate(resolve));
    }
    return {history, operations, controls, click, failNextDelete: () => { failDelete = true; }};
}

test('undo and redo remap recreated annotation IDs across later steps', async () => {
    const {history, operations, click, controls} = setup();
    const annotation = {uuid: 1, page: 2, type: 'drawing', owner: true, audience: 'private',
        lines: [[1, 1], [2, 2]], opacity: 0.38};
    history.record({kind: 'create', annotation: {...annotation}});
    history.record({kind: 'edit', before: {...annotation}, after: {...annotation, width: 14}});
    history.record({kind: 'delete', annotation: {...annotation, width: 14}});
    for (let i = 0; i < 3; i++) { await click('pdfworkspace-undo'); }
    assert.deepEqual(operations.map(([action, id]) => [action, id]),
        [['add', 100], ['edit', 100], ['delete', 100]]);
    for (let i = 0; i < 3; i++) { await click('pdfworkspace-redo'); }
    assert.deepEqual(operations.slice(3).map(([action, id]) => [action, id]),
        [['add', 101], ['edit', 101], ['delete', 101]]);
    assert.equal(controls['pdfworkspace-redo'].disabled, true);
});

test('failed server deletion leaves the history cursor unchanged', async () => {
    const {history, controls, click, failNextDelete} = setup();
    history.record({kind: 'create', annotation: {uuid: 1, page: 1, type: 'drawing', owner: true}});
    failNextDelete();
    await click('pdfworkspace-undo');
    assert.equal(controls['pdfworkspace-undo'].disabled, false);
    assert.equal(controls['pdfworkspace-redo'].disabled, true);
});
