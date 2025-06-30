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
 * Library functions for Student-Tutor assignment plugin
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add navigation node to course navigation for tutors
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context $context
 */
function local_studenttutor_extend_navigation_course($navigation, $course, $context) {
    global $USER, $DB;
    
    // Check if user is enrolled in this course as teacher/editingteacher
    $roles = get_user_roles($context, $USER->id);
    $is_tutor = false;
    
    foreach ($roles as $role) {
        if ($role->shortname === 'teacher' || $role->shortname === 'editingteacher') {
            $is_tutor = true;
            break;
        }
    }
    
    if ($is_tutor) {
        // Check if this tutor has any students assigned in this course OR globally
        $has_students = $DB->record_exists_sql("
            SELECT 1 FROM {local_studenttutor_assign} a
            WHERE a.tutorid = :tutorid 
            AND (a.courseid = :courseid OR a.courseid = 0)
            AND (a.status = 1 OR a.status = 'active')
        ", ['tutorid' => $USER->id, 'courseid' => $course->id]);
        
        if ($has_students) {
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
    }
}

/**
 * Add navigation to settings menu for administrators
 */
function local_studenttutor_extend_settings_navigation($settingsnav, $context) {
    global $PAGE;
    
    // Only add to course context
    if ($context->contextlevel == CONTEXT_COURSE && $context->instanceid != SITEID) {
        if (has_capability('local/studenttutor:manageassignments', $context)) {
            $node = $settingsnav->add(
                get_string('studenttutor_settings', 'local_studenttutor'),
                new moodle_url('/local/studenttutor/course_manage.php', ['courseid' => $context->instanceid]),
                navigation_node::TYPE_CUSTOM
            );
        }
    }
}
