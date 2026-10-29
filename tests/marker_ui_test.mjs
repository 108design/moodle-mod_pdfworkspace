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
 */
/*
 * Marker and sidebar behavior tests.
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {runInNewContext} from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../amd/src/viewer.js', import.meta.url), 'utf8');

function bundledFunction(startName, nextName) {
    const start = source.indexOf(`function ${startName}(`);
    const end = source.indexOf(`function ${nextName}(`, start);
    assert.ok(start >= 0 && end > start);
    return runInNewContext(`${source.slice(start, end)}\n${startName}`);
}

test('Shift reduces a marker stroke to a straight segment from its first point', () => {
    const nextStrokePoints = bundledFunction('nextStrokePoints', 'savePoint');
    let points = nextStrokePoints([], {x: 10, y: 20}, false);
    points = nextStrokePoints(points, {x: 15, y: 26}, false);
    points = nextStrokePoints(points, {x: 30, y: 40}, true);
    assert.equal(JSON.stringify(points), '[[10,20],[30,40]]');
    points = nextStrokePoints(points, {x: 30, y: 40}, false);
    assert.equal(points.length, 2, 'Mouseup at the same point must not add a kink');
});

test('sidebar channels distinguish public, private, teacher and selected student', () => {
    const questionAudienceChannel = bundledFunction('questionAudienceChannel', 'renderQuestions');
    const cases = [
        [{annotationaudience: 'public', visibility: 'public'}, 'public'],
        [{annotationaudience: 'private', visibility: 'private'}, 'private'],
        [{annotationaudience: 'protected', visibility: 'protected'}, 'protected'],
        [{annotationaudience: 'targeted', visibility: 'protected'}, 'targeted'],
        [{annotationaudience: 'public', visibility: 'protected'}, 'protected'],
    ];
    for (const [question, expected] of cases) {
        assert.equal(questionAudienceChannel(question), expected);
    }
});

test('a reverse-direction brush stroke is selectable along its full path, not in empty space', () => {
    const pointNearDrawing = bundledFunction('pointNearDrawing', 'getOffsetAnnotationRect');
    const path = {
        getScreenCTM: () => ({a: 1, b: 0, c: 0, d: 1, e: 0, f: 0}),
        getAttribute: (name) => name === 'd' ? 'M100 100 60 60 M60 60 20 100Z' : '4',
    };
    assert.equal(pointNearDrawing(82, 82, path), true);
    assert.equal(pointNearDrawing(42, 78, path), true);
    assert.equal(pointNearDrawing(60, 95, path), false);
});
