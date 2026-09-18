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
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the local_studenttutor plugin.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
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

    if ($oldversion < 2025063013) {
        // Ensure title field exists in history table and has default values
        $table = new xmldb_table('local_studenttutor_history');
        $field = new xmldb_field('title', XMLDB_TYPE_CHAR, '255', null, false, null, null, 'activitytype');

        // Add title field if it doesn't exist
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update existing records without titles
        $DB->execute("UPDATE {local_studenttutor_history}
                      SET title = CASE
                          WHEN activitytype = 'meeting' THEN 'Meeting with student'
                          WHEN activitytype = 'email' THEN 'Email communication'
                          WHEN activitytype = 'feedback' THEN 'Feedback provided'
                          WHEN activitytype = 'assessment' THEN 'Assessment review'
                          WHEN activitytype = 'phone' THEN 'Phone conversation'
                          ELSE 'Tutoring activity'
                      END
                      WHERE title IS NULL OR title = ''");

        // Studenttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2025063013, 'local', 'studenttutor');
    }

    if ($oldversion < 2025070102) {
        // Define table local_studenttutor_activity_types to store dynamic activity types
        $table = new xmldb_table('local_studenttutor_activity_types');

        // Adding fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('icon', XMLDB_TYPE_CHAR, '50', null, null, null, 'fa-circle');
        $table->add_field('color', XMLDB_TYPE_CHAR, '7', null, null, null, '#007bff');
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes
        $table->add_index('shortname', XMLDB_INDEX_UNIQUE, ['shortname']);
        $table->add_index('active', XMLDB_INDEX_NOTUNIQUE, ['active']);
        $table->add_index('sortorder', XMLDB_INDEX_NOTUNIQUE, ['sortorder']);

        // Conditionally launch create table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Insert default activity types
        $time = time();
        $default_types = [
            [
                'name' => 'Convocatória para reunião',
                'shortname' => 'convocatoria_reuniao',
                'description' => 'Convocação de estudantes para reuniões',
                'icon' => 'fa-bell',
                'color' => '#007bff',
                'sortorder' => 1,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Retorno de dúvidas',
                'shortname' => 'retorno_duvidas',
                'description' => 'Esclarecimento de dúvidas dos estudantes',
                'icon' => 'fa-question-circle',
                'color' => '#17a2b8',
                'sortorder' => 2,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Reunião Virtual para webconferencia',
                'shortname' => 'reuniao_virtual_webconf',
                'description' => 'Reuniões virtuais via webconferência',
                'icon' => 'fa-video',
                'color' => '#28a745',
                'sortorder' => 3,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Reunião presencial no polo',
                'shortname' => 'reuniao_presencial_polo',
                'description' => 'Reuniões presenciais realizadas no polo de ensino',
                'icon' => 'fa-users',
                'color' => '#fd7e14',
                'sortorder' => 4,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Reunião com grupo de Trabalho do Seminário Integrador - Online',
                'shortname' => 'grupo_seminario_online',
                'description' => 'Reuniões online com grupos de trabalho do Seminário Integrador',
                'icon' => 'fa-laptop',
                'color' => '#6f42c1',
                'sortorder' => 5,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Reunião com grupo de Trabalho do Seminário Integrador - Presencial',
                'shortname' => 'grupo_seminario_presencial',
                'description' => 'Reuniões presenciais com grupos de trabalho do Seminário Integrador',
                'icon' => 'fa-chalkboard-teacher',
                'color' => '#e83e8c',
                'sortorder' => 6,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Apresentação de Seminário Temático',
                'shortname' => 'apresentacao_seminario',
                'description' => 'Apresentações de seminários temáticos pelos estudantes',
                'icon' => 'fa-presentation',
                'color' => '#20c997',
                'sortorder' => 7,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Aplicação de Prova/Atividade',
                'shortname' => 'aplicacao_prova',
                'description' => 'Aplicação de provas e atividades avaliativas',
                'icon' => 'fa-clipboard-check',
                'color' => '#dc3545',
                'sortorder' => 8,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Atividade de nivelamento nas área temáticas que o estudante apresenta dificuldade',
                'shortname' => 'nivelamento_areas',
                'description' => 'Atividades de nivelamento em áreas temáticas com dificuldade',
                'icon' => 'fa-chart-line',
                'color' => '#ffc107',
                'sortorder' => 9,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Diagnóstico sobre dificuldade do estudante em relação as áreas temáticas',
                'shortname' => 'diagnostico_dificuldades',
                'description' => 'Diagnóstico e identificação de dificuldades do estudante',
                'icon' => 'fa-stethoscope',
                'color' => '#6610f2',
                'sortorder' => 10,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Outros',
                'shortname' => 'outros',
                'description' => 'Outras atividades não categorizadas',
                'icon' => 'fa-ellipsis-h',
                'color' => '#6c757d',
                'sortorder' => 11,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
        ];

        foreach ($default_types as $type) {
            $DB->insert_record('local_studenttutor_activity_types', (object)$type);
        }

        // Studenttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2025070102, 'local', 'studenttutor');
    }

    if ($oldversion < 2025070103) {
        // Update existing activity types with new Brazilian-specific types
        // First, clear existing types
        $DB->delete_records('local_studenttutor_activity_types');

        // Insert new activity types
        $time = time();
        $new_types = [
            [
                'name' => 'Convocatória para reunião',
                'shortname' => 'convocatoria_reuniao',
                'description' => 'Convocação de estudantes para reuniões',
                'icon' => 'fa-bell',
                'color' => '#007bff',
                'sortorder' => 1,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Retorno de dúvidas',
                'shortname' => 'retorno_duvidas',
                'description' => 'Esclarecimento de dúvidas dos estudantes',
                'icon' => 'fa-question-circle',
                'color' => '#17a2b8',
                'sortorder' => 2,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Reunião Virtual para webconferencia',
                'shortname' => 'reuniao_virtual_webconf',
                'description' => 'Reuniões virtuais via webconferência',
                'icon' => 'fa-video',
                'color' => '#28a745',
                'sortorder' => 3,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Reunião presencial no polo',
                'shortname' => 'reuniao_presencial_polo',
                'description' => 'Reuniões presenciais realizadas no polo de ensino',
                'icon' => 'fa-users',
                'color' => '#fd7e14',
                'sortorder' => 4,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Reunião com grupo de Trabalho do Seminário Integrador - Online',
                'shortname' => 'grupo_seminario_online',
                'description' => 'Reuniões online com grupos de trabalho do Seminário Integrador',
                'icon' => 'fa-laptop',
                'color' => '#6f42c1',
                'sortorder' => 5,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Reunião com grupo de Trabalho do Seminário Integrador - Presencial',
                'shortname' => 'grupo_seminario_presencial',
                'description' => 'Reuniões presenciais com grupos de trabalho do Seminário Integrador',
                'icon' => 'fa-chalkboard-teacher',
                'color' => '#e83e8c',
                'sortorder' => 6,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Apresentação de Seminário Temático',
                'shortname' => 'apresentacao_seminario',
                'description' => 'Apresentações de seminários temáticos pelos estudantes',
                'icon' => 'fa-presentation',
                'color' => '#20c997',
                'sortorder' => 7,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Aplicação de Prova/Atividade',
                'shortname' => 'aplicacao_prova',
                'description' => 'Aplicação de provas e atividades avaliativas',
                'icon' => 'fa-clipboard-check',
                'color' => '#dc3545',
                'sortorder' => 8,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Atividade de nivelamento nas área temáticas que o estudante apresenta dificuldade',
                'shortname' => 'nivelamento_areas',
                'description' => 'Atividades de nivelamento em áreas temáticas com dificuldade',
                'icon' => 'fa-chart-line',
                'color' => '#ffc107',
                'sortorder' => 9,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Diagnóstico sobre dificuldade do estudante em relação as áreas temáticas',
                'shortname' => 'diagnostico_dificuldades',
                'description' => 'Diagnóstico de dificuldades do estudante em áreas temáticas',
                'icon' => 'fa-stethoscope',
                'color' => '#6610f2',
                'sortorder' => 10,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
            [
                'name' => 'Outros',
                'shortname' => 'outros',
                'description' => 'Outras atividades não categorizadas',
                'icon' => 'fa-ellipsis-h',
                'color' => '#6c757d',
                'sortorder' => 11,
                'active' => 1,
                'timecreated' => $time,
                'timemodified' => $time,
            ],
        ];

        foreach ($new_types as $type) {
            $DB->insert_record('local_studenttutor_activity_types', (object)$type);
        }

        // Studenttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2025070103, 'local', 'studenttutor');
    }

    if ($oldversion < 2025070104) {
        // Add activity_date field to history table
        $table = new xmldb_table('local_studenttutor_history');
        $field = new xmldb_field('activity_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Add activity_date field if it doesn't exist
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Update existing records to use timecreated as activity_date for compatibility
            $DB->execute('UPDATE {local_studenttutor_history} SET activity_date = timecreated WHERE activity_date = 0');
        }

        // Studenttutor savepoint reached.
        upgrade_plugin_savepoint(true, 2025070104, 'local', 'studenttutor');
    }

    return true;
}
