#!/usr/bin/env python3
# PDF Workspace PDF export.
# Copyright 2026 Andreas Giesen <andreas@108design.com>
# SPDX-License-Identifier: GPL-3.0-or-later

"""Build a copy of the source PDF with visible vector marks and PDF notes.

Moodle prepares the input JSON after checking the requesting user's permissions.
This helper never connects to the database and receives only filtered content.
"""

import io
import json
import math
import re
import sys
from datetime import datetime, timezone
from pathlib import Path

from pypdf import PdfReader, PdfWriter
from pypdf.annotations import Text
from pypdf.constants import AnnotationFlag
from pypdf.generic import NameObject, TextStringObject
from reportlab.lib.colors import Color
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas


def number(value, default=0.0):
    try:
        result = float(value)
        return result if math.isfinite(result) else default
    except (TypeError, ValueError):
        return default


def color(value, default="#000000"):
    value = str(value or default).strip().lower()
    named = {"black": "#000000", "red": "#ff0000", "blue": "#0000ff", "yellow": "#ffff00"}
    value = named.get(value, value)
    match = re.fullmatch(r"#([0-9a-f]{3}|[0-9a-f]{6})", value)
    if match:
        digits = match.group(1)
        if len(digits) == 3:
            digits = "".join(c * 2 for c in digits)
        return Color(*(int(digits[i:i + 2], 16) / 255 for i in (0, 2, 4)))
    match = re.fullmatch(r"rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)(?:\s*,[^)]*)?\)", value)
    if match:
        return Color(*(min(int(component), 255) / 255 for component in match.groups()))
    return color(default)


def annotation_anchor(item):
    kind, data = item["type"], item["data"]
    if kind in ("highlight", "strikeout"):
        rects = data.get("rectangles") or []
        if rects:
            return number(rects[0].get("x")), number(rects[0].get("y"))
    if kind == "drawing":
        lines = data.get("lines") or []
        if lines:
            return number(lines[0][0]), number(lines[0][1])
    return number(data.get("x")), number(data.get("y"))


def draw_item(c, item):
    kind, data = item["type"], item["data"]
    ink = color(data.get("color"), "#00549f")
    c.saveState()
    c.setStrokeColor(ink)
    c.setFillColor(ink)
    if kind == "drawing":
        lines = data.get("lines") or []
        if len(lines) >= 2:
            c.setStrokeColor(color(data.get("color"), "#000000"))
            c.setLineWidth(max(0.2, min(number(data.get("width"), 1), 100)))
            c.setLineCap(1)
            c.setLineJoin(1)
            c.setStrokeAlpha(max(0, min(number(data.get("opacity"), 1), 1)))
            path = c.beginPath()
            path.moveTo(number(lines[0][0]), number(lines[0][1]))
            for point in lines[1:]:
                path.lineTo(number(point[0]), number(point[1]))
            c.drawPath(path, stroke=1, fill=0)
    elif kind == "area":
        c.setLineWidth(1)
        c.rect(number(data.get("x")), number(data.get("y")),
               max(0, number(data.get("width"))), max(0, number(data.get("height"))),
               stroke=1, fill=0)
    elif kind in ("highlight", "strikeout"):
        for rect in data.get("rectangles") or []:
            x, y = number(rect.get("x")), number(rect.get("y"))
            w, h = max(0, number(rect.get("width"))), max(0, number(rect.get("height")))
            if kind == "highlight":
                c.setFillAlpha(0.35)
                c.rect(x, y, w, h, stroke=0, fill=1)
            else:
                c.setLineWidth(1)
                c.line(x, y, x + w, y)
    elif kind == "textbox":
        size = max(4, min(number(data.get("size"), 12), 100))
        c.setFont(TEXT_FONT, size)
        c.translate(number(data.get("x")), number(data.get("y")) + size)
        c.scale(1, -1)
        c.drawString(0, 0, str(data.get("content") or "")[:2000])
    elif kind in ("point", "pin"):
        x, y = number(data.get("x")), number(data.get("y"))
        c.setFillColor(color("#f6a800" if data.get("color") else "#00549f"))
        c.circle(x, y, 4, stroke=0, fill=1)
        c.line(x, y, x, y + 14)
        c.circle(x, y + 14, 6, stroke=1, fill=0)
    c.restoreState()


def register_text_font():
    candidates = (
        "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
        "/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf",
    )
    for path in candidates:
        if Path(path).is_file():
            pdfmetrics.registerFont(TTFont("PdfWorkspaceText", path))
            return "PdfWorkspaceText"
    return "Helvetica"


TEXT_FONT = register_text_font()


def annotate_pages(writer, reader, items, offset):
    by_page = {}
    for item in items:
        page_number = int(item.get("page", 0))
        if 1 <= page_number <= len(reader.pages):
            by_page.setdefault(page_number - 1, []).append(item)

    for page_number, annotations in by_page.items():
        page = writer.pages[offset + page_number]
        crop, media = page.cropbox, page.mediabox
        unit = number(page.get("/UserUnit"), 1) or 1
        left, top = float(crop.left), float(crop.top)
        memory = io.BytesIO()
        overlay = canvas.Canvas(memory, pagesize=(float(media.width), float(media.height)))
        overlay.translate(-float(media.left), -float(media.bottom))
        # Stored SVG geometry uses the unrotated PDF.js viewport at scale 1.
        # A page's /Rotate is applied by the reader after this merged content.
        overlay.translate(left, top)
        overlay.scale(1 / unit, -1 / unit)
        for item in annotations:
            draw_item(overlay, item)
        overlay.save()
        memory.seek(0)
        page.merge_page(PdfReader(memory).pages[0])

        for item in annotations:
            x, y = annotation_anchor(item)
            px, py = left + x / unit, top - y / unit
            for index, comment in enumerate(item.get("comments") or []):
                # Keep adjacent replies individually editable in external readers.
                nx = min(max(px + (index % 3) * 18, float(crop.left)), float(crop.right) - 18)
                ny = min(max(py - (index // 3) * 18, float(crop.bottom)), float(crop.top) - 18)
                note = Text(rect=(nx, ny, nx + 18, ny + 18),
                            text=str(comment.get("content") or ""),
                            flags=AnnotationFlag.PRINT)
                note[NameObject("/T")] = TextStringObject(str(comment.get("author") or ""))
                note[NameObject("/Name")] = NameObject("/Comment")
                timestamp = number(comment.get("timecreated"))
                if timestamp:
                    moment = datetime.fromtimestamp(timestamp, timezone.utc)
                    note[NameObject("/M")] = TextStringObject(moment.strftime("D:%Y%m%d%H%M%SZ"))
                writer.add_annotation(offset + page_number, note)


def build(source, payload, destination):
    with open(payload, encoding="utf-8") as handle:
        data = json.load(handle)
    # Retain the legacy single-document invocation for existing consumers.
    documents = data.get("documents")
    if documents is None:
        documents = [{"source": source, "annotations": data["annotations"]}]
    if not documents:
        raise ValueError("No PDFs selected for the workspace download")
    writer = PdfWriter()
    for index, document in enumerate(documents):
        reader = PdfReader(document["source"], strict=False)
        if reader.is_encrypted:
            # is_encrypted stays true after successful decryption; use its result.
            if not reader.decrypt(""):
                raise ValueError("This PDF requires an opening password")
        offset = len(writer.pages)
        title = document.get("title") or None
        if title and reader.get_fields():
            # Different PDFs may use identical form field names.
            reader.add_form_topname(f"workspace_{index}")
        writer.append(reader, outline_item=title)
        annotate_pages(writer, reader, document.get("annotations") or [], offset)

    with open(destination, "wb") as handle:
        writer.write(handle)


if __name__ == "__main__":
    if len(sys.argv) != 4:
        sys.exit("usage: combined_pdf.py SOURCE.pdf FILTERED.json OUTPUT.pdf")
    build(*sys.argv[1:])
