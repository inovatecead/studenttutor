<?php
/**
 * Verificação das Chaves do Dashboard
 * 
 * @package    local_studenttutor
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/studenttutor/lib.php');

echo "=== VERIFICAÇÃO DAS CHAVES DO DASHBOARD ===\n\n";

// Testar função e chaves
echo "1. TESTANDO FUNÇÃO E CHAVES...\n";
try {
    $data = local_studenttutor_get_dashboard_data();
    
    $required_keys = [
        'total_assignments',
        'total_students', 
        'total_tutors',
        'recent_activities',
        'efficiency_rate',      // <- Chave que estava causando erro
        'efficiency_growth',    // <- Chave que estava causando erro
        'alerts'
    ];
    
    $all_keys_present = true;
    foreach ($required_keys as $key) {
        if (array_key_exists($key, $data)) {
            echo "   ✅ Chave '$key': " . json_encode($data[$key]) . "\n";
        } else {
            echo "   ❌ Chave '$key': AUSENTE!\n";
            $all_keys_present = false;
        }
    }
    
    if ($all_keys_present) {
        echo "\n   🎉 TODAS AS CHAVES ESTÃO PRESENTES!\n";
        echo "   📊 Dados completos: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "\n   ❌ ALGUMAS CHAVES ESTÃO FALTANDO!\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE DE EFICIÊNCIA ===\n";
echo "Taxa de Eficiência: {$data['efficiency_rate']}%\n";
echo "Crescimento: {$data['efficiency_growth']}%\n";

echo "\n=== VERIFICAÇÃO CONCLUÍDA ===\n";
echo "Se todos os itens mostram ✅, os warnings foram corrigidos!\n\n";
