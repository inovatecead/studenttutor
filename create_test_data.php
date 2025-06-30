<?php
/**
 * Script para criar dados de teste para o plugin Student-Tutor
 * 
 * Cria:
 * - 2 cursos
 * - 10 alunos
 * - 2 tutores  
 * - 1 professor
 * 
 * Uso: php create_test_data.php
 * 
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/user/lib.php');

// Verificar se é CLI
if (!CLI_SCRIPT) {
    die('Este script deve ser executado via linha de comando.');
}

// Configurações
$courses_data = [
    ['fullname' => 'Matemática Avançada', 'shortname' => 'MAT001', 'category' => 1],
    ['fullname' => 'Física Fundamental', 'shortname' => 'FIS001', 'category' => 1]
];

$students_data = [
    ['firstname' => 'Ana', 'lastname' => 'Silva', 'username' => 'ana.silva', 'email' => 'ana.silva@exemplo.com'],
    ['firstname' => 'Bruno', 'lastname' => 'Santos', 'username' => 'bruno.santos', 'email' => 'bruno.santos@exemplo.com'],
    ['firstname' => 'Carlos', 'lastname' => 'Oliveira', 'username' => 'carlos.oliveira', 'email' => 'carlos.oliveira@exemplo.com'],
    ['firstname' => 'Diana', 'lastname' => 'Costa', 'username' => 'diana.costa', 'email' => 'diana.costa@exemplo.com'],
    ['firstname' => 'Eduardo', 'lastname' => 'Lima', 'username' => 'eduardo.lima', 'email' => 'eduardo.lima@exemplo.com'],
    ['firstname' => 'Fernanda', 'lastname' => 'Almeida', 'username' => 'fernanda.almeida', 'email' => 'fernanda.almeida@exemplo.com'],
    ['firstname' => 'Gabriel', 'lastname' => 'Ferreira', 'username' => 'gabriel.ferreira', 'email' => 'gabriel.ferreira@exemplo.com'],
    ['firstname' => 'Helena', 'lastname' => 'Rodrigues', 'username' => 'helena.rodrigues', 'email' => 'helena.rodrigues@exemplo.com'],
    ['firstname' => 'Igor', 'lastname' => 'Martins', 'username' => 'igor.martins', 'email' => 'igor.martins@exemplo.com'],
    ['firstname' => 'Julia', 'lastname' => 'Pereira', 'username' => 'julia.pereira', 'email' => 'julia.pereira@exemplo.com']
];

$tutors_data = [
    ['firstname' => 'Roberto', 'lastname' => 'Silva', 'username' => 'roberto.tutor', 'email' => 'roberto.tutor@exemplo.com'],
    ['firstname' => 'Maria', 'lastname' => 'Santos', 'username' => 'maria.tutor', 'email' => 'maria.tutor@exemplo.com']
];

$professor_data = [
    'firstname' => 'João', 
    'lastname' => 'Professor', 
    'username' => 'joao.professor', 
    'email' => 'joao.professor@exemplo.com'
];

echo "=== Criando Dados de Teste para Student-Tutor Plugin ===\n\n";

/**
 * Função para criar usuário
 */
function create_user($userdata, $role = 'student') {
    global $DB, $CFG;
    
    // Verificar se usuário já existe
    if ($DB->record_exists('user', ['username' => $userdata['username']])) {
        echo "Usuário {$userdata['username']} já existe - pulando...\n";
        return $DB->get_record('user', ['username' => $userdata['username']])->id;
    }
    
    $user = new stdClass();
    $user->firstname = $userdata['firstname'];
    $user->lastname = $userdata['lastname'];
    $user->username = $userdata['username'];
    $user->email = $userdata['email'];
    $user->password = hash_internal_user_password('Teste123!'); // Senha padrão
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $user->auth = 'manual';
    $user->lang = 'pt_br';
    $user->timezone = '99';
    $user->firstnamephonetic = '';
    $user->lastnamephonetic = '';
    $user->middlename = '';
    $user->alternatename = '';
    $user->timecreated = time();
    $user->timemodified = time();
    
    $userid = user_create_user($user, false, false);
    
    echo "✓ Usuário criado: {$user->firstname} {$user->lastname} ({$user->username}) - ID: {$userid}\n";
    
    return $userid;
}

/**
 * Função para criar curso
 */
function create_course($coursedata) {
    global $DB;
    
    // Verificar se curso já existe
    if ($DB->record_exists('course', ['shortname' => $coursedata['shortname']])) {
        echo "Curso {$coursedata['shortname']} já existe - pulando...\n";
        return $DB->get_record('course', ['shortname' => $coursedata['shortname']])->id;
    }
    
    $course = new stdClass();
    $course->fullname = $coursedata['fullname'];
    $course->shortname = $coursedata['shortname'];
    $course->category = $coursedata['category'];
    $course->summary = 'Curso criado automaticamente para testes do plugin Student-Tutor';
    $course->summaryformat = FORMAT_HTML;
    $course->format = 'topics';
    $course->visible = 1;
    $course->startdate = time();
    $course->timecreated = time();
    $course->timemodified = time();
    
    $courseid = $DB->insert_record('course', $course);
    
    // Criar contexto do curso
    $context = context_course::instance($courseid);
    
    echo "✓ Curso criado: {$course->fullname} ({$course->shortname}) - ID: {$courseid}\n";
    
    return $courseid;
}

/**
 * Função para matricular usuário no curso
 */
function enrol_user_in_course($userid, $courseid, $rolename) {
    global $DB;
    
    // Obter role ID
    $role = $DB->get_record('role', ['shortname' => $rolename]);
    if (!$role) {
        echo "Erro: Role '{$rolename}' não encontrada\n";
        return false;
    }
    
    // Obter contexto do curso
    $context = context_course::instance($courseid);
    
    // Verificar se já está matriculado
    if (is_enrolled($context, $userid)) {
        echo "Usuário {$userid} já matriculado no curso {$courseid} - pulando...\n";
        return true;
    }
    
    // Matricular usuário
    $enrol = enrol_get_plugin('manual');
    $instances = enrol_get_instances($courseid, true);
    
    foreach ($instances as $instance) {
        if ($instance->enrol === 'manual') {
            $enrol->enrol_user($instance, $userid, $role->id, time());
            echo "✓ Usuário {$userid} matriculado no curso {$courseid} como {$rolename}\n";
            return true;
        }
    }
    
    echo "Erro: Não foi possível matricular usuário {$userid} no curso {$courseid}\n";
    return false;
}

try {
    // 1. Criar cursos
    echo "1. Criando cursos...\n";
    $created_courses = [];
    foreach ($courses_data as $course_data) {
        $courseid = create_course($course_data);
        $created_courses[] = $courseid;
    }
    echo "✓ Total de cursos criados: " . count($created_courses) . "\n\n";
    
    // 2. Criar alunos
    echo "2. Criando alunos...\n";
    $created_students = [];
    foreach ($students_data as $student_data) {
        $userid = create_user($student_data, 'student');
        $created_students[] = $userid;
    }
    echo "✓ Total de alunos criados: " . count($created_students) . "\n\n";
    
    // 3. Criar tutores
    echo "3. Criando tutores...\n";
    $created_tutors = [];
    foreach ($tutors_data as $tutor_data) {
        $userid = create_user($tutor_data, 'teacher');
        $created_tutors[] = $userid;
    }
    echo "✓ Total de tutores criados: " . count($created_tutors) . "\n\n";
    
    // 4. Criar professor
    echo "4. Criando professor...\n";
    $professor_id = create_user($professor_data, 'editingteacher');
    echo "✓ Professor criado\n\n";
    
    // 5. Matricular usuários nos cursos
    echo "5. Matriculando usuários nos cursos...\n";
    
    foreach ($created_courses as $courseid) {
        echo "Matriculando no curso ID: {$courseid}\n";
        
        // Matricular todos os alunos
        foreach ($created_students as $studentid) {
            enrol_user_in_course($studentid, $courseid, 'student');
        }
        
        // Matricular tutores como professores
        foreach ($created_tutors as $tutorid) {
            enrol_user_in_course($tutorid, $courseid, 'teacher');
        }
        
        // Matricular professor
        enrol_user_in_course($professor_id, $courseid, 'editingteacher');
        
        echo "\n";
    }
    
    // 6. Resumo final
    echo "=== RESUMO FINAL ===\n";
    echo "✓ Cursos criados: " . count($created_courses) . "\n";
    echo "✓ Alunos criados: " . count($created_students) . "\n";
    echo "✓ Tutores criados: " . count($created_tutors) . "\n";
    echo "✓ Professor criado: 1\n";
    echo "\nCursos IDs: " . implode(', ', $created_courses) . "\n";
    echo "Alunos IDs: " . implode(', ', $created_students) . "\n";
    echo "Tutores IDs: " . implode(', ', $created_tutors) . "\n";
    echo "Professor ID: {$professor_id}\n";
    echo "\nSenha padrão para todos os usuários: Teste123!\n";
    echo "\n✅ Dados de teste criados com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante a criação dos dados: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
