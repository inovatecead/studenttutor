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
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');

use local_studenttutor\assignment_manager;
use local_studenttutor\form\assignment_form;

require_login();

$id = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();

require_capability('local/studenttutor:manageassignments', $context);

$PAGE->set_url(new moodle_url('/local/studenttutor/assign.php', ['id' => $id]));
$PAGE->set_context($context);

$returnurl = new moodle_url('/local/studenttutor/index.php');

// Determinar modo de operação
$assignment = null;
$is_edit_mode = false;

if ($id > 0) {
    // EDIT MODE - Load existing assignment
    $assignment = assignment_manager::get_assignment($id);
    if (!$assignment) {
        redirect(
            $returnurl,
            get_string('assignmentnotfound', 'local_studenttutor'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
    $is_edit_mode = true;
    $PAGE->set_title(get_string('edit_assignment', 'local_studenttutor'));
    $PAGE->set_heading(get_string('edit_assignment', 'local_studenttutor'));
} else {
    // CREATE MODE
    $PAGE->set_title(get_string('add_assignment', 'local_studenttutor'));
    $PAGE->set_heading(get_string('add_assignment', 'local_studenttutor'));
}

$mform = new assignment_form(null, ['assignment' => $assignment]);

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    // Process student IDs from JSON or traditional array
    $studentids = [];
    if (!empty($data->studentids_json)) {
        // From dynamic form (JSON)
        $studentids = json_decode($data->studentids_json, true);
    } else if (!empty($data->studentids)) {
        // From traditional form (array)
        $studentids = $data->studentids;
    }

    // Validação de dados obrigatórios
    if (empty($studentids) || empty($data->tutorid)) {
        redirect(
            $returnurl,
            get_string('required_fields_missing', 'local_studenttutor'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    // Validação adicional: verificar se o tutor é válido
    if (!local_studenttutor_is_tutor($data->tutorid)) {
        redirect(
            $returnurl,
            get_string('invalid_tutor', 'local_studenttutor'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
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
            redirect(
                $returnurl,
                get_string('assignment_updated_success', 'local_studenttutor'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else {
            redirect(
                $returnurl,
                get_string('assignment_update_error', 'local_studenttutor'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
    } else {
        // CREATE MODE - Create new assignments with duplicate validation
        $records_to_insert = [];

        foreach ($studentids as $studentid) {
            // Verificar se já existe atribuição
            $existing = $DB->get_record('local_studenttutor_assign', [
                'studentid' => $studentid,
                'tutorid' => $data->tutorid,
                'courseid' => $data->courseid,
                'status' => 'active',
            ]);

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
            $record->status = 'active';
            $record->timeassigned = time();
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
        $messages = [];
        if ($success_count > 0) {
            $messages[] = get_string('assignments_created_count', 'local_studenttutor', $success_count);
        }
        if ($duplicate_count > 0) {
            $messages[] = get_string('assignments_duplicated_count', 'local_studenttutor', $duplicate_count);
        }
        if ($error_count > 0) {
            $messages[] = get_string('assignments_error_count', 'local_studenttutor', $error_count);
        }

        $final_message = implode('. ', $messages);
        $notification_type = $success_count > 0 ? \core\output\notification::NOTIFY_SUCCESS
            : \core\output\notification::NOTIFY_WARNING;

        redirect($returnurl, $final_message, null, $notification_type);
    }
}

// Add CSS for better styling
$PAGE->requires->css('/local/studenttutor/styles/assign_form.css');
$PAGE->requires->css('/local/studenttutor/styles/assign_enhanced.css');

// Dynamic student loading.
$PAGE->requires->js('/local/studenttutor/scripts/assign_simple.js');

echo $OUTPUT->header();

echo $OUTPUT->heading($is_edit_mode ? get_string('edit_assignment', 'local_studenttutor')
    : get_string('add_assignment', 'local_studenttutor'));

$mform->display();

echo $OUTPUT->footer();
