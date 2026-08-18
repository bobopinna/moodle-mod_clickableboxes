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
 * This file contains the backup activity for the clickable boxes plugin
 *
 * @package   mod_clickableboxes
 * @copyright 2026 onwards Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/clickableboxes/backup/moodle2/restore_clickableboxes_stepslib.php');

/**
 * Provides the steps to perform one complete restore of the Clickable Boxes instance.
 */
class restore_clickableboxes_activity_task extends restore_activity_task {
    /**
     * Define the specific settings for this activity.
     */
    protected function define_my_settings() {
        // No specific settings required.
    }

    /**
     * Define the steps needed to restore this activity.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_clickableboxes_activity_structure_step('clickableboxes_structure', 'clickableboxes.xml'));
    }

    /**
     * Define the decoding rules for texts mapped to specific fields.
     */
    public static function define_decode_contents() {
        $rules = [];

        // This explicitly tells Moodle to decode files/links inside the WYSIWYG editor belonging to the context mapping.
        $rules[] = new restore_decode_content('clickableboxes', ['intro'], 'clickableboxes');
        $rules[] = new restore_decode_content('clickableboxes_items', ['content'], 'clickableboxes_item');

        return $rules;
    }

    /**
     * Define the decoding rules for URLs.
     */
    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('CLICKABLEBOXESINDEX', '/mod/clickableboxes/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('CLICKABLEBOXESVIEWBYID', '/mod/clickableboxes/view.php?id=$1', 'course_module');

        return $rules;
    }

    /**
     * Define the log rules for this activity.
     */
    public static function define_restore_log_rules() {
        $rules = [];
        return $rules;
    }
}
