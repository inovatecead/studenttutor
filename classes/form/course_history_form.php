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
 * Form for adding a tutoring history entry.
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
use local_studenttutor\assignment_manager;

/**
 * Form for adding a tutoring history entry from the course view.
 */
class course_history_form extends \moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        global $USER;

        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $preselected_student = $this->_customdata['studentid'];

        // Get tutor's students in this course (including global assignments).
        $students = assignment_manager::get_tutor_students_including_global($USER->id, $courseid, 'active');

        $student_options = [];
        foreach ($students as $assignment) {
            $student_options[$assignment->studentid] = fullname(
                username_load_fields_from_object((object)[], $assignment, 'student_')
            );
        }

        if (empty($student_options)) {
            $mform->addElement('static', 'nostudents', '', get_string('no_students_assigned', 'local_studenttutor'));
            return;
        }

        // Student selection.
        $mform->addElement('select', 'studentid', get_string('student', 'local_studenttutor'), $student_options);
        $mform->setType('studentid', PARAM_INT);
        $mform->addRule('studentid', get_string('required'), 'required', null, 'client');

        if ($preselected_student && isset($student_options[$preselected_student])) {
            $mform->setDefault('studentid', $preselected_student);
        }

        // Activity type, from the types configured for the plugin.
        $types = activity_type_manager::get_activity_types_options(true);

        $mform->addElement('select', 'action_type', get_string('action_type', 'local_studenttutor'), $types);
        $mform->setType('action_type', PARAM_TEXT);
        $mform->addRule('action_type', get_string('required'), 'required', null, 'client');

        // Activity date.
        $mform->addElement('date_selector', 'activity_date', get_string('activity_date', 'local_studenttutor'));
        $mform->setDefault('activity_date', time());
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

        // Hidden fields.
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, get_string('add_history_entry', 'local_studenttutor'));
    }
}
