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
 * External API for getting assignments
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
use external_multiple_structure;
use local_studenttutor\assignment_manager;

/**
 * External API for getting assignments
 */
class get_assignments extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'filters' => new external_single_structure([
                'studentid' => new external_value(PARAM_INT, 'Student ID', VALUE_OPTIONAL),
                'tutorid' => new external_value(PARAM_INT, 'Tutor ID', VALUE_OPTIONAL),
                'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_OPTIONAL),
                'status' => new external_value(PARAM_ALPHA, 'Assignment status', VALUE_OPTIONAL),
            ], 'Filters for assignments', VALUE_DEFAULT, []),
            'sort' => new external_value(PARAM_TEXT, 'Sort order', VALUE_DEFAULT, 'a.timeassigned DESC'),
            'limitfrom' => new external_value(PARAM_INT, 'Start position for pagination', VALUE_DEFAULT, 0),
            'limitnum' => new external_value(PARAM_INT, 'Number of records to return', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Get assignments with details
     *
     * @param array $filters Filters to apply
     * @param string $sort Sort order
     * @param int $limitfrom Start position
     * @param int $limitnum Number of records
     * @return array
     */
    public static function execute($filters = [], $sort = 'a.timeassigned DESC', $limitfrom = 0, $limitnum = 0) {
        global $USER;

        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'filters' => $filters,
            'sort' => $sort,
            'limitfrom' => $limitfrom,
            'limitnum' => $limitnum,
        ]);

        // Check permissions
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/studenttutor:viewassignments', $context);

        // Get assignments using manager
        $assignments = assignment_manager::get_all_assignments_with_details(
            $params['filters'],
            $params['sort'],
            $params['limitfrom'],
            $params['limitnum']
        );

        // Format results. The public field names are kept for API compatibility:
        // timecreated maps to the real timeassigned column and createdby to assignedby.
        $result = [];
        foreach ($assignments as $assignment) {
            $result[] = [
                'id' => $assignment->id,
                'studentid' => $assignment->studentid,
                'tutorid' => $assignment->tutorid,
                'courseid' => $assignment->courseid,
                'status' => $assignment->status,
                'timecreated' => $assignment->timeassigned,
                'timemodified' => $assignment->timemodified,
                'createdby' => $assignment->assignedby,
                // Every name field selected by username_load_fields_from_object() is
                // copied, so the display name honours the site settings and fullname()
                // does not warn about missing name fields.
                'tutor_name' => fullname(username_load_fields_from_object((object)[], $assignment, 'tutor_')),
                'student_name' => fullname(username_load_fields_from_object((object)[], $assignment, 'student_')),
                'course_name' => $assignment->course_name ?: get_string('all_courses', 'local_studenttutor'),
            ];
        }

        return ['assignments' => $result];
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'assignments' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Assignment ID'),
                    'studentid' => new external_value(PARAM_INT, 'Student ID'),
                    'tutorid' => new external_value(PARAM_INT, 'Tutor ID'),
                    'courseid' => new external_value(PARAM_INT, 'Course ID'),
                    'status' => new external_value(PARAM_ALPHA, 'Assignment status'),
                    'timecreated' => new external_value(PARAM_INT, 'Time created'),
                    'timemodified' => new external_value(PARAM_INT, 'Time modified'),
                    'createdby' => new external_value(PARAM_INT, 'Created by user ID'),
                    'tutor_name' => new external_value(PARAM_TEXT, 'Tutor full name'),
                    'student_name' => new external_value(PARAM_TEXT, 'Student full name'),
                    'course_name' => new external_value(PARAM_TEXT, 'Course name'),
                ])
            ),
        ]);
    }
}
