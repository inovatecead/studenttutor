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
 * Library functions for the Nexo Tutoria Acadêmica plugin.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/accesslib.php');

/**
 * Add the "My students" node to the course navigation for tutors.
 *
 * @param navigation_node $navigation The navigation node to extend.
 * @param stdClass $course The course record.
 * @param context $context The course context.
 */
function local_studenttutor_extend_navigation_course($navigation, $course, $context) {
    global $USER;

    if (!local_studenttutor_is_tutor($USER->id, $course->id)) {
        return;
    }

    $url = new moodle_url('/local/studenttutor/course_view.php', ['courseid' => $course->id]);
    $node = $navigation->add(
        get_string('my_students', 'local_studenttutor'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'studenttutor_mystudents',
        new pix_icon('i/users', '')
    );
    $node->showinflatnavigation = true;
}

/**
 * Get the configured tutor role shortnames.
 *
 * @return array List of role shortnames (may be empty).
 */
function local_studenttutor_get_tutor_roles() {
    $roles = [];

    // Primary tutor role (default: tutortematico).
    $primaryrole = get_config('local_studenttutor', 'tutor_role');
    if (empty($primaryrole)) {
        $primaryrole = 'tutortematico';
    }
    $roles[] = $primaryrole;

    // Additional tutor roles, comma separated.
    $additionalroles = get_config('local_studenttutor', 'additional_tutor_roles');
    if (!empty($additionalroles)) {
        foreach (explode(',', $additionalroles) as $role) {
            $roles[] = trim($role);
        }
    }

    // Drop empty values and duplicates.
    $roles = array_filter(array_unique($roles), function ($role) {
        return $role !== '';
    });

    return array_values($roles);
}

/**
 * Check whether a user holds a configured tutor role.
 *
 * @param int $userid User ID.
 * @param int $courseid Course ID to scope the check to; 0 checks any course.
 * @return bool True if the user is a tutor.
 */
function local_studenttutor_is_tutor($userid, $courseid = 0) {
    global $DB;

    // Intentionally not cached: a static cache cannot be invalidated when role
    // assignments change, and it leaks state between tests because Moodle reuses
    // the same ids after each rollback.
    $roles = local_studenttutor_get_tutor_roles();
    if (empty($roles)) {
        return false;
    }

    [$rolesql, $roleparams] = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'role');

    $params = array_merge(['ctxlevel' => CONTEXT_COURSE, 'userid' => $userid], $roleparams);

    $coursesql = '';
    if ($courseid > 0) {
        $coursesql = ' AND ctx.instanceid = :courseid';
        $params['courseid'] = $courseid;
    }

    $sql = "SELECT 1
              FROM {role_assignments} ra
              JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :ctxlevel
              JOIN {role} r ON r.id = ra.roleid
             WHERE ra.userid = :userid
               AND r.shortname $rolesql
               $coursesql";

    return $DB->record_exists_sql($sql, $params);
}
