<?php
// Verificar e corrigir estrutura da tabela history
require_once('../../config.php');
require_login();

echo "<h1>Debug - Estrutura da Tabela History</h1>";

// 1. Verificar estrutura da tabela
echo "<h2>1. Estrutura da Tabela local_studenttutor_history</h2>";

try {
    $sql = "DESCRIBE {local_studenttutor_history}";
    $structure = $DB->get_records_sql($sql);
    
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $has_title_field = false;
    foreach ($structure as $field) {
        echo "<tr>";
        echo "<td>{$field->field}</td>";
        echo "<td>{$field->type}</td>";
        echo "<td>{$field->null}</td>";
        echo "<td>" . ($field->key ?: '') . "</td>";
        echo "<td>" . ($field->default ?: '') . "</td>";
        echo "</tr>";
        
        if ($field->field === 'title') {
            $has_title_field = true;
        }
    }
    echo "</table>";
    
    echo "<p><strong>Campo 'title' existe:</strong> " . ($has_title_field ? 'SIM' : 'NÃO') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao verificar estrutura: " . $e->getMessage() . "</p>";
}

// 2. Se o campo title não existe, vamos adicioná-lo
if (!$has_title_field) {
    echo "<h2>2. Adicionando Campo 'title'</h2>";
    
    try {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_studenttutor_history');
        $field = new xmldb_field('title', XMLDB_TYPE_CHAR, '255', null, false, null, null, 'activitytype');
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            echo "<p style='color: green;'>✓ Campo 'title' adicionado com sucesso!</p>";
        } else {
            echo "<p style='color: blue;'>Campo 'title' já existe.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erro ao adicionar campo: " . $e->getMessage() . "</p>";
    }
}

// 3. Verificar registros existentes
echo "<h2>3. Registros na Tabela</h2>";
$records = $DB->get_records_sql("SELECT * FROM {local_studenttutor_history} ORDER BY id DESC LIMIT 5");

if ($records) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Tutor ID</th><th>Activity Type</th><th>Title</th><th>Description</th><th>Created</th></tr>";
    
    foreach ($records as $record) {
        echo "<tr>";
        echo "<td>{$record->id}</td>";
        echo "<td>{$record->studentid}</td>";
        echo "<td>{$record->tutorid}</td>";
        echo "<td>{$record->activitytype}</td>";
        echo "<td>" . (isset($record->title) ? $record->title : 'N/A') . "</td>";
        echo "<td>" . substr($record->description, 0, 50) . "...</td>";
        echo "<td>" . date('Y-m-d H:i:s', $record->timecreated) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Nenhum registro encontrado.</p>";
}

// 4. Atualizar registros sem título
if ($has_title_field || isset($_GET['fix'])) {
    echo "<h2>4. Corrigir Registros sem Título</h2>";
    
    $records_without_title = $DB->get_records_sql("
        SELECT * FROM {local_studenttutor_history} 
        WHERE title IS NULL OR title = '' 
        ORDER BY id DESC
    ");
    
    echo "<p>Registros sem título: " . count($records_without_title) . "</p>";
    
    if (isset($_GET['fix']) && count($records_without_title) > 0) {
        foreach ($records_without_title as $record) {
            // Gerar título baseado no tipo de atividade
            $title = '';
            switch ($record->activitytype) {
                case 'meeting':
                    $title = 'Reunião de Tutoria';
                    break;
                case 'email':
                    $title = 'Comunicação por Email';
                    break;
                case 'feedback':
                    $title = 'Feedback ao Estudante';
                    break;
                case 'assessment':
                    $title = 'Revisão de Avaliação';
                    break;
                default:
                    $title = 'Atividade de Tutoria';
            }
            
            $DB->set_field('local_studenttutor_history', 'title', $title, ['id' => $record->id]);
        }
        
        echo "<p style='color: green;'>✓ " . count($records_without_title) . " registros atualizados com títulos padrão!</p>";
    } else if (count($records_without_title) > 0) {
        echo "<p><a href='?fix=1' style='background: #007cba; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Corrigir Registros</a></p>";
    }
}

echo "<p><a href='/moodle/local/studenttutor/reports.php'>Voltar para Reports</a></p>";
?>
