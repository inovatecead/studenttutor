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
 * Assign students to tutors page
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/classes/assignment_form.php');
require_once(__DIR__ . '/lib.php');

use local_studenttutor\assignment_manager;

require_login();

$id = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();

require_capability('local/studenttutor:manageassignments', $context);

$PAGE->set_url(new moodle_url('/local/studenttutor/assign.php', array('id' => $id)));
$PAGE->set_context($context);

$returnurl = new moodle_url('/local/studenttutor/index.php');

// Determinar modo de operação
$assignment = null;
$is_edit_mode = false;

if ($id > 0) {
    // EDIT MODE - Load existing assignment
    $assignment = assignment_manager::get_assignment($id);
    if (!$assignment) {
        redirect($returnurl, 'Atribuição não encontrada', null, \core\output\notification::NOTIFY_ERROR);
    }
    $is_edit_mode = true;
    $PAGE->set_title('Editar Atribuição');
    $PAGE->set_heading('Editar Atribuição');
} else {
    // CREATE MODE
    $PAGE->set_title('Nova Atribuição');
    $PAGE->set_heading('Nova Atribuição');
}

$mform = new assignment_form(null, array('assignment' => $assignment));

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    
    // Process student IDs from JSON or traditional array
    $studentids = array();
    if (!empty($data->studentids_json)) {
        // From dynamic form (JSON)
        $studentids = json_decode($data->studentids_json, true);
    } elseif (!empty($data->studentids)) {
        // From traditional form (array)
        $studentids = $data->studentids;
    }
    
    // Validação de dados obrigatórios
    if (empty($studentids) || empty($data->tutorid)) {
        redirect($returnurl, 'Dados obrigatórios não preenchidos', null, \core\output\notification::NOTIFY_ERROR);
    }
    
    // Validação adicional: verificar se o tutor é válido
    if (!local_studenttutor_is_tutor($data->tutorid)) {
        redirect($returnurl, 'Tutor selecionado não é válido', null, \core\output\notification::NOTIFY_ERROR);
    }
    
    global $DB, $USER;
    $success_count = 0;
    $duplicate_count = 0;
    $error_count = 0;
    
    if ($is_edit_mode) {
        // EDIT MODE - Update existing assignment
        $record = new stdClass();
        $record->id = $assignment->id;
        $record->studentid = $studentids[0]; // Em modo edição, apenas um estudante
        $record->tutorid = $data->tutorid;
        $record->courseid = $data->courseid;
        $record->status = isset($data->status) ? $data->status : 'active';
        $record->timemodified = time();
        
        if ($DB->update_record('local_studenttutor_assign', $record)) {
            redirect($returnurl, 'Atribuição atualizada com sucesso', null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect($returnurl, 'Erro ao atualizar atribuição', null, \core\output\notification::NOTIFY_ERROR);
        }
    } else {
        // CREATE MODE - Create new assignments with duplicate validation
        $records_to_insert = array();
        
        foreach ($studentids as $studentid) {
            // Verificar se já existe atribuição
            $existing = $DB->get_record('local_studenttutor_assign', array(
                'studentid' => $studentid,
                'tutorid' => $data->tutorid,
                'courseid' => $data->courseid,
                'status' => 'active'
            ));
            
            if ($existing) {
                $duplicate_count++;
                continue; // Pular esta atribuição
            }
            
            // Preparar registro para inserção
            $record = new stdClass();
            $record->studentid = $studentid;
            $record->tutorid = $data->tutorid;
            $record->courseid = $data->courseid;
            $record->assignedby = $USER->id;
            $record->createdby = $USER->id;
            $record->status = 'active';
            $record->timeassigned = time();
            $record->timecreated = time();
            $record->timemodified = time();
            
            $records_to_insert[] = $record;
        }
        
        // Inserção otimizada para lotes grandes
        if (!empty($records_to_insert)) {
            if (count($records_to_insert) > 10) {
                // Para lotes grandes, usar insert_records se disponível
                try {
                    $DB->insert_records('local_studenttutor_assign', $records_to_insert);
                    $success_count = count($records_to_insert);
                } catch (Exception $e) {
                    // Fallback para inserção individual
                    foreach ($records_to_insert as $record) {
                        if ($DB->insert_record('local_studenttutor_assign', $record)) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                    }
                }
            } else {
                // Para lotes pequenos, inserção individual
                foreach ($records_to_insert as $record) {
                    if ($DB->insert_record('local_studenttutor_assign', $record)) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
            }
        }
        
        // Mensagem de resultado detalhada
        $messages = array();
        if ($success_count > 0) {
            $messages[] = "{$success_count} atribuição(ões) criada(s) com sucesso";
        }
        if ($duplicate_count > 0) {
            $messages[] = "{$duplicate_count} atribuição(ões) já existia(m)";
        }
        if ($error_count > 0) {
            $messages[] = "{$error_count} erro(s) ao criar atribuições";
        }
        
        $final_message = implode('. ', $messages);
        $notification_type = $success_count > 0 ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_WARNING;
        
        redirect($returnurl, $final_message, null, $notification_type);
    }
}

// Add CSS for better styling
$PAGE->requires->css('/local/studenttutor/styles/assign_form.css');
$PAGE->requires->css('/local/studenttutor/styles/assign_enhanced.css');

// Add JavaScript for dynamic student loading
// Temporarily disabled due to AMD module issues
// $PAGE->requires->js_call_amd('local_studenttutor/assign_dynamic', 'init');

// Add fallback JavaScript (vanilla JS)
$PAGE->requires->js('/local/studenttutor/scripts/assign_simple.js');

// Add debug JavaScript to verify module loading
// Temporarily disabled due to AMD module issues
/*
$PAGE->requires->js_amd_inline("
require(['local_studenttutor/assign_dynamic'], function(assignDynamic) {
    console.log('assign_dynamic module loaded successfully');
    assignDynamic.init();
});
");
*/

echo $OUTPUT->header();

echo $OUTPUT->heading($is_edit_mode ? 'Editar Atribuição' : 'Nova Atribuição');

$mform->display();

echo $OUTPUT->footer();
