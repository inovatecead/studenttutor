<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade script for local_studenttutor plugin
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_studenttutor_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025062902) {

        // Define field title to be added to local_studenttutor_history.
        $table = new xmldb_table('local_studenttutor_history');
        $field = new xmldb_field('title', XMLDB_TYPE_CHAR, '255', null, false, null, null, 'activitytype');

        // Conditionally launch add field title.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Studenttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2025062902, 'local', 'studenttutor');
    }

    if ($oldversion < 2025062903) {

        // Fix field names in assignment table to match manager expectations.
        $table = new xmldb_table('local_studenttutor_assign');
        
        // Rename timeassigned to timecreated if needed.
        $field = new xmldb_field('timeassigned', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'assignedby');
        if ($dbman->field_exists($table, $field)) {
            $field = new xmldb_field('timeassigned', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'assignedby');
            $dbman->rename_field($table, $field, 'timecreated');
        } else {
            // Add timecreated field if it doesn't exist.
            $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'assignedby');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Add createdby field if it doesn't exist.
        $field = new xmldb_field('createdby', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timecreated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            
            // Update existing records to use assignedby as createdby.
            $DB->execute('UPDATE {local_studenttutor_assign} SET createdby = assignedby WHERE createdby IS NULL OR createdby = 0');
            
            // Now make the field NOT NULL.
            $field = new xmldb_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'timecreated');
            $dbman->change_field_notnull($table, $field);
        }

        // Studenttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2025062903, 'local', 'studenttutor');
    }
    
    if ($oldversion < 2025063001) {
        // Corrigir campos faltantes na tabela de atribuições
        $table = new xmldb_table('local_studenttutor_assign');
        
        // Adicionar campo createdby se não existir
        $field = new xmldb_field('createdby', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            
            // Atualizar registros existentes para usar assignedby como createdby
            $DB->execute('UPDATE {local_studenttutor_assign} SET createdby = assignedby WHERE createdby IS NULL OR createdby = 0');
            
            // Agora tornar o campo NOT NULL
            $field = new xmldb_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'timemodified');
            $dbman->change_field_notnull($table, $field);
        }
        
        // Adicionar campo timecreated se não existir
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timeassigned');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            
            // Atualizar registros existentes para usar timeassigned como timecreated
            $DB->execute('UPDATE {local_studenttutor_assign} SET timecreated = timeassigned WHERE timecreated IS NULL OR timecreated = 0');
            
            // Agora tornar o campo NOT NULL
            $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'timeassigned');
            $dbman->change_field_notnull($table, $field);
        }
        
        // Garantir que timeassigned tem valor padrão para novos registros
        $field = new xmldb_field('timeassigned', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'assignedby');
        if ($dbman->field_exists($table, $field)) {
            // Atualizar registros existentes sem timeassigned
            $DB->execute('UPDATE {local_studenttutor_assign} SET timeassigned = timemodified WHERE timeassigned IS NULL OR timeassigned = 0');
        }
        
        // Studenttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2025063001, 'local', 'studenttutor');
    }

    return true;
}
