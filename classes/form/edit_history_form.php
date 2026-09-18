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
 * Form for editing a tutoring history entry.
 *
 * Page scripts must not declare classes: they cannot be autoloaded and cannot be
 * reused, so the form lives in its own class file.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studenttutor\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_studenttutor\activity_type_manager;

/**
 * Form for editing a tutoring history entry.
 */
class edit_history_form extends \moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $history_entry = $this->_customdata['history_entry'];

        // Activity type, from the types configured for the plugin.
        $types = activity_type_manager::get_activity_types_options(true);

        $mform->addElement('select', 'activitytype', get_string('action_type', 'local_studenttutor'), $types);
        $mform->setType('activitytype', PARAM_TEXT);
        $mform->addRule('activitytype', get_string('required'), 'required', null, 'client');
        $mform->setDefault('activitytype', $history_entry->activitytype);

        // Activity date.
        $mform->addElement('date_selector', 'activity_date', get_string('activity_date', 'local_studenttutor'));
        $mform->setDefault('activity_date', $history_entry->activity_date ?: time());
        $mform->addHelpButton('activity_date', 'activity_date', 'local_studenttutor');

        // Description.
        $mform->addElement(
            'textarea',
            'description',
            get_string('description', 'local_studenttutor'),
            ['rows' => 6, 'cols' => 60]
        );
        $mform->setType('description', PARAM_TEXT);
        $mform->addRule('description', get_string('required'), 'required', null, 'client');
        $mform->setDefault('description', $history_entry->description);

        // Hidden fields.
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'studentid');
        $mform->setType('studentid', PARAM_INT);

        $mform->addElement('hidden', 'historyid');
        $mform->setType('historyid', PARAM_INT);

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
