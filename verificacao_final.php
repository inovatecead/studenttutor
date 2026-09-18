<?php
/**
 * Verificação Final - Problemas JavaScript e Traduções
 * 
 * @package    local_studenttutor
 * @copyright  2025 Your Organization  
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

echo "=== VERIFICAÇÃO FINAL - CORREÇÕES JAVASCRIPT E TRADUÇÃO ===\n\n";

// 1. Verificar arquivo JavaScript
echo "1. VERIFICANDO ARQUIVO JAVASCRIPT...\n";
$js_file = $CFG->dirroot . '/local/studenttutor/dashboard/js/chart.min.js';
if (file_exists($js_file)) {
    $size = filesize($js_file);
    echo "   ✅ Arquivo 'chart.min.js' existe ({$size} bytes)\n";
} else {
    echo "   ❌ Arquivo 'chart.min.js' NÃO encontrado!\n";
}

// 2. Verificar referência no dashboard.php
echo "\n2. VERIFICANDO REFERÊNCIA NO DASHBOARD.PHP...\n";
$dashboard_file = $CFG->dirroot . '/local/studenttutor/dashboard.php';
$content = file_get_contents($dashboard_file);
if (strpos($content, 'chart.min.js') !== false && strpos($content, 'charts.min.js') === false) {
    echo "   ✅ Dashboard.php referencia 'chart.min.js' corretamente\n";
} else if (strpos($content, 'charts.min.js') !== false) {
    echo "   ❌ Dashboard.php ainda referencia 'charts.min.js' (INCORRETO)\n";
} else {
    echo "   ⚠️  Referência JavaScript não encontrada\n";
}

// 3. Verificar string de tradução inglês
echo "\n3. VERIFICANDO TRADUÇÃO INGLÊS...\n";
try {
    $string = get_string('dashboard', 'local_studenttutor', null, 'en');
    echo "   ✅ String 'dashboard' (EN): {$string}\n";
} catch (Exception $e) {
    echo "   ❌ Erro ao obter string 'dashboard' (EN): " . $e->getMessage() . "\n";
}

// 4. Verificar string de tradução português
echo "\n4. VERIFICANDO TRADUÇÃO PORTUGUÊS...\n";
try {
    $string = get_string('dashboard', 'local_studenttutor', null, 'pt_br');
    echo "   ✅ String 'dashboard' (PT-BR): {$string}\n";
} catch (Exception $e) {
    echo "   ❌ Erro ao obter string 'dashboard' (PT-BR): " . $e->getMessage() . "\n";
}

// 5. Teste de carregamento do dashboard
echo "\n5. SIMULANDO CARREGAMENTO DO DASHBOARD...\n";
try {
    // Simular alguns requisitos do dashboard
    $dashboard_data = [];
    if (function_exists('local_studenttutor_get_dashboard_data')) {
        $dashboard_data = local_studenttutor_get_dashboard_data();
        echo "   ✅ Função dashboard funciona: " . json_encode($dashboard_data) . "\n";
    } else {
        echo "   ❌ Função local_studenttutor_get_dashboard_data não encontrada\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro ao executar dashboard: " . $e->getMessage() . "\n";
}

echo "\n=== RESUMO FINAL ===\n";
echo "Se todos os itens acima mostram ✅, o problema foi resolvido!\n";
echo "Acesse: http://seu-dominio/local/studenttutor/dashboard.php\n\n";
