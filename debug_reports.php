<?php
// Debug para reports.php
require_once('../../config.php');
require_login();

use local_studenttutor\history_manager;

echo "<h1>Debug Reports</h1>";

// 1. Verificar registros na tabela history
echo "<h2>1. Registros na Tabela History</h2>";
$total_history = $DB->count_records('local_studenttutor_history');
echo "<p>Total de registros na tabela: {$total_history}</p>";

$history_records = $DB->get_records_sql("
    SELECT h.*, 
           tu.firstname as tutor_firstname, tu.lastname as tutor_lastname,
           st.firstname as student_firstname, st.lastname as student_lastname,
           c.fullname as course_name
    FROM {local_studenttutor_history} h
    JOIN {user} tu ON h.tutorid = tu.id
    JOIN {user} st ON h.studentid = st.id
    LEFT JOIN {course} c ON h.courseid = c.id
    ORDER BY h.timecreated DESC
");

echo "<p>Registros com JOINs: " . count($history_records) . "</p>";

if ($history_records) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Tutor</th><th>Student</th><th>Course</th><th>Type</th><th>Title</th><th>Date</th></tr>";
    foreach ($history_records as $record) {
        echo "<tr>";
        echo "<td>{$record->id}</td>";
        echo "<td>{$record->tutor_firstname} {$record->tutor_lastname}</td>";
        echo "<td>{$record->student_firstname} {$record->student_lastname}</td>";
        echo "<td>" . ($record->course_name ?: 'Global') . "</td>";
        echo "<td>{$record->activitytype}</td>";
        echo "<td>{$record->title}</td>";
        echo "<td>" . date('Y-m-d H:i:s', $record->timecreated) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Verificar atribuições
echo "<h2>2. Atribuições</h2>";
$assignments = $DB->get_records_sql("SELECT * FROM {local_studenttutor_assign} ORDER BY id DESC");
echo "<p>Total de atribuições: " . count($assignments) . "</p>";

if ($assignments) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Tutor ID</th><th>Course ID</th><th>Status</th></tr>";
    foreach ($assignments as $assign) {
        echo "<tr>";
        echo "<td>{$assign->id}</td>";
        echo "<td>{$assign->studentid}</td>";
        echo "<td>{$assign->tutorid}</td>";
        echo "<td>{$assign->courseid}</td>";
        echo "<td>{$assign->status}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Testar método history_manager
echo "<h2>3. Método history_manager::get_history_with_details</h2>";
$history_filtered = history_manager::get_history_with_details();
echo "<p>Registros retornados pelo método: " . count($history_filtered) . "</p>";

if ($history_filtered) {
    foreach ($history_filtered as $record) {
        echo "<p>ID: {$record->id} | Tutor: {$record->tutor_firstname} {$record->tutor_lastname} | Student: {$record->student_firstname} {$record->student_lastname} | Course: " . ($record->course_name ?: 'Global') . "</p>";
    }
}

// 4. Testar query manual sem EXISTS
echo "<h2>4. Query Manual (sem EXISTS)</h2>";
$sql_manual = "SELECT h.*, 
               tu.firstname as tutor_firstname, tu.lastname as tutor_lastname,
               st.firstname as student_firstname, st.lastname as student_lastname,
               c.fullname as course_name
        FROM {local_studenttutor_history} h
        JOIN {user} tu ON h.tutorid = tu.id
        JOIN {user} st ON h.studentid = st.id
        LEFT JOIN {course} c ON h.courseid = c.id
        ORDER BY h.timecreated DESC";

$manual_results = $DB->get_records_sql($sql_manual);
echo "<p>Query manual (sem EXISTS): " . count($manual_results) . " registros</p>";

// 5. Testar query com EXISTS corrigida
echo "<h2>5. Query com EXISTS Corrigida</h2>";
$sql_exists = "SELECT h.*, 
               tu.firstname as tutor_firstname, tu.lastname as tutor_lastname,
               st.firstname as student_firstname, st.lastname as student_lastname,
               c.fullname as course_name
        FROM {local_studenttutor_history} h
        JOIN {user} tu ON h.tutorid = tu.id
        JOIN {user} st ON h.studentid = st.id
        LEFT JOIN {course} c ON h.courseid = c.id
        WHERE EXISTS (
            SELECT 1 FROM {local_studenttutor_assign} a
            WHERE a.tutorid = h.tutorid 
            AND a.studentid = h.studentid 
            AND (a.courseid = h.courseid OR a.courseid = 0)
            AND a.status = 'active'
        )
        ORDER BY h.timecreated DESC";

$exists_results = $DB->get_records_sql($sql_exists);
echo "<p>Query com EXISTS corrigida: " . count($exists_results) . " registros</p>";

echo "<p><a href='/moodle/local/studenttutor/reports.php'>Voltar para Reports</a></p>";
?>
