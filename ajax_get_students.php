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
 * AJAX endpoint to get students by course
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Security checks
require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/studenttutor:manageassignments', $context);

// Get parameters
$courseid = required_param('courseid', PARAM_INT);

header('Content-Type: application/json');

try {
    global $DB;

    // Name fields come from \core_user\fields so that fullname() receives every field it
    // needs and the label honours the site settings for phonetic/middle/alternate names.
    $namefields = \core_user\fields::for_name()->get_sql('u', false, '')->selects;

    if ($courseid == 0) {
        // All courses - get all students with tutor count and groups
        $students = $DB->get_records_sql("
            SELECT DISTINCT u.id, u.email{$namefields},
                   COUNT(DISTINCT lsa.id) as tutor_count,
                   GROUP_CONCAT(DISTINCT CONCAT(tu.firstname, ' ', tu.lastname) SEPARATOR ', ') as tutor_names_concat,
                   GROUP_CONCAT(DISTINCT CONCAT(g.name, ' (', c.shortname, ')') SEPARATOR ', ') as group_names_concat
            FROM {user} u
            JOIN {role_assignments} ra ON ra.userid = u.id
            JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
            JOIN {role} r ON r.id = ra.roleid
            JOIN {course} c ON c.id = ctx.instanceid
            LEFT JOIN {local_studenttutor_assign} lsa ON lsa.studentid = u.id AND lsa.status = 'active'
            LEFT JOIN {user} tu ON tu.id = lsa.tutorid
            LEFT JOIN {groups_members} gm ON gm.userid = u.id
            LEFT JOIN {groups} g ON g.id = gm.groupid AND g.courseid = c.id
            WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
            AND r.shortname = ?
            GROUP BY u.id, u.firstname, u.lastname, u.email
            ORDER BY
                CASE
                    WHEN COUNT(DISTINCT lsa.id) = 0 THEN 1  -- Sem tutores primeiro
                    WHEN COUNT(DISTINCT lsa.id) = 1 THEN 2  -- Com 1 tutor segundo
                    ELSE 3                                  -- Com múltiplos tutores por último
                END,
                u.lastname, u.firstname
        ", ['student']);
    } else {
        // Specific course - get students with tutor count and groups for this course
        $students = $DB->get_records_sql("
            SELECT DISTINCT u.id, u.email{$namefields},
                   COUNT(DISTINCT lsa.id) as tutor_count,
                   GROUP_CONCAT(DISTINCT CONCAT(tu.firstname, ' ', tu.lastname) SEPARATOR ', ') as tutor_names_concat,
                   GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') as group_names_concat,
                   GROUP_CONCAT(DISTINCT g.id SEPARATOR ', ') as group_ids_concat
            FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {role_assignments} ra ON ra.userid = u.id
            JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
            JOIN {role} r ON r.id = ra.roleid
            LEFT JOIN {local_studenttutor_assign} lsa ON lsa.studentid = u.id
                      AND lsa.courseid = ? AND lsa.status = 'active'
            LEFT JOIN {user} tu ON tu.id = lsa.tutorid
            LEFT JOIN {groups_members} gm ON gm.userid = u.id
            LEFT JOIN {groups} g ON g.id = gm.groupid AND g.courseid = ?
            WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
            AND e.courseid = ?
            AND ctx.instanceid = ?
            AND r.shortname = ?
            GROUP BY u.id, u.firstname, u.lastname, u.email
            ORDER BY
                CASE
                    WHEN COUNT(DISTINCT lsa.id) = 0 THEN 1  -- Sem tutores primeiro
                    WHEN COUNT(DISTINCT lsa.id) = 1 THEN 2  -- Com 1 tutor segundo
                    ELSE 3                                  -- Com múltiplos tutores por último
                END,
                COALESCE(g.name, 'ZZ_Sem_Grupo'),  -- Estudantes sem grupo por último
                u.lastname, u.firstname
        ", [$courseid, $courseid, $courseid, $courseid, 'student']);
    }

    // Format response with enhanced sorting information
    $response = [];
    $summary = [
        'without_tutors' => 0,
        'with_one_tutor' => 0,
        'with_multiple_tutors' => 0,
        'total_students' => count($students),
        'without_groups' => 0,
        'with_groups' => 0,
    ];

    foreach ($students as $student) {
        $tutor_count = (int)$student->tutor_count;
        $tutor_names = [];
        $group_names = [];
        $group_ids = [];

        // Parse tutor names from concatenated string
        if (!empty($student->tutor_names_concat)) {
            $tutor_names = explode(', ', $student->tutor_names_concat);
            $tutor_names = array_filter($tutor_names); // Remove empty values
        }

        // Parse group names from concatenated string
        if (!empty($student->group_names_concat)) {
            $group_names = explode(', ', $student->group_names_concat);
            $group_names = array_filter($group_names); // Remove empty values
        }

        // Parse group IDs if available
        if (!empty($student->group_ids_concat)) {
            $group_ids = explode(', ', $student->group_ids_concat);
            $group_ids = array_filter($group_ids); // Remove empty values
        }

        // Determine category for summary
        if ($tutor_count == 0) {
            $summary['without_tutors']++;
            $category = 'without_tutors';
            $category_label = 'Sem tutores';
        } else if ($tutor_count == 1) {
            $summary['with_one_tutor']++;
            $category = 'with_one_tutor';
            $category_label = 'Com 1 tutor';
        } else {
            $summary['with_multiple_tutors']++;
            $category = 'with_multiple_tutors';
            $category_label = 'Com múltiplos tutores';
        }

        // Count groups summary
        if (empty($group_names)) {
            $summary['without_groups']++;
        } else {
            $summary['with_groups']++;
        }

        $response[] = [
            'id' => $student->id,
            'name' => fullname($student),
            'email' => $student->email,
            'tutor_count' => $tutor_count,
            'tutor_names' => $tutor_names,
            'group_names' => $group_names,
            'group_ids' => $group_ids,
            'group_display' => !empty($group_names) ? implode(', ', $group_names) : 'Sem grupo',
            'has_tutors' => $tutor_count > 0,
            'has_groups' => !empty($group_names),
            'category' => $category,
            'category_label' => $category_label,
            'sort_priority' => $tutor_count == 0 ? 1 : ($tutor_count == 1 ? 2 : 3),
        ];
    }

    echo json_encode([
        'success' => true,
        'students' => $response,
        'summary' => $summary,
    ]);
} catch (Exception $e) {
    debugging('Error loading students list: ' . $e->getMessage(), DEBUG_DEVELOPER);
    echo json_encode([
        'success' => false,
        'error' => get_string('error'),
    ]);
}
