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

/**
 * Structure step to restore one clickableboxes activity.
 */
class restore_clickableboxes_activity_structure_step extends restore_activity_structure_step {
    /**
     * Structure step to restore one clickableboxes activity.
     */
    protected function define_structure() {
        $paths = [];

        $paths[] = new restore_path_element('clickableboxes', '/activity/clickableboxes');
        $paths[] = new restore_path_element(
            'clickableboxes_item',
            '/activity/clickableboxes/clickableboxes_items/clickableboxes_item'
        );

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the main clickableboxes record.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_clickableboxes($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();
        // Timestamps may be updated if needed, but usually kept from backup.

        // Insert the record.
        $newitemid = $DB->insert_record('clickableboxes', $data);

        // Map the old ID to the new ID for the main table.
        $this->apply_activity_instance($newitemid);

        $this->set_mapping('clickableboxes', $oldid, $newitemid);
    }

    /**
     * Process the individual boxes (items).
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_clickableboxes_item($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Get the new parent ID.
        $data->clickableboxid = $this->get_new_parentid('clickableboxes');

        // Insert the item record.
        $newitemid = $DB->insert_record('clickableboxes_items', $data);

        // Map the old item ID to the new item ID to correctly restore files.
        $this->set_mapping('clickableboxes_item', $oldid, $newitemid, true);
    }

    /**
     * Once the structure is completely restored, restore the attached files.
     */
    protected function after_execute() {
        // Restore intro files.
        $this->add_related_files('mod_clickableboxes', 'intro', null);

        // Add related files for the 'boximage' and the content area.
        $this->add_related_files('mod_clickableboxes', 'boximage', 'clickableboxes_item');
        $this->add_related_files('mod_clickableboxes', 'content', 'clickableboxes_item');
    }
}
