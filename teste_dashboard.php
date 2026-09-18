<?php
/**
 * Teste de funcionalidade - Dashboard
 * Execute este arquivo para testar se o dashboard funciona
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/studenttutor/lib.php');

echo "=== TESTE DE FUNCIONALIDADE - DASHBOARD ===\n\n";

// 1. Testar se a função existe
echo "1. TESTANDO FUNÇÃO local_studenttutor_get_dashboard_data()...\n";
if (function_exists('local_studenttutor_get_dashboard_data')) {
    echo "   ✅ Função existe!\n";
    
    try {
        $data = local_studenttutor_get_dashboard_data();
        echo "   ✅ Função executada com sucesso!\n";
        echo "   📊 Dados retornados: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } catch (Exception $e) {
        echo "   ❌ Erro ao executar função: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ Função NÃO existe!\n";
}

// 2. Testar traduções
echo "\n2. TESTANDO TRADUÇÕES...\n";
try {
    $pt_string = get_string('dashboard', 'local_studenttutor');
    echo "   ✅ Tradução PT-BR: $pt_string\n";
} catch (Exception $e) {
    echo "   ❌ Erro tradução PT-BR: " . $e->getMessage() . "\n";
}

// 3. Verificar arquivos JavaScript
echo "\n3. VERIFICANDO ARQUIVOS JAVASCRIPT...\n";
$js_files = [
    'dashboard/js/chart.min.js',
    'dashboard/js/dashboard.js'
];

foreach ($js_files as $js_file) {
    $path = $CFG->dirroot . '/local/studenttutor/' . $js_file;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "   ✅ $js_file existe ({$size} bytes)\n";
    } else {
        echo "   ❌ $js_file NÃO encontrado!\n";
    }
}

echo "\n=== TESTE CONCLUÍDO ===\n";
echo "Se todos os itens mostram ✅, o dashboard deve funcionar!\n\n";
