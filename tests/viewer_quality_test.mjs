/*
 * PDF viewer quality tests.
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {runInNewContext} from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../shared/index.js', import.meta.url), 'utf8').replace(/\r\n/g, '\n');
const start = source.indexOf('function getOutputScale(ctx, viewport) {');
const end = source.indexOf('function roundToDivide(x, div) {', start);
assert.ok(start >= 0 && end > start, 'viewer output scale function exists');
const functionSource = source.slice(start, end);

function outputScale(dpr, width, height) {
    return runInNewContext(`${functionSource}\ngetOutputScale({}, {width: ${width}, height: ${height}})`, {
        window: {devicePixelRatio: dpr},
        Math,
    });
}

test('standard PDF page uses native display density', () => {
    const scale = outputScale(1, 595, 842);
    assert.equal(scale.sx, 1);
    assert.equal(scale.sy, 1);
});

test('fractional Windows display scaling is retained', () => {
    assert.equal(outputScale(1.25, 595, 842).sx, 1.25);
});

test('higher device pixel ratio is retained', () => {
    assert.equal(outputScale(4, 595, 842).sx, 4);
});

test('large pages stay within canvas budgets', () => {
    const width = 10000;
    const height = 10000;
    const scale = outputScale(2, width, height);
    assert.ok(width * height * scale.sx * scale.sy <= 16777216.01);
    assert.ok(width * scale.sx <= 8192);
    assert.ok(height * scale.sy <= 8192);
});

test('a page becomes loaded only after canvas and text rendering finish', async () => {
    const start = source.indexOf('let pageRenderTasks = new WeakMap();');
    const end = source.indexOf('/**\n                                 * Scale the elements', start);
    let finishCanvas;
    let finishText;
    const canvasDone = new Promise(resolve => { finishCanvas = resolve; });
    const textDone = new Promise(resolve => { finishText = resolve; });
    const page = {
        isConnected: true,
        dataset: {loaded: 'false'},
        style: {visibility: 'hidden'},
        querySelector: () => ({getContext: () => ({})}),
    };
    const pdfPage = {getViewport: () => ({}), render: () => ({promise: canvasDone})};
    const context = {
        document: {getElementById: () => page},
        currentAnnotations: [],
        _PDFJSAnnotate2: {default: {getAnnotations: async () => ({annotations: []}), render: async () => {}}},
        pdfRenderer: {renderTextLayer: () => textDone},
        scalePage: () => null,
        notification: {addNotification: () => assert.fail('unexpected render error')},
        M: {util: {get_string: () => 'error'}},
        console,
    };
    const render = runInNewContext(`${source.slice(start, end)}; renderPage`, context);
    const options = {pdfDocument: {getPage: async () => pdfPage}, scale: 1, rotate: 0};
    const result = render(1, options);
    assert.equal(render(1, options), result, 'concurrent page requests share a task');
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(page.dataset.loaded, 'false');
    assert.equal(page.style.visibility, 'hidden', 'unfinished page remains hidden');
    finishCanvas();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(page.dataset.loaded, 'false', 'text rendering is also awaited');
    assert.equal(page.style.visibility, 'hidden', 'canvas alone does not reveal the page');
    finishText();
    await result;
    assert.equal(page.dataset.loaded, 'true');
    assert.equal(page.style.visibility, 'visible');
});
