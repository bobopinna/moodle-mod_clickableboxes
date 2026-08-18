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

require_once($CFG->dirroot . '/mod/clickableboxes/backup/moodle2/backup_clickableboxes_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the Clickable Boxes instance.
 *
 * @package   mod_clickableboxes
 * @copyright 2026 onwards Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_clickableboxes_activity_task extends backup_activity_task {
    /**
     * Define the specific settings for this activity.
     */
    protected function define_my_settings() {
        // No specific settings required for this plugin.
    }

    /**
     * Define the steps needed to back up this activity.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_clickableboxes_activity_structure_step('clickableboxes_structure', 'clickableboxes.xml'));
    }

    /**
     * Code to execute to encode URLs.
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of clickableboxes.
        $search = '/(' . $base . '\/mod\/clickableboxes\/index.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@CLICKABLEBOXESINDEX*$2@$', $content);

        // Link to a specific clickablebox view.
        $search = '/(' . $base . '\/mod\/clickableboxes\/view.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@CLICKABLEBOXESVIEWBYID*$2@$', $content);

        return $content;
    }
}
