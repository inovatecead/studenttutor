<?php
// Debug para add_history.php
require_once('../../config.php');
require_login();

use local_studenttutor\history_manager;

$courseid = optional_param('courseid', 2, PARAM_INT);
$studentid = optional_param('studentid', 10, PARAM_INT);

echo "<h1>Debug Add History - Course: {$courseid}, Student: {$studentid}</h1>";

// Simular dados do formulário
$test_data = (object) [
    'studentid' => $studentid,
    'action_type' => 'meeting',
    'title' => 'Teste de Título da Reunião',
    'description' => 'Esta é uma descrição de teste para verificar se o título está sendo salvo corretamente.',
    'courseid' => $courseid
];

echo "<h2>1. Dados de Teste</h2>";
echo "<pre>";
var_dump($test_data);
echo "</pre>";

// Testar método add_history_entry
echo "<h2>2. Testando history_manager::add_history_entry</h2>";

try {
    $entryid = history_manager::add_history_entry(
        $test_data->studentid,   // studentid
        $USER->id,               // tutorid  
        $test_data->action_type, // activitytype
        $test_data->title,       // title
        $test_data->description, // description
        $test_data->courseid,    // courseid
        $USER->id                // createdby
    );
    
    if ($entryid) {
        echo "<p style='color: green;'>✓ Entrada criada com sucesso! ID: {$entryid}</p>";
        
        // Verificar se foi salvo corretamente
        $saved_entry = $DB->get_record('local_studenttutor_history', ['id' => $entryid]);
        
        echo "<h3>Dados Salvos:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>ID</td><td>{$saved_entry->id}</td></tr>";
        echo "<tr><td>Student ID</td><td>{$saved_entry->studentid}</td></tr>";
        echo "<tr><td>Tutor ID</td><td>{$saved_entry->tutorid}</td></tr>";
        echo "<tr><td>Course ID</td><td>{$saved_entry->courseid}</td></tr>";
        echo "<tr><td>Activity Type</td><td>{$saved_entry->activitytype}</td></tr>";
        echo "<tr><td>Title</td><td>" . (isset($saved_entry->title) ? $saved_entry->title : 'CAMPO NÃO EXISTE') . "</td></tr>";
        echo "<tr><td>Description</td><td>" . substr($saved_entry->description, 0, 50) . "...</td></tr>";
        echo "<tr><td>Time Created</td><td>" . date('Y-m-d H:i:s', $saved_entry->timecreated) . "</td></tr>";
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ Falhou ao criar entrada</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Verificar estrutura da tabela
echo "<h2>3. Estrutura da Tabela</h2>";
$structure = $DB->get_records_sql("DESCRIBE {local_studenttutor_history}");
$has_title = false;

echo "<table border='1'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th></tr>";
foreach ($structure as $field) {
    echo "<tr>";
    echo "<td>{$field->field}</td>";
    echo "<td>{$field->type}</td>";
    echo "<td>{$field->null}</td>";
    echo "</tr>";
    
    if ($field->field === 'title') {
        $has_title = true;
    }
}
echo "</table>";

echo "<p><strong>Campo 'title' existe:</strong> " . ($has_title ? 'SIM' : 'NÃO') . "</p>";

// Verificar validation method
echo "<h2>4. Testando Validação</h2>";
try {
    $validation_result = history_manager::validate_history_data(
        $test_data->studentid,
        $USER->id,
        $test_data->action_type,
        $test_data->title,
        $test_data->description
    );
    
    echo "<p>Resultado da validação: " . ($validation_result ? 'PASSOU' : 'FALHOU') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro na validação: " . $e->getMessage() . "</p>";
}

echo "<p><a href='/moodle/local/studenttutor/add_history.php?courseid={$courseid}&studentid={$studentid}'>Voltar para Add History</a></p>";
?>
