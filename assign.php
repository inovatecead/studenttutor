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

use local_studenttutor\assignment_manager;

require_login();

$id = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();

require_capability('local/studenttutor:manageassignments', $context);

$PAGE->set_url(new moodle_url('/local/studenttutor/assign.php', array('id' => $id)));
$PAGE->set_context($context);
$PAGE->set_title(get_string('add_assignment_title', 'local_studenttutor'));
$PAGE->set_heading(get_string('add_assignment_title', 'local_studenttutor'));

$returnurl = new moodle_url('/local/studenttutor/index.php');

// TEMPORARIAMENTE DESABILITADO: Edição de atribuições
// Se um ID for passado, redirecionar com mensagem de que a edição está desabilitada
if ($id > 0) {
    $message = get_string('edit_temporarily_disabled', 'local_studenttutor');
    redirect($returnurl, $message, null, \core\output\notification::NOTIFY_INFO);
}

// Apenas modo de criação é permitido
$assignment = null;

$mform = new assignment_form(null, array('assignment' => $assignment));

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    
    // Apenas modo de criação permitido
    // CREATE MODE - Create new assignments
    if (empty($data->studentids) || empty($data->tutorid)) {
        redirect($returnurl, 'Missing required data', null, \core\output\notification::NOTIFY_ERROR);
    }
    
    global $DB, $USER;
    $success_count = 0;
    
    foreach ($data->studentids as $studentid) {
        $record = new stdClass();
        $record->studentid = $studentid;
        $record->tutorid = $data->tutorid;
        $record->courseid = $data->courseid;
        $record->assignedby = $USER->id;  // Quem está criando a atribuição
        $record->createdby = $USER->id;   // Quem criou o registro
        $record->status = 'active';       // Usar string conforme o campo VARCHAR
        $record->timeassigned = time();   // CAMPO OBRIGATÓRIO - quando foi atribuído
        $record->timecreated = time();
        $record->timemodified = time();
        
        $newid = $DB->insert_record('local_studenttutor_assign', $record);
        if ($newid) {
            $success_count++;
        }
    }
    
    if ($success_count > 0) {
        $message = get_string('assignments_created', 'local_studenttutor', $success_count);
        redirect($returnurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($returnurl, 'Failed to create assignments', null, \core\output\notification::NOTIFY_ERROR);
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('add_assignment_title', 'local_studenttutor'));

$mform->display();

echo $OUTPUT->footer();
