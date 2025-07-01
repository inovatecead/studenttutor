<?php
/**
 * Script para adicionar campo activity_date em produção
 * Execute este script como administrador
 * URL: /local/studenttutor/fix_database.php
 */

require_once(__DIR__ . '/../../config.php');

global $CFG, $DB, $PAGE, $OUTPUT, $USER;

require_login();

// Verificar se é admin
if (!is_siteadmin($USER)) {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

$PAGE->set_url('/local/studenttutor/fix_database.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Fix StudentTutor Database');
$PAGE->set_heading('Fix StudentTutor Database');

echo $OUTPUT->header();
echo $OUTPUT->heading('Adicionando campo activity_date na tabela de histórico');

try {
    // Verificar se o campo já existe
    $columns = $DB->get_columns('local_studenttutor_history');
    
    if (array_key_exists('activity_date', $columns)) {
        echo '<div class="alert alert-info">✅ Campo activity_date já existe!</div>';
    } else {
        echo '<div class="alert alert-warning">Adicionando campo activity_date...</div>';
        
        // Adicionar o campo usando SQL direto
        $sql = "ALTER TABLE {local_studenttutor_history} ADD COLUMN activity_date BIGINT(10) NOT NULL DEFAULT 0";
        $DB->execute($sql);
        
        echo '<div class="alert alert-success">✅ Campo activity_date adicionado com sucesso!</div>';
        
        // Atualizar registros existentes
        echo '<div class="alert alert-info">Atualizando registros existentes...</div>';
        $update_sql = "UPDATE {local_studenttutor_history} SET activity_date = timecreated WHERE activity_date = 0";
        $DB->execute($update_sql);
        
        echo '<div class="alert alert-success">✅ Registros atualizados com sucesso!</div>';
    }
    
    echo '<div class="alert alert-success">Operação concluída!</div>';
    echo '<hr>';
    echo '<a href="/local/studenttutor/" class="btn btn-primary">← Voltar para StudentTutor</a>';
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">❌ Erro: ' . $e->getMessage() . '</div>';
    echo '<hr>';
    echo '<a href="/local/studenttutor/" class="btn btn-primary">← Voltar para StudentTutor</a>';
}

echo $OUTPUT->footer();
