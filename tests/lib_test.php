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

namespace mod_clickableboxes;

use advanced_testcase;
use context_module;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/clickableboxes/lib.php');

/**
 * PHPUnit tests for the clickable boxes module.
 */
/**
 * Unit tests for mod_clickableboxes lib.
 *
 * @package    mod_clickableboxes
 * @category   test
 * @copyright  2026 onwards Roberto Pinna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends advanced_testcase {
    /**
     * Test set up.
     */
    public function setUp(): void {
        // Reset database and state after each test.
        $this->resetAfterTest();
        $this->setAdminUser();
        parent::setUp();
    }

    /**
     * Tests add_instance() with valid data.
     *
     * @covers ::mod_clickableboxes_add_instance
     * [CoversFunction(::mod_clickableboxes_add_instance)]
     */
    public function test_clickableboxes_add_instance(): void {
        global $DB;

        // Generate a course.
        $course = $this->getDataGenerator()->create_course();

        // Prepare data for the module.
        $record = new stdClass();
        $record->course = $course->id;
        $record->name = 'Test Clickable Boxes';
        $record->intro = 'Intro text';
        $record->introformat = FORMAT_HTML;
        $record->box_repeats = 2;

        $record->boxcontent = [
            0 => ['text' => 'First box content', 'format' => FORMAT_HTML],
            1 => ['text' => 'Second box content', 'format' => FORMAT_HTML],
        ];
        $record->boxlink = [
            0 => 'https://moodle.org',
            1 => 'https://example.com',
        ];

        // Create the module instance using the data generator.
        $module = $this->getDataGenerator()->create_module('clickableboxes', (array)$record);

        // Assert the main record was created.
        $this->assertNotEmpty($module->id);
        $this->assertEquals('Test Clickable Boxes', $module->name);

        // Assert the boxes were created.
        $boxes = $DB->get_records('clickableboxes_items', ['clickableboxid' => $module->id], 'sortorder ASC');
        $this->assertCount(2, $boxes);

        // Check the first box data.
        $firstbox = reset($boxes);
        $this->assertEquals('First box content', $firstbox->content);
        $this->assertEquals('https://moodle.org', $firstbox->boxlink);
    }

    /**
     * Tests delete_instance().
     *
     * @covers ::mod_clickableboxes_delete_instance
     * [CoversFunction(::mod_clickableboxes_delete_instance)]
     */
    public function test_clickableboxes_delete_instance(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('clickableboxes', [
            'course' => $course->id,
            'box_repeats' => 1,
        ]);

        // Ensure records exist.
        $this->assertTrue($DB->record_exists('clickableboxes', ['id' => $module->id]));
        $this->assertTrue($DB->record_exists('clickableboxes_items', ['clickableboxid' => $module->id]));

        // Delete the instance using our lib.php function.
        $result = clickableboxes_delete_instance($module->id);

        // Assert deletion was successful and records are removed.
        $this->assertTrue($result);
        $this->assertFalse($DB->record_exists('clickableboxes', ['id' => $module->id]));
        $this->assertFalse($DB->record_exists('clickableboxes_items', ['clickableboxid' => $module->id]));
    }
}
