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
 * Data generator for the Nexo Tutoria Acadêmica plugin.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Generator for assignments, history entries and activity types.
 */
class local_studenttutor_generator extends component_generator_base {
    /**
     * Create a student-tutor assignment record.
     *
     * @param int|null $studentid Student id (a new user is created when omitted).
     * @param int|null $tutorid Tutor id (a new user is created when omitted).
     * @param int $courseid Course id, 0 for a global assignment.
     * @param string $status Assignment status.
     * @return int The assignment id.
     */
    public function create_assignment($studentid = null, $tutorid = null, $courseid = 0, $status = 'active') {
        global $DB, $USER;

        $studentid = $studentid ?: $this->datagenerator->create_user()->id;
        $tutorid = $tutorid ?: $this->datagenerator->create_user()->id;

        $assignment = (object)[
            'studentid' => $studentid,
            'tutorid' => $tutorid,
            'courseid' => $courseid,
            'assignedby' => $USER->id,
            'timeassigned' => time(),
            'timemodified' => time(),
            'status' => $status,
        ];

        return $DB->insert_record('local_studenttutor_assign', $assignment);
    }

    /**
     * Create an activity type.
     *
     * @param string|null $name Display name.
     * @param string|null $shortname Unique shortname.
     * @param int $active Whether the type is active.
     * @param int|null $sortorder Sort order.
     * @return int The activity type id.
     */
    public function create_activity_type($name = null, $shortname = null, $active = 1, $sortorder = null) {
        global $DB;

        $shortname = $shortname ?: 'tipo' . random_string(8);

        $record = (object)[
            'name' => $name ?: 'Tipo de teste',
            'shortname' => $shortname,
            'description' => 'Tipo criado pelos testes automatizados',
            'icon' => 'fa-circle',
            'color' => '#007bff',
            'active' => $active,
            'sortorder' => $sortorder === null ? 0 : $sortorder,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        return $DB->insert_record('local_studenttutor_activity_types', $record);
    }

    /**
     * Create a tutoring history entry.
     *
     * @param int|null $studentid Student id.
     * @param int|null $tutorid Tutor id.
     * @param string $activitytype Activity type shortname (created when unknown).
     * @param string $description Tutoring notes.
     * @param int $courseid Course id.
     * @param int|null $createdby Author id.
     * @param int|null $activitydate Date the activity took place.
     * @return int The history entry id.
     */
    public function create_history_entry(
        $studentid = null,
        $tutorid = null,
        $activitytype = 'tipoteste',
        $description = 'Registro criado pelos testes automatizados',
        $courseid = 0,
        $createdby = null,
        $activitydate = null
    ) {
        global $DB, $USER;

        $studentid = $studentid ?: $this->datagenerator->create_user()->id;
        $tutorid = $tutorid ?: $this->datagenerator->create_user()->id;

        // The shortname must exist in the activity types table to pass validation.
        if (!$DB->record_exists('local_studenttutor_activity_types', ['shortname' => $activitytype])) {
            $this->create_activity_type('Tipo de teste', $activitytype);
        }

        $entry = (object)[
            'studentid' => $studentid,
            'tutorid' => $tutorid,
            'courseid' => $courseid,
            'activitytype' => $activitytype,
            'description' => $description,
            'timecreated' => time(),
            'timemodified' => time(),
            'createdby' => $createdby ?: $USER->id,
            'activity_date' => $activitydate ?: time(),
        ];

        return $DB->insert_record('local_studenttutor_history', $entry);
    }
}
