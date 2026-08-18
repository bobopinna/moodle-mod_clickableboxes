<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Definition of lib functions
 *
 * @package    mod_clickableboxes
 * @copyright  2026 Roberto Pinna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Supported features
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function clickableboxes_supports($feature) {
    switch ($feature) {
        case FEATURE_IDNUMBER:
            return true;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_NO_VIEW_LINK:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Adds clickable boxes instance.
 *
 * @param stdClass $data The data taken in settings
 * @return int The instance id of the new instance
 */
function clickableboxes_add_instance($data) {
    global $DB;

    // Set a default name if the field was left empty by the teacher.
    if (!isset($data->name) || trim($data->name) === '') {
        $data->name = get_string('pluginname', 'clickableboxes');
    }

    $record = new stdClass();
    $record->course = $data->course;
    $record->name = $data->name;
    $record->timecreated = time();
    $record->timemodified = time();
    $id = $DB->insert_record('clickableboxes', $record);

    $context = context_module::instance($data->coursemodule);
    clickableboxes_save_boxes($id, $data, $context);

    return $id;
}

/**
 * Update clickable boxes instance.
 *
 * @param stdClass $data The data taken in settings
 * @return boolean False if update fails
 */
function clickableboxes_update_instance($data) {
    global $DB;

    // Set a default name if the field was left empty by the teacher.
    if (!isset($data->name) || trim($data->name) === '') {
        $data->name = get_string('pluginname', 'clickableboxes');
    }

    $record = new stdClass();
    $record->id = $data->instance;
    $record->name = $data->name;
    $record->timemodified = time();
    if ($DB->update_record('clickableboxes', $record)) {
        $context = context_module::instance($data->coursemodule);
        clickableboxes_save_boxes($record->id, $data, $context);
        return true;
    } else {
        return false;
    }
}

/**
 * Delete instance by activity id
 *
 * @param int $id
 * @return bool success
 */
function clickableboxes_delete_instance($id) {
    global $DB;
    if (!$DB->record_exists('clickableboxes', ['id' => $id])) {
        return false;
    }
    // Retrieve the module to also delete attached files.
    $cm = get_coursemodule_from_instance('clickableboxes', $id);
    if ($cm) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_clickableboxes', 'boximage');
    }

    $DB->delete_records('clickableboxes_items', ['clickableboxid' => $id]);
    $DB->delete_records('clickableboxes', ['id' => $id]);
    return true;
}

/**
 * Store boxes data
 *
 * @param int $instanceid Resource instance id
 * @param stdClass $data Resource settings data
 * @param stdClass $context Resource context data
 * @return bool success
 */
function clickableboxes_save_boxes($instanceid, $data, $context) {
    global $DB;

    // Retrieve existing boxes to update them (keeps IDs intact for attached files).
    $existingboxes = $DB->get_records('clickableboxes_items', ['clickableboxid' => $instanceid], 'id ASC');
    $existingids = array_keys($existingboxes);

    // Collect all submitted boxes into an array so we can sort them.
    $boxestosave = [];
    for ($i = 0; $i < $data->box_repeats; $i++) {
        $hascontent = !empty($data->boxcontent[$i]['text']) || !empty($data->boxlink[$i]) || isset($data->boximage[$i]);
        if ($hascontent) {
            $boxdata = new stdClass();
            // Keep track of the original form field index.
            $boxdata->original_index = $i;
            // Grab the dragged sort order from the hidden field, fallback to physical index.
            $boxdata->sortorder = isset($data->boxsortorder[$i]) ? (int)$data->boxsortorder[$i] : ($i + 1);
            $boxestosave[] = $boxdata;
        }
    }

    // Sort the array based on the visual order chosen by the user.
    usort($boxestosave, function ($a, $b) {
        return $a->sortorder - $b->sortorder;
    });

    // Save to the database sequentially.
    $sortcounter = 1;
    foreach ($boxestosave as $btosave) {
        $i = $btosave->original_index;

        $box = new stdClass();
        $box->clickableboxid = $instanceid;
        $box->sortorder = $sortcounter++;
        $box->content = $data->boxcontent[$i]['text'];
        $box->contentformat = $data->boxcontent[$i]['format'];
        $box->boxlink = $data->boxlink[$i] ?? '';
        $box->boxlinktarget = $data->boxlinktarget[$i] ?? 1;

        // If it already exists, update it, otherwise create it.
        if (!empty($existingids)) {
            $box->id = array_shift($existingids);
            $DB->update_record('clickableboxes_items', $box);
        } else {
            $box->id = $DB->insert_record('clickableboxes_items', $box);
        }

        // Save the image in Moodle's persistent file area.
        if (isset($data->boximage[$i])) {
            file_save_draft_area_files(
                $data->boximage[$i],
                $context->id,
                'mod_clickableboxes',
                'boximage',
                $box->id,
                ['subdirs' => 0, 'maxfiles' => 1]
            );
        }

        if (isset($data->boxcontent[$i]['itemid'])) {
            $box->content = file_save_draft_area_files(
                $data->boxcontent[$i]['itemid'],
                $context->id,
                'mod_clickableboxes',
                'content',
                $box->id,
                ['subdirs' => true],
                $box->content
            );
            // We must update the record again because file_save_draft_area_files rewrites the image URLs in the text.
            $DB->update_record('clickableboxes_items', $box);
        }
    }

    // Remove any boxes deleted by the teacher and their corresponding images.
    $fs = get_file_storage();
    foreach ($existingids as $oldid) {
        $DB->delete_records('clickableboxes_items', ['id' => $oldid]);
        $fs->delete_area_files($context->id, 'mod_clickableboxes', 'boximage', $oldid);
    }
}

/**
 * Generate the inline HTML for the course page.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function clickableboxes_get_coursemodule_info($coursemodule) {
    global $DB;

    $instance = $DB->get_record('clickableboxes', ['id' => $coursemodule->instance], '*', MUST_EXIST);
    $boxes = $DB->get_records('clickableboxes_items', ['clickableboxid' => $instance->id], 'sortorder ASC');

    $html = '';

    // Render the introductory text if it exists.
    if (!empty($instance->intro)) {
        // Format_module_intro automatically handles filters and internal images.
        $introtext = format_text($instance->intro, $instance->introformat, ['filter' => false]);
        $html .= '<div class="mod_clickableboxes_intro" style="margin-bottom: 24px;">' . $introtext . '</div>';
    }

    if (empty($boxes)) {
        if (!empty($html)) {
            $info = new cached_cm_info();
            $info->name = $instance->name;
            $info->content = $html;
            return $info;
        }
        return null;
    }

    $context = context_module::instance($coursemodule->id);
    $fs = get_file_storage();

    // Render the boxes grid.
    $html .= '<div class="mod_clickableboxes_grid">';
    foreach ($boxes as $box) {
        $rewrittentext = file_rewrite_pluginfile_urls(
            $box->content,
            'pluginfile.php',
            $context->id,
            'mod_clickableboxes',
            'content',
            $box->id
        );

        $content = format_text($rewrittentext, $box->contentformat, ['filter' => false]);

        $link = !empty($box->boxlink) ? $box->boxlink : '';

        $files = $fs->get_area_files($context->id, 'mod_clickableboxes', 'boximage', $box->id, 'sortorder', false);
        $imagehtml = '';
        if (!empty($files)) {
            $file = reset($files);
            $imageurl = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );
            $imagehtml = '<div class="clickablebox-img-wrapper"><img src="' . $imageurl . '" alt="" /></div>';
        }
        $html .= '<div class="clickablebox-item">';
        if (!empty($link)) {
            $target = (!isset($box->boxlinktarget) || $box->boxlinktarget == 1) ? '_blank' : '_self';
            $html .= '<a href="' . $link . '" class="clickablebox-item-link" target="' . $target . '" rel="noopener noreferrer">';
        }
        if (!empty($imagehtml)) {
            $html .= $imagehtml;
        }
        if (!empty($content)) {
            $html .= '<div class="clickablebox-content">' . $content . '</div>';
        }
        if (!empty($link)) {
            $html .= '</a>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';

    $info = new cached_cm_info();
    $info->name = $instance->name;
    $info->content = $html;

    return $info;
}

/**
 * Serves the files attached to the module (the box images).
 * Called automatically by the Moodle core (root/pluginfile.php).
 *
 * @param stdClass $course Course object.
 * @param stdClass $cm Course-module object.
 * @param context $context Moodle context.
 * @param string $filearea Name of the requested file area.
 * @param array $args Extra arguments (itemid, path, filename).
 * @param bool $forcedownload Whether to force download or not.
 * @param array $options Extra options.
 * @return bool Returns false if the file is not found or access is denied.
 */
function clickableboxes_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if (($filearea !== 'boximage') && ($filearea !== 'content')) {
        return false;
    }

    $itemid = (int)array_shift($args);

    if (!$box = $DB->get_record('clickableboxes_items', ['id' => $itemid])) {
        return false;
    }

    $filename = array_pop($args);

    $filepath = (empty($args)) ? '/' : '/' . implode('/', $args) . '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_clickableboxes', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
