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
 * Course-specific view for tutors to manage their students
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/lib.php');

use local_studenttutor\assignment_manager;
use local_studenttutor\history_manager;

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
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

$PAGE->set_url(new moodle_url('/local/studenttutor/course_view.php'), ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('my_students_in_course', 'local_studenttutor', $course->fullname));
$PAGE->set_heading($course->fullname);

// Add breadcrumb
$PAGE->navbar->add(get_string('my_students', 'local_studenttutor'));

echo $OUTPUT->header();

// Add some custom CSS for profile links
echo '<style>
.student-profile-link {
    font-weight: bold;
    text-decoration: none;
}
.student-profile-link:hover {
    text-decoration: underline;
}
</style>';

echo $OUTPUT->heading(get_string('my_students_in_course', 'local_studenttutor', $course->fullname));

// Get tutor's students in this course (including global assignments)
$assignments = assignment_manager::get_tutor_students_including_global($USER->id, $courseid, 'active');

if (empty($assignments)) {
    echo '<div class="alert alert-info">';
    echo '<h4>' . get_string('no_students_assigned', 'local_studenttutor') . '</h4>';
    echo '<p>Não há estudantes atribuídos a você neste curso.</p>';
    echo '<p>Para atribuir estudantes, acesse a página de atribuições do Nexo Tutoria Acadêmica.</p>';
    echo '</div>';
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', ['id' => $courseid]));
    echo $OUTPUT->footer();
    exit;
}

// Quick actions toolbar
echo html_writer::start_tag('div', ['class' => 'mb-3']);
echo html_writer::start_tag('div', ['class' => 'btn-toolbar', 'role' => 'toolbar']);

echo html_writer::link(
    new moodle_url('/local/studenttutor/add_history.php', ['courseid' => $courseid]),
    get_string('add_history_entry', 'local_studenttutor'),
    ['class' => 'btn btn-primary mr-2']
);

echo html_writer::link(
    new moodle_url('/course/view.php', ['id' => $courseid]),
    get_string('back_to_course', 'local_studenttutor'),
    ['class' => 'btn btn-secondary']
);

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Display students table
$table = new html_table();
$table->head = [
    get_string('student', 'local_studenttutor'),
    get_string('email'),
    get_string('date_assigned', 'local_studenttutor'),
    get_string('last_contact', 'local_studenttutor'),
    get_string('total_interactions', 'local_studenttutor'),
    get_string('actions', 'local_studenttutor'),
];

$table->attributes['class'] = 'table table-striped generaltable';

foreach ($assignments as $assignment) {
    $student = $DB->get_record('user', ['id' => $assignment->studentid]);

    // Get last history entry for this student
    $last_history = $DB->get_record_sql("
        SELECT h.*, h.timecreated
        FROM {local_studenttutor_history} h
        WHERE h.studentid = :studentid AND h.tutorid = :tutorid AND h.courseid = :courseid
        ORDER BY h.timecreated DESC
        LIMIT 1
    ", [
        'studentid' => $assignment->studentid,
        'tutorid' => $USER->id,
        'courseid' => $courseid,
    ]);

    // Count total interactions
    $total_interactions = $DB->count_records('local_studenttutor_history', [
        'studentid' => $assignment->studentid,
        'tutorid' => $USER->id,
        'courseid' => $courseid,
    ]);

    $student_name = fullname($student);

    // Create profile link for student name
    $profile_url = new moodle_url('/user/profile.php', ['id' => $assignment->studentid]);
    $student_name_with_link = html_writer::link($profile_url, $student_name, [
        'title' => get_string('viewprofile', 'core'),
        'class' => 'student-profile-link',
    ]);

    $date_assigned = userdate($assignment->timecreated, get_string('strftimedate'));
    $last_contact = $last_history ? userdate($last_history->timecreated, get_string('strftimedate')) : get_string('never', 'local_studenttutor');

    // Actions
    $actions = [];

    $actions[] = html_writer::link(
        new moodle_url('/user/profile.php', ['id' => $assignment->studentid]),
        get_string('viewprofile', 'core'),
        ['class' => 'btn btn-sm btn-outline-info', 'title' => get_string('viewprofile', 'core')]
    );

    $actions[] = html_writer::link(
        new moodle_url('/local/studenttutor/student_history.php', [
            'courseid' => $courseid,
            'studentid' => $assignment->studentid,
        ]),
        get_string('view_history', 'local_studenttutor'),
        ['class' => 'btn btn-sm btn-outline-primary']
    );

    $actions[] = html_writer::link(
        new moodle_url('/local/studenttutor/add_history.php', [
            'courseid' => $courseid,
            'studentid' => $assignment->studentid,
        ]),
        get_string('add_interaction', 'local_studenttutor'),
        ['class' => 'btn btn-sm btn-success']
    );

    $actions[] = html_writer::link(
        new moodle_url('/message/index.php', [
            'id' => $assignment->studentid,
        ]),
        get_string('send_message', 'local_studenttutor'),
        ['class' => 'btn btn-sm btn-outline-secondary']
    );

    $table->data[] = [
        $student_name_with_link,
        $student->email,
        $date_assigned,
        $last_contact,
        $total_interactions,
        implode(' ', $actions),
    ];
}

echo html_writer::table($table);

// Quick stats
if (count($assignments) > 0) {
    echo html_writer::start_tag('div', ['class' => 'mt-4 p-3 bg-light rounded']);
    echo html_writer::tag('h5', get_string('quick_stats', 'local_studenttutor'));

    $total_students = count($assignments);
    $total_all_interactions = $DB->count_records_sql("
        SELECT COUNT(*) FROM {local_studenttutor_history} h
        WHERE h.tutorid = :tutorid AND h.courseid = :courseid
    ", ['tutorid' => $USER->id, 'courseid' => $courseid]);

    $students_with_recent_contact = $DB->count_records_sql("
        SELECT COUNT(DISTINCT h.studentid)
        FROM {local_studenttutor_history} h
        WHERE h.tutorid = :tutorid AND h.courseid = :courseid
        AND h.timecreated > :since
    ", [
        'tutorid' => $USER->id,
        'courseid' => $courseid,
        'since' => time() - (7 * 24 * 60 * 60), // Last 7 days
    ]);

    echo html_writer::tag('p', get_string('stats_total_students', 'local_studenttutor', $total_students));
    echo html_writer::tag('p', get_string('stats_total_interactions', 'local_studenttutor', $total_all_interactions));
    echo html_writer::tag('p', get_string('stats_recent_contact', 'local_studenttutor', $students_with_recent_contact));

    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();
