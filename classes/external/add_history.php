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
 * External API for adding history entries
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
use local_studenttutor\history_manager;

/**
 * External API for adding history entries
 */
class add_history extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'studentid' => new external_value(PARAM_INT, 'Student ID'),
            'tutorid' => new external_value(PARAM_INT, 'Tutor ID'),
            'activitytype' => new external_value(PARAM_ALPHANUMEXT, 'Activity type shortname (e.g. convocatoria_reuniao)'),
            'title' => new external_value(PARAM_TEXT, 'Deprecated, ignored by this version. Use description.'),
            'description' => new external_value(PARAM_CLEANHTML, 'Activity description'),
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Add a new history entry
     *
     * @param int $studentid Student ID
     * @param int $tutorid Tutor ID
     * @param string $activitytype Activity type
     * @param string $title Activity title
     * @param string $description Activity description
     * @param int $courseid Course ID
     * @return array
     */
    public static function execute($studentid, $tutorid, $activitytype, $title, $description, $courseid = 0) {
        global $USER;

        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'studentid' => $studentid,
            'tutorid' => $tutorid,
            'activitytype' => $activitytype,
            'title' => $title,
            'description' => $description,
            'courseid' => $courseid,
        ]);

        // Check permissions
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/studenttutor:managehistory', $context);

        try {
            // Add history entry using manager: (studentid, tutorid, activitytype, description, courseid, createdby).
            $historyid = history_manager::add_history_entry(
                $params['studentid'],
                $params['tutorid'],
                $params['activitytype'],
                $params['description'],
                $params['courseid'],
                $USER->id
            );

            if ($historyid) {
                return [
                    'success' => true,
                    'historyid' => $historyid,
                    'message' => get_string('history_added', 'local_studenttutor'),
                ];
            } else {
                return [
                    'success' => false,
                    'historyid' => 0,
                    'message' => 'Failed to create history entry',
                ];
            }
        } catch (\Exception $e) {
            debugging('Exception in external add_history: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [
                'success' => false,
                'historyid' => 0,
                'message' => get_string('history_add_error', 'local_studenttutor'),
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
            'historyid' => new external_value(PARAM_INT, 'History entry ID if created'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
        ]);
    }
}
