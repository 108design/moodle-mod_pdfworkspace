<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
defined('MOODLE_INTERNAL') || die; // Prevents crashes on misconfigured production server.

if ($ADMIN->fulltree) {
    require_once('constants.php');
    $settings->add(new admin_setting_configcheckbox('mod_pdfworkspace/usevotes',
            get_string('global_setting_usevotes', 'pdfworkspace'), get_string('global_setting_usevotes_desc', 'pdfworkspace'), 1));

    $settings->add(new admin_setting_configcheckbox('mod_pdfworkspace/useprint',
            get_string('global_setting_useprint', 'pdfworkspace'), get_string('global_setting_useprint_desc', 'pdfworkspace'), 0));

    $settings->add(new admin_setting_configcheckbox('mod_pdfworkspace/useprintcomments',
            get_string('global_setting_useprint_comments', 'pdfworkspace'),
        get_string('global_setting_useprint_comments_desc', 'pdfworkspace'), 0));

    $settings->add(new admin_setting_configtext('mod_pdfworkspace/exportpython',
        get_string('combinedexportpython', 'pdfworkspace'),
        get_string('combinedexportpython_desc', 'pdfworkspace'), '', PARAM_RAW_TRIMMED));

    $settings->add(new admin_setting_configcheckbox('mod_pdfworkspace/use_studenttextbox',
            get_string('global_setting_use_studenttextbox', 'pdfworkspace'),
            get_string('global_setting_use_studenttextbox_desc', 'pdfworkspace'), 0));

    $settings->add(new admin_setting_configcheckbox('mod_pdfworkspace/use_studentdrawing',
            get_string('global_setting_use_studentdrawing', 'pdfworkspace'),
            get_string('global_setting_use_studentdrawing_desc', 'pdfworkspace'), 0));

    // Define what API to use for converting latex formulas into png.
    $options = array();
    $options[LATEX_TO_PNG_MOODLE] = get_string("global_setting_latexusemoodle", "pdfworkspace");
    $options[LATEX_TO_PNG_GOOGLE_API] = get_string("global_setting_latexusegoogle", "pdfworkspace");
    $settings->add(new admin_setting_configselect('mod_pdfworkspace/latexapi', get_string('global_setting_latexapisetting',
        'pdfworkspace'),
        get_string('global_setting_latexapisetting_desc', 'pdfworkspace'), LATEX_TO_PNG_MOODLE, $options));
    
    $name = new lang_string('global_setting_attobuttons', 'pdfworkspace');
    $desc = new lang_string('global_setting_attobuttons_desc', 'pdfworkspace');
    $default = 'collapse = collapse
style1 = bold, italic, underline
list = unorderedlist, orderedlist
insert = link
other = html
style2 = strike, subscript, superscript
font = fontfamily, fontsize
indent = indent, align
extra = equation, matrix, chemistry, charmap
undo = undo, image
screen = fullscreen';
    $setting = new admin_setting_configtextarea('mod_pdfworkspace/attobuttons', $name, $desc, $default);
    $settings->add($setting);

    if (isset($CFG->maxbytes)) {

        $name = new lang_string('maximumfilesize', 'pdfworkspace');
        $description = new lang_string('configmaxbytes', 'pdfworkspace');
    
        $maxbytes = get_config('mod_pdfworkspace', 'maxbytes');
        $element = new admin_setting_configselect('mod_pdfworkspace/maxbytes',
                                                  $name,
                                                  $description,
                                                  $CFG->maxbytes,
                                                  get_max_upload_sizes($CFG->maxbytes, 0, 0, $maxbytes));
        $settings->add($element);
    }

}
