# Vendored Mozilla PDF.js

PDF Workspace integration notes. Copyright 2026 Andreas Giesen
<andreas@108design.com>. GPL v3 or later. The PDF.js files and their licence
notices remain unchanged.

- Version: 6.3.289, official `pdfjs-dist` npm package (Apache-2.0).
- `pdf.mjs` and `pdf.worker.mjs`: unmodified `legacy/build` browser modules.
- `cmaps`, `standard_fonts`, `wasm`, `iccs`: matching package assets, with
  their upstream licences. No CDN or external document processing is used.
- The legacy build supplies compatibility polyfills; it is the current renderer,
  not the retired 2.14 renderer. Modern browsers with ES module support are required.
- Integration lives in the sibling `pdf-renderer.mjs`; do not patch vendor files.
- Update all assets and both modules together. Compare masked images, vector
  text, scans, text selection, rotations, zoom, and existing annotation geometry.

Source: https://github.com/mozilla/pdf.js
Package: https://www.npmjs.com/package/pdfjs-dist/v/6.3.289
