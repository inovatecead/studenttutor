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
 * External API for creating assignments
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studenttutor\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use local_studenttutor\assignment_manager;

/**
 * External API for creating assignments
 */
class create_assignment extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'tutorid' => new external_value(PARAM_INT, 'Tutor ID'),
            'studentid' => new external_value(PARAM_INT, 'Student ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_DEFAULT, 0),
            'assignedby' => new external_value(
                PARAM_INT,
                'Deprecated, ignored. The acting user is always recorded.',
                VALUE_DEFAULT,
                0
            ),
        ]);
    }

    /**
     * Create a new assignment
     *
     * @param int $tutorid Tutor ID
     * @param int $studentid Student ID
     * @param int $courseid Course ID
     * @param int $assignedby Assigned by user ID
     * @return array
     */
    public static function execute($tutorid, $studentid, $courseid = 0, $assignedby = null) {
        global $USER;

        // Validate parameters
        // The 'assignedby' parameter is accepted for backward compatibility but is
        // not passed on: the acting user is always recorded as the author.
        $params = self::validate_parameters(self::execute_parameters(), [
            'tutorid' => $tutorid,
            'studentid' => $studentid,
            'courseid' => $courseid,
        ]);

        // Check permissions
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/studenttutor:manageassignments', $context);

        try {
            // Create assignment using manager: (studentid, tutorid, courseid, assignedby).
            $assignmentid = assignment_manager::create_assignment(
                $params['studentid'],
                $params['tutorid'],
                $params['courseid'],
                $USER->id
            );

            if ($assignmentid) {
                return [
                    'success' => true,
                    'assignmentid' => $assignmentid,
                    'message' => get_string('assignment_created', 'local_studenttutor'),
                ];
            } else {
                return [
                    'success' => false,
                    'assignmentid' => 0,
                    'message' => get_string('assignment_creation_failed', 'local_studenttutor'),
                ];
            }
        } catch (\moodle_exception $e) {
            debugging('Exception in external create_assignment: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [
                'success' => false,
                'assignmentid' => 0,
                'message' => get_string('assignment_creation_failed', 'local_studenttutor'),
            ];
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation was successful'),
            'assignmentid' => new external_value(PARAM_INT, 'Assignment ID if created'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
