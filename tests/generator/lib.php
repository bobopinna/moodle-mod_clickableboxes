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

/**
 * Clickable boxes module data generator.
 */
class mod_clickableboxes_generator extends testing_module_generator {
    /**
     * Create a new instance of clickable boxes.
     *
     * @param stdClass|array $record Data for the new instance.
     * @param array $options Options for the creation.
     * @return stdClass The created instance.
     */
    public function create_instance($record = null, array $options = null) {

        // Convert the record to an array to easily merge default values.
        $record = (array)$record;

        if (empty($record['name'])) {
            $record['name'] = 'Clickable boxes ' . $this->instancecount;
        }
        if (empty($record['intro'])) {
            $record['intro'] = 'Default intro text';
        }
        if (!isset($record['introformat'])) {
            $record['introformat'] = FORMAT_HTML;
        }

        if (isset($record['box_repeats']) && $record['box_repeats'] > 0) {
            // Fill in missing array keys for the requested number of boxes.
            for ($i = 0; $i < $record['box_repeats']; $i++) {
                if (!isset($record['boxcontent'][$i])) {
                    $record['boxcontent'][$i] = [
                        'text' => 'Default box content ' . ($i + 1),
                        'format' => FORMAT_HTML,
                    ];
                }
                if (!isset($record['boxlink'][$i])) {
                    $record['boxlink'][$i] = '';
                }
                if (!isset($record['boxsortorder'][$i])) {
                    $record['boxsortorder'][$i] = $i + 1;
                }
            }
        } else {
            // If the test didn't specify any boxes, we ensure at least one default box is created.
            $record['box_repeats'] = 1;

            if (!isset($record['boxcontent'])) {
                $record['boxcontent'][0] = [
                    'text' => 'Default box content 1',
                    'format' => FORMAT_HTML,
                ];
            }
            if (!isset($record['boxlink'])) {
                $record['boxlink'][0] = '';
            }
            if (!isset($record['boxsortorder'])) {
                $record['boxsortorder'][0] = 1;
            }
        }

        return parent::create_instance((object)$record, (array)$options);
    }
}
