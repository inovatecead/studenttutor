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
 * Main page for Student-Tutor assignment plugin
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/lib.php');

use local_studenttutor\assignment_manager;

require_login();

$context = context_system::instance();
require_capability('local/studenttutor:viewassignments', $context);

// Check user role and apply automatic filters
$user_context_filter = null;
$is_admin = has_capability('local/studenttutor:manageassignments', $context);

// If user is not admin, filter by their assignments only
if (!$is_admin) {
    // Check if user is a tutor (has configured tutor role in any course)
    $user_is_tutor = local_studenttutor_is_tutor($USER->id);
    
    if ($user_is_tutor) {
        $user_context_filter = 'tutor';
    }
}

// Get filter parameters
$filter_tutor = optional_param('filter_tutor', 0, PARAM_INT);
$filter_student = optional_param('filter_student', 0, PARAM_INT);
$filter_course = optional_param('filter_course', 0, PARAM_INT);

// Handle assignment deletion
$delete_id = optional_param('delete', 0, PARAM_INT);
if ($delete_id && confirm_sesskey()) {
    require_capability('local/studenttutor:manageassignments', $context);
    
    if (assignment_manager::delete_assignment($delete_id)) {
        redirect(new moodle_url('/local/studenttutor/index.php'), 
                get_string('assignment_deleted', 'local_studenttutor'), 
                null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(new moodle_url('/local/studenttutor/index.php'), 
                get_string('error_deleting_assignment', 'local_studenttutor'), 
                null, \core\output\notification::NOTIFY_ERROR);
    }
}

$PAGE->set_url(new moodle_url('/local/studenttutor/index.php'), 
    array('filter_tutor' => $filter_tutor, 'filter_student' => $filter_student, 'filter_course' => $filter_course));
$PAGE->set_context($context);
$PAGE->set_title(get_string('assignments_title', 'local_studenttutor'));
$PAGE->set_heading(get_string('assignments_title', 'local_studenttutor'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('manage_assignments', 'local_studenttutor'));

// Navigation buttons
$buttons = array();
$buttons[] = $OUTPUT->single_button(
    new moodle_url('/local/studenttutor/assign.php'),
    get_string('add_assignment', 'local_studenttutor'),
    'get'
);
$buttons[] = $OUTPUT->single_button(
    new moodle_url('/local/studenttutor/reports.php'),
    get_string('view_history', 'local_studenttutor'),
    'get'
);

echo html_writer::div(implode(' ', $buttons), 'buttons');

// Add filters
echo html_writer::start_tag('div', array('class' => 'filters-form', 'style' => 'margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9;'));
echo html_writer::tag('h4', get_string('filters', 'local_studenttutor'), array('style' => 'margin-top: 0;'));

echo html_writer::start_tag('form', array('method' => 'get', 'action' => '', 'style' => 'display: flex; gap: 15px; align-items: end; flex-wrap: wrap;'));

// Tutor filter
$tutor_roles = local_studenttutor_get_tutor_roles();
$role_list = "'" . implode("','", $tutor_roles) . "'";
$tutors = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.firstname, u.lastname
    FROM {user} u
    JOIN {role_assignments} ra ON ra.userid = u.id
    JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
    JOIN {role} r ON r.id = ra.roleid
    WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
    AND r.shortname IN ($role_list)
    ORDER BY u.lastname, u.firstname
");
$tutor_options = array(0 => get_string('all_tutors', 'local_studenttutor'));
foreach ($tutors as $tutor) {
    $tutor_options[$tutor->id] = fullname($tutor);
}

echo html_writer::start_tag('div');
echo html_writer::tag('label', get_string('tutor', 'local_studenttutor'), array('style' => 'display: block; font-weight: bold; margin-bottom: 5px;'));
echo html_writer::select($tutor_options, 'filter_tutor', $filter_tutor, false, array('style' => 'min-width: 150px;'));
echo html_writer::end_tag('div');

// Student filter
$students = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.firstname, u.lastname
    FROM {user} u
    JOIN {role_assignments} ra ON ra.userid = u.id
    JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
    JOIN {role} r ON r.id = ra.roleid
    WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
    AND r.shortname = 'student'
    ORDER BY u.lastname, u.firstname
");
$student_options = array(0 => get_string('all_students', 'local_studenttutor'));
foreach ($students as $student) {
    $student_options[$student->id] = fullname($student);
}

echo html_writer::start_tag('div');
echo html_writer::tag('label', get_string('student', 'local_studenttutor'), array('style' => 'display: block; font-weight: bold; margin-bottom: 5px;'));
echo html_writer::select($student_options, 'filter_student', $filter_student, false, array('style' => 'min-width: 150px;'));
echo html_writer::end_tag('div');

// Course filter
$courses = $DB->get_records('course', array('visible' => 1), 'fullname', 'id, fullname');
$course_options = array(0 => get_string('all_courses', 'local_studenttutor'));
foreach ($courses as $course) {
    if ($course->id != SITEID) {
        $course_options[$course->id] = $course->fullname;
    }
}

echo html_writer::start_tag('div');
echo html_writer::tag('label', get_string('course', 'local_studenttutor'), array('style' => 'display: block; font-weight: bold; margin-bottom: 5px;'));
echo html_writer::select($course_options, 'filter_course', $filter_course, false, array('style' => 'min-width: 200px;'));
echo html_writer::end_tag('div');

// Filter buttons
echo html_writer::start_tag('div', array('style' => 'display: flex; gap: 10px;'));
echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('filter', 'local_studenttutor'), 'class' => 'btn btn-primary'));
echo html_writer::link(new moodle_url('/local/studenttutor/index.php'), get_string('clear', 'local_studenttutor'), array('class' => 'btn btn-secondary'));
echo html_writer::end_tag('div');

echo html_writer::end_tag('form');
echo html_writer::end_tag('div');

// Display current assignments
echo html_writer::start_tag('div', array('class' => 'assignments-list'));

// Build filters for assignment query
$filters = array();
if ($filter_tutor > 0) {
    $filters['tutorid'] = $filter_tutor;
}
if ($filter_student > 0) {
    $filters['studentid'] = $filter_student;
}
if ($filter_course > 0) {
    $filters['courseid'] = $filter_course;
}

$assignments = assignment_manager::get_all_assignments_with_details($filters);

if ($assignments) {
    $table = new html_table();
    $table->head = array(
        get_string('tutor', 'local_studenttutor'),
        get_string('student', 'local_studenttutor'),
        get_string('course', 'local_studenttutor'),
        get_string('date_assigned', 'local_studenttutor'),
        get_string('status', 'local_studenttutor'),
        get_string('actions', 'local_studenttutor')
    );

    foreach ($assignments as $assignment) {
        $tutor_name = fullname((object)array(
            'firstname' => $assignment->tutor_firstname,
            'lastname' => $assignment->tutor_lastname
        ));
        $student_name = fullname((object)array(
            'firstname' => $assignment->student_firstname,
            'lastname' => $assignment->student_lastname
        ));
        $course_name = $assignment->course_name ? $assignment->course_name : get_string('all_courses', 'local_studenttutor');
        $date_assigned = userdate($assignment->timeassigned);
        $status = $assignment->status == 'active' ? get_string('status_active', 'local_studenttutor') : get_string('status_inactive', 'local_studenttutor');

        $actions = '';
        if (has_capability('local/studenttutor:manageassignments', $context)) {
            // TEMPORARIAMENTE DESABILITADO: Link de edição de atribuições
            /*
            $actions .= html_writer::link(
                new moodle_url('/local/studenttutor/assign.php', array('id' => $assignment->id)),
                get_string('edit', 'local_studenttutor'),
                array('class' => 'btn btn-sm btn-secondary', 'style' => 'margin-right: 5px;')
            );
            */
            
            // Add notice that editing is disabled
            // $actions .= html_writer::tag('span', 
            //     get_string('edit_temporarily_disabled', 'local_studenttutor'),
            //     array('class' => 'text-muted small', 'style' => 'margin-right: 10px;')
            // );
            
            // Add delete button
            $delete_url = new moodle_url('/local/studenttutor/index.php', array(
                'delete' => $assignment->id,
                'sesskey' => sesskey()
            ));
            $actions .= html_writer::link(
                $delete_url,
                get_string('delete'),
                array(
                    'class' => 'btn btn-sm btn-danger',
                    'onclick' => 'return confirm("' . get_string('confirm_delete_assignment', 'local_studenttutor') . '");'
                )
            );
        }

        $table->data[] = array(
            $tutor_name,
            $student_name,
            $course_name,
            $date_assigned,
            $status,
            $actions
        );
    }

    echo html_writer::table($table);
} else {
    echo html_writer::div(get_string('no_assignments', 'local_studenttutor'), 'alert alert-info');
}

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
