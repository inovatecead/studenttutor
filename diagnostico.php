<?php
/**
 * Script de Diagnóstico para Student Tutor Plugin
 * 
 * Este script verifica os problemas mais comuns encontrados em produção
 * 
 * @package    local_studenttutor
 * @copyright  2025 Your Organization  
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Configurações de segurança - remover em produção após uso
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');

echo "=== DIAGNÓSTICO STUDENT TUTOR PLUGIN ===\n\n";

// 1. Verificar se o plugin está instalado
echo "1. VERIFICANDO INSTALAÇÃO DO PLUGIN...\n";
$pluginman = core_plugin_manager::instance();
$plugin = $pluginman->get_plugin_info('local_studenttutor');
if ($plugin) {
    echo "   ✅ Plugin instalado - Versão: " . $plugin->versiondb . "\n";
} else {
    echo "   ❌ Plugin NÃO encontrado!\n";
}

// 2. Verificar tabelas no banco
echo "\n2. VERIFICANDO TABELAS NO BANCO...\n";
$dbman = $DB->get_manager();
$tables = ['local_studenttutor_assign', 'local_studenttutor_history'];
foreach ($tables as $table) {
    if ($dbman->table_exists($table)) {
        $count = $DB->count_records($table);
        echo "   ✅ Tabela '{$table}' existe - {$count} registros\n";
    } else {
        echo "   ❌ Tabela '{$table}' NÃO existe!\n";
    }
}

// 3. Verificar capabilities
echo "\n3. VERIFICANDO CAPABILITIES...\n";
$context = context_system::instance();
$capabilities = [
    'local/studenttutor:manage',
    'local/studenttutor:viewassignments', 
    'local/studenttutor:manageassignments',
    'local/studenttutor:viewhistory',
    'local/studenttutor:managehistory'
];

foreach ($capabilities as $cap) {
    try {
        $exists = get_capability_info($cap);
        if ($exists) {
            echo "   ✅ Capability '{$cap}' existe\n";
        } else {
            echo "   ❌ Capability '{$cap}' NÃO existe!\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Erro ao verificar '{$cap}': " . $e->getMessage() . "\n";
    }
}

// 4. Verificar arquivos críticos
echo "\n4. VERIFICANDO ARQUIVOS CRÍTICOS...\n";
$critical_files = [
    'lib.php',
    'dashboard.php',
    'index.php',
    'lang/pt_br/local_studenttutor.php',
    'lang/en/local_studenttutor.php',
    'db/access.php',
    'db/install.xml'
];

$plugin_dir = $CFG->dirroot . '/local/studenttutor';
foreach ($critical_files as $file) {
    $filepath = $plugin_dir . '/' . $file;
    if (file_exists($filepath)) {
        echo "   ✅ Arquivo '{$file}' existe\n";
    } else {
        echo "   ❌ Arquivo '{$file}' NÃO encontrado!\n";
    }
}

// 5. Verificar função crítica
echo "\n5. VERIFICANDO FUNÇÃO LOCAL_STUDENTTUTOR_GET_DASHBOARD_DATA...\n";
if (function_exists('local_studenttutor_get_dashboard_data')) {
    echo "   ✅ Função 'local_studenttutor_get_dashboard_data' existe\n";
    try {
        $data = local_studenttutor_get_dashboard_data();
        echo "   ✅ Função executada com sucesso\n";
        echo "   📊 Dados retornados: " . json_encode($data) . "\n";
    } catch (Exception $e) {
        echo "   ❌ Erro ao executar função: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ Função 'local_studenttutor_get_dashboard_data' NÃO existe!\n";
}

// 6. Verificar JavaScript/CSS
echo "\n6. VERIFICANDO ARQUIVOS JAVASCRIPT/CSS...\n";
$js_css_files = [
    'dashboard/js/chart.min.js',
    'dashboard/js/dashboard.js', 
    'dashboard/css/dashboard.css'
];

foreach ($js_css_files as $file) {
    $filepath = $plugin_dir . '/' . $file;
    if (file_exists($filepath)) {
        $size = filesize($filepath);
        echo "   ✅ Arquivo '{$file}' existe ({$size} bytes)\n";
    } else {
        echo "   ❌ Arquivo '{$file}' NÃO encontrado!\n";
    }
}

// 7. Teste de permissão para usuário atual (se logado via web)
echo "\n7. VERIFICANDO PERMISSÕES DO USUÁRIO ATUAL...\n";
if (isloggedin() && !isguestuser()) {
    global $USER;
    echo "   👤 Usuário logado: {$USER->firstname} {$USER->lastname} (ID: {$USER->id})\n";
    
    foreach ($capabilities as $cap) {
        if (has_capability($cap, $context)) {
            echo "   ✅ Usuário TEM permissão: {$cap}\n";
        } else {
            echo "   ❌ Usuário NÃO tem permissão: {$cap}\n";
        }
    }
} else {
    echo "   ℹ️  Executando via CLI - não há usuário logado\n";
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
echo "Salve este output e compartilhe para análise dos problemas.\n\n";
