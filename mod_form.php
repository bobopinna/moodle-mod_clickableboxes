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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Clickable boxes settings form
 *
 * @package mod_clickableboxes
 * @copyright 2026 onwards Roberto Pinna
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_clickableboxes_mod_form extends moodleform_mod {
    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        global $DB, $CFG;
        $mform = $this->_form;

        // General section.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name', 'clickableboxes'), ['size' => '64', 'maxlength' => 255]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'name', 'clickableboxes');

        // Add the standard Moodle intro editor (generates 'intro' and 'introformat' fields).
        $this->standard_intro_elements();

        // Repeatable boxes section.
        $mform->addElement('header', 'boxesheader', get_string('boxes', 'clickableboxes'));
        $mform->setExpanded('boxesheader', true);

        // Configuration of repeatable elements.
        $repeatarray = [];

        // Add a localized header and a visual separator for each box.
        $repeatarray[] = $mform->createElement('static', 'boxheading', get_string('boxnumber', 'clickableboxes', '{no}'), '<hr>');

        // Hidden field to track the drag and drop sort order.
        $repeatarray[] = $mform->createElement('hidden', 'boxsortorder', 0);
        $mform->setType('boxsortorder', PARAM_INT);

        $repeatarray[] = $mform->createElement(
            'filemanager',
            'boximage',
            get_string('boximage', 'clickableboxes'),
            null,
            ['subdirs' => 0, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => 1, 'accepted_types' => 'image']
        );

        $strnewwindow = get_string('opennewwindow', 'clickableboxes');
        $strboxlink = get_string('boxlink', 'clickableboxes');

        $linkgroup = [];
        $linkgroup[] = $mform->createElement('text', 'boxlink', '', ['size' => '50']);
        $linkgroup[] = $mform->createElement('advcheckbox', 'boxlinktarget', '', $strnewwindow, null, [0, 1]);
        $mform->disabledIf('boxlinktarget', 'boxlink', 'eq', '');

        $repeatarray[] = $mform->createElement('group', 'boxlinkgroup', $strboxlink, $linkgroup, ' &nbsp;&nbsp;&nbsp; ', false);
        $mform->setType('boxlink', PARAM_URL);

        $repeatarray[] = $mform->createElement(
            'editor',
            'boxcontent',
            get_string('boxcontent', 'clickableboxes'),
            null,
            ['maxfiles' => EDITOR_UNLIMITED_FILES]
        );

        $numrepeats = 1;
        if (!empty($this->current->id)) {
            $numrepeats = $DB->count_records('clickableboxes_items', ['clickableboxid' => $this->current->id]);
            if ($numrepeats == 0) {
                $numrepeats = 1;
            }
        }

        $repeatoptions = [];

        $this->repeat_elements(
            $repeatarray,
            $numrepeats,
            $repeatoptions,
            'box_repeats',
            'box_add_fields',
            1,
            get_string('addbox', 'clickableboxes'),
            true
        );

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Any data processing needed before the form is displayed
     * (needed to set up draft areas for editor and filemanager elements)
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        global $DB;
        if ($this->current->instance) {
            $context = context_module::instance($this->current->coursemodule);
            $boxes = $DB->get_records('clickableboxes_items', ['clickableboxid' => $this->current->instance], 'sortorder ASC');
            $i = 0;
            foreach ($boxes as $box) {
                // Preload the hidden sort order field.
                $defaultvalues['boxsortorder'][$i] = $box->sortorder;

                // Preload the text and prepare the embedded files for the editor.
                $draftitemidcontent = file_get_submitted_draft_itemid('boxcontent[' . $i . ']');
                $text = file_prepare_draft_area(
                    $draftitemidcontent,
                    $context->id,
                    'mod_clickableboxes',
                    'content',
                    $box->id,
                    ['subdirs' => true],
                    $box->content
                );

                // Preload the text.
                $defaultvalues['boxcontent'][$i] = [
                    'text' => $text,
                    'format' => $box->contentformat,
                    'itemid' => $draftitemidcontent,
                ];

                // Preload the link and target.
                $defaultvalues['boxlink'][$i] = $box->boxlink;
                $defaultvalues['boxlinktarget'][$i] = !empty($box->boxlinktarget) ? 1 : 0;

                // Preload the image in the filemanager using Moodle APIs.
                $draftitemid = file_get_submitted_draft_itemid('boximage[' . $i . ']');
                file_prepare_draft_area(
                    $draftitemid,
                    $context->id,
                    'mod_clickableboxes',
                    'boximage',
                    $box->id,
                    ['subdirs' => 0, 'maxfiles' => 1]
                );
                $defaultvalues['boximage'][$i] = $draftitemid;

                $i++;
            }
        }
    }

    /**
     * Any strings and javascript needed by form
     *
     * @return void
     */
    public function definition_after_data() {
        global $PAGE;
        parent::definition_after_data();

        // Prepare localized strings to pass to our Javascript module.
        $strings = [
            'boxnumber'   => get_string('boxnumber', 'clickableboxes', '###'),
            'clicktoedit' => get_string('clicktoedit', 'clickableboxes'),
            'backtogrid'  => get_string('backtogrid', 'clickableboxes'),
        ];

        // Call the AMD module ('pluginname/filename') and execute its 'init' function.
        $PAGE->requires->js_call_amd('mod_clickableboxes/form_grid', 'init', [$strings]);
    }
}
