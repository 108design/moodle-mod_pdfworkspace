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
