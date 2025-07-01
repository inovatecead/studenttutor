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
 * View history for a specific student in course context
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use local_studenttutor\history_manager;

$courseid = required_param('courseid', PARAM_INT);
$studentid = required_param('studentid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$historyid = optional_param('historyid', 0, PARAM_INT);

require_login();

// Get course and context
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$student = $DB->get_record('user', array('id' => $studentid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);

// Check if user is a tutor in this course
$is_tutor = local_studenttutor_is_tutor($USER->id, $courseid);

if (!$is_tutor) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('access_denied', 'local_studenttutor'));
}

// Verify that this student is assigned to this tutor (course-specific or global)
$assignment = $DB->get_record_sql("
    SELECT * FROM {local_studenttutor_assign}
    WHERE tutorid = :tutorid 
    AND studentid = :studentid 
    AND (courseid = :courseid OR courseid = 0)
    AND status = :status
    LIMIT 1
", [
    'tutorid' => $USER->id,
    'studentid' => $studentid,
    'courseid' => $courseid,
    'status' => 'active'
]);

if (!$assignment) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('student_not_assigned', 'local_studenttutor'));
}

// Handle actions (delete, edit)
if ($action && $historyid && confirm_sesskey()) {
    require_capability('local/studenttutor:managehistory', $context);
    
    if ($action === 'delete') {
        // Verify the history entry belongs to this tutor and student
        $history_entry = $DB->get_record('local_studenttutor_history', [
            'id' => $historyid,
            'tutorid' => $USER->id,
            'studentid' => $studentid,
            'courseid' => $courseid
        ]);
        
        if ($history_entry) {
            if ($DB->delete_records('local_studenttutor_history', ['id' => $historyid])) {
                \core\notification::success(get_string('history_deleted_success', 'local_studenttutor'));
            } else {
                \core\notification::error(get_string('history_delete_error', 'local_studenttutor'));
            }
        } else {
            \core\notification::error(get_string('history_not_found', 'local_studenttutor'));
        }
        
        redirect(new moodle_url('/local/studenttutor/student_history.php', [
            'courseid' => $courseid,
            'studentid' => $studentid
        ]));
    }
}

$PAGE->set_url(new moodle_url('/local/studenttutor/student_history.php'), 
    array('courseid' => $courseid, 'studentid' => $studentid));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('history_for_student', 'local_studenttutor', fullname($student)));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// Breadcrumbs
$PAGE->navbar->add(get_string('my_students', 'local_studenttutor'), 
    new moodle_url('/local/studenttutor/course_view.php', ['courseid' => $courseid]));
$PAGE->navbar->add(get_string('history_for_student', 'local_studenttutor', fullname($student)));

echo $OUTPUT->heading(get_string('history_for_student', 'local_studenttutor', fullname($student)));

// Action buttons
echo html_writer::start_tag('div', ['class' => 'mb-3']);
echo html_writer::link(
    new moodle_url('/local/studenttutor/add_history.php', [
        'courseid' => $courseid,
        'studentid' => $studentid
    ]),
    get_string('add_interaction', 'local_studenttutor'),
    ['class' => 'btn btn-primary mr-2']
);

echo html_writer::link(
    new moodle_url('/local/studenttutor/course_view.php', ['courseid' => $courseid]),
    get_string('back_to_students', 'local_studenttutor'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_tag('div');

// Get history for this student and tutor in this course
$history_entries = $DB->get_records_sql("
    SELECT h.*, h.timecreated, h.activity_date
    FROM {local_studenttutor_history} h
    WHERE h.studentid = :studentid AND h.tutorid = :tutorid AND h.courseid = :courseid
    ORDER BY h.activity_date DESC, h.timecreated DESC
", [
    'studentid' => $studentid,
    'tutorid' => $USER->id,
    'courseid' => $courseid
]);

if (empty($history_entries)) {
    echo $OUTPUT->notification(get_string('no_history_entries', 'local_studenttutor'), 'info');
} else {
    echo html_writer::start_tag('div', ['class' => 'history-timeline']);
    
    foreach ($history_entries as $entry) {
        echo html_writer::start_tag('div', ['class' => 'card mb-3']);
        echo html_writer::start_tag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
        
        echo html_writer::start_tag('div');
        echo html_writer::tag('h6', $entry->title, ['class' => 'mb-0']);
        
        // Show activity date if different from created date
        $activity_date = $entry->activity_date ?: $entry->timecreated;
        echo html_writer::tag('small', 
            get_string('activity_date', 'local_studenttutor') . ': ' . userdate($activity_date, get_string('strftimedaydate')), 
            ['class' => 'text-info d-block']);
        echo html_writer::tag('small', 
            get_string('created_on', 'local_studenttutor') . ': ' . userdate($entry->timecreated), 
            ['class' => 'text-muted d-block']);
        echo html_writer::end_tag('div');
        
        // Action buttons
        if (has_capability('local/studenttutor:managehistory', $context)) {
            echo html_writer::start_tag('div', ['class' => 'btn-group btn-group-sm']);
            
            // Edit button (will redirect to edit form)
            echo html_writer::link(
                new moodle_url('/local/studenttutor/edit_history.php', [
                    'courseid' => $courseid,
                    'studentid' => $studentid,
                    'historyid' => $entry->id
                ]),
                get_string('edit'),
                ['class' => 'btn btn-outline-primary btn-sm', 'title' => get_string('edit')]
            );
            
            // Delete button with confirmation
            $delete_url = new moodle_url('/local/studenttutor/student_history.php', [
                'courseid' => $courseid,
                'studentid' => $studentid,
                'action' => 'delete',
                'historyid' => $entry->id,
                'sesskey' => sesskey()
            ]);
            
            echo html_writer::link(
                $delete_url,
                get_string('delete'),
                [
                    'class' => 'btn btn-outline-danger btn-sm',
                    'title' => get_string('delete'),
                    'onclick' => 'return confirm("' . get_string('confirm_delete_history', 'local_studenttutor') . '");'
                ]
            );
            
            echo html_writer::end_tag('div');
        }
        
        echo html_writer::end_tag('div');
        
        echo html_writer::start_tag('div', ['class' => 'card-body']);
        
        // Map activity types to display strings
        $activity_types = [
            'meeting' => get_string('action_meeting', 'local_studenttutor'),
            'email' => get_string('action_email', 'local_studenttutor'),
            'feedback' => get_string('action_feedback', 'local_studenttutor'),
            'assessment' => get_string('action_assessment', 'local_studenttutor'),
            'phone' => get_string('action_phone', 'local_studenttutor'),
            'other' => get_string('action_other', 'local_studenttutor')
        ];
        
        $type_string = isset($activity_types[$entry->activitytype]) ? 
            $activity_types[$entry->activitytype] : 
            ucfirst($entry->activitytype);
            
        echo html_writer::tag('span', $type_string, ['class' => 'badge badge-secondary mb-2']);
        
        echo html_writer::tag('p', nl2br(s($entry->description)), ['class' => 'mb-0']);
        
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    }
    
    echo html_writer::end_tag('div');
    
    // Statistics
    echo html_writer::start_tag('div', ['class' => 'mt-4 p-3 bg-light rounded']);
    echo html_writer::tag('h5', get_string('interaction_summary', 'local_studenttutor'));
    
    $total_entries = count($history_entries);
    $types_count = array();
    $first_contact = null;
    $last_contact = null;
    
    foreach ($history_entries as $entry) {
        if (!isset($types_count[$entry->activitytype])) {
            $types_count[$entry->activitytype] = 0;
        }
        $types_count[$entry->activitytype]++;
        
        if ($first_contact === null || $entry->timecreated < $first_contact) {
            $first_contact = $entry->timecreated;
        }
        if ($last_contact === null || $entry->timecreated > $last_contact) {
            $last_contact = $entry->timecreated;
        }
    }
    
    echo html_writer::tag('p', get_string('stats_total_interactions', 'local_studenttutor', $total_entries));
    echo html_writer::tag('p', get_string('first_contact', 'local_studenttutor') . ': ' . 
        ($first_contact ? userdate($first_contact) : get_string('never', 'local_studenttutor')));
    echo html_writer::tag('p', get_string('last_contact', 'local_studenttutor') . ': ' . 
        ($last_contact ? userdate($last_contact) : get_string('never', 'local_studenttutor')));
    
    if (!empty($types_count)) {
        echo html_writer::tag('h6', get_string('interaction_types', 'local_studenttutor'));
        foreach ($types_count as $type => $count) {
            $type_string = isset($activity_types[$type]) ? 
                $activity_types[$type] : 
                ucfirst($type);
            echo html_writer::tag('p', "{$type_string}: {$count}", ['class' => 'mb-1']);
        }
    }
    
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();
