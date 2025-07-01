<?php
/**
 * Script simples para adicionar campo activity_date em produção
 * Execute este script como administrador
 * 
 * @package    local_studenttutor
 */

require_once(__DIR__ . '/../../../config.php');
require_login();

// Verificar se é admin
if (!is_siteadmin()) {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

echo "<h2>Adicionando campo activity_date na tabela de histórico</h2>";

try {
    // Verificar se o campo já existe
    $columns = $DB->get_columns('local_studenttutor_history');
    
    if (array_key_exists('activity_date', $columns)) {
        echo "<p style='color:green;'>✅ Campo activity_date já existe!</p>";
    } else {
        echo "<p>Adicionando campo activity_date...</p>";
        
        // Adicionar o campo
        $DB->execute("ALTER TABLE {local_studenttutor_history} ADD COLUMN activity_date BIGINT(10) NOT NULL DEFAULT 0");
        
        echo "<p style='color:green;'>✅ Campo activity_date adicionado com sucesso!</p>";
        
        // Atualizar registros existentes
        echo "<p>Atualizando registros existentes...</p>";
        $DB->execute("UPDATE {local_studenttutor_history} SET activity_date = timecreated WHERE activity_date = 0");
        
        echo "<p style='color:green;'>✅ Registros atualizados com sucesso!</p>";
    }
    
    echo "<p><strong>Operação concluída!</strong></p>";
    echo "<p><a href='/local/studenttutor/'>← Voltar para StudentTutor</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
