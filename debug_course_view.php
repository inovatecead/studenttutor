<?php
// Debug para course_view.php
require_once('../../config.php');
require_login();

use local_studenttutor\assignment_manager;

$courseid = optional_param('courseid', 2, PARAM_INT);
$userid = $USER->id;

echo "<h1>Debug Course View - Curso ID: {$courseid}, User ID: {$userid}</h1>";

// 1. Verificar se o usuário é tutor no curso
$course = $DB->get_record('course', array('id' => $courseid));
$context = context_course::instance($courseid);
$roles = get_user_roles($context, $userid);

echo "<h2>1. Verificação de Papel do Usuário</h2>";
echo "<p>Curso: {$course->fullname}</p>";
echo "<p>Papéis do usuário no curso:</p>";
foreach ($roles as $role) {
    echo "<li>{$role->shortname} - {$role->name}</li>";
}

$is_tutor = false;
foreach ($roles as $role) {
    if ($role->shortname === 'teacher' || $role->shortname === 'editingteacher') {
        $is_tutor = true;
        break;
    }
}
echo "<p>É tutor: " . ($is_tutor ? 'SIM' : 'NÃO') . "</p>";

// 2. Verificar atribuições diretas na tabela
echo "<h2>2. Atribuições na Tabela (Raw)</h2>";
$sql = "SELECT * FROM {local_studenttutor_assign} WHERE tutorid = :tutorid ORDER BY id DESC";
$all_assignments = $DB->get_records_sql($sql, ['tutorid' => $userid]);

echo "<p>Total de atribuições para este tutor: " . count($all_assignments) . "</p>";
if ($all_assignments) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Tutor ID</th><th>Course ID</th><th>Status</th><th>Time Assigned</th></tr>";
    foreach ($all_assignments as $assign) {
        echo "<tr>";
        echo "<td>{$assign->id}</td>";
        echo "<td>{$assign->studentid}</td>";
        echo "<td>{$assign->tutorid}</td>";
        echo "<td>{$assign->courseid}</td>";
        echo "<td>{$assign->status}</td>";
        echo "<td>" . date('Y-m-d H:i:s', $assign->timeassigned) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Testar o método get_tutor_students_including_global
echo "<h2>3. Método get_tutor_students_including_global</h2>";
$assignments = assignment_manager::get_tutor_students_including_global($userid, $courseid, 'active');

echo "<p>Resultado do método: " . count($assignments) . " atribuições</p>";
if ($assignments) {
    echo "<table border='1'>";
    echo "<tr><th>Student</th><th>Course ID</th><th>Course Name</th><th>Is Global</th><th>Status</th></tr>";
    foreach ($assignments as $assign) {
        echo "<tr>";
        echo "<td>{$assign->student_firstname} {$assign->student_lastname}</td>";
        echo "<td>{$assign->courseid}</td>";
        echo "<td>" . ($assign->course_name ?: 'Global') . "</td>";
        echo "<td>" . ($assign->is_global_assignment ? 'SIM' : 'NÃO') . "</td>";
        echo "<td>{$assign->status}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Testar query manual
echo "<h2>4. Query Manual</h2>";
$sql = "SELECT DISTINCT a.*, 
               tu.firstname as tutor_firstname, tu.lastname as tutor_lastname, tu.email as tutor_email,
               st.firstname as student_firstname, st.lastname as student_lastname, st.email as student_email,
               c.fullname as course_name, c.shortname as course_shortname,
               CASE WHEN a.courseid = 0 THEN 1 ELSE 0 END as is_global_assignment
        FROM {local_studenttutor_assign} a
        JOIN {user} tu ON a.tutorid = tu.id AND tu.deleted = 0
        JOIN {user} st ON a.studentid = st.id AND st.deleted = 0
        LEFT JOIN {course} c ON a.courseid = c.id
        WHERE a.tutorid = :tutorid 
        AND a.status = :status
        AND (a.courseid = :courseid OR a.courseid = 0)
        ORDER BY is_global_assignment DESC, st.lastname, st.firstname";

$params = [
    'tutorid' => $userid,
    'status' => 'active',
    'courseid' => $courseid
];

$manual_assignments = $DB->get_records_sql($sql, $params);

echo "<p>Query manual retornou: " . count($manual_assignments) . " registros</p>";
if ($manual_assignments) {
    foreach ($manual_assignments as $assign) {
        echo "<p>Student: {$assign->student_firstname} {$assign->student_lastname} | Course ID: {$assign->courseid} | Global: " . ($assign->is_global_assignment ? 'SIM' : 'NÃO') . "</p>";
    }
}

// 5. Verificar se existem estudantes no curso
echo "<h2>5. Estudantes no Curso</h2>";
$students_in_course = get_enrolled_users($context, 'moodle/course:isincompletionreports', 0, 'u.id, u.firstname, u.lastname, u.email');
echo "<p>Estudantes matriculados no curso: " . count($students_in_course) . "</p>";
foreach ($students_in_course as $student) {
    echo "<li>{$student->firstname} {$student->lastname} (ID: {$student->id})</li>";
}

echo "<p><a href='/moodle/local/studenttutor/course_view.php?courseid={$courseid}'>Voltar para Course View</a></p>";
?>
