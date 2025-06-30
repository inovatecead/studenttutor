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
 * Language strings for Student-Tutor assignment plugin
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Student-Tutor Assignment';

// Navigation
$string['manage_assignments'] = 'Manage Student-Tutor Assignments';
$string['view_history'] = 'View Tutoring History';
$string['my_students'] = 'My Students';

// Page titles
$string['assignments_title'] = 'Student-Tutor Assignments';
$string['history_title'] = 'Tutoring History';
$string['add_assignment_title'] = 'Assign Student to Tutor';
$string['add_history_title'] = 'Add Tutoring Activity';

// Form labels
$string['select_tutor'] = 'Select Tutor';
$string['select_student'] = 'Select Student';
$string['select_course'] = 'Select Course';
$string['action_type'] = 'Activity Type';
$string['activity_title'] = 'Activity Title';
$string['description'] = 'Description';

// Action types
$string['action_meeting'] = 'Meeting';
$string['action_email'] = 'Email Communication';
$string['action_feedback'] = 'Feedback';
$string['action_assessment'] = 'Assessment Review';
$string['action_phone'] = 'Phone Call';
$string['action_other'] = 'Other';

// Filter options
$string['filters'] = 'Filters';
$string['filter'] = 'Filter';
$string['clear'] = 'Clear';
$string['all_tutors'] = 'All Tutors';
$string['all_students'] = 'All Students';

// Messages
$string['assignment_created'] = 'Student successfully assigned to tutor';
$string['assignment_updated'] = 'Assignment updated successfully';
$string['assignment_deleted'] = 'Assignment removed successfully';
$string['history_added'] = 'Activity added to history successfully';

// Errors
$string['error_no_permission'] = 'You do not have permission to perform this action';
$string['error_invalid_user'] = 'Invalid user selected';
$string['error_invalid_course'] = 'Invalid course selected';
$string['error_assignment_exists'] = 'This student is already assigned to this tutor in this course';

// Table headers
$string['tutor'] = 'Tutor';
$string['student'] = 'Student';
$string['course'] = 'Course';
$string['assigned_date'] = 'Assigned Date';
$string['actions'] = 'Actions';
$string['date'] = 'Date';
$string['activity'] = 'Activity';

// Buttons
$string['assign_student'] = 'Assign Student';
$string['add_activity'] = 'Add Activity';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['view_details'] = 'View Details';
$string['back'] = 'Back';

// Reports
$string['reports'] = 'Reports';
$string['summary_report'] = 'Summary Report';
$string['assignments_report'] = 'Assignments Report';
$string['history_report'] = 'History Report';
$string['statistic'] = 'Statistic';
$string['value'] = 'Value';
$string['total_assignments'] = 'Total Assignments';
$string['active_assignments'] = 'Active Assignments';
$string['total_activities'] = 'Total Activities';
$string['unique_tutors'] = 'Unique Tutors';
$string['unique_students'] = 'Unique Students';
$string['recent_activity'] = 'Recent Activity (Last 7 days)';
$string['activities_count'] = 'Activities Count';
$string['no_recent_activity'] = 'No recent activity found';
$string['download_assignments'] = 'Download Assignments Report';
$string['download_history'] = 'Download History Report';
$string['all_courses'] = 'All Courses';
$string['activity_type'] = 'Activity Type';
$string['unassign'] = 'Unassign';

// Status strings
$string['status_active'] = 'Active';
$string['status_inactive'] = 'Inactive';
$string['status_completed'] = 'Completed';

// Activity types for reports
$string['activity_meeting'] = 'Meeting';
$string['activity_email'] = 'Email Communication';
$string['activity_feedback'] = 'Feedback';
$string['activity_assessment'] = 'Assessment Review';
$string['activity_guidance'] = 'Academic Guidance';
$string['activity_other'] = 'Other';

// Additional messages
$string['noassignments'] = 'No student-tutor assignments found.';
$string['nohistory'] = 'No tutoring activities found.';

// Settings
$string['settings'] = 'Settings';
$string['general_settings'] = 'General Settings';
$string['general_settings_desc'] = 'Configure general settings for the Student-Tutor plugin';
$string['enable_plugin'] = 'Enable Plugin';
$string['enable_plugin_desc'] = 'Enable or disable the Student-Tutor plugin functionality';
$string['max_assignments'] = 'Maximum Assignments';
$string['max_assignments_desc'] = 'Maximum number of students that can be assigned to a single tutor';
$string['tutor_role'] = 'Tutor Role Short Name';
$string['tutor_role_desc'] = 'Short name of the role that should be considered as tutors (e.g., tutortematico, teacher, editingteacher)';
$string['additional_tutor_roles'] = 'Additional Tutor Roles';
$string['additional_tutor_roles_desc'] = 'Comma-separated list of additional role short names that should be considered as tutors (e.g., teacher,editingteacher)';

// Additional form labels
$string['add_assignment'] = 'Add Assignment';
$string['edit_assignment'] = 'Edit Assignment';
$string['assignment_created'] = 'Assignment created successfully';
$string['assignment_updated'] = 'Assignment updated successfully';
$string['assignment_exists'] = 'This assignment already exists';
$string['add_history_entry'] = 'Add History Entry';
$string['edit_assignment_info'] = 'When editing an assignment, only the course can be changed. The tutor and student cannot be modified.';

// Table headers
$string['tutor'] = 'Tutor';
$string['student'] = 'Student';
$string['course'] = 'Course';
$string['date_assigned'] = 'Date Assigned';
$string['status'] = 'Status';
$string['actions'] = 'Actions';
$string['date'] = 'Date';
$string['activity_type'] = 'Activity Type';

// Status
$string['active'] = 'Active';
$string['inactive'] = 'Inactive';

// Messages
$string['no_assignments'] = 'No assignments found';
$string['no_history'] = 'No history entries found';
$string['all_courses'] = 'All Courses';
$string['general'] = 'General';

// Events
$string['event_assignment_created'] = 'Assignment created';
$string['event_assignment_updated'] = 'Assignment updated';

// Errors
$string['error_no_permission'] = 'You do not have permission to perform this action';
$string['error_invalid_assignment'] = 'Invalid assignment ID';
$string['assignmentnotfound'] = 'Assignment not found';
$string['assignmentalreadyexists'] = 'This assignment already exists';
$string['assignment_creation_failed'] = 'Failed to create assignment';
$string['assignment_update_failed'] = 'Failed to update assignment';

// History management
$string['confirm_delete_history'] = 'Are you sure you want to delete this history entry? This action cannot be undone.';
$string['history_deleted_success'] = 'History entry deleted successfully';
$string['history_delete_error'] = 'Error deleting history entry';
$string['history_not_found'] = 'History entry not found';
$string['edit_history'] = 'Edit History Entry';
$string['history_updated_success'] = 'History entry updated successfully';
$string['history_update_error'] = 'Error updating history entry';
$string['editing_history_for'] = 'Editing history entry for {$a}';

// Course integration
$string['my_students_in_course'] = 'My Students in {$a}';
$string['no_students_assigned'] = 'No students assigned to you in this course';
$string['back_to_course'] = 'Back to Course';
$string['add_history_entry'] = 'Add Tutoring Activity';
$string['add_interaction'] = 'Add Interaction';
$string['send_message'] = 'Send Message';
$string['last_contact'] = 'Last Contact';
$string['total_interactions'] = 'Total Interactions';
$string['never'] = 'Never';
$string['quick_stats'] = 'Quick Statistics';
$string['stats_total_students'] = 'Total students: {$a}';
$string['stats_total_interactions'] = 'Total interactions: {$a}';
$string['stats_recent_contact'] = 'Students contacted in last 7 days: {$a}';
$string['history_added_success'] = 'Tutoring activity added successfully';
$string['history_add_error'] = 'Error adding tutoring activity';
$string['adding_history_for'] = 'Adding tutoring activity for: {$a}';
$string['access_denied'] = 'Access denied';
$string['studenttutor_settings'] = 'Student-Tutor Management';
$string['other'] = 'Other';
$string['student_not_assigned'] = 'This student is not assigned to you';
$string['history_for_student'] = 'Tutoring History for {$a}';
$string['back_to_students'] = 'Back to My Students';
$string['no_history_entries'] = 'No tutoring activities recorded yet';
$string['interaction_summary'] = 'Interaction Summary';
$string['first_contact'] = 'First contact';
$string['interaction_types'] = 'Types of interactions';
$string['confirm_delete_assignment'] = 'Are you sure you want to delete this assignment?';
$string['assignment_deleted'] = 'Assignment deleted successfully';
$string['error_deleting_assignment'] = 'Error deleting assignment';

// Filter strings
$string['all_activity_types'] = 'All Activity Types';
$string['date_from'] = 'Date From';
$string['date_to'] = 'Date To';

// Interface strings for reports
$string['reports_title'] = 'Tutoring Reports';
$string['filters_active'] = 'Active filters';
$string['total_records'] = 'Total records found';
$string['no_filters_active'] = 'No active filters';
$string['select_tutors'] = 'Select tutors...';
$string['select_students'] = 'Select students...';
$string['back_to_assignments'] = 'Back to Assignments';

// Multiple assignment messages
$string['assignment_exists_for'] = 'Assignment already exists for student: {$a}';
$string['assignments_created_multiple'] = '{$a} assignments created successfully';
$string['assignments_partially_created'] = '{$a->success} assignments created successfully. Error for: {$a->errors}';
$string['select_students'] = 'Select students';

// Assignment management strings
$string['edit_assignment'] = 'Edit Assignment';
$string['assignment_updated'] = 'Assignment updated successfully';
$string['assignments_created'] = '{$a} assignments created successfully';
$string['assignmentnotfound'] = 'Assignment not found';
$string['edit_temporarily_disabled'] = 'Assignment editing is temporarily disabled. Please contact the administrator if you need to modify an assignment.';
