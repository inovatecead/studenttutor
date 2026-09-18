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
 * External API for getting history entries
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
use local_studenttutor\history_manager;

/**
 * External API for getting history entries
 */
class get_history extends external_api {
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
                'activitytype' => new external_value(PARAM_ALPHANUMEXT, 'Activity type', VALUE_OPTIONAL),
            ], 'Filters for history entries', VALUE_DEFAULT, []),
            'sort' => new external_value(PARAM_TEXT, 'Sort order', VALUE_DEFAULT, 'h.timecreated DESC'),
            'limitfrom' => new external_value(PARAM_INT, 'Start position for pagination', VALUE_DEFAULT, 0),
            'limitnum' => new external_value(PARAM_INT, 'Number of records to return', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Get history entries with details
     *
     * @param array $filters Filters to apply
     * @param string $sort Sort order
     * @param int $limitfrom Start position
     * @param int $limitnum Number of records
     * @return array
     */
    public static function execute($filters = [], $sort = 'h.timecreated DESC', $limitfrom = 0, $limitnum = 0) {
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
        require_capability('local/studenttutor:viewhistory', $context);

        // Get history using manager
        $history = history_manager::get_history_with_details(
            $params['filters'],
            $params['sort'],
            $params['limitfrom'],
            $params['limitnum']
        );

        // Format results.
        $result = [];
        foreach ($history as $entry) {
            $result[] = [
                'id' => $entry->id,
                'studentid' => $entry->studentid,
                'tutorid' => $entry->tutorid,
                'courseid' => $entry->courseid,
                'activitytype' => $entry->activitytype,
                'description' => $entry->description,
                'timecreated' => $entry->timecreated,
                'timemodified' => $entry->timemodified,
                'createdby' => $entry->createdby,
                // See get_assignments: all name fields are copied so that the display
                // name follows the site settings and fullname() does not warn.
                'tutor_name' => fullname(username_load_fields_from_object((object)[], $entry, 'tutor_')),
                'student_name' => fullname(username_load_fields_from_object((object)[], $entry, 'student_')),
                'course_name' => $entry->course_name ?: get_string('general', 'local_studenttutor'),
            ];
        }

        return ['history' => $result];
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'history' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'History entry ID'),
                    'studentid' => new external_value(PARAM_INT, 'Student ID'),
                    'tutorid' => new external_value(PARAM_INT, 'Tutor ID'),
                    'courseid' => new external_value(PARAM_INT, 'Course ID'),
                    'activitytype' => new external_value(PARAM_ALPHANUMEXT, 'Activity type'),
                    'description' => new external_value(PARAM_RAW, 'Activity description'),
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
