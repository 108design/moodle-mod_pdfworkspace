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
 * Defining plugin specific functions
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen (see README)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @author    Rabea de Groot, Anna Heynkes, Friederike Schwager
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_pdfworkspace\output\answermenu;
use mod_pdfworkspace\output\questionmenu;
use mod_pdfworkspace\output\reportmenu;
use mod_pdfworkspace\output\index;

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/pdfworkspace/lib.php");
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/mod/pdfworkspace/constants.php');

/**
 * Check access to the combined PDF export independently of the two separate downloads.
 *
 * @param stdClass $activity The PDF Workspace activity.
 * @param context_module $context Module context.
 * @return bool
 */
function pdfworkspace_can_download_combined($activity, $context) {
    return !empty($activity->usecombineddownload) ||
        (has_capability('mod/pdfworkspace:printdocument', $context) &&
            has_capability('mod/pdfworkspace:printcomments', $context));
}

/**
 * Display embedded pdfworkspace file.
 * @param object $pdfworkspace
 * @param object $cm
 * @param object $course
 * @param stored_file $file main file
 * @return does not return
 */
function pdfworkspace_display_embed($pdfworkspace, $cm, $course, $file, $page = 1, $annoid = null, $commid = null) {
    global $CFG, $PAGE, $OUTPUT, $USER;

    // The revision attribute's existance is demanded by moodle for versioning and could be saved in the pdfworkspace table in the future.
    // Note, however, that we forbid file replacement in order to prevent a change of meaning in other people's comments.
    $pdfworkspace->revision = 1;

    $context = context_module::instance($cm->id);
    $path = '/' . $context->id . '/mod_pdfworkspace/content/' . $pdfworkspace->revision . $file->get_filepath() . $file->get_filename();
    $fullurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', $path, false);

    $documentobject = new stdClass();
    $documentobject->annotatorid = $pdfworkspace->id;
    $documentobject->pdfdocid = $pdfworkspace->currentdocumentid;
    $documentobject->fullurl = $fullurl;

    $stringman = get_string_manager();
    // With this method you get the strings of the language-Files.
    $strings = $stringman->load_component_strings('pdfworkspace', 'en');
    // Method to use the language-strings in javascript.
    $PAGE->requires->strings_for_js(array_keys($strings), 'pdfworkspace');
    // Load and execute the javascript files.
    $PAGE->requires->js(new moodle_url("/mod/pdfworkspace/shared/textclipper.js"));
    $PAGE->requires->js(new moodle_url("/mod/pdfworkspace/shared/index.js?ver=00077"));
    $PAGE->requires->js(new moodle_url("/mod/pdfworkspace/shared/locallib.js?ver=00009"));

    // Pass parameters from PHP to JavaScript.

    // 1. Toolbar settings.
    $toolbarsettings = new stdClass();
    $toolbarsettings->use_studenttextbox = $pdfworkspace->use_studenttextbox;
    $toolbarsettings->use_studentdrawing = $pdfworkspace->use_studentdrawing;
    $toolbarsettings->useprint = $pdfworkspace->useprint;
    $toolbarsettings->useprintcomments = $pdfworkspace->useprintcomments;
    // 2. Capabilities.
    $capabilities = new stdClass();
    $capabilities->viewquestions = has_capability('mod/pdfworkspace:viewquestions', $context);
    $capabilities->viewanswers = has_capability('mod/pdfworkspace:viewanswers', $context);
    $capabilities->viewposts = has_capability('mod/pdfworkspace:viewposts', $context);
    $capabilities->viewreports = has_capability('mod/pdfworkspace:viewreports', $context);
    $capabilities->deleteany = has_capability('mod/pdfworkspace:deleteany', $context);
    $capabilities->hidecomment = has_capability('mod/pdfworkspace:hidecomments', $context);
    $capabilities->seehiddencomments = has_capability('mod/pdfworkspace:seehiddencomments', $context);
    $capabilities->usetextbox = has_capability('mod/pdfworkspace:usetextbox', $context);
    $capabilities->usedrawing = has_capability('mod/pdfworkspace:usedrawing', $context);
    $capabilities->useprint = has_capability('mod/pdfworkspace:printdocument', $context);
    $capabilities->useprintcomments = has_capability('mod/pdfworkspace:printcomments', $context);
    // 3. Comment editor setting.
    $editorsettings = new stdClass();
    $editorsettings->active_editor = get_class(editors_get_preferred_editor(FORMAT_HTML));

    $params = [$cm, $documentobject, $context->id, $USER->id, $capabilities, $toolbarsettings, $page, $annoid, $commid, $editorsettings];
    $PAGE->requires->js_init_call('adjustPdfworkspaceNavbar', null, true);
    $PAGE->requires->js_init_call('startIndex', $params, true);
    // The renderer renders the original index.php / takes the template and renders it.
    $myrenderer = $PAGE->get_renderer('mod_pdfworkspace');
    echo $myrenderer->render_index(new index($pdfworkspace, $capabilities, $file));
    $PAGE->requires->js_init_call('checkOnlyOneCheckbox', null, true);
    $PAGE->requires->js_init_call('checkOnlyOneCheckbox', null, true);

    pdfworkspace_print_intro($pdfworkspace, $cm, $course);

    echo $OUTPUT->footer();
    die;
}

function pdfworkspace_get_image_options_editor() {
    $imageoptions = new \stdClass();
    $imageoptions->maxbytes = get_config('mod_pdfworkspace', 'maxbytes');
    $imageoptions->maxfiles = PDFWORKSPACE_EDITOR_UNLIMITED_FILES;
    $imageoptions->autosave = false;
    $imageoptions->env = 'editor';
    $draftitemid = file_get_unused_draft_itemid();
    $imageoptions->itemid = $draftitemid;
    return $imageoptions;
}

function pdfworkspace_get_editor_options($context) {
    $options = [];
    $options = [
        'atto:toolbar' => get_config('mod_pdfworkspace', 'attobuttons'),
        'maxbytes' => get_config('mod_pdfworkspace', 'maxbytes'),
        'maxfiles' => PDFWORKSPACE_EDITOR_UNLIMITED_FILES,
        'return_types' => 15,
        'enable_filemanagement' => true,
        'removeorphaneddrafts' => false,
        'autosave' => false,
        'noclean' => false,
        'trusttext' => 0,
        'subdirs' => true,
        'forcehttps' => false,
        'areamaxbytes' => FILE_AREA_MAX_BYTES_UNLIMITED,
    ];
    return $options;
}

function pdfworkspace_get_relativelink($content, $commentid, $context) {
    preg_match('/@@PLUGINFILE@@/', $content, $matches);
    if ($matches) {
        $relativelink = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $context->id, 'mod_pdfworkspace', 'post', $commentid);
        return $relativelink;
    }
    return $content;
}

function pdfworkspace_extract_images($contentarr, $itemid, $context=null) {
    // Remove quotes here, in case if there is no math form.
    if (gettype($contentarr) === 'string') {
        $str = preg_replace('/[\"]/', "", $contentarr);
        $contentarr = [$str];
    }
    $res = [];
    $index = 0;
    foreach ($contentarr as $content) {
        $index++;
        if (gettype($content) === "array") {
            $res[] = $content;
            continue;
        }
        $res = pdfworkspace_split_content_image($content, $res, $itemid, $context);
    }
    return $res;
}

function pdfworkspace_split_content_image($content, $res, $itemid, $context=null) {
    global $CFG;
    // Gets all files in the comment with id itemid.
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_pdfworkspace', 'post', $itemid);
    $fileinfo = [];
    foreach ($files as $file) {
        if ($file->is_directory() && $file->get_filepath() === '/') {
            continue;
        }
        $info = [];
        $info['fileid'] = $file->get_id();
        $info['filename'] = $file->get_filename();
        $info['filepath'] = $file->get_filepath();
        $info['filecontent'] = $file->get_content();
        $info['filesize'] = $file->get_filesize();
        $info['filemimetype'] = $file->get_mimetype();
        $fileinfo[] = $info;
    }

    $imgmatch = [];
    $firststr = '';
    $data = [];
    while (preg_match_all('/<img/', $content, $imgmatch)) {
        $offsetlength = strlen($content);
        $imgposstart = strpos($content, '<img');
        $imgposend = strpos($content, '>', $imgposstart);

        $firststr = substr($content, 0, $imgposstart);
        $imgstr = substr($content, $imgposstart, $imgposend - $imgposstart + 1);
        $laststr = substr($content, $imgposend + 1, $offsetlength - $imgposend);

        preg_match('/(https...{1,}[.]((gif)|(jpe)g*|(jpg)|(png)|(svg)|(svgz)))/i', $imgstr, $url);
        preg_match('/(gif)|(jpe)g*|(jpg)|(png)|(svg)|(svgz)/i', $url[0], $format);
        if (!$format) {
            throw new \moodle_exception('error:unsupportedextension', 'pdfworkspace');
        }
        if (in_array('jpg', $format) || in_array('jpeg', $format) || in_array('jpe', $format)
        || in_array('JPG', $format) || in_array('JPEG', $format) || in_array('JPE', $format)) {
            $format[0] = 'jpeg';
        }

        $tempinfo = [];
        $encodedurl = urldecode($url[0]);
        foreach ($fileinfo as $file) {
            $count = substr_count($encodedurl, $file['filename']);
            if ($count) {
                $tempinfo = $file;
                break;
            }
        }

        try {
            if ($tempinfo) {
                $imagedata = 'data:' . $tempinfo['filemimetype'] . ';base64,' .  base64_encode($tempinfo['filecontent']);
                $data['image'] = $imagedata;
                $data['format'] = $tempinfo['filemimetype'];
                $data['fileid'] = $tempinfo['fileid'];
                $data['filename'] = $tempinfo['filename'];
                $data['filepath'] = $tempinfo['filepath'];
                $data['filesize'] = $tempinfo['filesize'];
                $data['imagestorage'] = 'intern';
            } else if (!str_contains($CFG->wwwroot, $url[0])) {
                $data['imagestorage'] = 'extern';
                $data['format'] = $format[0];
                $imgcontent = @file_get_contents($url[0]);
                if ($imgcontent) {
                    $data['image'] = 'data:image/' . $format[0] . ";base64," . base64_encode($imgcontent);
                } else {
                    throw new Exception(get_string('error:findimage', 'pdfworkspace', $encodedurl));
                }
            } else {
                throw new Exception(get_string('error:findimage', 'pdfworkspace', $encodedurl));
            }
            preg_match('/height=[0-9]+/', $imgstr, $height);
            if ($height) {
                $data['imageheight'] = str_replace("\"", "", explode('=', $height[0])[1]);
            } else if (!$height && $data['imagestorage'] === 'extern') {
                $imagemetadata = getimagesize($url[0]);
                $data['imageheight'] = $imagemetadata[1];
            } else {
                throw new Exception(get_string('error:getimageheight', 'pdfworkspace', $encodedurl));
            }
            preg_match('/width=[0-9]+/', $imgstr, $width);
            if ($width) {
                $data['imagewidth'] = str_replace("\"", "", explode('=', $width[0])[1]);
            } else if (!$width && $data['imagestorage'] === 'extern') {
                $imagemetadata = getimagesize($url[0]);
                $data['imagewidth'] = $imagemetadata[0];
            } else {
                throw new Exception(get_string('error:getimagewidth', 'pdfworkspace', $encodedurl));
            }
        } catch (Exception $ex) {
            $data['image'] = "error";
            $data['message'] = $ex->getMessage();
        } finally {
            $res[] = $firststr;
            $res[] = $data;
            $content = $laststr;
        }

    }
    $res[] = $content;

    return $res;
}

function pdfworkspace_data_preprocessing($context, $textarea, $draftitemid = 0) {
    global $PAGE;

    $options = pdfworkspace_get_editor_options($context);

    // TinyMCE needs an image draft area for paste and drag-and-drop even when
    // the legacy Atto toolbar configuration has no image button.
    $editor = editors_get_preferred_editor(FORMAT_HTML);
    $imagebtn = get_class($editor) === \editor_tiny\editor::class;
    $attobuttons = (string)$options['atto:toolbar'];
    $grouplines = explode("\n", $attobuttons);
    if (!$imagebtn) {
        foreach ($grouplines as $groupline) {
            $line = explode('=', $groupline, 2);
            if (count($line) < 2) {
                continue;
            }
            $groups = array_map('trim', explode(',', $line[1]));
            if (in_array('image', $groups, true)) {
                $imagebtn = true;
                break;
            }
        }
    }
    if (!$imagebtn) {
        $editor->use_editor($textarea, $options);
    } else {
        // initialize Filepicker if image button is active.
        $args = new \stdClass();
        // need these three to filter repositories list.
        $args->accepted_types = ['web_image'];
        $args->return_types = 15;
        $args->context = $context;
        $args->env = 'filepicker';
        // advimage plugin
        $imageoptions = (object)initialise_filepicker($args);
        $imageoptions->context = $context;
        $imageoptions->client_id = uniqid();
        $imageoptions->maxbytes = get_config('mod_pdfworkspace', 'maxbytes');
        $imageoptions->maxfiles = PDFWORKSPACE_EDITOR_UNLIMITED_FILES;
        $imageoptions->autosave = false;
        $imageoptions->env = 'editor';
        if (!$draftitemid) {
            $draftitemid = file_get_unused_draft_itemid();
        }
        $imageoptions->itemid = $draftitemid;
        $editor->use_editor($textarea, $options, ['image' => $imageoptions]);
    }

    // Add draftitemid and editorformat into input-tags.
    $editorformat = editors_get_preferred_format(FORMAT_HTML);
    return ['draftItemId' => $draftitemid, 'editorFormat' => $editorformat];
}

/**
 * Same function as core, however we need to add files into the existing draft area!
 * Copied from hsuforum.
 */
function pdfworkspace_file_prepare_draft_area(&$draftitemid, $contextid, $component, $filearea, $itemid, ?array $options=null, ?string $text=null) {
    global $CFG, $USER, $CFG, $DB;

    $options = (array)$options;
    if (!isset($options['subdirs'])) {
        $options['subdirs'] = false;
    }
    if (!isset($options['forcehttps'])) {
        $options['forcehttps'] = false;
    }

    $usercontext = \context_user::instance($USER->id);
    $fs = get_file_storage();

    $filerecord = ['contextid' => $usercontext->id, 'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftitemid];
    if (!is_null($itemid) && $files = $fs->get_area_files($contextid, $component, $filearea, $itemid)) {
        foreach ($files as $file) {
            if ($file->is_directory() && $file->get_filepath() === '/') {
                // we need a way to mark the age of each draft area,
                // by not copying the root dir we force it to be created automatically with current timestamp
                continue;
            }
            if (!$options['subdirs'] && ($file->is_directory() || $file->get_filepath() !== '/')) {
                continue;
            }

            // We are adding to an already existing draft area so we need to make sure we don't double add draft files!
            $checkfile = array_merge($filerecord, ['filename' => $file->get_filename()]);
            $draftexists = $DB->get_record('files', $checkfile);
            if ($draftexists) {
                continue;
            }
            $draftfile = $fs->create_file_from_storedfile($filerecord, $file);
            // XXX: This is a hack for file manager (MDL-28666)
            // File manager needs to know the original file information before copying
            // to draft area, so we append these information in mdl_files.source field
            // {@link file_storage::search_references()}
            // {@link file_storage::search_references_count()}
            $sourcefield = $file->get_source();
            $newsourcefield = new \stdClass;
            $newsourcefield->source = $sourcefield;
            $original = new \stdClass;
            $original->contextid = $contextid;
            $original->component = $component;
            $original->filearea  = $filearea;
            $original->itemid    = $itemid;
            $original->filename  = $file->get_filename();
            $original->filepath  = $file->get_filepath();
            $newsourcefield->original = \file_storage::pack_reference($original);
            $draftfile->set_source(serialize($newsourcefield));
            // End of file manager hack
        }
    }
    if (!is_null($text)) {
        // at this point there should not be any draftfile links yet,
        // because this is a new text from database that should still contain the @@pluginfile@@ links
        // this happens when developers forget to post process the text
        $text = str_replace("\"$CFG->httpswwwroot/draftfile.php", "\"$CFG->httpswwwroot/brokenfile.php#", $text);
    }
    if (is_null($text)) {
        return null;
    }

    // relink embedded files - editor can not handle @@PLUGINFILE@@ !
    return file_rewrite_pluginfile_urls($text, 'draftfile.php', $usercontext->id, 'user', 'draft', $draftitemid, $options);
}

function pdfworkspace_get_instance_name($id) {

    global $DB;
    return $DB->get_field('pdfworkspace', 'name', array('id' => $id), $strictness = MUST_EXIST);
}

function pdfworkspace_get_course_name_by_id($courseid) {
    global $DB;
    return $DB->get_field('course', 'fullname', array('id' => $courseid), $strictness = MUST_EXIST);
}

function pdfworkspace_get_username($userid) {
    global $DB;
    $user = $DB->get_record('user', array('id' => $userid));
    return fullname($user);
}

function pdfworkspace_get_annotationtype_id($typename) {
    global $DB;
    if ($typename == 'point') {
        $typename = 'pin';
    }
    $result = $DB->get_records('pdfworkspace_annotationtypes', array('name' => $typename));
    foreach ($result as $r) {
        return $r->id;
    }
}

function pdfworkspace_get_annotationtype_name($typeid) {
    global $DB;
    $result = $DB->get_records('pdfworkspace_annotationtypes', array('id' => $typeid));
    foreach ($result as $r) {
        return $r->name;
    }
}

function pdfworkspace_handle_latex($context, string $subject) {
    global $CFG;
    $latexapi = get_config('mod_pdfworkspace', 'latexapi');

    // Look for these formulae: $$ ... $$, \( ... \) and \[ ... \]
    // !!! keep indentation!
    $pattern = <<<'SIGN'
~(?:\$\$.*?\$\$)|(?:\\\(.*?\\\))|(?:\\\[.*?\\\])~
SIGN;

    $matches = array();
    $hits = preg_match_all($pattern, $subject, $matches, PREG_OFFSET_CAPTURE);

    if ($hits == 0) {
        return $subject;
    }

    $textstart = 0;
    $formulalength = 0;
    $formulaoffset = 0;
    $result = [];
    $matches = $matches[0];
    foreach ($matches as $match) {
        $formulalength = strlen($match[0]);
        $formulaoffset = $match[1];
        $string = $match[0];
        $string = str_replace('\xrightarrow', '\rightarrow', $string);
        $string = str_replace('\xlefttarrow', '\leftarrow', $string);

        $pos = strpos($string, '\\[');
        if ($pos !== false) {
            $string = substr_replace($string, '', $pos, strlen('\\['));
        }

        $pos = strpos($string, '\\(');
        if ($pos !== false) {
            $string = substr_replace($string, '', $pos, strlen('\\('));
        }

        $string = str_replace('\\]', '', $string);

        $string = str_replace('\\)', '', $string);

        $string = str_replace('\begin{aligned}', '', $string);
        $string = str_replace('\end{aligned}', '', $string);

        $string = str_replace('\begin{align*}', '', $string);
        $string = str_replace('\end{align*}', '', $string);

        // Find any backslash preceding a ( or [ and replace it with \backslash
        $pattern = '~\\\\(?=[\\\(\\\[])~';
        $string = preg_replace($pattern, '\\backslash', $string);
        $match[0] = $string;

        $result[] = trim(substr($subject, $textstart, $formulaoffset - $textstart));
        if ($latexapi == LATEX_TO_PNG_GOOGLE_API) {
            $result[] = pdfworkspace_process_latex_google($match[0]);
        } else {
            $result[] = pdfworkspace_process_latex_moodle($context, $match[0]);
        }
        $textstart = $formulaoffset + $formulalength;
    }
    if ($textstart != strlen($subject) - 1) {
        $result[] = trim(substr($subject, $textstart, strlen($subject) - $textstart));
    }
    return $result;
}

function pdfworkspace_process_latex_moodle($context, $string) {
    global $CFG;
    require_once($CFG->libdir . '/moodlelib.php');
    require_once($CFG->dirroot . '/filter/tex/latex.php');
    require_once($CFG->dirroot . '/filter/tex/lib.php');
    $result = array();
    $tex = new latex();
    $md5 = md5($string);
    $image = $tex->render($string, $md5 . 'png');
    if ($image == false) {
        return false;
    }
    $imagedata = file_get_contents($image);
    $result['mathform'] = IMAGE_PREFIX . base64_encode($imagedata);
    // Imageinfo returns an array with the info of the size of the image. In Parameter 1 there is the height, which is the only
    // thing needed here.
    $imageinfo = getimagesize($image);
    $result['mathformheight'] = $imageinfo[1];
    $result['format'] = 'PNG';
    return $result;
}
/**
 * Function takes a latex code string, modifies and url encodes it for the Google Api to process,
 * and returns the resulting image along with its height
 *
 * @param type $string
 * @return type
 */
function pdfworkspace_process_latex_google(string $string) {

    $length = strlen($string);
    $im = null;
    if ($length <= 200) { // Google API constraint XXX find better alternative if possible.
        $latexdata = urlencode($string);
        $requesturl = LATEX_TO_PNG_REQUEST . $latexdata;
        $im = @file_get_contents($requesturl); // '@' suppresses warnings so that one failed google request doesn't prevent the pdf from being printed,
        // but just the one formula from being presented as a picture.
    }
    if ($im != null) {
        $array = [];
        try {
            list($width, $height) = getimagesize($requesturl); // XXX alternative: acess height by decoding the string (saving the extra server request)?
            if ($height != null) {
                $imagedata = IMAGE_PREFIX . base64_encode($im); // Image.
                $array['image'] = $imagedata;
                $array['imageheight'] = $height;
                return $array;
            }
        } catch (Exception $ex) {
            return $string;
        }
    } else {
        return $string;
    }
}

function pdfworkspace_send_forward_message($recipients, $messageparams, $course, $cm, $context) {
    $name = 'forwardedquestion';
    $text = new stdClass();
    $module = get_string('modulename', 'pdfworkspace');
    $modulename = format_string($cm->name, true);
    $text->text = pdfworkspace_format_notification_message_text($course, $cm, $context, $module, $modulename, $messageparams, $name);
    $text->url = $messageparams->urltoquestion;

    foreach ($recipients as $recipient) {
        $text->html = pdfworkspace_format_notification_message_html($course, $cm, $context, $module, $modulename, $messageparams, $name, $recipient);
        pdfworkspace_notify_manager($recipient, $course, $cm, $name, $text);
    }
}

function pdfworkspace_notify_manager($recipient, $course, $cm, $name, $messagetext, $anonymous = false) {

    global $USER;
    $userfrom = $USER;
    $modulename = format_string($cm->name, true);
    if ($anonymous) {
        $userfrom = clone($USER);
        $userfrom->firstname = get_string('pdfworkspacename', 'pdfworkspace') . ':';
        $userfrom->lastname = $modulename;
    }
    $message = new \core\message\message();
    $message->component = 'mod_pdfworkspace';
    $message->name = $name;
    $message->courseid = $course->id;
    $message->userfrom = $anonymous ? core_user::get_noreply_user() : $userfrom;
    $message->userto = $recipient;
    $message->subject = get_string('notificationsubject:' . $name, 'pdfworkspace', $modulename);
    $message->fullmessage = $messagetext->text;
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml = $messagetext->html;
    $message->smallmessage = get_string('notificationsubject:' . $name, 'pdfworkspace', $modulename);
    $message->notification = 1; // For personal messages '0'. Important: the 1 without '' and 0 with ''.
    $message->contexturl = $messagetext->url;
    $message->contexturlname = 'Context name';
    $content = array('*' => array('header' => ' test ', 'footer' => ' test ')); // Extra content for specific processor.

    $messageid = message_send($message);

    return $messageid;
}

function pdfworkspace_format_notification_message_text($course, $cm, $context, $modulename, $pdfworkspacename, $paramsforlanguagestring, $messagetype) {
    global $CFG;
    $formatparams = array('context' => $context->get_course_context());
    $posttext = format_string($course->shortname, true, $formatparams) .
        ' -> ' .
        $modulename .
        ' -> ' .
        format_string($pdfworkspacename, true, $formatparams) . "\n";
    $posttext .= '---------------------------------------------------------------------' . "\n";
    $posttext .= "\n";
    $posttext .= get_string($messagetype . 'text', 'pdfworkspace', $paramsforlanguagestring) . "\n---------------------------------------------------------------------\n";
    return $posttext;
}

/**
 * Format a notification for HTML.
 *
 * @param string $messagetype
 * @param stdClass $info
 * @param stdClass $course
 * @param stdClass $context
 * @param string $modulename
 * @param stdClass $coursemodule
 * @param string $assignmentname
 */
function pdfworkspace_format_notification_message_html($course, $cm, $context, $modulename, $pdfworkspacename, $report, $messagetype, $recipientid) {
    global $CFG, $USER;
    $formatparams = array('context' => $context->get_course_context());
    $posthtml = '<p><font face="sans-serif">' .
        '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $course->id . '">' .
        format_string($course->shortname, true, $formatparams) .
        '</a> ->' .
        '<a href="' . $CFG->wwwroot . '/mod/pdfworkspace/index.php?id=' . $course->id . '">' .
        $modulename .
        '</a> ->' .
        '<a href="' . $CFG->wwwroot . '/mod/pdfworkspace/view.php?id=' . $cm->id . '">' .
        format_string($pdfworkspacename, true, $formatparams) .
        '</a></font></p>';
    $posthtml .= '<hr /><font face="sans-serif">';
    $report->urltoreport = $CFG->wwwroot . '/mod/pdfworkspace/view.php?id=' . $cm->id . '&action=overviewreports';
    $posthtml .= '<p>' . get_string($messagetype . 'html', 'pdfworkspace', $report) . '</p>';
    $linktonotificationsettingspage = new moodle_url('/message/notificationpreferences.php', array('userid' => $recipientid));
    $linktonotificationsettingspage = $linktonotificationsettingspage->__toString();
    $posthtml .= '</font><hr />';
    $posthtml .= '<font face="sans-serif"><p>' . get_string('unsubscribe_notification', 'pdfworkspace', $linktonotificationsettingspage) . '</p></font>';
    return $posthtml;
}

/**
 * Internal function - create click to open text with link.
 */
function pdfworkspace_get_clicktoopen($file, $revision, $extra = '') {
    global $CFG;

    $filename = $file->get_filename();
    $path = '/' . $file->get_contextid() . '/mod_pdfworkspace/content/' . $revision . $file->get_filepath() . $file->get_filename();
    $fullurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', $path, false);

    $string = get_string('clicktoopen2', 'pdfworkspace', "<a href=\"$fullurl\" $extra>$filename</a>");

    return $string;
}

/**
 * Internal function - create click to open text with link.
 */
function pdfworkspace_get_clicktodownload($file, $revision) {
    global $CFG;

    $filename = $file->get_filename();
    $path = '/' . $file->get_contextid() . '/mod_pdfworkspace/content/' . $revision . $file->get_filepath() . $file->get_filename();
    $fullurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', $path, true);

    $string = get_string('clicktodownload', 'pdfworkspace', "<a href=\"$fullurl\">$filename</a>");

    return $string;
}

/**
 * Print pdfworkspace header.
 * @param object $pdfworkspace
 * @param object $cm
 * @param object $course
 * @return void
 */
function pdfworkspace_print_header($pdfworkspace, $cm, $course) {
    global $PAGE, $OUTPUT;
    $PAGE->set_title($course->shortname . ': ' . $pdfworkspace->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($pdfworkspace);
    echo $OUTPUT->header();
}

/**
 * Gets details of the file to cache in course cache to be displayed using {@see pdfworkspace_get_optional_details()}
 *
 * @param object $pdfworkspace pdfworkspace table row (only property 'displayoptions' is used here)
 * @param object $cm Course-module table row
 * @return string Size and type or empty string if show options are not enabled
 */
function pdfworkspace_get_file_details($pdfworkspace, $cm) {
    $filedetails = array();

    $context = context_module::instance($cm->id);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_pdfworkspace', 'content', 0, 'sortorder DESC, id ASC', false);
    // For a typical file pdfworkspace, the sortorder is 1 for the main file
    // and 0 for all other files. This sort approach is used just in case
    // there are situations where the file has a different sort order.
    $mainfile = $files ? reset($files) : null;

    foreach ($files as $file) {
        // This will also synchronize the file size for external files if needed.
        $filedetails['size'] += $file->get_filesize();
        if ($file->get_repository_id()) {
            // If file is a reference the 'size' attribute can not be cached.
            $filedetails['isref'] = true;
        }
    }

    return $filedetails;
}

/**
 * Print pdfworkspace introduction.
 * @param object $pdfworkspace
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function pdfworkspace_print_intro($pdfworkspace, $cm, $course, $ignoresettings = false) {
    global $OUTPUT;
    if ($ignoresettings) {
        $gotintro = trim(strip_tags($pdfworkspace->intro));
        if ($gotintro || $extraintro) {
            echo $OUTPUT->box_start('mod_introbox', 'pdfworkspaceintro');
            if ($gotintro) {
                echo format_module_intro('pdfworkspace', $pdfworkspace, $cm->id);
            }
            echo $extraintro;
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Print warning that file can not be found.
 * @param object $pdfworkspace
 * @param object $cm
 * @param object $course
 * @return void, does not return
 */
function pdfworkspace_print_filenotfound($pdfworkspace, $cm, $course) {
    global $DB, $OUTPUT;

    pdfworkspace_print_header($pdfworkspace, $cm, $course);
    // pdfworkspace_print_heading($pdfworkspace, $cm, $course);//TODO Method is not defined.
    pdfworkspace_print_intro($pdfworkspace, $cm, $course);
    echo $OUTPUT->notification(get_string('filenotfound', 'pdfworkspace'));

    echo $OUTPUT->footer();
    die;
}

/**
 * Function returns the number of new comments, drawings and textboxes*
 * in this annotator. 'New' is defined here as 'no older than 24h' but
 * can easily be changed to another time span.
 * *Drawings and textboxes cannot be commented. In their case (only),
 * therefore, annotations are counted.
 *
 */
function pdfworkspace_get_number_of_new_activities($annotatorid) {

    global $DB;

    $parameters = array();
    $parameters[] = $annotatorid;
    $parameters[] = strtotime("-1 day");

    $sql = "SELECT c.id FROM {pdfworkspace_annotations} a JOIN {pdfworkspace_comments} c ON c.annotationid = a.id "
        . "WHERE a.pdfworkspaceid = ? AND c.timemodified >= ?";
    $sql2 = "SELECT a.id FROM {pdfworkspace_annotations} a JOIN {pdfworkspace_annotationtypes} t ON a.annotationtypeid = t.id "
        . "WHERE a.pdfworkspaceid = ? AND a.timecreated >= ? AND t.name IN('drawing','textbox')";

    return ( count($DB->get_records_sql($sql, $parameters)) + count($DB->get_records_sql($sql2, $parameters)) );
}

/**
 * Function returns the datetime of the last modification on or in the specified annotator.
 * The modification can be the creation of the annotator, a change of title or description,
 * a new annotation or a new comment. Reports are not considered.
 *
 * @param int $annotatorid
 * @return datetime $timemodified
 * The timestamp can be transformed into a readable string with this moodle method:
 * userdate($timestamp, $format = '', $timezone = 99, $fixday = true, $fixhour = true);
 */
function pdfworkspace_get_datetime_of_last_modification($annotatorid) {

    global $DB;

    // 1. When was the last time the annotator itself (i.e. its title, description or pdf) was modified?
    $timemodified = $DB->get_record('pdfworkspace', array('id' => $annotatorid), 'timemodified', MUST_EXIST);
    $timemodified = $timemodified->timemodified;

    // 2. When was the last time an annotation or a comment was added in the specified annotator?
    $sql = "SELECT max(a.timecreated) AS last_annotation, max(c.timemodified) AS last_comment "
        . "FROM {pdfworkspace_annotations} a LEFT OUTER JOIN {pdfworkspace_comments} c ON a.id = c.annotationid "
        . "WHERE a.pdfworkspaceid = ?";
    $newposts = $DB->get_records_sql($sql, array($annotatorid));

    if (!empty($newposts)) {

        foreach ($newposts as $entry) {

            // 2.a) If there is an annotation younger than the creation/modification of the annotator, set timemodified to the annotation time.
            if (!empty($entry->last_annotation) && ($entry->last_annotation > $timemodified)) {
                $timemodified = $entry->last_annotation;
            }
            // 2.b) If there is a comment younger than the creation/modification of the annotator or its newest annotation, set timemodified to the comment time.
            if (!empty($entry->last_comment) && ($entry->last_comment > $timemodified)) {
                $timemodified = $entry->last_comment;
            }
            return $timemodified;
        }
    }
}

/**
 * File browsing support class
 */
class pdfworkspace_content_file_info extends file_info_stored {

    public function get_parent() {
        if ($this->lf->get_filepath() === '/' && $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }

    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' && $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }

}

/** Check the independent workspace download right and the activity's participant setting. */
function pdfworkspace_can_download_workspace($activity, $context, $withcomments = false) {
    global $USER;
    $capability = $withcomments ? 'downloadworkspacecomments' : 'downloadworkspace';
    $setting = $withcomments ? 'useworkspacecomments' : 'useworkspacedownload';
    return has_capability('mod/pdfworkspace:view', $context) &&
        has_capability('mod/pdfworkspace:' . $capability, $context) &&
        (!empty($activity->$setting) || \mod_pdfworkspace\visibility::is_staff($context, $USER->id));
}

/** Return only included documents, in their independently stored export order. */
function pdfworkspace_workspace_documents($activityid) {
    global $DB;
    return $DB->get_records('pdfworkspace_documents', ['pdfworkspaceid' => $activityid, 'exportincluded' => 1],
        'exportorder ASC, sortorder ASC, id ASC');
}

/** Save only document settings belonging to this activity; never accept foreign document IDs. */
function pdfworkspace_save_workspace_selection($data) {
    global $DB;
    $context = context_module::instance($data->coursemodule);
    require_capability('moodle/course:manageactivities', $context);
    $documents = $DB->get_records('pdfworkspace_documents', ['pdfworkspaceid' => $data->id]);
    $ordered = [];
    foreach ($documents as $document) {
        $field = 'exportorder_' . $document->id;
        if (!property_exists($data, $field)) {
            continue; // A newly uploaded PDF starts excluded, at the end.
        }
        $position = (int)$data->$field;
        // Removing a PDF can leave a gap; normalise the surviving rows below.
        if ($position < 1) {
            throw new invalid_parameter_exception(get_string('error:workspaceorder', 'pdfworkspace'));
        }
        $ordered[] = [$position, $document->exportorder, $document->id];
    }
    sort($ordered);
    foreach ($ordered as $position => $entry) {
        $id = $entry[2];
        $field = 'exportincluded_' . $id;
        $DB->update_record('pdfworkspace_documents', (object)[
            'id' => $id, 'exportorder' => $position, 'exportincluded' => (int)!empty($data->$field),
        ]);
    }
}

/** Return the PDFs in their stable tab order, repairing old backups without document rows. */
function pdfworkspace_get_documents($activityid, $contextid) {
    global $DB;
    $documents = $DB->get_records('pdfworkspace_documents', ['pdfworkspaceid' => $activityid],
        'sortorder ASC, id ASC');
    if ($documents) {
        $first = reset($documents);
        $DB->set_field('pdfworkspace_annotations', 'documentid', $first->id,
            ['pdfworkspaceid' => $activityid, 'documentid' => null]);
        return $documents;
    }
    $files = get_file_storage()->get_area_files($contextid, 'mod_pdfworkspace', 'content', 0,
        'sortorder DESC, id ASC', false);
    $firstid = null;
    $order = 0;
    foreach ($files as $file) {
        $id = $DB->insert_record('pdfworkspace_documents', (object)[
            'pdfworkspaceid' => $activityid,
            'filepath' => $file->get_filepath(),
            'filename' => $file->get_filename(),
            'contenthash' => $file->get_contenthash(),
            'sortorder' => $order++,
            'exportincluded' => 1,
            'exportorder' => $order - 1,
        ]);
        if ($firstid === null) {
            $firstid = $id;
        }
    }
    if ($firstid !== null) {
        $DB->set_field('pdfworkspace_annotations', 'documentid', $firstid,
            ['pdfworkspaceid' => $activityid, 'documentid' => null]);
    }
    return $DB->get_records('pdfworkspace_documents', ['pdfworkspaceid' => $activityid],
        'sortorder ASC, id ASC');
}

/** Block changes that would make an existing annotation point at different PDF pages. */
function pdfworkspace_validate_draft_documents($activityid, $contextid, $draftfiles) {
    global $DB;
    $documents = pdfworkspace_get_documents($activityid, $contextid);
    $draftmap = [];
    foreach ($draftfiles as $file) {
        $draftmap[$file->get_filepath() . $file->get_filename()] = $file;
    }
    foreach ($documents as $document) {
        if (!$DB->record_exists('pdfworkspace_annotations', ['documentid' => $document->id])) {
            continue;
        }
        $key = $document->filepath . $document->filename;
        if (!isset($draftmap[$key]) || $draftmap[$key]->get_contenthash() !== $document->contenthash) {
            return $document->filename;
        }
    }
    return null;
}

function pdfworkspace_set_mainfile($data, $removedocumentids = []) {
    global $DB, $USER;
    $fs = get_file_storage();
    $cmid = $data->coursemodule;
    $draftitemid = $data->files; // Name from the filemanger.

    $context = context_module::instance($cmid);
    $activityid = (int)$data->id;
    $documents = pdfworkspace_get_documents($activityid, $context->id);
    $originaldocumentids = array_keys($documents);
    if ($removedocumentids) {
        foreach ($removedocumentids as $documentid) {
            if (!isset($documents[$documentid])) {
                throw new moodle_exception('invaliddocument', 'pdfworkspace');
            }
            $document = $documents[$documentid];
            if ($DB->record_exists('pdfworkspace_annotations', ['documentid' => $documentid])) {
                throw new moodle_exception('documentannotatedimmutable', 'pdfworkspace', '', $document->filename);
            }
        }
    }
    if ($draftitemid) {
        $usercontext = context_user::instance($USER->id);
        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid,
            'sortorder, id', false);
        if (empty($data->instance)) {
            // New activity: the draft area contains the complete document set.
            file_save_draft_area_files($draftitemid, $context->id, 'mod_pdfworkspace', 'content', 0,
                ['subdirs' => false]);
        } else {
            // Existing documents are never copied into this draft. Check for
            // collisions before changing any stored file.
            $removed = array_fill_keys($removedocumentids, true);
            foreach ($draftfiles as $draftfile) {
                $path = $draftfile->get_filepath();
                $name = $draftfile->get_filename();
                foreach ($documents as $document) {
                    if ($document->filepath === $path && $document->filename === $name &&
                            !isset($removed[$document->id])) {
                        throw new moodle_exception('documentalreadyexists', 'pdfworkspace', '', $name);
                    }
                }
            }
        }
    } else {
        $draftfiles = [];
    }
    if (!empty($data->instance)) {
        if (count($documents) - count($removedocumentids) + count($draftfiles) < 1) {
            throw new moodle_exception('required');
        }
        foreach ($draftfiles as $draftfile) {
            if (strtolower(pathinfo($draftfile->get_filename(), PATHINFO_EXTENSION)) !== 'pdf' ||
                    $draftfile->get_filepath() !== '/') {
                throw new invalid_parameter_exception('Only PDF files at the root are supported');
            }
        }
        foreach ($removedocumentids as $documentid) {
            $document = $documents[$documentid];
            $oldfile = $fs->get_file($context->id, 'mod_pdfworkspace', 'content', 0,
                $document->filepath, $document->filename);
            if ($oldfile) {
                $oldfile->delete();
            }
        }
        foreach ($draftfiles as $draftfile) {
            $fs->create_file_from_storedfile([
                'contextid' => $context->id,
                'component' => 'mod_pdfworkspace',
                'filearea' => 'content',
                'itemid' => 0,
                'filepath' => $draftfile->get_filepath(),
                'filename' => $draftfile->get_filename(),
                'userid' => $USER->id,
            ], $draftfile);
        }
    }
    $files = $fs->get_area_files($context->id, 'mod_pdfworkspace', 'content', 0, 'sortorder', false);
    $documents = pdfworkspace_get_documents($activityid, $context->id);
    $exportorder = $documents ? max(array_column($documents, 'exportorder')) + 1 : 0;
    $bykey = [];
    foreach ($documents as $document) {
        $bykey[$document->filepath . $document->filename] = $document;
    }
    $seen = [];
    $order = 0;
    foreach ($files as $file) {
        $key = $file->get_filepath() . $file->get_filename();
        $seen[$key] = true;
        if (isset($bykey[$key])) {
            $document = $bykey[$key];
            $document->contenthash = $file->get_contenthash();
            $document->sortorder = $order++;
            if (!empty($data->instance) && !in_array($document->id, $originaldocumentids)) {
                $document->exportincluded = 0;
                $document->exportorder = $exportorder++;
            }
            $DB->update_record('pdfworkspace_documents', $document);
        } else {
            $DB->insert_record('pdfworkspace_documents', (object)[
                'pdfworkspaceid' => $activityid,
                'filepath' => $file->get_filepath(),
                'filename' => $file->get_filename(),
                'contenthash' => $file->get_contenthash(),
                'sortorder' => $order++,
                'exportincluded' => empty($data->instance) ? 1 : 0,
                'exportorder' => $exportorder++,
            ]);
        }
    }
    foreach ($bykey as $key => $document) {
        if (!isset($seen[$key])) {
            $DB->delete_records('pdfworkspace_documents', ['id' => $document->id]);
        }
    }
    if (count($files) == 1) {
        // Only one file attached, set it as main file automatically.
        $file = reset($files);
        file_set_sortorder($context->id, 'mod_pdfworkspace', 'content', 0, $file->get_filepath(), $file->get_filename(), 1);
    }
}

function pdfworkspace_render_listitem_actions(?array $actions = null) {
    $menu = new action_menu();
    $menu->attributes['class'] .= ' course-item-actions item-actions';
    $hasitems = false;
    foreach ($actions as $key => $action) {
        $hasitems = true;
        $menu->add(new action_menu_link(
            $action['url'], $action['icon'], $action['string'], in_array($key, []), ['data-action' => $key, 'class' => 'action-' . $key]
        ));
    }
    if (!$hasitems) {
        return '';
    }
    return pdfworkspace_render_action_menu($menu);
}

function pdfworkspace_render_action_menu($menu) {
    global $OUTPUT;
    return $OUTPUT->render($menu);
}

function pdfworkspace_subscribe_all($annotatorid, $context) {
    global $DB;
    $sql = "SELECT id FROM {pdfworkspace_annotations} "
        . "WHERE pdfworkspaceid = ? AND annotationtypeid NOT IN "
        . "(SELECT id FROM {pdfworkspace_annotationtypes} WHERE name = ? OR name = ?)";
    $params = [$annotatorid, 'drawing', 'textbox'];
    $ids = $DB->get_fieldset_sql($sql, $params);
    foreach ($ids as $annotationid) {
        pdfworkspace_comment::insert_subscription($annotationid, $context);
    }
}

function pdfworkspace_unsubscribe_all($annotatorid) {
    global $DB, $USER;
    $sql = "SELECT a.id FROM {pdfworkspace_annotations} a JOIN {pdfworkspace_subscriptions} s "
        . "ON s.annotationid = a.id AND s.userid = ? WHERE pdfworkspaceid = ?";
    $ids = $DB->get_fieldset_sql($sql, [$USER->id, $annotatorid]);
    foreach ($ids as $annotationid) {
        pdfworkspace_comment::delete_subscription($annotationid);
    }
}

/**
 * Checks wether a user has subscribed to all questions in an annotator.
 * Returns 1 if all questions are subscribed, 0 if no questions are subscribed and -1 if at least one but not all questions are subscribed.
 * @param type $annotatorid
 */
function pdfworkspace_subscribed($annotatorid) {
    global $DB, $USER;
    $sql = "SELECT COUNT(*) FROM {pdfworkspace_annotations} a JOIN {pdfworkspace_subscriptions} s "
        . "ON s.annotationid = a.id AND s.userid = ? WHERE a.pdfworkspaceid = ?";
    $subscriptions = $DB->count_records_sql($sql, [$USER->id, $annotatorid]);
    $sql = "SELECT COUNT(*) FROM {pdfworkspace_annotations} "
        . "WHERE pdfworkspaceid = ? AND annotationtypeid NOT IN "
        . "(SELECT id FROM {pdfworkspace_annotationtypes} WHERE name = ? OR name = ?)";
    $params = [$annotatorid, 'drawing', 'textbox'];
    $annotations = $DB->count_records_sql($sql, $params);

    if ($subscriptions === 0) {
        return 0;
    } else if ($subscriptions === $annotations) {
        return 1;
    } else {
        return -1;
    }
}

/**
 *
 * @param type $timestamp
 * @return string Day, D Month Y, Time
 */
function pdfworkspace_get_user_datetime($timestamp) {
    $userdatetime = userdate($timestamp, $format = '', $timezone = 99, $fixday = true, $fixhour = true); // Method in lib/moodlelib.php
    return $userdatetime;
}

/**
 *
 * @param type $timestamp
 * @return string
 */
function pdfworkspace_get_user_datetime_shortformat($timestamp) {
    $shortformat = get_string('strftimedatetime', 'pdfworkspace'); // Format strings in moodle\lang\en\langconfig.php.
    $userdatetime = userdate($timestamp, $shortformat, $timezone = 99, $fixday = true, $fixhour = true); // Method in lib/moodlelib.php
    return $userdatetime;
}

/**
 * Function is executed each time one of the overview categories is accessed.
 * It creates the tab navigation and makes javascript accessible.
 *
 * @param type $CFG
 * @param type $PAGE
 * @param type $myrenderer
 * @param type $taburl
 * @param type $action
 * @param type $pdfworkspace
 * @param type $context
 */
function pdfworkspace_prepare_overviewpage($cmid, $myrenderer, $taburl, $action, $pdfworkspace, $context) {

    global $CFG, $PAGE;

    // 1.1 Display tab navigation.
    echo $myrenderer->pdfworkspace_render_tabs($taburl, $pdfworkspace->name, $context, $action['tab']);
    echo pdfworkspace_render_overview_navigation($cmid, $context, $action['action']);

    // 1.2 Give javascript (see below) access to the language string repository.
    $stringman = get_string_manager();
    $strings = $stringman->load_component_strings('pdfworkspace', 'en'); // Method gets the strings of the language files.
    $PAGE->requires->strings_for_js(array_keys($strings), 'pdfworkspace'); // Method to use the language-strings in javascript.
    // 1.3 Add the javascript file that determines the dynamic behaviour of the page.
    $PAGE->requires->js(new moodle_url("/mod/pdfworkspace/shared/overview.js?ver=00005"));
    $PAGE->requires->js_init_call('startOverview', [], true);
}

/** Persistent, permission-aware links within the Overview tab. */
function pdfworkspace_render_overview_navigation($cmid, $context, $active) {
    $items = [
        ['overviewquestions', 'viewquestions', 'questionstab', 'fa-comment-o'],
        ['overviewanswers', 'viewanswers', 'answerstab', 'fa-reply'],
        ['overviewownposts', 'viewposts', 'ownpoststab', 'fa-user-o'],
        ['overviewreports', 'viewreports', 'reportstab', 'fa-flag-o'],
    ];
    $html = html_writer::start_tag('nav', ['class' => 'pdfworkspace-overview-nav',
        'aria-label' => get_string('overview', 'pdfworkspace')]);
    foreach ($items as [$action, $capability, $label, $icon]) {
        if (!has_capability('mod/pdfworkspace:' . $capability, $context)) {
            continue;
        }
        $url = new moodle_url('/mod/pdfworkspace/view.php', ['id' => $cmid, 'action' => $action]);
        $attributes = ['class' => 'pdfworkspace-overview-pill' . ($active === $action ? ' is-active' : '')];
        if ($active === $action) {
            $attributes['aria-current'] = 'page';
        }
        $html .= html_writer::link($url,
            html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']) .
            html_writer::tag('span', get_string($label, 'pdfworkspace')), $attributes);
    }
    return $html . html_writer::end_tag('nav');
}

/** Recipients who can actually open this question in its fixed audience. */
function pdfworkspace_forward_recipients($question, $context, $senderid) {
    $recipients = [];
    foreach (get_enrolled_users($context, 'mod/pdfworkspace:getforwardedquestions') as $user) {
        if ((int)$user->id === (int)$senderid ||
                !\mod_pdfworkspace\visibility::can_view_comment($question, $context, $user->id)) {
            continue;
        }
        $recipients[$user->id] = fullname($user);
    }
    return $recipients;
}

/** Native form controls for status, PDF and page size; no fragile URL string parsing. */
function pdfworkspace_render_overview_filters($cmid, $activityid, $action, $filtername,
        $selected, $options, $itemsperpage, $documentfilter) {
    $html = html_writer::start_tag('form', ['method' => 'get',
        'action' => new moodle_url('/mod/pdfworkspace/view.php'),
        'class' => 'pdfworkspace-overview-filters']);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cmid]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => $action]);
    if ($filtername) {
        $html .= pdfworkspace_overview_select($filtername, get_string('overviewstatus', 'pdfworkspace'),
            $options, $selected);
    }
    $docs = pdfworkspace_get_documents($activityid, context_module::instance($cmid)->id);
    if (count($docs) > 1) {
        $docoptions = [0 => get_string('overviewalldocuments', 'pdfworkspace')];
        foreach ($docs as $doc) {
            $docoptions[$doc->id] = !empty($doc->displayname) ? $doc->displayname : $doc->filename;
        }
        $html .= pdfworkspace_overview_select('documentfilter', get_string('documenttabs', 'pdfworkspace'),
            $docoptions, $documentfilter);
    }
    $html .= pdfworkspace_overview_select('itemsperpage', get_string('itemsperpage', 'pdfworkspace'),
        [5 => '5', 10 => '10', -1 => get_string('all', 'pdfworkspace')], $itemsperpage);
    $html .= html_writer::tag('button', get_string('applyfilters', 'pdfworkspace'),
        ['type' => 'submit', 'class' => 'btn btn-primary']);
    $html .= html_writer::link(new moodle_url('/mod/pdfworkspace/view.php',
        ['id' => $cmid, 'action' => $action]), get_string('resetfilters', 'pdfworkspace'),
        ['class' => 'btn btn-outline-secondary']);
    return $html . html_writer::end_tag('form');
}

/** Ensure the document filter belongs to the current activity. */
function pdfworkspace_valid_document_filter($activityid, $contextid, $requested) {
    $requested = (int)$requested;
    if (!$requested) {
        return 0;
    }
    $documents = pdfworkspace_get_documents($activityid, $contextid);
    if (!isset($documents[$requested])) {
        throw new invalid_parameter_exception('Invalid PDF document filter');
    }
    return $requested;
}

/** Plain PDF title for sorting and display. */
function pdfworkspace_overview_document_title($activityid, $contextid, $documentid) {
    $documents = pdfworkspace_get_documents($activityid, $contextid);
    $document = $documents[$documentid] ?? null;
    return $document ? (!empty($document->displayname) ? $document->displayname : $document->filename) : '';
}

/** Short PDF and page label for a row in this activity. */
function pdfworkspace_overview_document_name($activityid, $contextid, $documentid, $page, $url = null) {
    $name = pdfworkspace_overview_document_title($activityid, $contextid, $documentid);
    $label = html_writer::tag('span', s($name), ['class' => 'pdfworkspace-overview-document', 'title' => $name]) .
        html_writer::tag('small', get_string('page') . ' ' . (int)$page, ['class' => 'pdfworkspace-overview-page']);
    return $url ? html_writer::link($url, $label) : $label;
}

/** Two-line plain-text preview; Bootstrap shows the full text on hover or focus. */
function pdfworkspace_overview_preview($content, $url = null, $attributes = []) {
    $text = trim(preg_replace('/\s+/u', ' ', html_to_text($content, 0, false)));
    $attributes['class'] = 'pdfworkspace-overview-preview ' . ($attributes['class'] ?? '');
    $attributes['title'] = $text;
    return $url ? html_writer::link($url, s($text), $attributes) : html_writer::tag('span', s($text),
        $attributes + ['tabindex' => '0']);
}

/** Compact metadata with a stable date rather than a long relative sentence. */
function pdfworkspace_overview_person($author, $timestamp) {
    return html_writer::tag('div', $author, ['class' => 'pdfworkspace-overview-author']) .
        html_writer::tag('div', userdate($timestamp, '%d.%m.%y, %H:%M'), ['class' => 'pdfworkspace-overview-meta']);
}

/** Statistics are temporarily restricted to actual site administrators. */
function pdfworkspace_require_statistics_access($context) {
    require_capability('mod/pdfworkspace:viewstatistics', $context);
    if (!is_siteadmin()) {
        throw new required_capability_exception($context, 'mod/pdfworkspace:viewstatistics', 'nopermissions', '');
    }
}

/** Labelled select using Moodle/Bootstrap form styling. */
function pdfworkspace_overview_select($name, $label, $options, $selected) {
    $id = 'pdfworkspace-filter-' . $name;
    $html = html_writer::start_tag('label', ['for' => $id, 'class' => 'pdfworkspace-filter-field']);
    $html .= html_writer::tag('span', $label);
    $html .= html_writer::start_tag('select', ['id' => $id, 'name' => $name, 'class' => 'form-select']);
    foreach ($options as $value => $text) {
        $attributes = ['value' => $value];
        if ((string)$value === (string)$selected) {
            $attributes['selected'] = 'selected';
        }
        $html .= html_writer::tag('option', s($text), $attributes);
    }
    return $html . html_writer::end_tag('select') . html_writer::end_tag('label');
}

/**
 * Function serves as subcontroller that tells the annotator model to collect
 * all or all unsolved/solved questions asked in this course.
 *
 * @param int $openannotator
 * @param int $courseid
 * @param type $questionfilter
 * @return type
 */
function pdfworkspace_get_questions($activityid, $context, $questionfilter, $documentfilter = 0) {

    global $DB;

    $cmid = $context->instanceid;

    $sql = "SELECT a.id as annoid, a.page, a.documentid, a.pdfworkspaceid, p.name AS pdfworkspacename, p.usevotes, cm.id AS cmid, c.isquestion, c.annotationid, "
        . "c.id as commentid, c.content, c.userid, c.visibility, c.timecreated, c.isdeleted, c.ishidden, "
        . "SUM(vote) AS votes, MAX(answ.timecreated) AS lastanswered "
        . "FROM {pdfworkspace_annotations} a "
        . "JOIN {pdfworkspace_comments} c ON c.annotationid = a.id "
        . "JOIN {pdfworkspace} p ON a.pdfworkspaceid = p.id "
        . "JOIN {course_modules} cm ON p.id = cm.instance "
        . "LEFT JOIN {pdfworkspace_votes} v ON c.id=v.commentid "
        . "LEFT JOIN {pdfworkspace_comments} answ ON answ.annotationid = a.id "
        . "WHERE c.isquestion = 1 AND p.id = ? AND cm.id = ? ";
    if ($questionfilter == 0) {
        $sql = $sql . ' AND c.solved = 0 ';
    }
    if ($questionfilter == 1) {
        $sql = $sql . ' AND NOT c.solved = 0 ';
    }
    if ($documentfilter) {
        $sql .= ' AND a.documentid = ? ';
    }
    $sql = $sql . "GROUP BY a.id, p.name, p.usevotes, cm.id, c.id, c.annotationid, a.page, a.documentid, a.pdfworkspaceid, c.content, c.userid, c.visibility, "
        . "c.timecreated, c.isdeleted, c.ishidden, c.isquestion ";
    $params = [$activityid, $cmid];
    if ($documentfilter) {
        $params[] = $documentfilter;
    }
    $questions = $DB->get_records_sql($sql, $params);

    $seehidden = has_capability('mod/pdfworkspace:seehiddencomments', $context);
    $labelhidden = "<br><span class='tag tag-info'>" . get_string('hiddenfromstudents') . "</span>"; // XXX use moodle method if exists.
    $labelunavailable = "<br><span class='tag tag-info'>" . get_string('restricted') . "</span>";

    $res = [];
    foreach ($questions as $key => $question) {

        $entrycontext = context_module::instance($question->cmid);
        if (!pdfworkspace_can_see_comment($question, $entrycontext)) {
            continue;
        }

        if (empty($question->votes)) {
            $question->votes = 0;
        }
        if ($question->usevotes == 0) {
            $question->votes = '-';
        }
        $question->answercount = pdfworkspace_count_answers($question->annoid, $entrycontext);

        $lastanswer = pdfworkspace_get_last_answer($question->annoid, $entrycontext);
        if ($lastanswer) {
            $question->lastuser = $lastanswer->userid;
            $question->lastanswered = $lastanswer->timecreated;
            $question->lastanswerid = $lastanswer->id;
            $question->lastuservisibility = $lastanswer->visibility;
        } else {
            $question->lastanswered = false;
        }

        if ($question->isdeleted == 1) {
            $question->content = "<em>" . get_string('deletedQuestion', 'pdfworkspace') . "</em>";
        } else if ($question->ishidden) {
            switch ($seehidden) {
                case 0:
                    $question->content = "<em>" . get_string('hiddenComment', 'pdfworkspace') . "</em>";
                    break;
                case 1:
                    $question->content = $question->content . $labelhidden;
                    $question->displayhidden = true;
                    break;
                default:
                    $question->content = "<em>" . get_string('hiddenComment', 'pdfworkspace') . "</em>";
            }
        }

        $question->content = pdfworkspace_get_relativelink($question->content, $question->commentid, $context);
        $question->content = format_text($question->content, FORMAT_MOODLE);
        $question->pdfworkspacename = pdfworkspace_overview_document_title($question->pdfworkspaceid, $context->id, $question->documentid);
        $question->link = (new moodle_url('/mod/pdfworkspace/view.php', array('id' => $question->cmid,
            'doc' => $question->documentid, 'page' => $question->page, 'annoid' => $question->annoid, 'commid' => $question->commentid)))->out(false);

        $res[] = $question;

    }
    return $res;
}

/**
 * Function serves as subcontroller that tells the annotator model to collect all
 * questions and answers this user posted in the course.
 *
 * @param int $courseid
 * @return type
 */
function pdfworkspace_get_posts_by_this_user($activityid, $context, $documentfilter = 0) {

    global $DB, $USER;

    $cmid = $context->instanceid;

    $seehidden = has_capability('mod/pdfworkspace:seehiddencomments', $context);
    $labelhidden = "<br><span class='tag tag-info'>" . get_string('hiddenforparticipants', 'pdfworkspace') . "</span>";
    $labelunavailable = "<br><span class='tag tag-info'>" . get_string('restricted') . "</span>";

    $sql = "SELECT c.id as commid, c.annotationid, c.content, c.timemodified, c.ishidden, a.id AS annoid, "
        . "a.page, a.documentid, a.pdfworkspaceid, p.name AS pdfworkspacename, p.usevotes, cm.id AS cmid, "
        . "SUM(v.vote) AS votes "
        . "FROM {pdfworkspace_comments} c "
        . "JOIN {pdfworkspace_annotations} a ON c.annotationid = a.id "
        . "JOIN {pdfworkspace} p ON a.pdfworkspaceid = p.id "
        . "JOIN {course_modules} cm ON p.id = cm.instance "
        . "LEFT JOIN {pdfworkspace_votes} v ON c.id = v.commentid "
        . "WHERE c.userid = ? AND c.isdeleted = 0 AND p.id = ? AND cm.id = ? ";
    if ($documentfilter) {
        $sql .= ' AND a.documentid = ? ';
    }
    $sql .= "GROUP BY a.id, p.name, p.usevotes, cm.id, c.id, c.annotationid, c.content, c.timemodified, c.ishidden, a.page, a.documentid, a.pdfworkspaceid";

    $params = [$USER->id, $activityid, $cmid];
    if ($documentfilter) {
        $params[] = $documentfilter;
    }

    $posts = $DB->get_records_sql($sql, $params);

    foreach ($posts as $key => $post) {
        if (empty($post->votes)) {
            $post->votes = 0;
        }
        if ($post->usevotes == 0) {
            $post->votes = '-';
        }

        if ($post->ishidden) { // Post in annotator is hidden.
            switch ($seehidden) {
                case 0:
                    $post->content = "<em>" . get_string('hiddenComment', 'pdfworkspace') . "</em>";
                    break;
                case 1:
                    $post->content = $post->content . $labelhidden;
                    $post->displayhidden = true;
                    break;
                default:
                    $post->content = "<em>" . get_string('hiddenComment', 'pdfworkspace') . "</em>";
            }
        }

        $params = array('id' => $post->cmid, 'doc' => $post->documentid, 'page' => $post->page, 'annoid' => $post->annotationid, 'commid' => $post->commid);
        $post->pdfworkspacename = pdfworkspace_overview_document_title($post->pdfworkspaceid, $context->id, $post->documentid);
        $post->link = (new moodle_url('/mod/pdfworkspace/view.php', $params))->out(false);
        $post->content = pdfworkspace_get_relativelink($post->content, $post->commid, $context);
        $post->content = format_text($post->content, FORMAT_MOODLE);
    }
    return $posts;
}

/**
 * Function serves as subcontroller that tells the annotator model to collect
 * all answers given to questions that the current user asked or subscribed to
 * in this course.
 *
 * @param int $courseid
 * @param Moodle object? $context
 * @param int $answerfilter
 * @return array of stdClass objects
 */
function pdfworkspace_get_answers_for_this_user($activityid, $context, $answerfilter = 1, $documentfilter = 0) {

    global $DB, $USER;

    $cmid = $context->instanceid;

    $seehidden = has_capability('mod/pdfworkspace:seehiddencomments', $context);
    $labelhidden = "<br><span class='tag tag-info'>" . get_string('hiddenforparticipants', 'pdfworkspace') . "</span>";
    $labelunavailable = "<br><span class='tag tag-info'>" . get_string('restricted') . "</span>";

    if ($answerfilter == 0) { // Either: get all answers in this annotator.
        $sql = "SELECT c.id AS answerid, c.content AS answer, c.userid AS userid, c.visibility, "
            . "c.timemodified, c.solved AS correct, c.ishidden AS answerhidden, a.id AS annoid, a.page, a.documentid, q.id AS questionid, "
            . "q.userid AS questionuserid, c.isquestion, c.annotationid, "
            . "q.visibility AS questionvisibility, "
            . "q.content AS answeredquestion, q.isdeleted AS questiondeleted, q.ishidden AS questionhidden, p.id AS annotatorid, "
            . "p.name AS pdfworkspacename, cm.id AS cmid, s.id AS issubscribed "
            . "FROM {pdfworkspace_annotations} a "
            . "LEFT JOIN {pdfworkspace_subscriptions} s ON a.id = s.annotationid AND s.userid = ? "
            . "JOIN {pdfworkspace_comments} q ON q.annotationid = a.id " // Question comment.
            . "JOIN {pdfworkspace_comments} c ON c.annotationid = a.id " // Answer comment.
            . "JOIN {pdfworkspace} p ON a.pdfworkspaceid = p.id "
            . "JOIN {course_modules} cm ON p.id = cm.instance "
            . "WHERE p.id = ? AND q.isquestion = 1 AND NOT c.isquestion = 1 AND NOT c.isdeleted = 1 AND cm.id = ? ";
    } else { // Or: get answers to those questions the user subscribed to.
        $sql = "SELECT c.id AS answerid, c.content AS answer, c.userid AS userid, c.visibility, "
            . "c.timemodified, c.solved AS correct, c.ishidden AS answerhidden, a.id AS annoid, a.page, a.documentid, q.id AS questionid, "
            . "q.userid AS questionuserid, c.isquestion, c.annotationid, "
            . "q.visibility AS questionvisibility, "
            . "q.content AS answeredquestion, q.isdeleted AS questiondeleted, q.ishidden AS questionhidden, p.id AS annotatorid, "
            . "p.name AS pdfworkspacename, cm.id AS cmid "
            . "FROM {pdfworkspace_subscriptions} s "
            . "JOIN {pdfworkspace_annotations} a ON a.id = s.annotationid "
            . "JOIN {pdfworkspace_comments} q ON q.annotationid = a.id " // Question comment.
            . "JOIN {pdfworkspace_comments} c ON c.annotationid = a.id " // Answer comment.
            . "JOIN {pdfworkspace} p ON a.pdfworkspaceid = p.id "
            . "JOIN {course_modules} cm ON p.id = cm.instance "
            . "WHERE s.userid = ? AND p.id = ? AND q.isquestion = 1 AND NOT c.isquestion = 1 AND NOT c.isdeleted = 1 AND cm.id = ? ";
    }

    if ($documentfilter) {
        $sql .= ' AND a.documentid = ? ';
    }
    $sql .= ' ORDER BY annoid ASC';

    $params = [$USER->id, $activityid, $cmid];
    if ($documentfilter) {
        $params[] = $documentfilter;
    }

    $entries = $DB->get_records_sql($sql, $params);

    $res = [];
    foreach ($entries as $key => $entry) {
        $entrycontext = context_module::instance($entry->cmid);
        if (!pdfworkspace_can_see_comment($entry, $entrycontext)) {
            continue;
        }
        $thread = (object)[
            'id' => $entry->questionid,
            'annotationid' => $entry->annotationid,
            'userid' => $entry->questionuserid,
            'visibility' => $entry->questionvisibility,
            'isquestion' => 1,
        ];
        if (!pdfworkspace_can_see_comment($thread, $entrycontext)) {
            continue;
        }
        $entry->pdfworkspacename = pdfworkspace_overview_document_title($entry->annotatorid, $context->id, $entry->documentid);
        $entry->link = (new moodle_url('/mod/pdfworkspace/view.php',
            array('id' => $entry->cmid, 'doc' => $entry->documentid, 'page' => $entry->page, 'annoid' => $entry->annoid, 'commid' => $entry->answerid)))->out(false);
        $entry->questionlink = (new moodle_url('/mod/pdfworkspace/view.php',
            array('id' => $entry->cmid, 'doc' => $entry->documentid, 'page' => $entry->page, 'annoid' => $entry->annoid, 'commid' => $entry->questionid)))->out(false);

        if ($entry->questiondeleted == 1) {
            $entry->answeredquestion = get_string('deletedComment', 'pdfworkspace');
        } else if ($entry->questionhidden) {
            switch ($seehidden) {
                case 0:
                    $entry->answeredquestion = "<em>" . get_string('hiddenComment', 'pdfworkspace') . "</em>";
                    break;
                case 1:
                    $entry->displayquestionhidden = true;
                    $entry->answeredquestion = $entry->answeredquestion . $labelhidden;
                    break;
                default:
                    $entry->answeredquestion = "<em>" . get_string('hiddenComment', 'pdfworkspace') . "</em>";
            }
        }

        if ($entry->answerhidden) {
            switch ($seehidden) {
                case 0:
                    $entry->answer = "<em>" . get_string('hiddenComment', 'pdfworkspace') . "</em>";
                    break;
                case 1:
                    $entry->answer = $entry->answer . $labelhidden;
                    $entry->displayhidden = true;
                    break;
                default:
                    $entry->answer = "<em>" . get_string('hiddenComment', 'pdfworkspace') . "</em>";
            }
        }

        $res[] = $entry;
    }

    return $res;
}

/**
 * Function retrieves reports and their respective reported comments from db.
 * Depending on the reportfilter, only read/unread reports or all reports are retrieved.
 *
 * @param int $courseid
 * @param int $reportfilter: 0 for unread, 1 for read, 2 for all
 * @return array of report objects
 */
function pdfworkspace_get_reports($activityid, $context, $reportfilter = 0, $documentfilter = 0) {

    global $DB, $USER;

    $cmid = $context->instanceid;

    // Retrieve reports from db as an array of stdClass objects, representing a report record each.
    $sql = "SELECT r.id as reportid, r.commentid, r.message as report, r.userid AS reportinguser, r.timecreated, r.seen, "
        . "a.page, a.documentid, c.id AS commentid, c.annotationid, c.userid AS commentauthor, c.content AS reportedcomment, c.timecreated AS commenttime, c.visibility, "
        . "p.id AS annotatorid, p.name AS pdfworkspacename, cm.id AS cmid, cm.visible AS cmvisible "
        . "FROM {pdfworkspace_reports} r "
        . "JOIN {pdfworkspace_comments} c ON r.commentid = c.id "
        . "JOIN {pdfworkspace_annotations} a ON c.annotationid = a.id "
        . "JOIN {pdfworkspace} p ON a.pdfworkspaceid = p.id "
        . "JOIN {course_modules} cm ON p.id = cm.instance "
        . "WHERE cm.id = ? AND r.pdfworkspaceid = ?";

    if ($reportfilter != 2) {
        $sql = $sql . ' AND r.seen = ?';
        $params = [$cmid, $activityid, $reportfilter];
    } else {
        $params = [$cmid, $activityid];
    }
    if ($documentfilter) {
        $sql .= ' AND a.documentid = ? ';
        $params[] = $documentfilter;
    }
    $reports = $DB->get_records_sql($sql, $params);

    foreach ($reports as $report) {
        $reportedcomment = $DB->get_record('pdfworkspace_comments', ['id' => $report->commentid]);
        $reportcontext = context_module::instance($report->cmid);
        if (!$reportedcomment || !has_capability('mod/pdfworkspace:viewreports', $reportcontext, $USER->id) ||
                !\mod_pdfworkspace\visibility::can_view_comment(
                $reportedcomment, $reportcontext, $USER->id)) {
            unset($reports[$report->reportid]);
            continue;
        }
        $report->pdfworkspacename = pdfworkspace_overview_document_title($report->annotatorid, $context->id, $report->documentid);
        $report->link = (new moodle_url('/mod/pdfworkspace/view.php',
            array('id' => $report->cmid, 'doc' => $report->documentid, 'page' => $report->page, 'annoid' => $report->annotationid, 'commid' => $report->commentid)))->out(false);
        $report->reportedcomment = pdfworkspace_get_relativelink($report->reportedcomment, $report->commentid, $reportcontext);
        $report->reportedcomment = format_text($report->reportedcomment, FORMAT_MOODLE);
        $questionid = $DB->get_record('pdfworkspace_comments', ['annotationid' => $report->annotationid, 'isquestion' => 1], 'id');
        $report->report = pdfworkspace_get_relativelink($report->report, $questionid, $reportcontext);
        $report->report = format_text($report->report, FORMAT_MOODLE);
    }
    return $reports;
}

/**
 * Comparison functions (for sorting tables on overview tab).
 */
class pdfworkspace_compare {

    public static function compare_votes_ascending($a, $b) {
        if ($a->usevotes == 0 && $b->usevotes == 0 && $a->votes == $b->votes) {
            return 0;
        }
        return ($a->usevotes != 1 || ($a->votes < $b->votes)) ? -1 : 1;
    }

    public static function compare_votes_descending($a, $b) {
        if ($a->usevotes == 0 && $b->usevotes == 0 && $a->votes == $b->votes) {
            return 0;
        }
        return ($b->usevotes != 1 || ($a->votes > $b->votes)) ? -1 : 1;
    }

    public static function compare_answers_ascending($a, $b) {
        if ($a->answercount == $b->answercount) {
            return 0;
        }
        return ($a->answercount < $b->answercount) ? -1 : 1;
    }

    public static function compare_answers_descending($a, $b) {
        if ($a->answercount == $b->answercount) {
            return 0;
        }
        return ($a->answercount > $b->answercount) ? -1 : 1;
    }

    public static function compare_time_ascending($a, $b) {
        if ($a->timemodified == $b->timemodified) {
            return 0;
        }
        return ($a->timemodified < $b->timemodified) ? -1 : 1;
    }

    public static function compare_time_descending($a, $b) {
        if ($a->timemodified == $b->timemodified) {
            return 0;
        }
        return ($a->timemodified > $b->timemodified) ? -1 : 1;
    }

    public static function compare_lastanswertime_ascending($a, $b) {
        if ($a->lastanswered == $b->lastanswered) {
            return 0;
        }
        return ($a->lastanswered < $b->lastanswered) ? -1 : 1;
    }

    public static function compare_lastanswertime_descending($a, $b) {
        if ($a->lastanswered == $b->lastanswered) {
            return 0;
        }
        return ($a->lastanswered > $b->lastanswered) ? -1 : 1;
    }

    public static function compare_commenttime_ascending($a, $b) {
        if ($a->commenttime == $b->commenttime) {
            return 0;
        }
        return ($a->commenttime < $b->commenttime) ? -1 : 1;
    }

    public static function compare_commenttime_descending($a, $b) {
        if ($a->commenttime == $b->commenttime) {
            return 0;
        }
        return ($a->commenttime > $b->commenttime) ? -1 : 1;
    }

    public static function compare_creationtime_ascending($a, $b) {
        if ($a->timecreated == $b->timecreated) {
            return 0;
        }
        return ($a->timecreated < $b->timecreated) ? -1 : 1;
    }

    public static function compare_creationtime_descending($a, $b) {
        if ($a->timecreated == $b->timecreated) {
            return 0;
        }
        return ($a->timecreated > $b->timecreated) ? -1 : 1;
    }

    public static function compare_alphabetically_ascending($a, $b) {
        if ($a->pdfworkspacename == $b->pdfworkspacename) {
            return 0;
        }
        if (strcasecmp($a->pdfworkspacename, $b->pdfworkspacename) < 0) {
            return -1;
        } else {
            return 1;
        }
    }

    public static function compare_alphabetically_descending($a, $b) {
        if ($a->pdfworkspacename == $b->pdfworkspacename) {
            return 0;
        }
        if (strcasecmp($a->pdfworkspacename, $b->pdfworkspacename) > 0) {
            return -1;
        } else {
            return 1;
        }
    }

    public static function compare_question_ascending($a, $b) {
        if ($a->answeredquestion == $b->answeredquestion) {
            return 0;
        }
        if (strcasecmp($a->answeredquestion, $b->answeredquestion) < 0) {
            return -1;
        } else {
            return 1;
        }
    }

    public static function compare_question_descending($a, $b) {
        if ($a->answeredquestion == $b->answeredquestion) {
            return 0;
        }
        if (strcasecmp($a->answeredquestion, $b->answeredquestion) > 0) {
            return -1;
        } else {
            return 1;
        }
    }

}

/**
 * Function sorts entries in a table according to time, number of votes or annotator.
 * Function is applicable to 'unsolved questions' and 'my posts' category on overview page.
 *
 * @param array $questions
 * @param string $sortcriterium The column according to which the table should be sorted
 * @param int $sortorder 3 for descending, 4 for ascending
 */
function pdfworkspace_sort_entries($questions, $sortcriterium, $sortorder) {
    switch ($sortcriterium) {
        case 'col1':
            if ($sortorder === 4) {
                usort($questions, 'pdfworkspace_compare::compare_time_ascending');
            } else if ($sortorder === 3) {
                usort($questions, 'pdfworkspace_compare::compare_time_descending');
            }
            break;
        case 'col2':
            if ($sortorder === 3) {
                usort($questions, 'pdfworkspace_compare::compare_votes_ascending');
            } else if ($sortorder === 4) {
                usort($questions, 'pdfworkspace_compare::compare_votes_descending');
            }
            break;
        case 'col3':
            if ($sortorder === 4) {
                usort($questions, 'pdfworkspace_compare::compare_alphabetically_ascending');
            } else if ($sortorder === 3) {
                usort($questions, 'pdfworkspace_compare::compare_alphabetically_descending');
            }
            break;
        default:
    }
    return $questions;
}

function pdfworkspace_sort_questions($questions, $sortcriterium, $sortorder) {
    switch ($sortcriterium) {
        case 'col1':
            if ($sortorder === 4) {
                usort($questions, 'pdfworkspace_compare::compare_creationtime_ascending');
            } else if ($sortorder === 3) {
                usort($questions, 'pdfworkspace_compare::compare_creationtime_descending');
            }
            break;
        case 'col2':
            if ($sortorder === 3) {
                usort($questions, 'pdfworkspace_compare::compare_votes_ascending');
            } else if ($sortorder === 4) {
                usort($questions, 'pdfworkspace_compare::compare_votes_descending');
            }
            break;
        case 'col3':
            if ($sortorder === 4) {
                usort($questions, 'pdfworkspace_compare::compare_answers_ascending');
            } else if ($sortorder === 3) {
                usort($questions, 'pdfworkspace_compare::compare_answers_descending');
            }
            break;
        case 'col4':
            if ($sortorder === 4) {
                usort($questions, 'pdfworkspace_compare::compare_lastanswertime_ascending');
            } else if ($sortorder === 3) {
                usort($questions, 'pdfworkspace_compare::compare_lastanswertime_descending');
            }
            break;
        case 'col5':
            if ($sortorder === 4) {
                usort($questions, 'pdfworkspace_compare::compare_alphabetically_ascending');
            } else if ($sortorder === 3) {
                usort($questions, 'pdfworkspace_compare::compare_alphabetically_descending');
            }
            break;
        default:
    }
    return $questions;
}

/**
 * Function sorts entries in a table according to annotator or time.
 * Applicable for overview answers category.
 *
 * XXX Maybe rename 'colx' into something like 'time', so as to avoid code redundancy.
 *
 * @param array $answers
 * @param int $sortcriterium
 * @param int $sortorder
 * @return array $answers
 */
function pdfworkspace_sort_answers($answers, $sortcriterium, $sortorder) {
    switch ($sortcriterium) {
        case 'col4':
            if ($sortorder === 4) {
                usort($answers, 'pdfworkspace_compare::compare_alphabetically_ascending');
            } else if ($sortorder === 3) {
                usort($answers, 'pdfworkspace_compare::compare_alphabetically_descending');
            }
            break;
        case 'col2':
            if ($sortorder === 4) {
                usort($answers, 'pdfworkspace_compare::compare_time_ascending');
            } else if ($sortorder === 3) {
                usort($answers, 'pdfworkspace_compare::compare_time_descending');
            }
            break;
        case 'col3':
            if ($sortorder === 4) {
                usort($answers, 'pdfworkspace_compare::compare_question_ascending');
            } else if ($sortorder === 3) {
                usort($answers, 'pdfworkspace_compare::compare_question_descending');
            }
            break;
        default:
    }
    return $answers;
}

/**
 *
 * @param array $reports
 * @param string $sortcriterium
 * @param int $sortorder
 * @return array $reports (sorted)
 */
function pdfworkspace_sort_reports($reports, $sortcriterium, $sortorder) {
    switch ($sortcriterium) {
        case 'col1':
            if ($sortorder === 4) {
                usort($reports, 'pdfworkspace_compare::compare_creationtime_ascending');
            } else if ($sortorder === 3) {
                usort($reports, 'pdfworkspace_compare::compare_creationtime_descending');
            }
            break;
        case 'col3':
            if ($sortorder === 4) {
                usort($reports, 'pdfworkspace_compare::compare_commenttime_ascending');
            } else if ($sortorder === 3) {
                usort($reports, 'pdfworkspace_compare::compare_commenttime_descending');
            }
            break;
        default:
    }
    return $reports;
}

/**
 * Function takes an array and returns its first key.
 *
 * @param array $array
 * @return mixed
 */
function pdfworkspace_get_first_key_in_array($array) {

    if (!function_exists('array_key_first')) { // Function exists in PHP version 7.3 and later.
        /**
         * Gets the first key of an array
         *
         * @param array $array
         * @return mixed
         */

        function array_key_first(array $array) {
            if (count($array)) {
                reset($array);
                return key($array);
            }
            return null;
        }

    }
    return array_key_first($array);
}

/**
 * This function renders the table of unsolved questions on the overview page.
 *
 * @param array $questions
 * @param int $thiscourse
 * @param Moodle url object $url
 * @param int $currentpage
 */
function pdfworkspace_print_questions($questions, $thiscourse, $urlparams, $currentpage, $itemsperpage, $context) {

    global $CFG, $OUTPUT, $USER;
    require_once("$CFG->dirroot/mod/pdfworkspace/model/overviewtable.php");

    $showdropdown = false;
    if (has_capability('mod/pdfworkspace:forwardquestions', $context)) {
        foreach ($questions as $question) {
            if (!$question->isdeleted && pdfworkspace_forward_recipients($question, $context, $USER->id)) {
                $showdropdown = true;
                break;
            }
        }
    }
    $usevotes = !empty(reset($questions)->usevotes);
    $questioncount = count($questions);
    $usepagination = !($itemsperpage == -1 || $itemsperpage >= $questioncount);
    $offset = $currentpage * $itemsperpage;

    if ($usepagination == 1 && ($offset >= $questioncount)) {
        $offset = 0;
        $urlparams['page'] = 0;
    }
    $url = new moodle_url($CFG->wwwroot . '/mod/pdfworkspace/view.php', $urlparams);

    // Define flexible table.
    $table = new questionstable($url, $showdropdown, $usevotes);
    $table->setup();
    // $table->pageable(false);
    // Sort the entries of the table according to time or number of votes.
    if (!empty($sortinfo = $table->get_sort_columns())) {
        $sortcriterium = pdfworkspace_get_first_key_in_array($sortinfo); // Returns the name (e.g. col2) of the column which was clicked for sorting.
        $sortorder = $sortinfo[$sortcriterium]; // 3 for descending, 4 for ascending.
        $questions = pdfworkspace_sort_questions($questions, $sortcriterium, $sortorder);
    }

    // Add data to the table and print the requested table (page).
    if (pdfworkspace_is_phone() || $itemsperpage == -1 || $itemsperpage >= $questioncount) { // No pagination.
        foreach ($questions as $question) {
            pdfworkspace_questionstable_add_row($thiscourse, $table, $question, $urlparams, $showdropdown);
        }
    } else {
        $table->pagesize($itemsperpage, $questioncount);
        for ($i = $offset; $i < $questioncount; $i++) {
            $question = $questions[$i];
            if ($itemsperpage === 0) {
                break;
            }
            pdfworkspace_questionstable_add_row($thiscourse, $table, $question, $urlparams, $showdropdown);
            $itemsperpage--;
        }
    }
    $table->finish_html();
}

/**
 * Function prints a table view of all answers to questions the current
 * user asked or subscribed to.
 *
 * @param int $annotator
 * @param Moodle url object $url
 * @param int $thiscourse
 */
function pdfworkspace_print_answers($data, $thiscourse, $url, $currentpage, $itemsperpage, $cmid, $answerfilter, $context) {

    global $CFG, $OUTPUT;
    require_once("$CFG->dirroot/mod/pdfworkspace/model/overviewtable.php");

    $table = new answerstable($url);
    $table->setup();

    // Sort the entries of the table according to time or number of votes.
    if (!empty($sortinfo = $table->get_sort_columns())) {
        $sortcriterium = pdfworkspace_get_first_key_in_array($sortinfo); // Returns the name (e.g. col2) of the column which was clicked for sorting.
        $sortorder = $sortinfo[$sortcriterium]; // 3 for descending, 4 for ascending.
        $data = pdfworkspace_sort_answers($data, $sortcriterium, $sortorder);
    }

    // Add data to the table and print the requested table page.
    if ($itemsperpage == -1) { // No pagination.
        foreach ($data as $answer) {
            pdfworkspace_answerstable_add_row($thiscourse, $table, $answer, $cmid, $currentpage, $itemsperpage, $answerfilter, $context);
        }
    } else {
        $answercount = count($data);
        $table->pagesize($itemsperpage, $answercount);
        $offset = $currentpage * $itemsperpage;
        $rowstoprint = $itemsperpage;
        for ($i = $offset; $i < $answercount; $i++) {
            $answer = $data[$i];
            if ($rowstoprint === 0) {
                break;
            }
            pdfworkspace_answerstable_add_row($thiscourse, $table, $answer, $cmid, $currentpage, $itemsperpage, $answerfilter, $context);
            $rowstoprint--;
        }
    }
    $table->finish_html();
}

/**
 *
 * @param type $posts
 * @param type $url
 * @param type $thiscourse
 */
function pdfworkspace_print_this_users_posts($posts, $thiscourse, $url, $currentpage, $itemsperpage) {

    global $CFG;
    require_once("$CFG->dirroot/mod/pdfworkspace/model/overviewtable.php");

    $table = new userspoststable($url, !empty(reset($posts)->usevotes));
    $table->setup();

    // Sort the entries of the table according to time or number of votes.
    if (!empty($sortinfo = $table->get_sort_columns())) {
        $sortcriterium = pdfworkspace_get_first_key_in_array($sortinfo); // Returns the name (e.g. col2) of the column which was clicked for sorting.
        $sortorder = $sortinfo[$sortcriterium]; // 3 for descending, 4 for ascending.
        $posts = pdfworkspace_sort_entries($posts, $sortcriterium, $sortorder);
    }

    // Add data to the table and print the requested table page.
    if ($itemsperpage == -1) {
        foreach ($posts as $post) {
            pdfworkspace_userspoststable_add_row($table, $post);
        }
    } else {
        $postcount = count($posts);
        $table->pagesize($itemsperpage, $postcount);
        $offset = $currentpage * $itemsperpage;
        for ($i = $offset; $i < $postcount; $i++) {
            $post = $posts[$i];
            if ($itemsperpage === 0) {
                break;
            }
            pdfworkspace_userspoststable_add_row($table, $post);
            $itemsperpage--;
        }
    }
    $table->finish_html();
}

/**
 * Function prints a table view of all comments that were reported as inappropriate.
 *
 * @param array of objects $reports
 * @param int $thiscourse
 * @param Moodle url object $url
 * @param int $currentpage
 */
function pdfworkspace_print_reports($reports, $thiscourse, $url, $currentpage, $itemsperpage, $cmid, $reportfilter, $context) {

    global $CFG, $OUTPUT;
    require_once("$CFG->dirroot/mod/pdfworkspace/model/overviewtable.php");

    $table = new reportstable($url);
    $table->setup();
    // Sort the entries of the table according to time or number of votes.
    if (!empty($sortinfo = $table->get_sort_columns())) {
        $sortcriterium = pdfworkspace_get_first_key_in_array($sortinfo); // Returns the name (e.g. col2) of the column which was clicked for sorting.
        $sortorder = $sortinfo[$sortcriterium]; // 3 for descending, 4 for ascending.
        $reports = pdfworkspace_sort_reports($reports, $sortcriterium, $sortorder);
    }
    // Add data to the table and print the requested table page.
    if ($itemsperpage == -1) {
        foreach ($reports as $report) {
            pdfworkspace_reportstable_add_row($thiscourse, $table, $report, $cmid, $itemsperpage, $reportfilter, $currentpage, $context);
        }
    } else {
        $reportcount = count($reports);
        $table->pagesize($itemsperpage, $reportcount);
        $offset = $currentpage * $itemsperpage;
        $rowstoprint = $itemsperpage;
        for ($i = $offset; $i < $reportcount; $i++) {
            $report = $reports[$i];
            if ($rowstoprint === 0) {
                break;
            }
            pdfworkspace_reportstable_add_row($thiscourse, $table, $report, $cmid, $itemsperpage, $reportfilter, $currentpage, $context);
            $rowstoprint--;
        }
    }
    $table->finish_html();
}

/**
 * This function adds a row of data to the overview table that displays all
 * unsolved questions in the course.
 *
 * @param int $thiscourse
 * @param questionstable $table
 * @param object $question
 */
function pdfworkspace_questionstable_add_row($thiscourse, $table, $question, $urlparams, $showdropdown) {

    global $CFG, $PAGE, $USER;
    if ($question->visibility == 'anonymous') {
        $author = get_string('anonymous', 'pdfworkspace');
    } else {
        $author = "<a href=" . $CFG->wwwroot . "/user/view.php?id=$question->userid&course=$thiscourse>" . pdfworkspace_get_username($question->userid) . "</a>";
    }
    if (!empty($question->lastanswered)) { // ! ($question->lastanswered != $question->timecreated) {
        if ($question->lastuservisibility == 'anonymous') {
            $lastresponder = get_string('anonymous', 'pdfworkspace');
        } else {
            $lastresponder = "<a href=" . $CFG->wwwroot . "/user/view.php?id=$question->lastuser&course=$thiscourse>" . pdfworkspace_get_username($question->lastuser) . "</a>";
        }
        $answertime = userdate($question->lastanswered, '%d.%m.%y, %H:%M');
        $lasturl = new moodle_url('/mod/pdfworkspace/view.php', ['id' => $question->cmid,
            'doc' => $question->documentid, 'page' => $question->page, 'annoid' => $question->annoid,
            'commid' => $question->lastanswerid]);
        $lastanswered = html_writer::tag('div', $lastresponder, ['class' => 'pdfworkspace-overview-author']) .
            html_writer::link($lasturl, $answertime, ['class' => 'pdfworkspace-overview-meta']);
    } else {
        $lastanswered = '-';
    }
    $classname = '';
    if (isset($question->displayhidden)) {
        $classname = 'dimmed_text';
    }
    $content = pdfworkspace_overview_preview($question->content, $question->link);
    $authorcell = pdfworkspace_overview_person($author, $question->timecreated);
    $document = pdfworkspace_overview_document_name($question->pdfworkspaceid,
        context_module::instance($question->cmid)->id, $question->documentid, $question->page, $question->link);
    $data = [$document, $content, $authorcell];
    if ($question->usevotes) {
        $data[] = $question->votes;
    }
    $answercell = html_writer::tag('div', (string)$question->answercount,
        ['class' => 'pdfworkspace-overview-answer-count']);
    array_push($data, $answercell, $lastanswered);

    if ($showdropdown) {
        $canforward = !$question->isdeleted && pdfworkspace_forward_recipients($question,
            context_module::instance($question->cmid), $USER->id);
        $data[] = $canforward ? $PAGE->get_renderer('mod_pdfworkspace')->render_overview_actions(
            new questionmenu($question->commentid, $urlparams)) : '';
    }
    $table->add_data($data, $classname);
}

/**
 * This function adds a row of data to the overview table that displays
 * answers to any question the user subscribed to.
 *
 * @param int $thiscourse
 * @param answerstable $table
 * @param object $answer
 */
function pdfworkspace_answerstable_add_row($thiscourse, $table, $answer, $cmid, $currentpage, $itemsperpage, $answerfilter, $context) {
    global $CFG, $PAGE;

    $answer->answer = pdfworkspace_get_relativelink($answer->answer, $answer->answerid, $context);
    $answer->answer = format_text($answer->answer, FORMAT_MOODLE, ['filter' => true]);
    $answer->answeredquestion = pdfworkspace_get_relativelink($answer->answeredquestion, $answer->questionid, $context);
    $answer->answeredquestion = format_text($answer->answeredquestion, FORMAT_MOODLE);
    $question = pdfworkspace_overview_preview($answer->answeredquestion, $answer->questionlink,
        ['class' => isset($answer->displayquestionhidden) ? 'dimmed' : '']);
    $document = pdfworkspace_overview_document_name($answer->annotatorid,
        context_module::instance($answer->cmid)->id, $answer->documentid, $answer->page, $answer->link);
    $answerid = 'answer_' . $answer->answerid;
    $answerlink = pdfworkspace_overview_preview($answer->answer, $answer->link,
        ['id' => $answerid, 'data-question' => $answer->questionid]);

    if ($answer->visibility == 'anonymous') {
        $answeredby = get_string('anonymous', 'pdfworkspace');
    } else {
        $answeredby = "<a href=" . $CFG->wwwroot . "/user/view.php?id=$answer->userid&course=$thiscourse>" . pdfworkspace_get_username($answer->userid) . "</a>";
    }
    $authorcell = pdfworkspace_overview_person($answeredby, $answer->timemodified);
    if ($answer->correct) {
        $authorcell .= html_writer::tag('small', get_string('correct', 'pdfworkspace'));
    }

    if (empty($answer->issubscribed)) {
        $issubscribed = null;
    } else {
        $issubscribed = $answer->issubscribed;
    }

    $classname = '';
    if (isset($answer->displayhidden)) {
        $classname = 'dimmed_text';
    }

    $myrenderer = $PAGE->get_renderer('mod_pdfworkspace');
    $actions = $myrenderer->render_overview_actions(new answermenu($answer->annoid, $issubscribed,
        $cmid, $currentpage, $itemsperpage, $answerfilter));

    $table->add_data(array($document, $question, $answerlink, $authorcell, $actions), $classname);
}

/**
 * This function adds a row of data to the overview table that displays all
 * comments the current user posted in this course.
 *
 * @param userspoststable $table
 * @param object $post
 */
function pdfworkspace_userspoststable_add_row($table, $post) {
    $time = userdate($post->timemodified, '%d.%m.%y, %H:%M');
    $content = pdfworkspace_overview_preview($post->content, $post->link);

    $classname = '';
    if (isset($post->displayhidden)) {
        $classname = 'dimmed_text';
    }
    $document = pdfworkspace_overview_document_name($post->pdfworkspaceid,
        context_module::instance($post->cmid)->id, $post->documentid, $post->page, $post->link);
    $data = [$document, $content, $time];
    if ($post->usevotes) {
        $data[] = $post->votes;
    }
    $table->add_data($data, $classname);
}

/**
 * This function adds a row of data to the overview table that displays all
 * comments reported in this course.
 *
 * @param int $thiscourse
 * @param reportstable $table
 * @param object $report
 * @param int $cmid
 * @param int $itemsperpage
 * @param int $reportfilter
 * @param int $currentpage
 */
function pdfworkspace_reportstable_add_row($thiscourse, $table, $report, $cmid, $itemsperpage, $reportfilter, $currentpage, $context) {
    global $CFG, $PAGE, $DB;
    $questionid = $DB->get_record('pdfworkspace_comments', ['annotationid' => $report->annotationid, 'isquestion' => 1], 'id');
    $report->report = pdfworkspace_get_relativelink($report->report, $questionid, $context);
    $report->reportedcomment = pdfworkspace_get_relativelink($report->reportedcomment, $report->commentid, $context);

    // Prepare report data for display.
    $reportid = 'report_' . $report->reportid;
    $document = pdfworkspace_overview_document_name($report->annotatorid, $context->id,
        $report->documentid, $report->page, $report->link);
    $reportedcommmentlink = pdfworkspace_overview_preview($report->reportedcomment, $report->link, ['id' => $reportid]);
    $writtenby = "<a href=" . $CFG->wwwroot . "/user/view.php?id=$report->commentauthor&course=$thiscourse>" . pdfworkspace_get_username($report->commentauthor) . "</a>";
    $reportedby = "<a href=" . $CFG->wwwroot . "/user/view.php?id=$report->reportinguser&course=$thiscourse>" . pdfworkspace_get_username($report->reportinguser) . "</a>";
    $reporttext = pdfworkspace_overview_preview($report->report);
    $reporter = pdfworkspace_overview_person($reportedby, $report->timecreated);
    $commentauthor = pdfworkspace_overview_person($writtenby, $report->commenttime);

    $classname = '';
    if (!($report->cmvisible)) {
        $classname = 'dimmed_text';
    }

    // Create the row's direct action.
    $myrenderer = $PAGE->get_renderer('mod_pdfworkspace');
    $actions = $myrenderer->render_overview_actions(new reportmenu($report, $cmid,
        $currentpage, $itemsperpage, $reportfilter));

    // Add a new row to the reports table.
    $table->add_data(array($document, $reporttext, $reporter, $reportedcommmentlink, $commentauthor, $actions), $classname);
}


/**
 * Function takes a moodle timestamp, calculates how much time has since elapsed
 * and returns this information as a string (e.g.: '3 days ago').
 *
 * @param int $timestamp
 * @return string
 */
function pdfworkspace_timeago($timestamp) {
    $strtime = array(get_string('second', 'pdfworkspace'), get_string('minute', 'pdfworkspace'), get_string('hour', 'pdfworkspace'));
    $strtime[] = get_string('day', 'pdfworkspace');
    $strtime[] = get_string('month', 'pdfworkspace');
    $strtime[] = get_string('year', 'pdfworkspace');
    $strtimeplural = array(get_string('seconds', 'pdfworkspace'), get_string('minutes', 'pdfworkspace'));
    $strtimeplural[] = get_string('hours', 'pdfworkspace');
    $strtimeplural[] = get_string('days', 'pdfworkspace');
    $strtimeplural[] = get_string('months', 'pdfworkspace');
    $strtimeplural[] = get_string('years', 'pdfworkspace');
    $length = array("60", "60", "24", "30", "12", "10");
    $currenttime = time();
    if ($currenttime >= $timestamp) {
        $diff = time() - $timestamp;
        if ($diff < 60) {
            return get_string('justnow', 'pdfworkspace');
        }
        for ($i = 0; $diff >= $length[$i] && $i < count($length) - 1; $i++) {
            $diff = $diff / $length[$i];
        }
        $diff = intval(round($diff));
        if ($diff === 1) {
            $diff = $diff . ' ' . $strtime[$i];
        } else {
            $diff = $diff . ' ' . $strtimeplural[$i];
        }
        return get_string('ago', 'pdfworkspace', $diff);
    }
}

/**
 * Function takes a moodle timestamp, calculates how much time has since elapsed
 * and returns this information as a string. If the timestamp is older than 2 days,
 * the ecaxt datetime is returned. Otherwise, the string looks like '3 days ago'.
 *
 * @param type $timestamp
 * @return string
 */
function pdfworkspace_optional_timeago($timestamp) {
    $currenttime = time();
    // For entries older than 2 days, display the exact time.
    if ($currenttime - $timestamp > 172799) {
        return pdfworkspace_get_user_datetime_shortformat($timestamp);
    } else {
        return pdfworkspace_timeago($timestamp);
    }
}

function pdfworkspace_is_mobile_device() {
    $param = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_DEFAULT); // XXX How to filter, here?
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $param);
}

function pdfworkspace_is_phone() {
    $param = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_DEFAULT); // XXX How to filter, here?
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $param);
}


function pdfworkspace_get_last_answer($annotationid, $context) {
    global $DB;
    $params = array('isquestion' => 0, 'annotationid' => $annotationid);
    $answers = $DB->get_records('pdfworkspace_comments', $params, 'timecreated DESC' );

    foreach ($answers as $answer) {
        if (!pdfworkspace_can_see_comment($answer, $context)) {
            continue;
        } else {
            $answer->content = pdfworkspace_get_relativelink($answer->content, $answer->id, $context);
            return $answer;
        }
    }
    return null;
}

function pdfworkspace_can_see_comment($comment, $context) {
    global $USER;
    if (is_array($comment)) {
        $comment = (object)$comment;
    }
    return \mod_pdfworkspace\visibility::can_view_comment($comment, $context, $USER->id);
}

/**
 * Count how many answers has a question with $annotationid
 * return only answers that the user can see
 */
function pdfworkspace_count_answers($annotationid, $context) {
    global $DB;
    $params = array('isquestion' => 0, 'annotationid' => $annotationid);
    $answers = $DB->get_records('pdfworkspace_comments', $params);
    $count = 0;

    foreach ($answers as $answer) {

        if (!pdfworkspace_can_see_comment($answer, $context)) {
            continue;
        }
        $count++;
    }
    return $count;
}
