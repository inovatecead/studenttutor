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
 * Assignment form for Student-Tutor assignment plugin
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/formslib.php');

class assignment_form extends moodleform {

    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        $assignment = $this->_customdata['assignment'];
        $courseid = isset($this->_customdata['courseid']) ? $this->_customdata['courseid'] : 0;

        // Get courses first
        $courses = $DB->get_records('course', array('visible' => 1), 'fullname', 'id, fullname');
        $course_options = array(0 => get_string('all_courses', 'local_studenttutor'));
        foreach ($courses as $course) {
            if ($course->id != SITEID) {
                $course_options[$course->id] = $course->fullname;
            }
        }

        // Get tutors using configured roles instead of hardcoded ones
        $tutor_roles = local_studenttutor_get_tutor_roles();
        $tutors = $this->get_users_by_role($tutor_roles, $courseid);
        $tutor_options = array();
        foreach ($tutors as $user) {
            $fullname = fullname($user);
            $tutor_options[$user->id] = $fullname;
        }

        // Get students (users with student role) - in alphabetical order
        $students = $this->get_users_by_role(['student'], $courseid);
        $student_options = array(); // Remove empty option for autocomplete
        foreach ($students as $user) {
            $fullname = fullname($user);
            $student_options[$user->id] = $fullname;
        }

        // Form fields
        $mform->addElement('autocomplete', 'courseid', 'Selecionar Curso', $course_options, array(
            'multiple' => false,
            'placeholder' => 'Digite para buscar um curso...',
            'showsuggestions' => true,
            'tags' => false,
            'noselectionstring' => 'Todos os cursos'
        ));
        $mform->setType('courseid', PARAM_INT);
        $mform->addHelpButton('courseid', 'course_help', 'local_studenttutor');

        $mform->addElement('autocomplete', 'tutorid', 'Selecionar Tutor', $tutor_options, array(
            'multiple' => false,
            'placeholder' => 'Digite para buscar um tutor...',
            'showsuggestions' => true,
            'tags' => false
        ));
        $mform->setType('tutorid', PARAM_INT);
        $mform->addRule('tutorid', 'Campo obrigatório', 'required', null, 'client');

        // Students will be loaded dynamically by JavaScript
        // Add a placeholder for student selection
        $mform->addElement('html', '<div id="students-dynamic-container">
            <div class="form-group">
                <label class="col-form-label">Estudantes</label>
                <div id="students-selection-area">
                    <p class="text-muted">Selecione um curso para ver os estudantes disponíveis.</p>
                </div>
            </div>
        </div>');

        // Hidden field to store selected student IDs (will be populated by JavaScript)
        $mform->addElement('hidden', 'studentids_json', '');
        $mform->setType('studentids_json', PARAM_TEXT);

        // Status field for edit mode
        if ($assignment) {
            $status_options = array(
                'active' => 'Ativo',
                'inactive' => 'Inativo'
            );
            $mform->addElement('select', 'status', 'Status', $status_options);
            $mform->setType('status', PARAM_TEXT);
            $mform->setDefault('status', 'active');
        }

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validation for new assignments only
        if (empty($data['studentids']) || empty($data['tutorid'])) {
            return $errors;
        }

        // Check for duplicate assignments
        global $DB;
        foreach ($data['studentids'] as $studentid) {
            $existing = $DB->get_record('local_studenttutor_assign', array(
                'studentid' => $studentid, 
                'tutorid' => $data['tutorid'],
                'courseid' => $data['courseid']
            ));
            if ($existing) {
                $student = $DB->get_record('user', array('id' => $studentid));
                $errors['studentids'] = get_string('assignment_exists_for', 'local_studenttutor', fullname($student));
                break;
            }
        }

        return $errors;
    }

    /**
     * Get users with specific role in a course (or all courses if courseid = 0)
     */
    private function get_users_by_role($role_shortnames, $courseid = 0) {
        global $DB;
        
        $role_list = "'" . implode("','", $role_shortnames) . "'";
        
        if ($courseid > 0) {
            // Get users with role in specific course
            $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email,
                           u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
                    FROM {user} u
                    JOIN {role_assignments} ra ON ra.userid = u.id
                    JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                    JOIN {course} c ON c.id = ctx.instanceid
                    JOIN {role} r ON r.id = ra.roleid
                    WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
                    AND r.shortname IN ($role_list)
                    AND c.id = :courseid
                    ORDER BY u.lastname, u.firstname";
            return $DB->get_records_sql($sql, ['courseid' => $courseid]);
        } else {
            // Get users with role in any course
            $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email,
                           u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
                    FROM {user} u
                    JOIN {role_assignments} ra ON ra.userid = u.id
                    JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                    JOIN {role} r ON r.id = ra.roleid
                    WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
                    AND r.shortname IN ($role_list)
                    ORDER BY u.lastname, u.firstname";
            return $DB->get_records_sql($sql);
        }
    }
}
