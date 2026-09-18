<?php
require_once(__DIR__ . '/../../config.php');
require_login();

// Test the AJAX endpoint
$courseid = 1; // Test with course ID 1

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $CFG->wwwroot . '/local/studenttutor/ajax_get_students.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'courseid' => $courseid,
    'sesskey' => sesskey()
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>Testing AJAX Endpoint</h3>";
echo "<p><strong>URL:</strong> " . $CFG->wwwroot . "/local/studenttutor/ajax_get_students.php</p>";
echo "<p><strong>Course ID:</strong> $courseid</p>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Test direct database query
echo "<h3>Direct Database Test</h3>";
global $DB;

try {
    if ($courseid == 0) {
        $students = $DB->get_records_sql("
            SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
            FROM {user} u
            JOIN {role_assignments} ra ON ra.userid = u.id
            JOIN {context} ctx ON ctx.id = ra.contextid
            JOIN {role} r ON r.id = ra.roleid
            WHERE r.shortname = 'student'
            AND u.deleted = 0
            AND u.suspended = 0
            ORDER BY u.lastname, u.firstname
            LIMIT 50
        ");
    } else {
        $students = $DB->get_records_sql("
            SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
            FROM {user} u
            JOIN {enrol} e ON e.courseid = :courseid
            JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = u.id
            JOIN {role_assignments} ra ON ra.userid = u.id
            JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = :courseid2
            JOIN {role} r ON r.id = ra.roleid
            WHERE r.shortname = 'student'
            AND u.deleted = 0
            AND u.suspended = 0
            AND ue.status = 0
            ORDER BY u.lastname, u.firstname
        ", ['courseid' => $courseid, 'courseid2' => $courseid]);
    }
    
    echo "<p><strong>Found " . count($students) . " students:</strong></p>";
    echo "<ul>";
    foreach ($students as $student) {
        echo "<li>ID: {$student->id} - {$student->firstname} {$student->lastname} ({$student->email})</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p><strong>Database Error:</strong> " . $e->getMessage() . "</p>";
}
?>
