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
 * Debug permissions and capabilities
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

require_once($CFG->libdir . '/clilib.php');

cli_heading('Debug Permissions - Student Tutor Plugin');

// Check if capabilities are defined
cli_heading('1. Checking Capabilities');
$capabilities = [
    'local/studenttutor:view',
    'local/studenttutor:assign',
    'local/studenttutor:addhistory',
    'local/studenttutor:managehistory',
    'local/studenttutor:viewreports'
];

foreach ($capabilities as $cap) {
    try {
        $exists = $DB->record_exists('capabilities', ['name' => $cap]);
        echo "$cap: " . ($exists ? "EXISTS" : "NOT FOUND") . "\n";
    } catch (Exception $e) {
        echo "$cap: ERROR - " . $e->getMessage() . "\n";
    }
}

// Check course context
cli_heading('2. Checking Course Context');
$courses = $DB->get_records_sql("SELECT * FROM {course} WHERE id > 1 LIMIT 5");
foreach ($courses as $course) {
    echo "Course: {$course->fullname} (ID: {$course->id})\n";
    break; // Only check first course
}

// Check role assignments
cli_heading('3. Checking Role Assignments');
$roles = $DB->get_records('role', null, 'sortorder');
foreach ($roles as $role) {
    if (in_array($role->shortname, ['teacher', 'editingteacher', 'student'])) {
        echo "Role: {$role->name} ({$role->shortname})\n";
        
        $role_capabilities = $DB->get_records('role_capabilities', ['roleid' => $role->id]);
        
        echo "Total capabilities for this role: " . count($role_capabilities) . "\n";
        
        $our_caps = [];
        foreach ($role_capabilities as $cap) {
            if (strpos($cap->capability, 'local/studenttutor:') === 0) {
                $our_caps[] = $cap->capability . " = " . $cap->permission;
            }
        }
        
        if (!empty($our_caps)) {
            foreach ($our_caps as $cap) {
                echo "  $cap\n";
            }
        } else {
            echo "  No local/studenttutor capabilities found for this role\n";
        }
        echo "\n";
    }
}

// Check studenttutor assignments
cli_heading('4. Checking Student-Tutor Assignments');
$assignments = $DB->get_records_sql("
    SELECT a.*, 
           u1.firstname as student_firstname, u1.lastname as student_lastname,
           u2.firstname as tutor_firstname, u2.lastname as tutor_lastname,
           c.fullname as course_name
    FROM {local_studenttutor_assign} a
    LEFT JOIN {user} u1 ON u1.id = a.studentid
    LEFT JOIN {user} u2 ON u2.id = a.tutorid
    LEFT JOIN {course} c ON c.id = a.courseid
    LIMIT 10
");

echo "Total assignments: " . count($assignments) . "\n";

if (!empty($assignments)) {
    foreach ($assignments as $assignment) {
        $course_name = $assignment->courseid == 0 ? 'GLOBAL' : $assignment->course_name;
        echo "ID: {$assignment->id} | Student: {$assignment->student_firstname} {$assignment->student_lastname} | ";
        echo "Tutor: {$assignment->tutor_firstname} {$assignment->tutor_lastname} | ";
        echo "Course: {$course_name} | Status: {$assignment->status} | ";
        echo "Time Assigned: " . ($assignment->timeassigned ? date('Y-m-d H:i:s', $assignment->timeassigned) : 'Not set') . "\n";
    }
}

// Check history entries
cli_heading('5. Checking History Entries');
$history = $DB->get_records_sql("
    SELECT h.*, 
           u1.firstname as student_firstname, u1.lastname as student_lastname,
           u2.firstname as tutor_firstname, u2.lastname as tutor_lastname,
           c.fullname as course_name
    FROM {local_studenttutor_history} h
    LEFT JOIN {user} u1 ON u1.id = h.studentid
    LEFT JOIN {user} u2 ON u2.id = h.tutorid
    LEFT JOIN {course} c ON c.id = h.courseid
    ORDER BY h.timecreated DESC
    LIMIT 10
");

echo "Total history entries: " . count($history) . "\n";

if (!empty($history)) {
    foreach ($history as $entry) {
        echo "ID: {$entry->id} | Student: {$entry->student_firstname} {$entry->student_lastname} | ";
        echo "Tutor: {$entry->tutor_firstname} {$entry->tutor_lastname} | ";
        echo "Course: {$entry->course_name} | Activity: {$entry->activitytype} | ";
        echo "Title: {$entry->title} | Created: " . date('Y-m-d H:i:s', $entry->timecreated) . "\n";
    }
}

cli_heading('Debug completed.');
