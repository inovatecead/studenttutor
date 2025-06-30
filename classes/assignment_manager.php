<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will             return $results;
        } catch (Exception $e) {eful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Assignment manager class for Student-Tutor Assignment plugin.
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studenttutor;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/accesslib.php');

/**
 * Class for managing student-tutor assignments.
 */
class assignment_manager {

    /** @var string Active assignment status */
    const STATUS_ACTIVE = 'active';
    
    /** @var string Inactive assignment status */
    const STATUS_INACTIVE = 'inactive';
    
    /** @var string Completed assignment status */
    const STATUS_COMPLETED = 'completed';

    /**
     * Create a new assignment between student and tutor.
     *
     * @param int $studentid Student user ID
     * @param int $tutorid Tutor user ID
     * @param int $courseid Course ID (0 for global assignment)
     * @param int $assignedby User ID who created the assignment
     * @return int|false Assignment ID or false on failure
     * @throws \moodle_exception
     */
    public static function create_assignment($studentid, $tutorid, $courseid = 0, $assignedby = null) {
        global $DB, $USER;

        // Validation
        if (!self::validate_assignment_data($studentid, $tutorid, $courseid)) {
            return false;
        }

        // Check for existing assignment
        if (self::assignment_exists($studentid, $tutorid, $courseid)) {
            throw new \moodle_exception('assignmentalreadyexists', 'local_studenttutor');
        }

        $assignedby = $assignedby ?: $USER->id;

        $assignment = new \stdClass();
        $assignment->studentid = $studentid;
        $assignment->tutorid = $tutorid;
        $assignment->courseid = $courseid;
        $assignment->assignedby = $assignedby;
        $assignment->timeassigned = time();    // CAMPO OBRIGATÓRIO - quando foi atribuído
        $assignment->timecreated = time();
        $assignment->timemodified = time();
        $assignment->createdby = $assignedby;
        $assignment->status = self::STATUS_ACTIVE;

        try {
            $assignmentid = $DB->insert_record('local_studenttutor_assign', $assignment);

            // Trigger assignment created event
            $event = \local_studenttutor\event\assignment_created::create(array(
                'context' => \context_system::instance(),
                'objectid' => $assignmentid,
                'other' => array(
                    'tutorid' => $tutorid,
                    'studentid' => $studentid,
                    'courseid' => $courseid
                )
            ));
            $event->trigger();

            return $assignmentid;

        } catch (\Exception $e) {
            debugging('Error creating assignment: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Update an existing assignment.
     *
     * @param int $assignmentid Assignment ID
     * @param array $data Updated data
     * @return bool Success status
     * @throws \moodle_exception
     */
    public static function update_assignment($assignmentid, $data) {
        global $DB;

        $assignment = self::get_assignment($assignmentid);
        if (!$assignment) {
            throw new \moodle_exception('assignmentnotfound', 'local_studenttutor');
        }

        // Validate new data if provided
        if (isset($data['studentid']) || isset($data['tutorid']) || isset($data['courseid'])) {
            $studentid = $data['studentid'] ?? $assignment->studentid;
            $tutorid = $data['tutorid'] ?? $assignment->tutorid;
            $courseid = $data['courseid'] ?? $assignment->courseid;

            if (!self::validate_assignment_data($studentid, $tutorid, $courseid)) {
                return false;
            }

            // Check for conflicts if key fields changed
            if ($studentid != $assignment->studentid || 
                $tutorid != $assignment->tutorid || 
                $courseid != $assignment->courseid) {
                
                if (self::assignment_exists($studentid, $tutorid, $courseid, $assignmentid)) {
                    throw new \moodle_exception('assignmentalreadyexists', 'local_studenttutor');
                }
            }
        }

        $updatedata = new \stdClass();
        $updatedata->id = $assignmentid;
        $updatedata->timemodified = time();

        $allowedfields = ['studentid', 'tutorid', 'courseid', 'status'];
        foreach ($allowedfields as $field) {
            if (isset($data[$field])) {
                $updatedata->$field = $data[$field];
            }
        }

        try {
            $result = $DB->update_record('local_studenttutor_assign', $updatedata);

            if ($result) {
                // Trigger assignment updated event
                $event = \local_studenttutor\event\assignment_updated::create(array(
                    'context' => \context_system::instance(),
                    'objectid' => $assignmentid,
                    'other' => array(
                        'tutorid' => $updatedata->tutorid ?? $assignment->tutorid,
                        'studentid' => $updatedata->studentid ?? $assignment->studentid,
                        'courseid' => $updatedata->courseid ?? $assignment->courseid
                    )
                ));
                $event->trigger();
            }

            return $result;

        } catch (\Exception $e) {
            debugging('Error updating assignment: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Delete an assignment.
     *
     * @param int $assignmentid Assignment ID
     * @return bool Success status
     */
    public static function delete_assignment($assignmentid) {
        global $DB;

        $assignment = self::get_assignment($assignmentid);
        if (!$assignment) {
            return false;
        }

        try {
            $result = $DB->delete_records('local_studenttutor_assign', array('id' => $assignmentid));
            
            // Also clean up related history entries if needed
            // Note: Consider soft delete instead for audit trail
            
            return $result;

        } catch (\Exception $e) {
            debugging('Error deleting assignment: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Get a single assignment by ID.
     *
     * @param int $assignmentid Assignment ID
     * @return \stdClass|false Assignment record or false
     */
    public static function get_assignment($assignmentid) {
        global $DB;

        try {
            return $DB->get_record('local_studenttutor_assign', array('id' => $assignmentid));
        } catch (\Exception $e) {
            debugging('Error getting assignment: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Get assignments with optional filters.
     *
     * @param array $filters Optional filters (courseid, studentid, tutorid, status)
     * @param string $sort Sort field (default: timecreated DESC)
     * @param int $limitfrom Start position for pagination
     * @param int $limitnum Number of records to return
     * @return array Array of assignment records with user details
     */
    public static function get_assignments($filters = array(), $sort = 'a.timecreated DESC', $limitfrom = 0, $limitnum = 0) {
        global $DB;

        $sql = "SELECT a.*, 
                       tu.firstname as tutor_firstname, tu.lastname as tutor_lastname, tu.email as tutor_email,
                       st.firstname as student_firstname, st.lastname as student_lastname, st.email as student_email,
                       c.fullname as course_name, c.shortname as course_shortname
                FROM {local_studenttutor_assign} a
                JOIN {user} tu ON a.tutorid = tu.id AND tu.deleted = 0
                JOIN {user} st ON a.studentid = st.id AND st.deleted = 0
                LEFT JOIN {course} c ON a.courseid = c.id
                WHERE 1=1";

        $params = array();

        // Apply filters
        if (!empty($filters['courseid'])) {
            $sql .= " AND a.courseid = :courseid";
            $params['courseid'] = $filters['courseid'];
        }

        if (!empty($filters['studentid'])) {
            $sql .= " AND a.studentid = :studentid";
            $params['studentid'] = $filters['studentid'];
        }

        if (!empty($filters['tutorid'])) {
            $sql .= " AND a.tutorid = :tutorid";
            $params['tutorid'] = $filters['tutorid'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND a.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['assignedby'])) {
            $sql .= " AND a.assignedby = :assignedby";
            $params['assignedby'] = $filters['assignedby'];
        }

        $sql .= " ORDER BY " . $sort;

        try {
            return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        } catch (\Exception $e) {
            debugging('Error getting assignments: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return array();
        }
    }

    /**
     * Get students assigned to a specific tutor.
     *
     * @param int $tutorid Tutor user ID
     * @param int $courseid Course ID filter (optional)
     * @param string $status Status filter (default: active)
     * @return array Array of student assignments
     */
    public static function get_tutor_students($tutorid, $courseid = null, $status = self::STATUS_ACTIVE) {
        $filters = array(
            'tutorid' => $tutorid,
            'status' => $status
        );

        if ($courseid !== null) {
            $filters['courseid'] = $courseid;
        }

        return self::get_assignments($filters, 'st.lastname, st.firstname');
    }

    /**
     * Get tutors assigned to a specific student.
     *
     * @param int $studentid Student user ID
     * @param int $courseid Course ID filter (optional)
     * @param string $status Status filter (default: active)
     * @return array Array of tutor assignments
     */
    public static function get_student_tutors($studentid, $courseid = null, $status = self::STATUS_ACTIVE) {
        $filters = array(
            'studentid' => $studentid,
            'status' => $status
        );

        if ($courseid !== null) {
            $filters['courseid'] = $courseid;
        }

        return self::get_assignments($filters, 'tu.lastname, tu.firstname');
    }

    /**
     * Get students assigned to a tutor including both course-specific and global assignments.
     *
     * @param int $tutorid Tutor user ID
     * @param int $courseid Course ID (will include both this course and global assignments)
     * @param string $status Status filter (default: active)
     * @return array Array of student assignments (course-specific + global, without duplicates)
     */
    public static function get_tutor_students_including_global($tutorid, $courseid, $status = self::STATUS_ACTIVE) {
        global $DB;
        
        // Query para buscar estudantes atribuídos ao tutor tanto no curso específico quanto globalmente
        $sql = "SELECT DISTINCT a.id, a.studentid, a.tutorid, a.courseid, a.status, a.timeassigned, a.timecreated, a.timemodified,
                       tu.firstname as tutor_firstname, tu.lastname as tutor_lastname, tu.email as tutor_email,
                       st.firstname as student_firstname, st.lastname as student_lastname, st.email as student_email,
                       c.fullname as course_name, c.shortname as course_shortname,
                       CASE WHEN a.courseid = 0 THEN 'Global' ELSE c.fullname END as assignment_scope,
                       CASE WHEN a.courseid = 0 THEN 1 ELSE 0 END as is_global_assignment
                FROM {local_studenttutor_assign} a
                JOIN {user} tu ON a.tutorid = tu.id AND tu.deleted = 0 AND tu.suspended = 0
                JOIN {user} st ON a.studentid = st.id AND st.deleted = 0 AND st.suspended = 0
                LEFT JOIN {course} c ON a.courseid = c.id AND c.visible = 1
                WHERE a.tutorid = :tutorid 
                AND a.status = :status
                AND (a.courseid = :courseid OR a.courseid = 0)
                ORDER BY is_global_assignment DESC, st.lastname ASC, st.firstname ASC";
                
        $params = [
            'tutorid' => $tutorid,
            'status' => $status,
            'courseid' => $courseid
        ];
        
        try {
            $results = $DB->get_records_sql($sql, $params);
            
            return $results;
        } catch (\Exception $e) {
            debugging('Error getting tutor students including global: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return array();
        }
    }

    /**
     * Check if an assignment already exists.
     *
     * @param int $studentid Student user ID
     * @param int $tutorid Tutor user ID
     * @param int $courseid Course ID
     * @param int $excludeid Assignment ID to exclude from check
     * @return bool True if assignment exists
     */
    public static function assignment_exists($studentid, $tutorid, $courseid, $excludeid = null) {
        global $DB;

        $params = array(
            'studentid' => $studentid,
            'tutorid' => $tutorid,
            'courseid' => $courseid
        );

        $sql = "studentid = :studentid AND tutorid = :tutorid AND courseid = :courseid";

        if ($excludeid) {
            $sql .= " AND id != :excludeid";
            $params['excludeid'] = $excludeid;
        }

        return $DB->record_exists_select('local_studenttutor_assign', $sql, $params);
    }

    /**
     * Validate assignment data.
     *
     * @param int $studentid Student user ID
     * @param int $tutorid Tutor user ID
     * @param int $courseid Course ID
     * @return bool True if valid
     */
    private static function validate_assignment_data($studentid, $tutorid, $courseid) {
        global $DB;

        // Check if users exist and are not deleted
        if (!$DB->record_exists('user', array('id' => $studentid, 'deleted' => 0))) {
            debugging('Student user not found or deleted: ' . $studentid, DEBUG_DEVELOPER);
            return false;
        }

        if (!$DB->record_exists('user', array('id' => $tutorid, 'deleted' => 0))) {
            debugging('Tutor user not found or deleted: ' . $tutorid, DEBUG_DEVELOPER);
            return false;
        }

        // Check if course exists (if not global assignment)
        if ($courseid > 0 && !$DB->record_exists('course', array('id' => $courseid))) {
            debugging('Course not found: ' . $courseid, DEBUG_DEVELOPER);
            return false;
        }

        // Prevent self-assignment
        if ($studentid == $tutorid) {
            debugging('Cannot assign user to themselves', DEBUG_DEVELOPER);
            return false;
        }

        return true;
    }

    /**
     * Get assignment statistics.
     *
     * @param array $filters Optional filters
     * @return array Statistics array
     */
    public static function get_statistics($filters = array()) {
        global $DB;

        $stats = array();

        // Base WHERE clause
        $where = "1=1";
        $params = array();

        if (!empty($filters['courseid'])) {
            $where .= " AND courseid = :courseid";
            $params['courseid'] = $filters['courseid'];
        }

        if (!empty($filters['status'])) {
            $where .= " AND status = :status";
            $params['status'] = $filters['status'];
        }

        try {
            // Total assignments
            $stats['total_assignments'] = $DB->count_records_select('local_studenttutor_assign', $where, $params);

            // Active assignments
            $activeparams = $params;
            $activeparams['status'] = self::STATUS_ACTIVE;
            $stats['active_assignments'] = $DB->count_records_select('local_studenttutor_assign', 
                $where . " AND status = :status", $activeparams);

            // Unique tutors
            $sql = "SELECT COUNT(DISTINCT tutorid) FROM {local_studenttutor_assign} WHERE " . $where;
            $stats['unique_tutors'] = $DB->count_records_sql($sql, $params);

            // Unique students
            $sql = "SELECT COUNT(DISTINCT studentid) FROM {local_studenttutor_assign} WHERE " . $where;
            $stats['unique_students'] = $DB->count_records_sql($sql, $params);

            return $stats;

        } catch (\Exception $e) {
            debugging('Error getting statistics: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return array();
        }
    }

    /**
     * Get all assignments with user and course details
     *
     * @param array $filters Optional filters (status, tutorid, studentid, courseid)
     * @param string $sort Sort order (default: 'timecreated DESC')
     * @param int $limitfrom Start position for pagination
     * @param int $limitnum Number of records to return
     * @return array Array of assignment records with details
     */
    public static function get_all_assignments_with_details($filters = array(), $sort = 'a.timeassigned DESC', $limitfrom = 0, $limitnum = 0) {
        global $DB;

        $sql = "SELECT a.*, 
                       tu.firstname as tutor_firstname, tu.lastname as tutor_lastname,
                       st.firstname as student_firstname, st.lastname as student_lastname,
                       c.fullname as course_name
                FROM {local_studenttutor_assign} a
                JOIN {user} tu ON a.tutorid = tu.id
                JOIN {user} st ON a.studentid = st.id
                LEFT JOIN {course} c ON a.courseid = c.id";
        
        $where = array();
        $params = array();

        // Apply filters
        if (!empty($filters['status'])) {
            $where[] = "a.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['tutorid'])) {
            $where[] = "a.tutorid = :tutorid";
            $params['tutorid'] = $filters['tutorid'];
        }

        if (!empty($filters['studentid'])) {
            $where[] = "a.studentid = :studentid";
            $params['studentid'] = $filters['studentid'];
        }

        if (!empty($filters['courseid'])) {
            $where[] = "a.courseid = :courseid";
            $params['courseid'] = $filters['courseid'];
        }

        // Add deleted filter (exclude deleted users)
        $where[] = "tu.deleted = 0";
        $where[] = "st.deleted = 0";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY " . $sort;

        try {
            return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        } catch (\Exception $e) {
            debugging('Error getting assignments with details: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return array();
        }
    }
}
