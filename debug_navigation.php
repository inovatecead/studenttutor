<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('courseid', PARAM_INT);

require_login();

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

echo html_writer::start_tag('div', array('style' => 'padding: 20px; font-family: monospace;'));

echo html_writer::tag('h2', 'Debug Navigation - Course ID: ' . $courseid);

// Check user info
echo html_writer::tag('h3', 'User Information');
echo "User ID: {$USER->id}<br>";
echo "Username: {$USER->username}<br>";
echo "Full Name: " . fullname($USER) . "<br><br>";

// Check configured tutor roles
echo html_writer::tag('h3', 'Configured Tutor Roles');
$tutor_roles = local_studenttutor_get_tutor_roles();
echo "Tutor roles: " . implode(', ', $tutor_roles) . "<br><br>";

// Check user roles in this course
echo html_writer::tag('h3', 'User Roles in Course');
$user_roles = get_user_roles($context, $USER->id);
if ($user_roles) {
    foreach ($user_roles as $role) {
        echo "Role: {$role->shortname} ({$role->name})<br>";
    }
} else {
    echo "No roles found in this course<br>";
}
echo "<br>";

// Check if user is tutor
echo html_writer::tag('h3', 'Tutor Check');
$is_tutor_course = local_studenttutor_is_tutor($USER->id, $courseid);
$is_tutor_any = local_studenttutor_is_tutor($USER->id, 0);
echo "Is tutor in this course: " . ($is_tutor_course ? 'YES' : 'NO') . "<br>";
echo "Is tutor in any course: " . ($is_tutor_any ? 'YES' : 'NO') . "<br><br>";

// Check for role match
echo html_writer::tag('h3', 'Role Match Check');
$role_match = false;
foreach ($user_roles as $role) {
    if (in_array($role->shortname, $tutor_roles)) {
        $role_match = true;
        echo "✓ Role match found: {$role->shortname}<br>";
    }
}
if (!$role_match) {
    echo "✗ No role match found<br>";
}
echo "<br>";

// Check assignments
echo html_writer::tag('h3', 'Student Assignments');
$has_students = $DB->record_exists_sql("
    SELECT 1 FROM {local_studenttutor_assign} a
    WHERE a.tutorid = :tutorid 
    AND (a.courseid = :courseid OR a.courseid = 0)
    AND a.status = 'active'
", ['tutorid' => $USER->id, 'courseid' => $courseid]);

echo "Has students assigned: " . ($has_students ? 'YES' : 'NO') . "<br>";

// List all assignments for this tutor
$assignments = $DB->get_records_sql("
    SELECT a.*, s.firstname as student_firstname, s.lastname as student_lastname, c.fullname as course_name
    FROM {local_studenttutor_assign} a
    JOIN {user} s ON s.id = a.studentid
    LEFT JOIN {course} c ON c.id = a.courseid
    WHERE a.tutorid = :tutorid
    ORDER BY a.timeassigned DESC
", ['tutorid' => $USER->id]);

if ($assignments) {
    echo "<br>All assignments for this tutor:<br>";
    foreach ($assignments as $assignment) {
        $course_name = $assignment->course_name ?: 'Global';
        echo "- Student: {$assignment->student_firstname} {$assignment->student_lastname}, Course: {$course_name}, Status: {$assignment->status}<br>";
    }
} else {
    echo "<br>No assignments found for this tutor<br>";
}

echo html_writer::end_tag('div');
