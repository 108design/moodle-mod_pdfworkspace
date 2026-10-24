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
 * File documentation for amd/src/admin_help_form.js.
 *
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Copyright (C) 2026 Andreas Giesen. See LICENSE.

/** Align help in our registered forms without changing fields or their values. */
export const init = () => {
    const navigation = document.querySelector('#usernavigation');
    const alignNavbarHelp = () => {
        if (!navigation) return;
        const help = navigation.querySelector('.mod-pdfworkspace-help-navbar');
        const children = [...navigation.children];
        const userMenu = children.find(node => node.matches('[data-region="usermenu"], .usermenu-container'));
        const userIndex = userMenu ? children.indexOf(userMenu) : children.length;
        // Older Moodle places its divider after the user menu, outside the help insertion area.
        const anchor = children.find((node, index) => index < userIndex &&
            node.classList.contains('divider')) || userMenu;
        if (help && anchor && (help.parentElement !== navigation || help.nextElementSibling !== anchor)) {
            navigation.insertBefore(help, anchor);
        }
    };
    // Navbar callbacks can finish in any order; keep help immediately before the user-area divider.
    alignNavbarHelp();
    if (navigation) new MutationObserver(alignNavbarHelp).observe(navigation, {childList: true});

    // Keep linked information beside its native control, retaining labels and defaults.
    document.querySelectorAll('.form-item .form-text .mod-pdfworkspace-context-help-icon').forEach(icon => {
        const setting = icon.closest('.form-item')?.querySelector('.form-setting');
        const field = setting?.querySelector('input:not([type="hidden"]), select, textarea');
        if (!field) return;
        let control = field;
        while (control.parentElement !== setting) control = control.parentElement;
        const row = document.createElement('div');
        row.className = 'mod-pdfworkspace-setting-control';
        control.before(row);
        row.append(control, icon);
    });

    // Native activity forms keep short ? help in the label column and linked i beside the control.
    document.querySelectorAll('[data-pdfworkspace-help-field]').forEach(holder => {
        const name = holder.dataset.pdfworkspaceHelpField;
        const field = document.getElementById('id_' + name);
        const item = field?.closest('.fitem');
        const element = item?.querySelector('.felement, .checkbox');
        const icon = holder.querySelector('.mod-pdfworkspace-context-help-icon');
        if (!field || !element || !icon) return;
        const control = name === 'files' ? document.getElementById('id_files_fieldset') :
            field.closest('.form-check') || field;
        if (!control || !element.contains(control)) return;
        const row = document.createElement('div');
        row.className = 'mod-pdfworkspace-setting-control';
        if (name === 'files') row.classList.add('w-100');
        control.before(row);
        row.append(control, icon);
        holder.closest('.fitem')?.remove();
    });
    const fields = ['name', 'files', 'studentdefaultaudience', 'staffdefaultaudience',
        'audience_student_private', 'audience_student_protected', 'audience_student_public',
        'audience_staff_private', 'audience_staff_targeted', 'audience_staff_public',
        'usevotes', 'use_studenttextbox', 'use_studentdrawing', 'useprint', 'useprintcomments',
        'usecombineddownload', 'useworkspacedownload', 'useworkspacecomments'];
    fields.forEach(name => {
        const field = document.getElementById('id_' + name);
        const item = field?.closest('.fitem');
        const label = item?.querySelector(':scope > .col-form-label');
        const help = item?.querySelector('.help-icon:not(.mod-pdfworkspace-context-help-icon)');
        if (help && label) {
            let addon = label.querySelector('.form-label-addon');
            if (!addon) {
                addon = document.createElement('span');
                addon.className = 'form-label-addon d-flex align-items-center align-self-start';
                label.append(addon);
            }
            label.classList.add('mod-pdfworkspace-form-help-label');
            addon.append(help);
        }
    });
};
