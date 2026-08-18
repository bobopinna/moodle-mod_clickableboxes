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
 * Define the complete clickableboxes structure for backup.
 */
class backup_clickableboxes_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the complete clickableboxes structure for backup.
     */
    protected function define_structure() {
        // Define the XML elements.
        $clickableboxes = new backup_nested_element('clickableboxes', ['id'], [
                'course', 'name', 'intro', 'introformat', 'timecreated', 'timemodified',
        ]);

        $items = new backup_nested_element('clickableboxes_items');

        $item = new backup_nested_element('clickableboxes_item', ['id'], [
                'sortorder', 'boxlink', 'boxlinktarget', 'content', 'contentformat',
        ]);

        // Build the tree hierarchy.
        $clickableboxes->add_child($items);
        $items->add_child($item);

        // Define data sources.
        $clickableboxes->set_source_table('clickableboxes', ['id' => backup::VAR_ACTIVITYID]);
        $item->set_source_table('clickableboxes_items', ['clickableboxid' => backup::VAR_PARENTID]);

        // Annotate the attached files for the introduction (standard Moodle intro).
        $clickableboxes->annotate_files('mod_clickableboxes', 'intro', null);

        // Annotate the attached images linked to the item ID.
        $item->annotate_files('mod_clickableboxes', 'boximage', 'id', $this->task->get_contextid());
        $item->annotate_files('mod_clickableboxes', 'content', 'id', $this->task->get_contextid());

        return $this->prepare_activity_structure($clickableboxes);
    }
}
