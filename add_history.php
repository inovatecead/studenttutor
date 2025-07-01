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
 * Add history entry from course context
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');

use local_studenttutor\assignment_manager;
use local_studenttutor\history_manager;

$courseid = required_param('courseid', PARAM_INT);
$studentid = optional_param('studentid', 0, PARAM_INT);

require_login();

// Get course and context
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);

// Check if user is a tutor in this course
$is_tutor = local_studenttutor_is_tutor($USER->id, $courseid);

if (!$is_tutor) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('access_denied', 'local_studenttutor'));
}

$PAGE->set_url(new moodle_url('/local/studenttutor/add_history.php'), array('courseid' => $courseid, 'studentid' => $studentid));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('add_history_entry', 'local_studenttutor'));
$PAGE->set_heading($course->fullname);

// Create form
class course_history_form extends moodleform {
    public function definition() {
        global $DB, $USER;
        
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $preselected_student = $this->_customdata['studentid'];
        
        // Get tutor's students in this course (including global assignments)
        $students = assignment_manager::get_tutor_students_including_global($USER->id, $courseid, 'active');
        
        $student_options = array();
        foreach ($students as $assignment) {
            $student_options[$assignment->studentid] = $assignment->student_firstname . ' ' . $assignment->student_lastname;
        }
        
        if (empty($student_options)) {
            $mform->addElement('static', 'nostudents', '', get_string('no_students_assigned', 'local_studenttutor'));
            return;
        }
        
        // Student selection
        $mform->addElement('select', 'studentid', get_string('student', 'local_studenttutor'), $student_options);
        $mform->setType('studentid', PARAM_INT);
        $mform->addRule('studentid', get_string('required'), 'required', null, 'client');
        
        if ($preselected_student && isset($student_options[$preselected_student])) {
            $mform->setDefault('studentid', $preselected_student);
        }
        
        // Activity type - using dynamic types from database
        $types = \local_studenttutor\activity_type_manager::get_activity_types_options(true);
        
        $mform->addElement('select', 'action_type', get_string('action_type', 'local_studenttutor'), $types);
        $mform->setType('action_type', PARAM_TEXT);
        $mform->addRule('action_type', get_string('required'), 'required', null, 'client');
        
        // Activity date (only if field exists in database)
        // Try to check if field exists by attempting a simple query
        try {
            $DB->get_record_sql("SELECT activity_date FROM {local_studenttutor_history} WHERE 1=0");
            $activity_date_exists = true;
        } catch (dml_exception $e) {
            $activity_date_exists = false;
        }
        
        if ($activity_date_exists) {
            $mform->addElement('date_selector', 'activity_date', get_string('activity_date', 'local_studenttutor'));
            $mform->setDefault('activity_date', time()); // Default to today
            $mform->addHelpButton('activity_date', 'activity_date', 'local_studenttutor');
        }
        
        // Description
        $mform->addElement('textarea', 'description', get_string('description', 'local_studenttutor'), 
            array('rows' => 6, 'cols' => 60));
        $mform->setType('description', PARAM_TEXT);
        $mform->addRule('description', get_string('required'), 'required', null, 'client');
        
        // Hidden fields
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        
        $this->add_action_buttons(true, get_string('add_history_entry', 'local_studenttutor'));
    }
}

$form = new course_history_form(null, array('courseid' => $courseid, 'studentid' => $studentid));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/studenttutor/course_view.php', array('courseid' => $courseid)));
} else if ($data = $form->get_data()) {
    try {
        // Debug: verificar dados recebidos
        debugging('Data received: ' . print_r($data, true), DEBUG_DEVELOPER);
        
        // Check if activity_date field exists by trying a test query
        try {
            $DB->get_record_sql("SELECT activity_date FROM {local_studenttutor_history} WHERE 1=0");
            $activity_date_exists = true;
        } catch (dml_exception $e) {
            $activity_date_exists = false;
        }
        
        if ($activity_date_exists && isset($data->activity_date)) {
            $entryid = history_manager::add_history_entry(
                $data->studentid,
                $USER->id,
                $data->action_type,
                $data->description,
                $courseid,
                $USER->id,
                $data->activity_date
            );
        } else {
            $entryid = history_manager::add_history_entry(
                $data->studentid,
                $USER->id,
                $data->action_type,
                $data->description,
                $courseid,
                $USER->id
            );
        }
        
        if ($entryid) {
            \core\notification::success(get_string('history_added_success', 'local_studenttutor'));
            redirect(new moodle_url('/local/studenttutor/course_view.php', array('courseid' => $courseid)));
        } else {
            \core\notification::error(get_string('history_add_error', 'local_studenttutor'));
        }
    } catch (Exception $e) {
        debugging('Exception in add_history: ' . $e->getMessage(), DEBUG_DEVELOPER);
        \core\notification::error(get_string('history_add_error', 'local_studenttutor') . ': ' . $e->getMessage());
    }
}

echo $OUTPUT->header();

// Breadcrumb
$PAGE->navbar->add(get_string('my_students', 'local_studenttutor'), new moodle_url('/local/studenttutor/course_view.php', ['courseid' => $courseid]));
$PAGE->navbar->add(get_string('add_history_entry', 'local_studenttutor'));

echo $OUTPUT->heading(get_string('add_history_entry', 'local_studenttutor'));

if ($studentid > 0) {
    $student = $DB->get_record('user', ['id' => $studentid]);
    if ($student) {
        echo $OUTPUT->notification(get_string('adding_history_for', 'local_studenttutor', fullname($student)), 'info');
    }
}

$form->display();

echo $OUTPUT->footer();
