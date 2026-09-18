<?php
/**
 * CLI Script para adicionar campo activity_date em produção
 * Execute via linha de comando: php fix_database_cli.php
 * 
 * @package    local_studenttutor
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');

global $DB;

// Verificar se está sendo executado via CLI
if (!CLI_SCRIPT) {
    die('Este script só pode ser executado via linha de comando.');
}

echo "StudentTutor - Adicionando campo activity_date\n";
echo "==============================================\n\n";

try {
    // Verificar se o campo já existe
    $columns = $DB->get_columns('local_studenttutor_history');
    
    if (array_key_exists('activity_date', $columns)) {
        echo "✅ Campo activity_date já existe na tabela!\n";
    } else {
        echo "Adicionando campo activity_date...\n";
        
        // Adicionar o campo usando SQL direto
        $sql = "ALTER TABLE {local_studenttutor_history} ADD COLUMN activity_date BIGINT(10) NOT NULL DEFAULT 0";
        $DB->execute($sql);
        
        echo "✅ Campo activity_date adicionado com sucesso!\n";
        
        // Atualizar registros existentes
        echo "Atualizando registros existentes...\n";
        $update_sql = "UPDATE {local_studenttutor_history} SET activity_date = timecreated WHERE activity_date = 0";
        $result = $DB->execute($update_sql);
        
        echo "✅ Registros atualizados com sucesso!\n";
    }
    
    echo "\n✅ Operação concluída com sucesso!\n\n";
    
    // Mostrar estatísticas da tabela
    $count = $DB->count_records('local_studenttutor_history');
    echo "Total de registros na tabela: $count\n";
    
    if ($count > 0) {
        $with_date = $DB->count_records_select('local_studenttutor_history', 'activity_date > 0');
        echo "Registros com activity_date definido: $with_date\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nConcluído!\n";
exit(0);
