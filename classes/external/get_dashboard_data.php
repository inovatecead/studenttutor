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
 * External API for dashboard data
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
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
use context_system;

/**
 * External API for dashboard data
 */
class get_dashboard_data extends external_api {

    /**
     * Parameters for get_dashboard_data
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            array(
                'period' => new external_value(PARAM_INT, 'Time period in days', VALUE_DEFAULT, 30),
                'filters' => new external_single_structure(
                    array(
                        'course_id' => new external_value(PARAM_INT, 'Course ID filter', VALUE_OPTIONAL),
                        'tutor_id' => new external_value(PARAM_INT, 'Tutor ID filter', VALUE_OPTIONAL),
                        'status' => new external_value(PARAM_TEXT, 'Status filter', VALUE_OPTIONAL),
                    ), 'Additional filters', VALUE_OPTIONAL
                )
            )
        );
    }

    /**
     * Get dashboard data
     *
     * @param int $period
     * @param array $filters
     * @return array
     */
    public static function execute($period = 30, $filters = array()) {
        global $USER;

        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), array(
            'period' => $period,
            'filters' => $filters
        ));

        // Check capability
        $context = context_system::instance();
        require_capability('local/studenttutor:viewdashboard', $context);

        // Get dashboard data from lib function
        $dashboarddata = local_studenttutor_get_dashboard_data($params['period'], $params['filters']);

        return $dashboarddata;
    }

    /**
     * Return structure for get_dashboard_data
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            array(
                'metrics' => new external_single_structure(
                    array(
                        'active_assignments' => new external_single_structure(
                            array(
                                'value' => new external_value(PARAM_INT, 'Number of active assignments'),
                                'change' => new external_value(PARAM_FLOAT, 'Percentage change', VALUE_OPTIONAL),
                            )
                        ),
                        'completed_assignments' => new external_single_structure(
                            array(
                                'value' => new external_value(PARAM_INT, 'Number of completed assignments'),
                                'change' => new external_value(PARAM_FLOAT, 'Percentage change', VALUE_OPTIONAL),
                            )
                        ),
                        'total_tutors' => new external_single_structure(
                            array(
                                'value' => new external_value(PARAM_INT, 'Total number of tutors'),
                                'change' => new external_value(PARAM_FLOAT, 'Percentage change', VALUE_OPTIONAL),
                            )
                        ),
                        'total_students' => new external_single_structure(
                            array(
                                'value' => new external_value(PARAM_INT, 'Total number of students'),
                                'change' => new external_value(PARAM_FLOAT, 'Percentage change', VALUE_OPTIONAL),
                            )
                        ),
                    )
                ),
                'charts' => new external_single_structure(
                    array(
                        'assignment' => new external_single_structure(
                            array(
                                'labels' => new external_multiple_structure(
                                    new external_value(PARAM_TEXT, 'Chart label')
                                ),
                                'datasets' => new external_multiple_structure(
                                    new external_single_structure(
                                        array(
                                            'label' => new external_value(PARAM_TEXT, 'Dataset label'),
                                            'data' => new external_multiple_structure(
                                                new external_value(PARAM_INT, 'Data point')
                                            ),
                                            'backgroundColor' => new external_value(PARAM_TEXT, 'Background color'),
                                            'borderColor' => new external_value(PARAM_TEXT, 'Border color'),
                                        )
                                    )
                                )
                            )
                        ),
                        'performance' => new external_single_structure(
                            array(
                                'labels' => new external_multiple_structure(
                                    new external_value(PARAM_TEXT, 'Chart label')
                                ),
                                'datasets' => new external_multiple_structure(
                                    new external_single_structure(
                                        array(
                                            'data' => new external_multiple_structure(
                                                new external_value(PARAM_INT, 'Data point')
                                            ),
                                            'backgroundColor' => new external_multiple_structure(
                                                new external_value(PARAM_TEXT, 'Background color')
                                            ),
                                        )
                                    )
                                )
                            )
                        ),
                    )
                ),
                'tables' => new external_single_structure(
                    array(
                        'recent_assignments' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'id' => new external_value(PARAM_INT, 'Assignment ID'),
                                    'title' => new external_value(PARAM_TEXT, 'Assignment title'),
                                    'tutor' => new external_value(PARAM_TEXT, 'Tutor name'),
                                    'student' => new external_value(PARAM_TEXT, 'Student name'),
                                    'status' => new external_value(PARAM_TEXT, 'Assignment status'),
                                    'created' => new external_value(PARAM_TEXT, 'Creation date'),
                                )
                            )
                        ),
                        'top_tutors' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'id' => new external_value(PARAM_INT, 'Tutor ID'),
                                    'name' => new external_value(PARAM_TEXT, 'Tutor name'),
                                    'assignments' => new external_value(PARAM_INT, 'Number of assignments'),
                                    'rating' => new external_value(PARAM_FLOAT, 'Average rating', VALUE_OPTIONAL),
                                )
                            )
                        ),
                    )
                ),
                'alerts' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'type' => new external_value(PARAM_TEXT, 'Alert type (success, warning, danger, info)'),
                            'title' => new external_value(PARAM_TEXT, 'Alert title'),
                            'message' => new external_value(PARAM_TEXT, 'Alert message'),
                            'time' => new external_value(PARAM_TEXT, 'Alert timestamp'),
                        )
                    )
                ),
                'period' => new external_value(PARAM_INT, 'Data period in days'),
                'generated_at' => new external_value(PARAM_INT, 'Generation timestamp'),
            )
        );
    }
}
