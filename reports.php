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
 * Reports and history page for Student-Tutor assignment plugin
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_studenttutor\history_manager;

require_login();

$context = context_system::instance();
require_capability('local/studenttutor:viewhistory', $context);

// Get filter parameters
$filter_tutor = optional_param_array('filter_tutor', array(), PARAM_INT);
$filter_student = optional_param_array('filter_student', array(), PARAM_INT);
$filter_course = optional_param('filter_course', 0, PARAM_INT);
$filter_activity_type = optional_param('filter_activity_type', '', PARAM_TEXT);
$filter_date_from = optional_param('filter_date_from', '', PARAM_TEXT);
$filter_date_to = optional_param('filter_date_to', '', PARAM_TEXT);

$PAGE->set_url(new moodle_url('/local/studenttutor/reports.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('history_title', 'local_studenttutor'));
$PAGE->set_heading(get_string('history_title', 'local_studenttutor'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('view_history', 'local_studenttutor'));


echo html_writer::div(
    $OUTPUT->single_button(
        new moodle_url('/local/studenttutor/index.php'),
        get_string('back_to_assignments', 'local_studenttutor'),
        'get',
        array('class' => 'btn btn-secondary')
    ),
    '',
    array('style' => 'margin-bottom: 20px;')
);

// Add filters form
echo html_writer::start_tag('div', array('class' => 'filters-form', 'style' => 'margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9;'));
echo html_writer::tag('h4', get_string('filters', 'local_studenttutor'), array('style' => 'margin-top: 0;'));

echo html_writer::start_tag('form', array('method' => 'get', 'action' => '', 'style' => 'display: flex; gap: 15px; align-items: end; flex-wrap: wrap;'));


// Tutor filter - Simple multiple select
$tutors = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.firstname, u.lastname
    FROM {user} u
    JOIN {local_studenttutor_history} h ON h.tutorid = u.id
    WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
    ORDER BY u.lastname, u.firstname
");

echo html_writer::start_tag('div');
echo html_writer::tag('label', get_string('tutor', 'local_studenttutor'), array('style' => 'display: block; font-weight: bold; margin-bottom: 5px;'));

// Create simple multiple select
echo '<select name="filter_tutor[]" multiple size="4" style="min-width: 250px; min-height: 80px;">';
echo '<option value="">-- ' . get_string('select_tutors', 'local_studenttutor') . ' --</option>';

foreach ($tutors as $tutor) {
    $selected = in_array($tutor->id, $filter_tutor) ? ' selected="selected"' : '';
    echo '<option value="' . $tutor->id . '"' . $selected . '>' . s(fullname($tutor)) . '</option>';
}

echo '</select>';
echo '<div style="font-size: 0.8em; color: #666; margin-top: 5px;">Segure Ctrl (ou Cmd) para selecionar múltiplos</div>';
echo html_writer::end_tag('div');

// Student filter - Simple multiple select
$students = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.firstname, u.lastname
    FROM {user} u
    JOIN {local_studenttutor_history} h ON h.studentid = u.id
    WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
    ORDER BY u.lastname, u.firstname
");

echo html_writer::start_tag('div');
echo html_writer::tag('label', get_string('student', 'local_studenttutor'), array('style' => 'display: block; font-weight: bold; margin-bottom: 5px;'));

// Create simple multiple select
echo '<select name="filter_student[]" multiple size="4" style="min-width: 250px; min-height: 80px;">';
echo '<option value="">-- ' . get_string('select_students', 'local_studenttutor') . ' --</option>';

foreach ($students as $student) {
    $selected = in_array($student->id, $filter_student) ? ' selected="selected"' : '';
    echo '<option value="' . $student->id . '"' . $selected . '>' . s(fullname($student)) . '</option>';
}

echo '</select>';
echo '<div style="font-size: 0.8em; color: #666; margin-top: 5px;">Segure Ctrl (ou Cmd) para selecionar múltiplos</div>';
echo html_writer::end_tag('div');

// Course filter
$courses = $DB->get_records_sql("
    SELECT DISTINCT c.id, c.fullname
    FROM {course} c
    JOIN {local_studenttutor_history} h ON h.courseid = c.id
    WHERE c.visible = 1
    ORDER BY c.fullname
");
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


// Activity type filter
$activity_types = array(
    '' => get_string('all_activity_types', 'local_studenttutor'),
    'meeting' => get_string('activity_meeting', 'local_studenttutor'),
    'email' => get_string('activity_email', 'local_studenttutor'),
    'feedback' => get_string('activity_feedback', 'local_studenttutor'),
    'assessment' => get_string('activity_assessment', 'local_studenttutor'),
    'other' => get_string('activity_other', 'local_studenttutor')
);

echo html_writer::start_tag('div');
echo html_writer::tag('label', get_string('activity_type', 'local_studenttutor'), array('style' => 'display: block; font-weight: bold; margin-bottom: 5px;'));
echo html_writer::select($activity_types, 'filter_activity_type', $filter_activity_type, false, array('style' => 'min-width: 120px;'));
echo html_writer::end_tag('div');

// Date filters
echo html_writer::start_tag('div');
echo html_writer::tag('label', get_string('date_from', 'local_studenttutor'), array('style' => 'display: block; font-weight: bold; margin-bottom: 5px;'));
echo html_writer::empty_tag('input', array('type' => 'date', 'name' => 'filter_date_from', 'value' => $filter_date_from, 'style' => 'min-width: 120px;'));
echo html_writer::end_tag('div');

echo html_writer::start_tag('div');
echo html_writer::tag('label', get_string('date_to', 'local_studenttutor'), array('style' => 'display: block; font-weight: bold; margin-bottom: 5px;'));
echo html_writer::empty_tag('input', array('type' => 'date', 'name' => 'filter_date_to', 'value' => $filter_date_to, 'style' => 'min-width: 120px;'));
echo html_writer::end_tag('div');

// Filter buttons
echo html_writer::start_tag('div', array('style' => 'display: flex; gap: 10px;'));
echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('filter', 'local_studenttutor'), 'class' => 'btn btn-primary'));
echo html_writer::link(new moodle_url('/local/studenttutor/reports.php'), get_string('clear', 'local_studenttutor'), array('class' => 'btn btn-secondary'));
echo html_writer::end_tag('div');

echo html_writer::end_tag('form');
echo html_writer::end_tag('div');

// Add new history entry button
// if (has_capability('local/studenttutor:managehistory', $context)) {
//     echo $OUTPUT->single_button(
//         new moodle_url('/local/studenttutor/add_history.php'),
//         get_string('add_history_entry', 'local_studenttutor'),
//         'get'
//     );
// }

// Display history entries
echo html_writer::start_tag('div', array('class' => 'history-list'));

// Build filters array for history query
$filters = array();

if (!empty($filter_tutor) && !in_array(0, $filter_tutor)) {
    $filters['tutorids'] = $filter_tutor;
}

if (!empty($filter_student) && !in_array(0, $filter_student)) {
    $filters['studentids'] = $filter_student;
}

if ($filter_course > 0) {
    $filters['courseid'] = $filter_course;
}

if (!empty($filter_activity_type)) {
    $filters['activitytype'] = $filter_activity_type;
}

if (!empty($filter_date_from)) {
    $filters['datefrom'] = strtotime($filter_date_from . ' 00:00:00');
}

if (!empty($filter_date_to)) {
    $filters['dateto'] = strtotime($filter_date_to . ' 23:59:59');
}

$history = history_manager::get_history_with_details($filters);

// Show active filters summary
$active_filters = array();
if (!empty($filter_tutor)) {
    $tutor_names = array();
    foreach ($filter_tutor as $tutorid) {
        $tutor = $DB->get_record('user', array('id' => $tutorid));
        if ($tutor) {
            $tutor_names[] = fullname($tutor);
        }
    }
    if (!empty($tutor_names)) {
        $active_filters[] = get_string('tutor', 'local_studenttutor') . ': ' . implode(', ', $tutor_names);
    }
}
if (!empty($filter_student)) {
    $student_names = array();
    foreach ($filter_student as $studentid) {
        $student = $DB->get_record('user', array('id' => $studentid));
        if ($student) {
            $student_names[] = fullname($student);
        }
    }
    if (!empty($student_names)) {
        $active_filters[] = get_string('student', 'local_studenttutor') . ': ' . implode(', ', $student_names);
    }
}
if ($filter_course > 0) {
    $course_name = $DB->get_field('course', 'fullname', array('id' => $filter_course));
    $active_filters[] = get_string('course', 'local_studenttutor') . ': ' . $course_name;
}
if (!empty($filter_activity_type)) {
    $active_filters[] = get_string('activity_type', 'local_studenttutor') . ': ' . get_string('activity_' . $filter_activity_type, 'local_studenttutor');
}
if (!empty($filter_date_from)) {
    $active_filters[] = get_string('date_from', 'local_studenttutor') . ': ' . $filter_date_from;
}
if (!empty($filter_date_to)) {
    $active_filters[] = get_string('date_to', 'local_studenttutor') . ': ' . $filter_date_to;
}

if (!empty($active_filters)) {
    echo html_writer::div(
        html_writer::tag('strong', get_string('filters_active', 'local_studenttutor') . ': ') . implode(' | ', $active_filters),
        'alert alert-info',
        array('style' => 'margin: 10px 0; font-size: 0.9em;')
    );
}

// Show results count
$total_results = count($history);
echo html_writer::div(
    html_writer::tag('strong', get_string('total_records', 'local_studenttutor') . ': ' . $total_results),
    'results-count',
    array('style' => 'margin: 10px 0; padding: 5px; background: #f0f0f0; border-left: 3px solid #007cba;')
);

if ($history) {
    $table = new html_table();
    $table->head = array(
        get_string('date', 'local_studenttutor'),
        get_string('tutor', 'local_studenttutor'),
        get_string('student', 'local_studenttutor'),
        get_string('course', 'local_studenttutor'),
        get_string('activity_type', 'local_studenttutor'),
        get_string('activity_title', 'local_studenttutor'),
        get_string('description', 'local_studenttutor')
    );
    
    // Adicionar classes CSS para melhor apresentação
    $table->attributes['class'] = 'table table-striped';
    $table->colclasses = array(
        'text-nowrap', // Data - não quebrar
        'text-nowrap', // Tutor - não quebrar
        'text-nowrap', // Estudante - não quebrar
        '', // Curso
        'text-center', // Tipo de atividade - centralizado
        '', // Título da atividade
        '' // Descrição
    );

    foreach ($history as $entry) {
        $tutor_name = fullname((object)array(
            'firstname' => $entry->tutor_firstname,
            'lastname' => $entry->tutor_lastname
        ));
        $student_name = fullname((object)array(
            'firstname' => $entry->student_firstname,
            'lastname' => $entry->student_lastname
        ));
        $course_name = $entry->course_name ? $entry->course_name : get_string('general', 'local_studenttutor');
        
        // Formatação melhorada da data com data e hora separadas
        $date_formatted = userdate($entry->timecreated, '%d/%m/%Y');
        $time_formatted = userdate($entry->timecreated, '%H:%M');
        $datetime_display = html_writer::div($date_formatted, 'font-weight-bold') . 
                           html_writer::div($time_formatted, 'text-muted small');
        
        // Fix activity type display
        $activity_type = $entry->activitytype;
        switch ($activity_type) {
            case 'meeting':
                $activity_type_display = get_string('activity_meeting', 'local_studenttutor');
                break;
            case 'email':
                $activity_type_display = get_string('activity_email', 'local_studenttutor');
                break;
            case 'feedback':
                $activity_type_display = get_string('activity_feedback', 'local_studenttutor');
                break;
            case 'assessment':
                $activity_type_display = get_string('activity_assessment', 'local_studenttutor');
                break;
            case 'other':
                $activity_type_display = get_string('activity_other', 'local_studenttutor');
                break;
            default:
                $activity_type_display = $activity_type;
        }

        $table->data[] = array(
            $datetime_display,
            $tutor_name,
            $student_name,
            $course_name,
            $activity_type_display,
            $entry->title,
            format_text($entry->description, FORMAT_MOODLE)
        );
    }

    echo html_writer::table($table);
} else {
    echo html_writer::div(get_string('no_history', 'local_studenttutor'), 'alert alert-info');
}

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
