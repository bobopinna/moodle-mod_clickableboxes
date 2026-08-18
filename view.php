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
 * Clickable Boxes module
 *
 * @package    mod_clickableboxes
 * @copyright  2026 onwards Roberto Pinna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Course Module ID.
$id = optional_param('id', 0, PARAM_INT);

// Clickable Boxes ID.
$c = optional_param('c', 0, PARAM_INT);

if ($id) {
    $PAGE->set_url('/mod/clickableboxes/view.php', ['id' => $id]);
    if (! $cm = get_coursemodule_from_id('clickableboxes', $id, 0, true)) {
        throw new \moodle_exception('invalidcoursemodule');
    }

    if (! $course = $DB->get_record('course', ['id' => $cm->course])) {
        throw new \moodle_exception('coursemisconf');
    }

    if (! $clickableboxes = $DB->get_record('clickableboxes', ['id' => $cm->instance])) {
        throw new \moodle_exception('invalidcoursemodule');
    }
} else {
    $PAGE->set_url('/mod/clickableboxes/view.php', ['c' => $c]);
    if (! $clickableboxes = $DB->get_record('clickableboxes', ['id' => $c])) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    if (! $course = $DB->get_record('course', ['id' => $clickableboxes->course])) {
        throw new \moodle_exception('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance('clickableboxes', $clickableboxes->id, $course->id, true)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
}

require_login($course, true, $cm);

$url = course_get_url($course, $cm->sectionnum, []);
$url->set_anchor('module-' . $id);
redirect($url);
