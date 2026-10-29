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
 * PDF Workspace integration with bundled PDF.js.
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Keep the Mozilla distribution unmodified and all document assets on this site.
import {getDocument, GlobalWorkerOptions, TextLayer, version} from './pdfjs/pdf.mjs';

const assets = new URL('./pdfjs/', import.meta.url);
GlobalWorkerOptions.workerSrc = new URL('pdf.worker.mjs', assets).href;
export {version};

export function loadDocument(url) {
    return getDocument({
        url,
        cMapUrl: new URL('cmaps/', assets).href,
        cMapPacked: true,
        standardFontDataUrl: new URL('standard_fonts/', assets).href,
        wasmUrl: new URL('wasm/', assets).href,
        iccUrl: new URL('iccs/', assets).href,
        // Embedded PDF JavaScript is not needed for annotation.
        isEvalSupported: false,
    }).promise;
}

export async function renderTextLayer(page, container, viewport) {
    const layer = new TextLayer({
        textContentSource: page.streamTextContent(),
        container,
        viewport,
    });
    await layer.render();
}
