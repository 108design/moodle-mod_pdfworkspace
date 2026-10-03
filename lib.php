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
 * Defining general functions for the plugin
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen (see README.md)
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @author    Ahmad Obeid, Rabea de Groot, Anna Heynkes
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/pdfworkspace/locallib.php');

// Ugly hack to make 3.11 and 4.0 work seamlessly.
if (!defined('FEATURE_MOD_PURPOSE')) {
    define('FEATURE_MOD_PURPOSE', 'mod_purpose');
}
if (!defined('MOD_PURPOSE_COMMUNICATION')) {
    define('MOD_PURPOSE_COMMUNICATION', 'communication');
}

/**
 * List of features supported in pdfworkspace module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function pdfworkspace_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_ADVANCED_GRADING:
            return false;
        case FEATURE_PLAGIARISM:
            return false;
        case FEATURE_COMMENT:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COMMUNICATION;
        default:
            return null;
    }
}
/**
 * Returns all other caps used in module
 * @return array
 */
function pdfworkspace_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}
/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function pdfworkspace_reset_userdata($data) {
    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function pdfworkspace_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function pdfworkspace_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add pdfworkspace instance.
 * @param object $data
 * @param mod_pdfworkspace_mod_form $mform
 * @return int new pdfworkspace instance id
 */
function pdfworkspace_add_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");
    require_once("$CFG->dirroot/mod/pdfworkspace/locallib.php");
    $cmid = $data->coursemodule;
    $data->timemodified = time();
    pdfworkspace_set_allowed_audiences($data);
    pdfworkspace_set_display_options($data);

    $data->id = $DB->insert_record('pdfworkspace', $data);

    // We need to use context now, so we need to make sure all needed info is already in db.
    $DB->set_field('course_modules', 'instance', $data->id, array('id' => $cmid));
    pdfworkspace_set_mainfile($data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'pdfworkspace', $data->id, $completiontimeexpected);

    return $data->id;
}

/**
 * Update pdfworkspace instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function pdfworkspace_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");
    require_once("$CFG->dirroot/mod/pdfworkspace/locallib.php");
    $data->timemodified = time();
    pdfworkspace_set_allowed_audiences($data);
    $removedocumentids = [];
    foreach (array_keys(get_object_vars($data)) as $property) {
        if (preg_match('/^removedocument_([0-9]+)$/', $property, $matches)) {
            if (!empty($data->$property)) {
                $removedocumentids[] = (int)$matches[1];
            }
            unset($data->$property);
        }
    }
    $data->id = $data->instance;
    $data->revision++;

    pdfworkspace_set_display_options($data); // Can be deleted or extended.

    $DB->update_record('pdfworkspace', $data);
    pdfworkspace_set_mainfile($data, $removedocumentids);
    pdfworkspace_save_workspace_selection($data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'pdfworkspace', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Convert the activity form's audience checkboxes to two stored role masks.
 *
 * @param object $data
 */
function pdfworkspace_set_allowed_audiences($data) {
    foreach (['student' => ['private' => 1, 'protected' => 2, 'public' => 4],
        'staff' => ['private' => 1, 'targeted' => 2, 'public' => 4]] as $role => $options) {
        $mask = 0;
        $submitted = false;
        foreach ($options as $audience => $bit) {
            $property = 'audience_' . $role . '_' . $audience;
            if (property_exists($data, $property)) {
                $submitted = true;
                if ($data->$property) {
                    $mask |= $bit;
                }
                unset($data->$property);
            }
        }
        if ($submitted) {
            $field = $role === 'student' ? 'studentaudiences' : 'staffaudiences';
            $data->$field = $mask;
        }
    }
}

/** Build Moodle's native inline editor beside a document navigation link. */
function pdfworkspace_document_title_editable($document, $cmid, $editable) {
    $title = !empty($document->displayname) ? $document->displayname : $document->filename;
    $url = new moodle_url('/mod/pdfworkspace/view.php', ['id' => $cmid, 'doc' => $document->id]);
    $link = html_writer::link($url, s($title), [
        'class' => 'pdfworkspace-documenttab-link',
        'title' => $document->filename,
    ]);
    return new \core\output\inplace_editable('mod_pdfworkspace', 'documenttitle', $document->id,
        $editable, $link, $title, get_string('editdocumenttitle', 'pdfworkspace'),
        get_string('documenttitle', 'pdfworkspace'));
}

/** Server callback for core_update_inplace_editable. */
function pdfworkspace_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB;
    if ($itemtype !== 'documenttitle') {
        throw new invalid_parameter_exception('Unknown editable item');
    }
    $document = $DB->get_record('pdfworkspace_documents', ['id' => (int)$itemid], '*', MUST_EXIST);
    $activity = $DB->get_record('pdfworkspace', ['id' => $document->pdfworkspaceid], 'id,course', MUST_EXIST);
    $cm = get_coursemodule_from_instance('pdfworkspace', $activity->id, $activity->course, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    \core_external\external_api::validate_context($context);
    require_capability('moodle/course:manageactivities', $context);
    $title = trim(clean_param($newvalue, PARAM_TEXT));
    if ($title === '' || core_text::strlen($title) > 80) {
        throw new invalid_parameter_exception(get_string('error:documenttitle', 'pdfworkspace'));
    }
    $DB->set_field('pdfworkspace_documents', 'displayname', $title, ['id' => $document->id]);
    $document->displayname = $title;
    return pdfworkspace_document_title_editable($document, $cm->id, true);
}

/**
 * Updates display options based on form input.
 *
 * Shared code used by pdfworkspace_add_instance and pdfworkspace_update_instance.
 * keep it, if you want defind more disply options
 * @param object $data Data object
 */
function pdfworkspace_set_display_options($data) {
    $displayoptions = array();
    $displayoptions['printintro'] = (int) !empty($data->printintro);
    $data->displayoptions = serialize($displayoptions);
}

/**
 * Delete pdfworkspace instance.
 * @param int $id in mdl_pdfworkspace
 * @return bool true
 */
function pdfworkspace_delete_instance($id) {

    global $DB;

    if (!$pdfworkspace = $DB->get_record('pdfworkspace', array('id' => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('pdfworkspace', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'pdfworkspace', $id, null);

    // Note: all context files are deleted automatically.
    // 1.a) Get all annotations of the annotator.
    $annotations = $DB->get_records('pdfworkspace_annotations', ['pdfworkspaceid' => $id]);

    // 1.b) For every annotation delete all subscriptions attached to it.
    foreach ($annotations as $annotation) {
        if (!$DB->delete_records('pdfworkspace_subscriptions', ['annotationid' => $annotation->id]) == 1) {
            return false;
        }
    }
    // 1.c) Then delete the annotations from the annotations table.
    if (!$DB->delete_records('pdfworkspace_annotations', ['pdfworkspaceid' => $id]) == 1) {
        return false;
    }
    $DB->delete_records('pdfworkspace_documents', ['pdfworkspaceid' => $id]);

    // 2.a) Get all comments in this annotator.
    $comments = $DB->get_records('pdfworkspace_comments', ['pdfworkspaceid' => $id]);

    // 2.b) Delete all votes in this annotator.
    foreach ($comments as $comment) {
        if (!$DB->delete_records('pdfworkspace_votes', ['commentid' => $comment->id]) == 1) {
            return false;
        }
    }
    // 2.c) Delete all comments in this annotator.
    if (!$DB->delete_records('pdfworkspace_comments', ['pdfworkspaceid' => $id]) == 1) {
        return false;
    }

    // 3. Deleting all the reports.
    if (!$DB->delete_records('pdfworkspace_reports', ['pdfworkspaceid' => $id])) {
        return false;
    }

    // 4. Delete the annotator itself.
    if (!$DB->delete_records('pdfworkspace', array('id' => $id)) == 1) {
        return false;
    }

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@see get_array_of_activities()} in course/lib.php
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info info
 */
function pdfworkspace_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->libdir/filelib.php");
    require_once("$CFG->dirroot/mod/pdfworkspace/locallib.php");
    require_once($CFG->libdir . '/completionlib.php');

    $context = context_module::instance($coursemodule->id);

    if (!$pdfworkspace = $DB->get_record('pdfworkspace', array('id' => $coursemodule->instance), 'id, name, course,
        timemodified, timecreated, intro, introformat')) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $pdfworkspace->name;
    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('pdfworkspace', $pdfworkspace, $coursemodule->id, false);
    }

    // See if there is at least one file.
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_pdfworkspace', 'content', 0, 'sortorder DESC, id ASC', false, 0, 0, 1);
    if (count($files) >= 1) {
        $mainfile = reset($files);
        // $info->icon = file_file_icon($mainfile, 24); // Uncomment to use pdf icon.
        $pdfworkspace->mainfile = $mainfile->get_filename();
    }
    // If any optional extra details are turned on, store in custom data,
    // add some file details as well to be used later by pdfworkspace_get_optional_details() without retriving.
    // Do not store filedetails if this is a reference - they will still need to be retrieved every time.

    return $info;
}

/**
 * Lists all browsable file areas
 *
 * @package  mod_pdfworkspace
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @return array
 */
function pdfworkspace_get_file_areas($course, $cm, $context) {
    $areas = array();
    $areas['content'] = get_string('pdfworkspacecontent', 'pdfworkspace');
    $areas['post'] = get_string('pdfworkspacepost', 'pdfworkspace');
    return $areas;
}

/**
 * File browsing support for pdfworkspace module content area.
 *
 * @package  mod_pdfworkspace
 * @category files
 * @param stdClass $browser file browser instance
 * @param stdClass $areas file areas
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param int $itemid item ID
 * @param string $filepath file path
 * @param string $filename file name
 * @return file_info instance or null if not found
 */
function pdfworkspace_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // Students can not peak here!
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'content') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot . '/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_pdfworkspace', 'content', 0, $filepath, $filename)) {
            if ($filepath === '/' && $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_pdfworkspace', 'content', 0);
            } else {
                // Not found.
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/pdfworkspace/locallib.php");
        return new pdfworkspace_content_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, true, false);
    }

    // Note: pdfworkspace_intro handled in file_browser automatically.

    return null;
}

/**
 * Serves the pdfworkspace files.
 *
 * @package  mod_pdfworkspace
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function pdfworkspace_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG, $DB, $USER;
    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/pdfworkspace:view', $context)) {
        return false;
    }

    if ($filearea !== 'content' && $filearea !== 'post') {
        // Intro is handled automatically in pluginfile.php.
        return false;
    }
    $fs = get_file_storage();
    if ($filearea === 'content') {
        array_shift($args); // Ignore revision - designed to prevent caching problems only.
        $relativepath = implode('/', $args);
        $fullpath = rtrim("/$context->id/mod_pdfworkspace/$filearea/0/$relativepath", '/');
        do {
            if (!$file = $fs->get_file_by_hash(sha1($fullpath))) {
                if ($fs->get_file_by_hash(sha1("$fullpath/."))) {
                    if ($file = $fs->get_file_by_hash(sha1("$fullpath/index.htm"))) {
                        break;
                    }
                    if ($file = $fs->get_file_by_hash(sha1("$fullpath/index.html"))) {
                        break;
                    }
                    if ($file = $fs->get_file_by_hash(sha1("$fullpath/Default.htm"))) {
                        break;
                    }
                }
                $pdfworkspace = $DB->get_record('pdfworkspace', array('id' => $cm->instance), 'id, legacyfiles', MUST_EXIST);
                if ($pdfworkspace->legacyfiles != RESOURCELIB_LEGACYFILES_ACTIVE) {
                    return false;
                }
                if (!$file = resourcelib_try_file_migration('/' . $relativepath, $cm->id, $cm->course, 'mod_pdfworkspace', 'content', 0)) {
                    return false;
                }
                // File migrate - update flag.
                $pdfworkspace->legacyfileslast = time();
                $DB->update_record('pdfworkspace', $pdfworkspace);
            }
        } while (false);

        // Should we apply filters?
        // $mimetype = $file->get_mimetype();
        $filter = 0;
        // Finally send the file.
        send_stored_file($file, null, $filter, $forcedownload, $options);
    }

    if ($filearea === 'post') {
        $commentid = isset($args[0]) ? (int)$args[0] : 0;
        $comment = $DB->get_record('pdfworkspace_comments',
            ['id' => $commentid, 'pdfworkspaceid' => $cm->instance]);
        if (!$comment || !\mod_pdfworkspace\visibility::can_view_comment($comment, $context, $USER->id)) {
            return false;
        }
        array_shift($args);
        $relativepath = implode('/', $args);
        $fullpath = rtrim("/$context->id/mod_pdfworkspace/$filearea/$commentid/$relativepath", '/');
        $file = $fs->get_file_by_hash(sha1($fullpath));
        if (!$file || $file->is_directory()) {
            // Annotations from other documents might have another contextid.
            $pdfid = $DB->get_record('pdfworkspace_comments', ['id' => $commentid], 'pdfworkspaceid');
            if ($pdfid) {
                $pdfworkspace = $DB->get_record('pdfworkspace', ['id' => $pdfid->pdfworkspaceid], '*', MUST_EXIST);
                $cm = get_coursemodule_from_instance('pdfworkspace', $pdfid->pdfworkspaceid, $pdfworkspace->course, false, MUST_EXIST);
                $context2 = context_module::instance($cm->id);
                $fullpath = rtrim("/$context2->id/mod_pdfworkspace/$filearea/$commentid/$relativepath", '/');
                if (!$file = $fs->get_file_by_hash(sha1($fullpath)) || $file->is_directory()) {
                    return false;
                }
                send_stored_file($file, null, 0, true, $options);
            }
        }
        send_stored_file($file, null, 0, true, $options);
    }
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function pdfworkspace_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = array('mod-pdfworkspace-*' => get_string('page-mod-pdfworkspace-x', 'pdfworkspace'));
    return $modulepagetype;
}

/**
 * Export file pdfworkspace contents
 *
 * @return array of file content
 */
function pdfworkspace_export_contents($cm, $baseurl) {
    global $CFG, $DB;
    $contents = array();
    $context = context_module::instance($cm->id);
    $pdfworkspace = $DB->get_record('pdfworkspace', array('id' => $cm->instance), '*', MUST_EXIST);
    if ($pdfworkspace->useprint == 1) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_pdfworkspace', 'content', 0, 'sortorder DESC, id ASC', false);
        $fileinfo = reset($files);
        $file = array();
        $file['type'] = 'file';
        $file['filename'] = $fileinfo->get_filename();
        $file['filepath'] = $fileinfo->get_filepath();
        $file['filesize'] = $fileinfo->get_filesize();
        $file['mimetype'] = 'pdf';
        $file['fileurl'] = moodle_url::make_webservice_pluginfile_url(
                    $context->id, 'mod_pdfworkspace', 'content', '1', $fileinfo->get_filepath(), $fileinfo->get_filename())->out(false);
        $file['timecreated'] = $fileinfo->get_timecreated();
        $file['timemodified'] = $fileinfo->get_timemodified();
        $file['sortorder'] = $fileinfo->get_sortorder();
        $file['userid'] = $fileinfo->get_userid();
        $file['author'] = $fileinfo->get_author();
        $file['license'] = $fileinfo->get_license();
        $file['mimetype'] = $fileinfo->get_mimetype();
        $file['isexternalfile'] = $fileinfo->is_external_file();
        if ($file['isexternalfile']) {
            $file['repositorytype'] = $fileinfo->get_repository_type();
        }
        $contents[] = $file;
    }
    return $contents;
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
// function pdfworkspace_dndupload_register() {
    // return array('files' => array(
                   // array('extension' => 'pdf', 'message' => get_string('dnduploadpdfworkspace', 'mod_pdfworkspace'))
                // ));
// }

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
// function pdfworkspace_dndupload_handle($uploadinfo) {
// // Gather the required info.
// $data = new stdClass();
// $data->course = $uploadinfo->course->id;
// $data->name = $uploadinfo->displayname;
// $data->intro = '';
// $data->introformat = FORMAT_HTML;
// $data->coursemodule = $uploadinfo->coursemodule;
// $data->files = $uploadinfo->draftitemid;
//
// // Set the display options to the site defaults.
// $config = get_config('pdfworkspace');//
//
// return pdfworkspace_add_instance($data, null);
// }

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $pdfworkspace   pdfworkspace object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function pdfworkspace_view($pdfworkspace, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $pdfworkspace->id,
    );

    $event = \mod_pdfworkspace\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('pdfworkspace', $pdfworkspace);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function pdfworkspace_check_updates_since(cm_info $cm, $from, $filter = array()) {
    $updates = course_check_module_updates_since($cm, $from, array('content'), $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_pdfworkspace_core_calendar_provide_event_action(calendar_event $event, \core_calendar\action_factory $factory) {
    $cm = get_fast_modinfo($event->courseid)->instances['pdfworkspace'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
                    get_string('view'), new \moodle_url('/mod/pdfworkspace/view.php', ['id' => $cm->id]), 1, true
    );
}

/**
 * Returns all annotations comments since a given time in specified annotator.
 *
 * @todo Document this functions args
 * @param $activities
 * @param $index
 * @param $timestart
 * @param $courseid
 * @param $cmid
 * @param int $userid
 * @param int $groupid
 * @throws dml_exception
 * @throws moodle_exception
 */
function pdfworkspace_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0) {
    global $CFG, $COURSE, $USER, $DB;
    require_once($CFG->dirroot . '/mod/pdfworkspace/locallib.php');
    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id' => $courseid));
    }

    $modinfo = get_fast_modinfo($course);

    $cm = $modinfo->cms[$cmid];
    $params = array($timestart, $cm->instance);

    if ($userid) {
        $userselect = "AND u.id = ? AND c.visibility='public'";
        $params[] = $userid;
    } else {
        $userselect = "";
    }
    if ($groupid) {
        $groupselect = "AND d.groupid = ?";
        $params[] = $groupid;
    } else {
        $groupselect = "";
    }

    if (class_exists('\core_user\fields')) {
        $allnames = \core_user\fields::for_name()->get_sql('u', true);
    } else {
         $allnames = get_all_user_name_fields(true, 'u');
    }

    if (!$posts = $DB->get_records_sql("SELECT p.*,c.id, c.userid AS userid, c.visibility, c.content, c.timecreated, c.annotationid, c.isquestion,
                                              $allnames, u.email, u.picture, u.imagealt, u.email, a.page
                                         FROM {pdfworkspace} p
                                              JOIN {pdfworkspace_annotations} a ON  a.pdfworkspaceid=p.id
                                              JOIN {pdfworkspace_comments} c       ON  c.annotationid = a.id
                                              JOIN {user} u              ON u.id = a.userid
                                        WHERE c.timecreated > ? AND p.id = ?
                                              $userselect AND c.isdeleted=0
                                    ORDER BY p.id ASC ", $params)) { // Order by initial posting date.
        return;
    }
    $printposts = array();
    $context = context_module::instance($cm->id);
    foreach ($posts as $post) {
        if (!pdfworkspace_can_see_comment($post, $context)) {
            continue;
        }
        $printposts[] = $post;
    }
    if (!$printposts) {
        return;
    }

    foreach ($printposts as $post) {
        $tmpactivity = new stdClass();

        $tmpactivity->type = 'pdfworkspace';
        $tmpactivity->cmid = $cm->id;
        $tmpactivity->name = format_string($cm->name, true);
        $tmpactivity->sectionnum = $cm->sectionnum;
        $tmpactivity->timestamp = $post->timecreated;

        $tmpactivity->content = new stdClass();
        $tmpactivity->content->id = $post->annotationid;
        $tmpactivity->content->commid = $post->id;
        $tmpactivity->content->isquestion = $post->isquestion;
        $tmpactivity->content->discussion = format_string($post->content);

        $tmpactivity->content->page = $post->page;
        $tmpactivity->visible = $post->visibility;

        $tmpactivity->user = new stdClass();
        // $additionalfields = array('id' => 'userid', 'picture', 'imagealt', 'email');
        $additionalfields = explode(',', user_picture::fields());
        $tmpactivity->user = username_load_fields_from_object($tmpactivity->user, $post, null, $additionalfields);
        $tmpactivity->user->id = $post->userid;

        $activities[$index++] = $tmpactivity;
    }

    return;
}

/**
 * Outputs the pdfworkspace post indicated by $activity.
 *
 * @param object $activity      the activity object the annotator resides in
 * @param int    $courseid      the id of the course the annotator resides in
 * @param bool   $detail        not used, but required for compatibilty with other modules
 * @param int    $modnames      not used, but required for compatibilty with other modules
 * @param bool   $viewfullnames not used, but required for compatibilty with other modules
 */
function pdfworkspace_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    global $OUTPUT;

    $content = $activity->content;

    $class = 'discussion';

    $tableoptions = [
        'border-top' => '0',
        'cellpadding' => '3',
        'cellspacing' => '0',
        'class' => '',
    ];
    $output = html_writer::start_tag('table', $tableoptions);
    $output .= html_writer::start_tag('tr');

    $authorhidden = ($activity->visible == 'anonymous') ? 1 : 0;

    // Show user picture if author should not be hidden.
    $pictureoptions = [
        'courseid' => $courseid,
    ];
    if (!$authorhidden) {
        $picture = $OUTPUT->user_picture($activity->user, $pictureoptions);
    } else {
        // $pictureoptions = [  'courseid' => $courseid, 'link' => $authorhidden, 'alttext' => $authorhidden, ];
        $pic = $OUTPUT->image_url('/u/f2');
        $picture = '<img src="' . $pic . '" class="userpicture" alt="' . get_string('anonymous', 'pdfworkspace') . '" width="35" height="35">';
    }
    $output .= html_writer::tag('td', $picture, ['class' => 'userpicture', 'valign' => 'top']);

    // Discussion title and author.
    $output .= html_writer::start_tag('td', ['class' => $class]);

    $class = 'title';

    $output .= html_writer::start_div($class);
    if ($detail) {
        $aname = s($activity->name);
        $output .= $OUTPUT->image_icon('icon', $aname, $activity->type);
    }
    $isquestion = ($content->isquestion) ? '<img src="' . $OUTPUT->image_url('t/message') . '" alt="' . get_string('question', 'pdfworkspace')
            . '" title="' . get_string('question', 'pdfworkspace') . '"> ' : '';
    $discussionurl = new moodle_url('/mod/pdfworkspace/view.php', ['id' => $activity->cmid, 'page' => $content->page, 'annoid' => $content->id, 'commid' => $content->commid]);
    // $discussionurl->set_anchor('p' . $activity->content->id);
    $output .= html_writer::link($discussionurl, ($isquestion . $content->discussion));
    $output .= html_writer::end_div();

    $timestamp = userdate($activity->timestamp);
    if ($authorhidden) {
        $by = new stdClass();
        $by->name = get_string('anonymous', 'pdfworkspace');
        $by->date = $timestamp;
        $authornamedate = get_string('bynameondate', 'pdfworkspace', $by);
    } else {
        $fullname = fullname($activity->user, $viewfullnames);
        $userurl = new moodle_url('/user/view.php');
        $userurl->params(['id' => $activity->user->id, 'course' => $courseid]);
        $by = new stdClass();
        $by->name = html_writer::link($userurl, $fullname);
        $by->date = $timestamp;
        $authornamedate = get_string('bynameondate', 'pdfworkspace', $by);
    }
    $output .= html_writer::div($authornamedate, 'user');
    $output .= html_writer::end_tag('td');
    $output .= html_writer::end_tag('tr');
    $output .= html_writer::end_tag('table');

    echo $output;
}

/**
 * Initialize the editor for editing a comment.
 * @param type $args
 * @return string
 */
function mod_pdfworkspace_output_fragment_open_edit_comment_editor($args) {
    global $DB, $USER;

    $context = context_module::instance($args['cmid']);
    require_capability('mod/pdfworkspace:view', $context);
    $cm = get_coursemodule_from_id('pdfworkspace', $args['cmid'], 0, false, MUST_EXIST);
    $comment = $DB->get_record('pdfworkspace_comments',
        ['id' => $args['uuid'], 'pdfworkspaceid' => $cm->instance], '*', MUST_EXIST);
    if (!\mod_pdfworkspace\visibility::can_view_comment($comment, $context, $USER->id) ||
            ((int)$comment->userid !== (int)$USER->id &&
                !has_capability('mod/pdfworkspace:editanypost', $context))) {
        throw new required_capability_exception($context, 'mod/pdfworkspace:edit', 'nopermissions', '');
    }

    $data = pdfworkspace_data_preprocessing($context, 'editarea' . $args['uuid'], 0);
    $displaycontent = pdfworkspace_file_prepare_draft_area($data['draftItemId'], $context->id, 'mod_pdfworkspace', 'post',
    $args['uuid'], pdfworkspace_get_editor_options($context), $comment->content);

    // Input fields.
    $out = '';
    $out .= html_writer::empty_tag('input', ['type' => 'hidden',
                                             'class' => 'pdfworkspace_' . $args['action'] . 'comment' . '_editoritemid',
                                             'name' => 'input_value_editor',
                                             'value' => $data['draftItemId']]);
    $out .= html_writer::empty_tag('input', ['type' => 'hidden',
                                             'class' => 'pdfworkspace_' . $args['action'] . 'comment' . '_editorformat',
                                             'name' => 'input_value_editor',
                                             'value' => $data['editorFormat']]);
    $out .= 'displaycontent:' . $displaycontent;
    return $out;
}

/**
 * Initialize the editor for adding a comment.
 * @param type $args
 * @return string
 */
function mod_pdfworkspace_output_fragment_open_add_comment_editor($args) {
    $context = context_module::instance($args['cmid']);

    $data = pdfworkspace_data_preprocessing($context, 'id_pdfworkspace_content', 0);
    $text = file_prepare_draft_area($data['draftItemId'], $context->id, 'mod_pdfworkspace', 'post', 0, pdfworkspace_get_editor_options($context));
    $out = '';
    $out = html_writer::empty_tag('input', ['type' => 'hidden',
                                            'class' => 'pdfworkspace_' . $args['action'] . 'comment' . '_editoritemid',
                                            'name' => 'input_value_editor',
                                            'value' => $data['draftItemId']]);
    $out .= html_writer::empty_tag('input', ['type' => 'hidden',
                                             'class' => 'pdfworkspace_' . $args['action'] . 'comment' . '_editorformat',
                                             'name' => 'input_value_editor',
                                             'value' => $data['editorFormat']]);
    return $out;
}
