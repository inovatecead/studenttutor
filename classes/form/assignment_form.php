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
 * Form for creating and editing student-tutor assignments.
 *
 * The class used to live in classes/assignment_form.php without a namespace, which
 * meant it could not be autoloaded; it is now a regular namespaced class.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studenttutor\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating and editing student-tutor assignments.
 */
class assignment_form extends \moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $assignment = $this->_customdata['assignment'];
        $courseid = isset($this->_customdata['courseid']) ? $this->_customdata['courseid'] : 0;

        // Get courses first.
        $courses = $DB->get_records('course', ['visible' => 1], 'fullname', 'id, fullname');
        $course_options = [0 => get_string('all_courses', 'local_studenttutor')];
        foreach ($courses as $course) {
            if ($course->id != SITEID) {
                $course_options[$course->id] = $course->fullname;
            }
        }

        // Get tutors using configured roles instead of hardcoded ones.
        $tutor_roles = local_studenttutor_get_tutor_roles();
        $tutors = $this->get_users_by_role($tutor_roles, $courseid);
        $tutor_options = [];
        foreach ($tutors as $user) {
            $fullname = fullname($user);
            $tutor_options[$user->id] = $fullname;
        }

        // Form fields.
        $mform->addElement('autocomplete', 'courseid', 'Selecionar Curso', $course_options, [
            'multiple' => false,
            'placeholder' => 'Digite para buscar um curso...',
            'showsuggestions' => true,
            'tags' => false,
            'noselectionstring' => 'Todos os cursos',
        ]);
        $mform->setType('courseid', PARAM_INT);
        $mform->addHelpButton('courseid', 'course_help', 'local_studenttutor');

        $mform->addElement('autocomplete', 'tutorid', 'Selecionar Tutor', $tutor_options, [
            'multiple' => false,
            'placeholder' => 'Digite para buscar um tutor...',
            'showsuggestions' => true,
            'tags' => false,
        ]);
        $mform->setType('tutorid', PARAM_INT);
        $mform->addRule('tutorid', 'Campo obrigatório', 'required', null, 'client');

        // Students are loaded dynamically by JavaScript (scripts/assign_simple.js),
        // which requests them through ajax_get_students.php.
        $mform->addElement('html', '<div id="students-dynamic-container">
            <div class="form-group">
                <label class="col-form-label">Estudantes</label>
                <div id="students-selection-area">
                    <p class="text-muted">Selecione um curso para ver os estudantes disponíveis.</p>
                </div>
            </div>
        </div>');

        // Hidden field to store selected student IDs (populated by JavaScript).
        $mform->addElement('hidden', 'studentids_json', '');
        $mform->setType('studentids_json', PARAM_TEXT);

        // Status field for edit mode.
        if ($assignment) {
            $status_options = [
                'active' => 'Ativo',
                'inactive' => 'Inativo',
            ];
            $mform->addElement('select', 'status', 'Status', $status_options);
            $mform->setType('status', PARAM_TEXT);
            $mform->setDefault('status', 'active');
        }

        $this->add_action_buttons();
    }

    /**
     * Validate the submitted data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array List of validation errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validation for new assignments only.
        if (empty($data['studentids']) || empty($data['tutorid'])) {
            return $errors;
        }

        // Check for duplicate assignments.
        global $DB;
        foreach ($data['studentids'] as $studentid) {
            $existing = $DB->get_record('local_studenttutor_assign', [
                'studentid' => $studentid,
                'tutorid' => $data['tutorid'],
                'courseid' => $data['courseid'],
            ]);
            if ($existing) {
                $student = $DB->get_record('user', ['id' => $studentid]);
                $errors['studentids'] = get_string('assignment_exists_for', 'local_studenttutor', fullname($student));
                break;
            }
        }

        return $errors;
    }

    /**
     * Get the users holding the given roles, optionally restricted to one course.
     *
     * @param array $role_shortnames Role shortnames to look for.
     * @param int $courseid Course id, or 0 for every course.
     * @return array Users keyed by id.
     */
    private function get_users_by_role($role_shortnames, $courseid = 0) {
        global $DB;

        if (empty($role_shortnames)) {
            return [];
        }

        [$rolesql, $roleparams] = $DB->get_in_or_equal($role_shortnames, SQL_PARAMS_NAMED, 'role');

        $params = array_merge(['ctxlevel' => CONTEXT_COURSE], $roleparams);

        // The name fields complete the user object, so that fullname() can honour the
        // site settings for phonetic, middle and alternate names.
        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname,
                       u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
                  FROM {user} u
                  JOIN {role_assignments} ra ON ra.userid = u.id
                  JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :ctxlevel
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
                   AND r.shortname $rolesql";

        if ($courseid > 0) {
            $sql .= " AND ctx.instanceid = :courseid";
            $params['courseid'] = $courseid;
        }

        $sql .= " ORDER BY u.lastname, u.firstname";

        return $DB->get_records_sql($sql, $params);
    }
}
