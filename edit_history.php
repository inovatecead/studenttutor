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
 * Edit history entry
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');

use local_studenttutor\form\edit_history_form;
use local_studenttutor\history_manager;

$courseid = required_param('courseid', PARAM_INT);
$studentid = required_param('studentid', PARAM_INT);
$historyid = required_param('historyid', PARAM_INT);

require_login();

// Get course, student and history entry
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$student = $DB->get_record('user', ['id' => $studentid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);

// Check if user is a tutor in this course
$is_tutor = local_studenttutor_is_tutor($USER->id, $courseid);

if (!$is_tutor) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('access_denied', 'local_studenttutor'));
}

// Check permissions
require_capability('local/studenttutor:managehistory', $context);

// Verify the history entry belongs to this tutor and student
$history_entry = $DB->get_record('local_studenttutor_history', [
    'id' => $historyid,
    'tutorid' => $USER->id,
    'studentid' => $studentid,
    'courseid' => $courseid,
]);

if (!$history_entry) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('history_not_found', 'local_studenttutor'));
}

$PAGE->set_url(
    new moodle_url('/local/studenttutor/edit_history.php'),
    ['courseid' => $courseid, 'studentid' => $studentid, 'historyid' => $historyid]
);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('edit_history', 'local_studenttutor'));
$PAGE->set_heading($course->fullname);

// The form lives in classes/form/edit_history_form.php and is autoloaded.
$form = new edit_history_form(null, ['history_entry' => $history_entry]);

// Set default values
$form->set_data([
    'courseid' => $courseid,
    'studentid' => $studentid,
    'historyid' => $historyid,
]);

$return_url = new moodle_url('/local/studenttutor/student_history.php', [
    'courseid' => $courseid,
    'studentid' => $studentid,
]);

if ($form->is_cancelled()) {
    redirect($return_url);
} else if ($data = $form->get_data()) {
    try {
        // Update the history entry using the manager
        $update_data = [
            'activitytype' => $data->activitytype,
            'description' => $data->description,
        ];

        if (isset($data->activity_date)) {
            $update_data['activity_date'] = $data->activity_date;
        }

        if (history_manager::update_history_entry($historyid, $update_data)) {
            \core\notification::success(get_string('history_updated_success', 'local_studenttutor'));
        } else {
            \core\notification::error(get_string('history_update_error', 'local_studenttutor'));
        }

        redirect($return_url);
    } catch (Exception $e) {
        debugging('Exception in edit_history: ' . $e->getMessage(), DEBUG_DEVELOPER);
        \core\notification::error(get_string('history_update_error', 'local_studenttutor'));
    }
}

echo $OUTPUT->header();

// Breadcrumbs
$PAGE->navbar->add(
    get_string('my_students', 'local_studenttutor'),
    new moodle_url('/local/studenttutor/course_view.php', ['courseid' => $courseid])
);
$PAGE->navbar->add(get_string('history_for_student', 'local_studenttutor', fullname($student)), $return_url);
$PAGE->navbar->add(get_string('edit_history', 'local_studenttutor'));

echo $OUTPUT->heading(get_string('edit_history', 'local_studenttutor'));

echo $OUTPUT->notification(get_string('editing_history_for', 'local_studenttutor', fullname($student)), 'info');

$form->display();

echo $OUTPUT->footer();
