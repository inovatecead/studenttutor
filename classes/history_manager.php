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
 * History manager class for the Nexo Tutoria Acadêmica plugin.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studenttutor;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/accesslib.php');

/**
 * Class for managing tutoring history and activities.
 */
class history_manager {
    /** @var string Meeting activity type */
    const TYPE_MEETING = 'meeting';

    /** @var string Email activity type */
    const TYPE_EMAIL = 'email';

    /** @var string Feedback activity type */
    const TYPE_FEEDBACK = 'feedback';

    /** @var string Assessment activity type */
    const TYPE_ASSESSMENT = 'assessment';

    /** @var string Guidance activity type */
    const TYPE_GUIDANCE = 'guidance';

    /** @var string Other activity type */
    const TYPE_OTHER = 'other';

    /**
     * Add a new history entry.
     *
     * @param int $studentid Student user ID
     * @param int $tutorid Tutor user ID
     * @param string $activitytype Type of activity
     * @param string $description Activity description
     * @param int $courseid Course ID (optional)
     * @param int $createdby User ID who created the entry
     * @param int $activity_date Date when the activity occurred (optional, defaults to current time)
     * @return int|false History entry ID or false on failure
     */
    public static function add_history_entry($studentid, $tutorid, $activitytype, $description, $courseid = 0, $createdby = null, $activity_date = null) {
        global $DB, $USER;

        // Validation.
        if (!self::validate_history_data($studentid, $tutorid, $activitytype, $description)) {
            return false;
        }

        $createdby = $createdby ?: $USER->id;
        $activity_date = $activity_date ?: time();

        $entry = new \stdClass();
        $entry->studentid = $studentid;
        $entry->tutorid = $tutorid;
        $entry->courseid = $courseid;
        $entry->activitytype = $activitytype;
        $entry->description = $description;
        $entry->timecreated = time();
        $entry->timemodified = time();
        $entry->createdby = $createdby;

        $entry->activity_date = $activity_date;

        try {
            $entryid = $DB->insert_record('local_studenttutor_history', $entry);
            return $entryid;
        } catch (\Exception $e) {
            debugging('Error creating history entry: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Update an existing history entry.
     *
     * @param int $entryid History entry ID
     * @param array $data Updated data
     * @return bool Success status
     */
    public static function update_history_entry($entryid, $data) {
        global $DB;

        $entry = self::get_history_entry($entryid);
        if (!$entry) {
            return false;
        }

        $updatedata = new \stdClass();
        $updatedata->id = $entryid;
        $updatedata->timemodified = time();

        $allowedfields = ['activitytype', 'activity_date', 'description', 'courseid'];
        foreach ($allowedfields as $field) {
            if (isset($data[$field])) {
                $updatedata->$field = $data[$field];
            }
        }

        try {
            return $DB->update_record('local_studenttutor_history', $updatedata);
        } catch (\Exception $e) {
            debugging('Error updating history entry: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Delete a history entry.
     *
     * @param int $entryid History entry ID
     * @return bool Success status
     */
    public static function delete_history_entry($entryid) {
        global $DB;

        try {
            return $DB->delete_records('local_studenttutor_history', ['id' => $entryid]);
        } catch (\Exception $e) {
            debugging('Error deleting history entry: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Get a single history entry by ID.
     *
     * @param int $entryid History entry ID
     * @return \stdClass|false History record or false
     */
    public static function get_history_entry($entryid) {
        global $DB;

        try {
            return $DB->get_record('local_studenttutor_history', ['id' => $entryid]);
        } catch (\Exception $e) {
            debugging('Error getting history entry: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Get history entries with optional filters.
     *
     * @param array $filters Optional filters (courseid, studentid, tutorid, activitytype, datefrom, dateto)
     * @param string $sort Sort field (default: timecreated DESC)
     * @param int $limitfrom Start position for pagination
     * @param int $limitnum Number of records to return
     * @return array Array of history records with user details
     */
    public static function get_history_entries($filters = [], $sort = 'h.timecreated DESC', $limitfrom = 0, $limitnum = 0) {
        global $DB;

        $nameselects = \core_user\fields::for_name()->get_sql('tu', false, 'tutor_')->selects .
                \core_user\fields::for_name()->get_sql('st', false, 'student_')->selects .
                \core_user\fields::for_name()->get_sql('cb', false, 'createdby_')->selects;

        $sql = "SELECT h.*{$nameselects},
                       c.fullname as course_name, c.shortname as course_shortname
                FROM {local_studenttutor_history} h
                JOIN {user} tu ON h.tutorid = tu.id AND tu.deleted = 0
                JOIN {user} st ON h.studentid = st.id AND st.deleted = 0
                LEFT JOIN {course} c ON h.courseid = c.id
                LEFT JOIN {user} cb ON h.createdby = cb.id AND cb.deleted = 0
                WHERE 1=1";

        $params = [];

        // Apply filters
        if (!empty($filters['courseid'])) {
            $sql .= " AND h.courseid = :courseid";
            $params['courseid'] = $filters['courseid'];
        }

        if (!empty($filters['studentid'])) {
            $sql .= " AND h.studentid = :studentid";
            $params['studentid'] = $filters['studentid'];
        }

        if (!empty($filters['tutorid'])) {
            $sql .= " AND h.tutorid = :tutorid";
            $params['tutorid'] = $filters['tutorid'];
        }

        if (!empty($filters['activitytype'])) {
            $sql .= " AND h.activitytype = :activitytype";
            $params['activitytype'] = $filters['activitytype'];
        }

        if (!empty($filters['datefrom'])) {
            $sql .= " AND h.timecreated >= :datefrom";
            $params['datefrom'] = $filters['datefrom'];
        }

        if (!empty($filters['dateto'])) {
            $sql .= " AND h.timecreated <= :dateto";
            $params['dateto'] = $filters['dateto'];
        }

        if (!empty($filters['createdby'])) {
            $sql .= " AND h.createdby = :createdby";
            $params['createdby'] = $filters['createdby'];
        }

        $sql .= " ORDER BY " . $sort;

        try {
            return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        } catch (\Exception $e) {
            debugging('Error getting history entries: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }

    /**
     * Get history entries for a specific student-tutor pair.
     *
     * @param int $studentid Student user ID
     * @param int $tutorid Tutor user ID
     * @param int $courseid Course ID filter (optional)
     * @param int $limitnum Number of records to return (default: 50)
     * @return array Array of history entries
     */
    public static function get_pair_history($studentid, $tutorid, $courseid = null, $limitnum = 50) {
        $filters = [
            'studentid' => $studentid,
            'tutorid' => $tutorid,
        ];

        if ($courseid !== null) {
            $filters['courseid'] = $courseid;
        }

        return self::get_history_entries($filters, 'h.timecreated DESC', 0, $limitnum);
    }

    /**
     * Get recent activities for a tutor.
     *
     * @param int $tutorid Tutor user ID
     * @param int $days Number of days to look back (default: 7)
     * @param int $limitnum Number of records to return (default: 20)
     * @return array Array of recent activities
     */
    public static function get_recent_activities($tutorid, $days = 7, $limitnum = 20) {
        $datefrom = time() - ($days * 24 * 60 * 60);

        $filters = [
            'tutorid' => $tutorid,
            'datefrom' => $datefrom,
        ];

        return self::get_history_entries($filters, 'h.timecreated DESC', 0, $limitnum);
    }

    /**
     * Get activity statistics.
     *
     * @param array $filters Optional filters
     * @return array Statistics array
     */
    public static function get_activity_statistics($filters = []) {
        global $DB;

        $stats = [];

        // Base WHERE clause
        $where = "1=1";
        $params = [];

        if (!empty($filters['courseid'])) {
            $where .= " AND courseid = :courseid";
            $params['courseid'] = $filters['courseid'];
        }

        if (!empty($filters['tutorid'])) {
            $where .= " AND tutorid = :tutorid";
            $params['tutorid'] = $filters['tutorid'];
        }

        if (!empty($filters['studentid'])) {
            $where .= " AND studentid = :studentid";
            $params['studentid'] = $filters['studentid'];
        }

        if (!empty($filters['datefrom'])) {
            $where .= " AND timecreated >= :datefrom";
            $params['datefrom'] = $filters['datefrom'];
        }

        if (!empty($filters['dateto'])) {
            $where .= " AND timecreated <= :dateto";
            $params['dateto'] = $filters['dateto'];
        }

        try {
            // Total activities
            $stats['total_activities'] = $DB->count_records_select('local_studenttutor_history', $where, $params);

            // Activities by type
            $sql = "SELECT activitytype, COUNT(*) as count
                    FROM {local_studenttutor_history}
                    WHERE " . $where . "
                    GROUP BY activitytype
                    ORDER BY count DESC";
            $stats['by_type'] = $DB->get_records_sql($sql, $params);

            // Activities by month (last 12 months)
            $sql = "SELECT
                        YEAR(FROM_UNIXTIME(timecreated)) as year,
                        MONTH(FROM_UNIXTIME(timecreated)) as month,
                        COUNT(*) as count
                    FROM {local_studenttutor_history}
                    WHERE " . $where . "
                    AND timecreated >= :yearago
                    GROUP BY YEAR(FROM_UNIXTIME(timecreated)), MONTH(FROM_UNIXTIME(timecreated))
                    ORDER BY year DESC, month DESC";

            $yearago = time() - (365 * 24 * 60 * 60);
            $monthparams = array_merge($params, ['yearago' => $yearago]);
            $stats['by_month'] = $DB->get_records_sql($sql, $monthparams);

            return $stats;
        } catch (\Exception $e) {
            debugging('Error getting activity statistics: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }

    /**
     * Get valid activity types.
     *
     * @return array Array of valid activity types
     */
    public static function get_activity_types() {
        // Use dynamic activity types from database
        return \local_studenttutor\activity_type_manager::get_activity_types_options(true);
    }

    /**
     * Validate history entry data.
     *
     * @param int $studentid Student user ID
     * @param int $tutorid Tutor user ID
     * @param string $activitytype Activity type
     * @param string $description Description
     * @return bool True if valid
     */
    private static function validate_history_data($studentid, $tutorid, $activitytype, $description) {
        global $DB;

        // Check if users exist and are not deleted
        if (!$DB->record_exists('user', ['id' => $studentid, 'deleted' => 0])) {
            debugging('Student user not found or deleted: ' . $studentid, DEBUG_DEVELOPER);
            return false;
        }

        if (!$DB->record_exists('user', ['id' => $tutorid, 'deleted' => 0])) {
            debugging('Tutor user not found or deleted: ' . $tutorid, DEBUG_DEVELOPER);
            return false;
        }

        // Validate activity type using dynamic types from database
        $activity_types = \local_studenttutor\activity_type_manager::get_activity_types_options(true);
        if (!array_key_exists($activitytype, $activity_types)) {
            debugging('Invalid activity type: ' . $activitytype, DEBUG_DEVELOPER);
            return false;
        }

        // Check description length
        if (empty(trim($description))) {
            debugging('Description cannot be empty', DEBUG_DEVELOPER);
            return false;
        }

        return true;
    }

    /**
     * Get history entries with user and course details
     *
     * @param array $filters Optional filters (studentid, tutorid, courseid, activitytype, datefrom, dateto)
     * @param string $sort Sort order (default: 'h.timecreated DESC')
     * @param int $limitfrom Start position for pagination
     * @param int $limitnum Number of records to return
     * @return array Array of history records with details
     */
    public static function get_history_with_details($filters = [], $sort = 'h.timecreated DESC', $limitfrom = 0, $limitnum = 0) {
        global $DB;

        $nameselects = \core_user\fields::for_name()->get_sql('tu', false, 'tutor_')->selects .
                \core_user\fields::for_name()->get_sql('st', false, 'student_')->selects;

        $sql = "SELECT h.*{$nameselects},
                       c.fullname as course_name
                FROM {local_studenttutor_history} h
                JOIN {user} tu ON h.tutorid = tu.id
                JOIN {user} st ON h.studentid = st.id
                LEFT JOIN {course} c ON h.courseid = c.id
                WHERE EXISTS (
                    SELECT 1 FROM {local_studenttutor_assign} a
                    WHERE a.tutorid = h.tutorid
                    AND a.studentid = h.studentid
                    AND (a.courseid = h.courseid OR a.courseid = 0)
                    AND a.status = 'active'
                )";

        $where = [];
        $params = [];

        // Apply filters
        if (!empty($filters['studentid'])) {
            $where[] = "h.studentid = :studentid";
            $params['studentid'] = $filters['studentid'];
        }

        if (!empty($filters['tutorid'])) {
            $where[] = "h.tutorid = :tutorid";
            $params['tutorid'] = $filters['tutorid'];
        }

        // Multiple tutors filter
        if (!empty($filters['tutorids'])) {
            [$insql, $inparams] = $DB->get_in_or_equal($filters['tutorids'], SQL_PARAMS_NAMED, 'tutor');
            $where[] = "h.tutorid $insql";
            $params = array_merge($params, $inparams);
        }

        // Multiple students filter
        if (!empty($filters['studentids'])) {
            [$insql, $inparams] = $DB->get_in_or_equal($filters['studentids'], SQL_PARAMS_NAMED, 'student');
            $where[] = "h.studentid $insql";
            $params = array_merge($params, $inparams);
        }

        if (!empty($filters['courseid'])) {
            $where[] = "h.courseid = :courseid";
            $params['courseid'] = $filters['courseid'];
        }

        if (!empty($filters['activitytype'])) {
            $where[] = "h.activitytype = :activitytype";
            $params['activitytype'] = $filters['activitytype'];
        }

        if (!empty($filters['datefrom'])) {
            $where[] = "h.timecreated >= :datefrom";
            $params['datefrom'] = $filters['datefrom'];
        }

        if (!empty($filters['dateto'])) {
            $where[] = "h.timecreated <= :dateto";
            $params['dateto'] = $filters['dateto'];
        }

        // Add deleted filter (exclude deleted users)
        $where[] = "tu.deleted = 0";
        $where[] = "st.deleted = 0";

        if (!empty($where)) {
            $sql .= " AND " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY " . $sort;

        try {
            return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        } catch (\Exception $e) {
            debugging('Error getting history with details: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }
}
