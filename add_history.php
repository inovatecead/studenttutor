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
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');

use local_studenttutor\form\course_history_form;
use local_studenttutor\history_manager;

$courseid = required_param('courseid', PARAM_INT);
$studentid = optional_param('studentid', 0, PARAM_INT);

require_login();

// Get course and context
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);

// Check if user is a tutor in this course
$is_tutor = local_studenttutor_is_tutor($USER->id, $courseid);

if (!$is_tutor) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('access_denied', 'local_studenttutor'));
}

$PAGE->set_url(new moodle_url('/local/studenttutor/add_history.php'), ['courseid' => $courseid, 'studentid' => $studentid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('add_history_entry', 'local_studenttutor'));
$PAGE->set_heading($course->fullname);

// The form lives in classes/form/course_history_form.php and is autoloaded.
$form = new course_history_form(null, ['courseid' => $courseid, 'studentid' => $studentid]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/studenttutor/course_view.php', ['courseid' => $courseid]));
} else if ($data = $form->get_data()) {
    try {
        $activity_date = isset($data->activity_date) ? $data->activity_date : time();

        $entryid = history_manager::add_history_entry(
            $data->studentid,
            $USER->id,
            $data->action_type,
            $data->description,
            $courseid,
            $USER->id,
            $activity_date
        );

        if ($entryid) {
            \core\notification::success(get_string('history_added_success', 'local_studenttutor'));
            redirect(new moodle_url('/local/studenttutor/course_view.php', ['courseid' => $courseid]));
        } else {
            \core\notification::error(get_string('history_add_error', 'local_studenttutor'));
        }
    } catch (Exception $e) {
        debugging('Exception in add_history: ' . $e->getMessage(), DEBUG_DEVELOPER);
        \core\notification::error(get_string('history_add_error', 'local_studenttutor'));
    }
}

echo $OUTPUT->header();

// Breadcrumb
$PAGE->navbar->add(
    get_string('my_students', 'local_studenttutor'),
    new moodle_url('/local/studenttutor/course_view.php', ['courseid' => $courseid])
);
$PAGE->navbar->add(get_string('add_history_entry', 'local_studenttutor'));

echo $OUTPUT->heading(get_string('add_history_entry', 'local_studenttutor'));

echo $OUTPUT->notification(get_string('history_privacy_notice', 'local_studenttutor'), 'info');

if ($studentid > 0) {
    $student = $DB->get_record('user', ['id' => $studentid]);
    if ($student) {
        echo $OUTPUT->notification(get_string('adding_history_for', 'local_studenttutor', fullname($student)), 'info');
    }
}

$form->display();

echo $OUTPUT->footer();
