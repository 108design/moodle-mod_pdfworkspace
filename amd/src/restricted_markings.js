/**
 * This file is part of Moodle - https://moodle.org/
 *
 * @module mod_pdfworkspace/restricted_markings
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {call} from 'core/ajax';

const EXTENT = 10000;
const MAX_POINTS = 300;
const SVG = 'http://www.w3.org/2000/svg';

/**
 * Mount image-relative drawing tools on a restricted page.
 */
export const init = () => {
    const root = document.querySelector('[data-restricted-markings]');
    if (!root) {
        return;
    }
    const image = root.querySelector('img');
    const layer = root.querySelector('svg');
    const status = root.querySelector('[role="status"]');
    const audience = root.querySelector('[data-mark-audience]');
    const recipient = root.querySelector('[data-mark-recipient]');
    const text = root.querySelector('[data-mark-text]');
    const commentForm = root.querySelector('[data-mark-comment]');
    const thread = root.querySelector('[data-mark-thread]');
    const comments = root.querySelector('[data-mark-comments]');
    const close = root.querySelector('[data-mark-close]');
    const controls = [...root.querySelectorAll('[data-mark-audience], [data-mark-recipient], [data-mark-text]')];
    const tools = [...root.querySelectorAll('[data-mark-tool]')];
    const remove = root.querySelector('[data-mark-remove]');
    const marks = JSON.parse(root.dataset.markings);
    const scope = {cmid: Number(root.dataset.cmid), document: Number(root.dataset.document), page: Number(root.dataset.page)};
    let tool = '';
    let drawing = null;
    let busy = false;
    let selected = 0;

    const refresh = () => {
        const active = Boolean(tool) && !busy && (audience?.value !== 'targeted' || Boolean(recipient?.value))
            && (tool !== 'text' || Boolean(text?.value.trim()));
        layer.classList.toggle('pdfworkspace-restricted-drawing', active);
        for (const button of tools) {
            button.disabled = busy;
            button.setAttribute('aria-pressed', String(button.dataset.markTool === tool));
        }
        for (const control of controls) {
            control.disabled = busy;
        }
        if (remove) {
            remove.disabled = busy || !marks.some(mark => mark.own);
        }
        if (recipient) {
            recipient.closest('label').hidden = audience.value !== 'targeted';
        }
        if (text) {
            text.closest('label').hidden = tool !== 'text';
        }
        if (commentForm) {
            commentForm.querySelector('button').disabled = busy;
        }
    };

    const xy = ([x, y]) => [x * image.naturalWidth / EXTENT, y * image.naturalHeight / EXTENT];
    const attributes = (node, values) => Object.entries(values).forEach(([key, value]) => node.setAttribute(key, value));
    const draw = (node, mark) => {
        const dimensions = Math.min(image.naturalWidth, image.naturalHeight);
        const [x, y] = xy(mark.points[0]);
        const [endX, endY] = xy(mark.points[mark.points.length - 1]);
        switch (mark.tool) {
            case 'frame':
                attributes(node, {x: Math.min(x, endX), y: Math.min(y, endY), width: Math.abs(x - endX),
                    height: Math.abs(y - endY), 'stroke-width': dimensions * .003});
                break;
            case 'point':
                attributes(node, {cx: x, cy: y, r: dimensions * .011, 'stroke-width': dimensions * .002});
                break;
            case 'text': {
                const width = image.naturalWidth * .34;
                const height = image.naturalHeight * .16;
                attributes(node, {x: Math.min(x, image.naturalWidth - width),
                    y: Math.min(y, image.naturalHeight - height), width, height});
                node.firstElementChild.style.fontSize = dimensions * .02 + 'px';
                break;
            }
            default: {
                const path = mark.points.map((point, index) => (index ? 'L' : 'M') + xy(point).join(' ')).join(' ');
                attributes(node, {d: path, 'stroke-width': dimensions * (mark.tool === 'marker' ? .017 : .003)});
            }
        }
    };
    const addArtwork = mark => {
        const element = {point: 'circle', frame: 'rect', text: 'foreignObject'}[mark.tool] || 'path';
        const node = document.createElementNS(SVG, element);
        node.classList.add('pdfworkspace-restricted-mark', 'pdfworkspace-restricted-' + mark.tool);
        if (mark.tool === 'text') {
            const note = document.createElement('div');
            note.className = 'pdfworkspace-restricted-note';
            note.textContent = mark.label;
            node.append(note);
        }
        if (mark.id) {
            attributes(node, {tabindex: 0, role: 'button', 'aria-label': root.dataset.open + ' ' + mark.id,
                'aria-pressed': selected === mark.id});
            node.dataset.markid = mark.id;
        }
        draw(node, mark);
        layer.append(node);
        return node;
    };
    const render = () => {
        if (!image.naturalWidth || !image.naturalHeight) {
            return;
        }
        layer.setAttribute('viewBox', '0 0 ' + image.naturalWidth + ' ' + image.naturalHeight);
        layer.replaceChildren();
        marks.forEach(addArtwork);
    };
    const pointOnImage = event => {
        const rect = layer.getBoundingClientRect();
        const normalized = (value, offset, span) => Math.round(Math.max(0, Math.min(EXTENT, (value - offset) * EXTENT / span)));
        return [normalized(event.clientX, rect.left, rect.width), normalized(event.clientY, rect.top, rect.height)];
    };
    const request = async(args, onSuccess, message = '') => {
        busy = true;
        refresh();
        status.textContent = '';
        try {
            const [response] = call([{methodname: 'mod_pdfworkspace_restricted_marking', args: {...scope, ...args}}]);
            onSuccess(await response);
            status.textContent = message;
        } catch (error) {
            status.textContent = error?.message || root.dataset.error;
        } finally {
            busy = false;
            render();
            refresh();
        }
    };

    tools.forEach(button => button.addEventListener('click', () => {
        tool = tool === button.dataset.markTool ? '' : button.dataset.markTool;
        refresh();
        if (tool === 'text') {
            text.focus();
        }
    }));
    controls.forEach(control => control.addEventListener('input', refresh));
    const activateMark = event => {
        const mark = event.target.closest('[data-markid]');
        if (tool || busy || !mark) {
            return;
        }
        if (event.type === 'keydown' && !['Enter', ' '].includes(event.key)) {
            return;
        }
        event.preventDefault();
        selected = Number(mark.dataset.markid);
        request({threadid: selected}, renderThread);
    };
    layer.addEventListener('click', activateMark);
    layer.addEventListener('keydown', activateMark);
    image.addEventListener('load', render);
    layer.addEventListener('pointerdown', event => {
        if (busy || drawing || !tool || !image.naturalWidth || event.button !== 0) {
            return;
        }
        event.preventDefault();
        const point = pointOnImage(event);
        drawing = {
            pointer: event.pointerId, tool, points: [point], label: text?.value.trim() || '',
            audience: audience.value, recipient: Number(recipient?.value || 0),
        };
        drawing.artwork = addArtwork(drawing);
        layer.setPointerCapture(event.pointerId);
    });
    layer.addEventListener('pointermove', event => {
        if (!drawing || event.pointerId !== drawing.pointer || drawing.points.length >= MAX_POINTS
                || ['point', 'text'].includes(drawing.tool)) {
            return;
        }
        const point = pointOnImage(event);
        const previous = drawing.points[drawing.points.length - 1];
        if (Math.abs(point[0] - previous[0]) + Math.abs(point[1] - previous[1]) >= 14) {
            if (drawing.tool === 'frame') {
                drawing.points[1] = point;
            } else {
                drawing.points.push(point);
            }
            draw(drawing.artwork, drawing);
        }
    });
    const discard = event => {
        if (drawing?.pointer === event.pointerId) {
            drawing.artwork.remove();
            drawing = null;
        }
    };
    layer.addEventListener('pointercancel', discard);
    layer.addEventListener('lostpointercapture', discard);
    layer.addEventListener('pointerup', event => {
        if (!drawing || event.pointerId !== drawing.pointer) {
            return;
        }
        const {audience, recipient, label} = drawing;
        const mark = {tool: drawing.tool, points: drawing.points, label, own: true};
        if (mark.tool === 'frame') {
            mark.points[1] = pointOnImage(event);
        } else if (mark.points.length < MAX_POINTS) {
            mark.points.push(pointOnImage(event));
        }
        drawing = null;
        request({tool: mark.tool, points: JSON.stringify(mark.points), audience, recipient, label}, result => {
            marks.push({...mark, id: result.id});
            tool = '';
        }, root.dataset.saved);
    });
    remove?.addEventListener('click', () => {
        const mark = [...marks].reverse().find(item => item.own);
        if (mark) {
            request({removeid: mark.id}, () => {
                marks.splice(marks.indexOf(mark), 1);
                if (selected === mark.id) {
                    thread.hidden = true;
                    selected = 0;
                }
            }, root.dataset.removed);
        }
    });
    const renderThread = result => {
        const rows = result.comments.map(comment => {
            const row = document.createElement('li');
            row.className = 'border-bottom pb-2 mb-2';
            const meta = document.createElement('strong');
            meta.textContent = comment.author + ' · ' + comment.date;
            const content = document.createElement('p');
            content.className = 'pdfworkspace-restricted-comment mb-1';
            content.textContent = comment.content;
            row.append(meta, content);
            if (comment.own) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-secondary btn-sm';
                button.textContent = root.dataset.delete;
                button.addEventListener('click', () => {
                    if (!busy) {
                        request({threadid: selected, removecommentid: comment.id}, renderThread);
                    }
                });
                row.append(button);
            }
            return row;
        });
        if (!rows.length) {
            const empty = document.createElement('li');
            empty.textContent = root.dataset.empty;
            rows.push(empty);
        }
        comments.replaceChildren(...rows);
        thread.hidden = false;
        close.focus({preventScroll: true});
        thread.scrollIntoView({block: 'nearest'});
    };
    close.addEventListener('click', () => {
        if (busy) {
            return;
        }
        const last = layer.querySelector('[data-markid="' + selected + '"]');
        thread.hidden = true;
        selected = 0;
        last?.setAttribute('aria-pressed', 'false');
        last?.focus();
    });
    commentForm?.addEventListener('submit', event => {
        event.preventDefault();
        const field = commentForm.querySelector('textarea');
        if (!busy && selected && field.value.trim()) {
            request({threadid: selected, comment: field.value}, result => {
                field.value = '';
                renderThread(result);
            });
        }
    });
    render();
    refresh();
};
