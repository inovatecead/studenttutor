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
 * English language strings for the Nexo Tutoria Acadêmica plugin.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin and settings.
$string['pluginname'] = 'Nexo Academic Tutoring';
$string['settings'] = 'Settings';
$string['general_settings'] = 'General Settings';
$string['general_settings_desc'] = 'Configure the general settings of the plugin';
$string['enable_plugin'] = 'Enable Plugin';
$string['enable_plugin_desc'] = 'Enable or disable the Nexo Academic Tutoring plugin';
$string['max_assignments'] = 'Maximum Assignments';
$string['max_assignments_desc'] = 'Maximum number of students a tutor can be assigned to';
$string['tutor_role'] = 'Tutor role';
$string['tutor_role_desc'] = 'Shortname of the role used to identify tutors (default: tutortematico).';
$string['additional_tutor_roles'] = 'Additional tutor roles';
$string['additional_tutor_roles_desc'] = 'Comma separated list of additional role shortnames used to identify tutors.';
$string['limited_access_redirect'] = 'Limited access. Redirecting to reports.';

// Navigation and pages.
$string['manage_assignments'] = 'Manage Tutoring Assignments';
$string['view_history'] = 'View Tutoring History';
$string['my_students'] = 'My Students';
$string['assignments_title'] = 'Tutoring Assignments';
$string['history_title'] = 'Tutoring History';
$string['add_history_entry'] = 'Add Tutoring Activity';
$string['edit_history'] = 'Edit Tutoring Record';
$string['adding_history_for'] = 'Adding tutoring activity for: {$a}';
$string['editing_history_for'] = 'Editing the tutoring record of {$a}';
$string['add_interaction'] = 'Add Interaction';
$string['history_for_student'] = 'Tutoring History for {$a}';
$string['my_students_in_course'] = 'My Students in {$a}';
$string['no_students_assigned'] = 'No students assigned to you in this course';
$string['back_to_course'] = 'Back to Course';
$string['back_to_students'] = 'Back to My Students';
$string['back_to_assignments'] = 'Back to Assignments';
$string['access_denied'] = 'Access denied';
$string['student_not_assigned'] = 'This student is not assigned to you';

// Assignments.
$string['add_assignment'] = 'Add Assignment';
$string['date_assigned'] = 'Date Assigned';
$string['assignment_created'] = 'Student successfully assigned to tutor';
$string['assignment_deleted'] = 'Assignment removed successfully';
$string['assignment_creation_failed'] = 'Failed to create assignment';
$string['assignment_exists_for'] = 'Assignment already exists for student: {$a}';
$string['assignmentalreadyexists'] = 'This assignment already exists';
$string['assignmentnotfound'] = 'Assignment not found';
$string['edit_assignment'] = 'Edit Assignment';
$string['delete_assignment'] = 'Delete Assignment';
$string['confirm_delete_assignment'] = 'Are you sure you want to delete this assignment?\n\nThis action cannot be undone!';
$string['assignment_updated_success'] = 'Assignment updated successfully';
$string['assignment_update_error'] = 'Error updating the assignment';
$string['required_fields_missing'] = 'Required data was not provided';
$string['invalid_tutor'] = 'The selected tutor is not valid';
$string['assignments_created_count'] = '{$a} assignment(s) created successfully';
$string['assignments_duplicated_count'] = '{$a} assignment(s) already existed';
$string['assignments_error_count'] = '{$a} error(s) while creating assignments';
$string['no_assignments_found'] = 'No assignments found.';
$string['assign_students_hint'] = 'To assign students, open the assignments page.';
$string['error_deleting_assignment'] = 'Error deleting assignment';

// Tutoring history.
$string['history_added'] = 'Activity added to history successfully';
$string['history_added_success'] = 'Tutoring activity added successfully';
$string['history_add_error'] = 'Error adding tutoring activity';
$string['history_updated_success'] = 'Tutoring record updated successfully';
$string['history_update_error'] = 'Error updating the tutoring record';
$string['history_deleted_success'] = 'Tutoring record deleted successfully';
$string['history_delete_error'] = 'Error deleting the tutoring record';
$string['history_not_found'] = 'Tutoring record not found';
$string['confirm_delete_history'] = 'Are you sure you want to delete this tutoring record?';
$string['no_history'] = 'No history entries found';
$string['no_history_entries'] = 'No tutoring activities recorded yet';

// Form fields.
$string['action_type'] = 'Activity Type';
$string['activity_type'] = 'Activity Type';
$string['description'] = 'Description';
$string['course_help'] = 'Select a course to filter students, or choose "All courses" for global assignments.';
$string['course_help_help'] = 'Choose the course to which the tutoring refers. Select "All courses" when the tutoring is not tied to a specific course.';
$string['activity_date'] = 'Activity date';
$string['activity_date_help'] = 'Select the date on which this tutoring activity took place.';
$string['created_on'] = 'Created on';
$string['name'] = 'Name';
$string['send_message'] = 'Send Message';
$string['status'] = 'Status';

// Table headers.
$string['tutor'] = 'Tutor';
$string['student'] = 'Student';
$string['course'] = 'Course';
$string['date'] = 'Date';
$string['actions'] = 'Actions';
$string['general'] = 'General';
$string['total_records'] = 'Total records found';

// Filters.
$string['filters'] = 'Filters';
$string['filter'] = 'Filter';
$string['clear'] = 'Clear';
$string['all_tutors'] = 'All Tutors';
$string['all_students'] = 'All Students';
$string['all_courses'] = 'All Courses';
$string['select_course'] = 'Select Course';
$string['select_tutor'] = 'Select Tutor';
$string['students'] = 'Students';
$string['select_course_to_see_students'] = 'Select a course to see the available students.';
$string['search_placeholder'] = 'Type to search...';
$string['search_course_placeholder'] = 'Type to search a course...';
$string['search_tutor_placeholder'] = 'Type to search a tutor...';
$string['search_student_placeholder'] = 'Type to search a student...';
$string['showing_results'] = 'Showing {$a->start}-{$a->end} of {$a->total} results';
$string['hold_ctrl_multi_select'] = 'Hold Ctrl (or Cmd) to select multiple items';
$string['without_tutors'] = 'Without tutors';
$string['with_one_tutor'] = 'With 1 tutor';
$string['with_multiple_tutors'] = 'With multiple tutors';
$string['without_group'] = 'No group';
$string['all_activity_types'] = 'All Activity Types';
$string['date_from'] = 'Date From';
$string['date_to'] = 'Date To';
$string['select_tutors'] = 'Select tutors...';
$string['select_students'] = 'Select students';
$string['filters_active'] = 'Active filters';

// Statistics.
$string['quick_stats'] = 'Quick Statistics';
$string['stats_total_students'] = 'Total students: {$a}';
$string['stats_total_interactions'] = 'Total interactions: {$a}';
$string['stats_recent_contact'] = 'Students contacted in last 7 days: {$a}';
$string['interaction_summary'] = 'Interaction Summary';
$string['first_contact'] = 'First contact';
$string['last_contact'] = 'Last Contact';
$string['total_interactions'] = 'Total Interactions';
$string['interaction_types'] = 'Types of interactions';
$string['never'] = 'Never';

// Activity type labels used by the history and reports.
$string['action_meeting'] = 'Meeting';
$string['action_email'] = 'Email Communication';
$string['action_feedback'] = 'Feedback';
$string['action_assessment'] = 'Assessment Review';
$string['action_phone'] = 'Phone call';
$string['action_other'] = 'Other';
$string['activity_meeting'] = 'Meeting';
$string['activity_email'] = 'Email Communication';
$string['activity_feedback'] = 'Feedback';
$string['activity_assessment'] = 'Assessment Review';
$string['activity_guidance'] = 'Academic Guidance';
$string['activity_other'] = 'Other';

// Activity types management.
$string['manage_activity_types'] = 'Manage Activity Types';
$string['add_activity_type'] = 'Add Activity Type';
$string['edit_activity_type'] = 'Edit Activity Type';
$string['activity_type_created'] = 'Activity type created successfully';
$string['activity_type_updated'] = 'Activity type updated successfully';
$string['activity_type_deleted'] = 'Activity type deleted successfully';
$string['activity_type_enabled'] = 'Activity type enabled';
$string['activity_type_disabled'] = 'Activity type disabled';
$string['activity_types_reordered'] = 'Activity types reordered successfully';
$string['no_activity_types'] = 'No activity types found';
$string['confirm_delete_activity_type'] = 'Are you sure you want to delete this activity type?';
$string['activity_type_create_error'] = 'Error creating activity type';
$string['activity_type_update_error'] = 'Error updating activity type';
$string['activity_type_delete_error'] = 'Error deleting activity type';
$string['activity_type_save_error'] = 'Error saving activity type';
$string['editing_activity_type'] = 'Editing activity type: {$a}';
$string['shortnameexists'] = 'This shortname already exists';
$string['activity_types_statistics'] = 'Usage Statistics';
$string['usage_count'] = 'Usage Count';
$string['uses'] = 'uses';
$string['order'] = 'Order';
$string['preview'] = 'Preview';
$string['shortname'] = 'Shortname';
$string['icon'] = 'Icon';
$string['color'] = 'Color';
$string['sortorder'] = 'Sort Order';
$string['export_csv'] = 'Export CSV';

// Activity type form fields.
$string['activity_type_name'] = 'Activity Type Name';
$string['activity_type_name_help'] = 'The display name for this activity type';
$string['activity_type_shortname'] = 'Shortname';
$string['activity_type_shortname_help'] = 'A unique identifier for this activity type (letters, numbers and underscores only)';
$string['activity_type_description'] = 'Description';
$string['activity_type_description_help'] = 'A brief description of when to use this activity type';
$string['activity_type_icon'] = 'Icon';
$string['activity_type_icon_help'] = 'FontAwesome icon to display with this activity type';
$string['activity_type_color'] = 'Color';
$string['activity_type_color_help'] = 'Color to use when displaying this activity type';
$string['activity_type_active'] = 'Active';
$string['activity_type_active_help'] = 'Whether this activity type is available for selection';
$string['activity_type_sortorder'] = 'Sort Order';
$string['activity_type_sortorder_help'] = 'Order in which this type appears in lists (lower numbers first)';

// Icons for the activity types.
$string['icon_users'] = 'Users (meetings)';
$string['icon_envelope'] = 'Envelope (email)';
$string['icon_comment'] = 'Comment (feedback)';
$string['icon_clipboard'] = 'Clipboard (assessment)';
$string['icon_compass'] = 'Compass (guidance)';
$string['icon_phone'] = 'Phone (calls)';
$string['icon_video'] = 'Video (video calls)';
$string['icon_file'] = 'File (documents)';
$string['icon_chart'] = 'Chart (analytics)';
$string['icon_lightbulb'] = 'Lightbulb (ideas)';
$string['icon_graduation'] = 'Graduation cap (academic)';
$string['icon_other'] = 'Other';

// Activity type colours.
$string['color_green'] = 'Green';
$string['color_blue'] = 'Blue';
$string['color_yellow'] = 'Yellow';
$string['color_red'] = 'Red';
$string['color_purple'] = 'Purple';
$string['color_orange'] = 'Orange';
$string['color_teal'] = 'Teal';
$string['color_gray'] = 'Gray';

// Events.
$string['event_assignment_created'] = 'Assignment created';
$string['event_assignment_updated'] = 'Assignment updated';

// Data minimisation notice shown on the tutoring record form.
$string['history_privacy_notice'] = 'Tutoring records are academic data about the student. Record only what is necessary for pedagogical follow-up and avoid sensitive personal data (health, racial or ethnic origin, religious conviction, political opinion, biometric or genetic data, sexual life).';

// Capabilities (Moodle displays these in the permission screens).
$string['studenttutor:manage'] = 'Manage the tutoring plugin settings';
$string['studenttutor:viewassignments'] = 'View student-tutor assignments';
$string['studenttutor:manageassignments'] = 'Manage student-tutor assignments';
$string['studenttutor:viewhistory'] = 'View tutoring history';
$string['studenttutor:managehistory'] = 'Manage tutoring history';
$string['studenttutor:assign_students'] = 'Assign students to tutors in a course';
$string['studenttutor:view_assignments'] = 'View assignments in a course';
$string['studenttutor:manage_history'] = 'Manage tutoring history in a course';
$string['studenttutor:view_own_students'] = 'View own assigned students';
$string['studenttutor:view_all_history'] = 'View the tutoring history of all staff';
$string['studenttutor:viewreports'] = 'View tutoring reports';

// Privacy metadata (Privacy API - do not change the key names).
$string['privacy:metadata'] = 'The Nexo Tutoring plugin stores which tutor is responsible for each student and the history of the tutoring interactions.';
$string['privacy:metadata:local_studenttutor_assign'] = 'Information about which tutor is responsible for which student.';
$string['privacy:metadata:local_studenttutor_history'] = 'Information about each tutoring interaction recorded by a tutor.';
$string['privacy:path'] = 'Tutoring';
$string['privacy:assignments'] = 'Assignments';
$string['privacy:history'] = 'Tutoring history';
$string['privacy:student'] = 'Student';
$string['privacy:tutor'] = 'Tutor';
$string['privacy:assignedby'] = 'Assigned by';
$string['privacy:createdby'] = 'Created by';
$string['privacy:course'] = 'Course';
$string['privacy:activitytype'] = 'Activity type';
$string['privacy:description'] = 'Tutoring notes';
$string['privacy:activitydate'] = 'Activity date';
$string['privacy:timecreated'] = 'Time created';
$string['privacy:timemodified'] = 'Time modified';
$string['privacy:timeassigned'] = 'Time assigned';
$string['privacy:status'] = 'Status';
