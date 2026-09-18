<?php
/**
 * Script para adicionar campo activity_date em produção
 * 
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

// Verificar se é admin
require_login();
require_capability('moodle/site:config', context_system::instance());

echo $OUTPUT->header();
echo $OUTPUT->heading('Fix StudentTutor Activity Date Field');

$dbman = $DB->get_manager();
$table = new xmldb_table('local_studenttutor_history');
$field = new xmldb_field('activity_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

if (!$dbman->field_exists($table, $field)) {
    echo $OUTPUT->notification('Adding activity_date field...', 'info');
    
    try {
        $dbman->add_field($table, $field);
        echo $OUTPUT->notification('Field added successfully!', 'success');
        
        // Update existing records
        $DB->execute('UPDATE {local_studenttutor_history} SET activity_date = timecreated WHERE activity_date = 0');
        echo $OUTPUT->notification('Existing records updated!', 'success');
        
    } catch (Exception $e) {
        echo $OUTPUT->notification('Error: ' . $e->getMessage(), 'error');
    }
} else {
    echo $OUTPUT->notification('Field activity_date already exists!', 'info');
}

echo $OUTPUT->footer();
