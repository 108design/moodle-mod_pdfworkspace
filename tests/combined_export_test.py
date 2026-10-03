# PDF Workspace PDF export.
# Copyright 2026 Andreas Giesen <andreas@108design.com>
# SPDX-License-Identifier: GPL-3.0-or-later

"""Focused generator test; run with Python that has pypdf and ReportLab."""

import json
import shutil
import sys
import tempfile
from pathlib import Path

from pypdf import PdfReader, PdfWriter
from reportlab.pdfgen import canvas

from sys import path as syspath
syspath.insert(0, str(Path(__file__).resolve().parents[1] / "export"))
from combined_pdf import build  # noqa: E402


def run():
    with tempfile.TemporaryDirectory() as directory:
        root = Path(directory)
        base = root / "base.pdf"
        c = canvas.Canvas(str(base), pagesize=(400, 500))
        c.drawString(40, 450, "Original selectable text")
        c.showPage()
        c.drawString(40, 450, "Rotated cropped source")
        c.save()
        writer = PdfWriter()
        writer.append(str(base))
        writer.pages[1].cropbox.lower_left = (20, 30)
        writer.pages[1].cropbox.upper_right = (380, 470)
        writer.pages[1].rotate(90)
        with (root / "source.pdf").open("wb") as handle:
            writer.write(handle)

        data = {"annotations": [
            {"page": 1, "type": "drawing", "data": {
                "lines": [[50, 60], [150, 60]], "width": 12, "color": "#ffff00", "opacity": .38,
            }, "comments": [{"content": "Erste Notiz", "author": "Test", "timecreated": 1000}]},
            {"page": 1, "type": "textbox", "data": {
                "x": 70, "y": 100, "size": 14, "content": "Zusatztext", "color": "#00549f",
            }, "comments": []},
            {"page": 2, "type": "area", "data": {
                "x": 40, "y": 60, "width": 80, "height": 30,
            }, "comments": [{"content": "Croptest", "author": "Test", "timecreated": 1000}]},
        ]}
        payload = root / "filtered.json"
        payload.write_text(json.dumps(data), encoding="utf-8")
        output = root / "output.pdf"
        build(str(root / "source.pdf"), str(payload), str(output))
        result = PdfReader(str(output))
        assert len(result.pages) == 2
        assert result.pages[1].rotation == 90
        assert "Original selectable text" in result.pages[0].extract_text()
        assert "Zusatztext" in result.pages[0].extract_text()
        assert "Rotated cropped source" in result.pages[1].extract_text()
        notes = [[note.get_object() for note in page.get("/Annots", [])] for page in result.pages]
        assert [len(page) for page in notes] == [1, 1]
        assert notes[0][0]["/Contents"] == "Erste Notiz"
        assert notes[1][0]["/Contents"] == "Croptest"
        assert list(notes[1][0]["/Rect"]) == [60, 410, 78, 428]
        if len(sys.argv) == 2:
            shutil.copyfile(output, sys.argv[1])
        print("combined export: source text, overlay, native notes, crop and rotation passed")


if __name__ == "__main__":
    run()
