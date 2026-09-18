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
 * Privacy provider for the Nexo Tutoria Acadêmica plugin.
 *
 * Data written by this plugin lives in the system context: assignments are
 * site-wide (a course id of 0 means "all courses") and the capabilities are
 * declared at CONTEXT_SYSTEM.
 *
 * Personal data is stored in two of the three own tables:
 *  - local_studenttutor_assign  : which tutor is responsible for which student.
 *  - local_studenttutor_history : each tutoring interaction, including the free
 *    text description written by the tutor.
 * The third table (local_studenttutor_activity_types) holds configuration only
 * (labels, colours, ordering) and therefore is deliberately NOT declared.
 *
 * A user may appear in the tables in more than one role (student, tutor,
 * author of the record). Requesting deletion removes every row in which the
 * user is referenced, which also removes the counterpart's view of that
 * interaction. This is intentional: the identifier of the data subject is part
 * of the personal data and cannot be kept.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studenttutor\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider implementation.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /** @var array Tables with personal data and the columns referencing a user. */
    const USER_REFERENCE_COLUMNS = [
        'local_studenttutor_assign' => ['studentid', 'tutorid', 'assignedby'],
        'local_studenttutor_history' => ['studentid', 'tutorid', 'createdby'],
    ];

    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection The collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_studenttutor_assign', [
            'studentid' => 'privacy:student',
            'tutorid' => 'privacy:tutor',
            'assignedby' => 'privacy:assignedby',
            'courseid' => 'privacy:course',
            'timeassigned' => 'privacy:timeassigned',
            'timemodified' => 'privacy:timemodified',
            'status' => 'privacy:status',
        ], 'privacy:metadata:local_studenttutor_assign');

        $collection->add_database_table('local_studenttutor_history', [
            'studentid' => 'privacy:student',
            'tutorid' => 'privacy:tutor',
            'createdby' => 'privacy:createdby',
            'courseid' => 'privacy:course',
            'activitytype' => 'privacy:activitytype',
            'description' => 'privacy:description',
            'activity_date' => 'privacy:activitydate',
            'timecreated' => 'privacy:timecreated',
            'timemodified' => 'privacy:timemodified',
        ], 'privacy:metadata:local_studenttutor_history');

        return $collection;
    }

    /**
     * Get the list of contexts containing personal data for a user.
     *
     * @param int $userid The user id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        if (self::user_has_data($userid)) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * Get the list of users with personal data in the given context.
     *
     * @param userlist $userlist The userlist to add to.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        if ($userlist->get_context()->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        $sql = "SELECT DISTINCT studentid AS userid FROM {local_studenttutor_assign}
                 UNION
                SELECT DISTINCT tutorid FROM {local_studenttutor_assign}
                 UNION
                SELECT DISTINCT assignedby FROM {local_studenttutor_assign}
                 UNION
                SELECT DISTINCT studentid FROM {local_studenttutor_history}
                 UNION
                SELECT DISTINCT tutorid FROM {local_studenttutor_history}
                 UNION
                SELECT DISTINCT createdby FROM {local_studenttutor_history}";

        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Export the personal data of a user.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }

            self::export_assignments($userid, $context);
            self::export_history($userid, $context);
        }
    }

    /**
     * Delete all personal data in the given context.
     *
     * @param \context $context The context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        $DB->delete_records('local_studenttutor_history');
        $DB->delete_records('local_studenttutor_assign');
    }

    /**
     * Delete all personal data of a single user.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        if (empty($contextlist->count())) {
            return;
        }

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }

            self::delete_rows_for_users([$contextlist->get_user()->id]);
        }
    }

    /**
     * Delete all personal data of a list of users in the given context.
     *
     * @param approved_userlist $userlist The approved users.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        if ($userlist->get_context()->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        self::delete_rows_for_users($userlist->get_userids());
    }

    /**
     * Whether a user is referenced by any record of this plugin.
     *
     * @param int $userid The user id.
     * @return bool
     */
    private static function user_has_data(int $userid): bool {
        global $DB;

        foreach (self::USER_REFERENCE_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                if ($DB->record_exists($table, [$column => $userid])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Export the assignment records of a user.
     *
     * @param int $userid The user id.
     * @param \context $context The system context.
     * @return void
     */
    private static function export_assignments(int $userid, \context $context) {
        global $DB;

        $sql = "SELECT a.id, a.courseid, a.status, a.timeassigned, a.timemodified,
                       st.id AS studentid, st.firstname AS studentfirstname,
                       st.lastname AS studentlastname, st.firstnamephonetic AS studentfirstnamephonetic,
                       st.lastnamephonetic AS studentlastnamephonetic, st.middlename AS studentmiddlename,
                       st.alternatename AS studentalternatename,
                       tu.id AS tutorid, tu.firstname AS tutorfirstname,
                       tu.lastname AS tutorlastname, tu.firstnamephonetic AS tutorfirstnamephonetic,
                       tu.lastnamephonetic AS tutorlastnamephonetic, tu.middlename AS tutormiddlename,
                       tu.alternatename AS tutoralternatename
                  FROM {local_studenttutor_assign} a
                  JOIN {user} st ON st.id = a.studentid
                  JOIN {user} tu ON tu.id = a.tutorid
                 WHERE a.studentid = :suserid OR a.tutorid = :tuserid OR a.assignedby = :auserid
              ORDER BY a.timeassigned ASC";

        $params = ['suserid' => $userid, 'tuserid' => $userid, 'auserid' => $userid];
        $records = $DB->get_records_sql($sql, $params);

        $data = [];
        foreach ($records as $record) {
            $data[] = (object)[
                'student' => fullname((object)[
                    'firstname' => $record->studentfirstname,
                    'lastname' => $record->studentlastname,
                    'firstnamephonetic' => $record->studentfirstnamephonetic,
                    'lastnamephonetic' => $record->studentlastnamephonetic,
                    'middlename' => $record->studentmiddlename,
                    'alternatename' => $record->studentalternatename,
                ]),
                'tutor' => fullname((object)[
                    'firstname' => $record->tutorfirstname,
                    'lastname' => $record->tutorlastname,
                    'firstnamephonetic' => $record->tutorfirstnamephonetic,
                    'lastnamephonetic' => $record->tutorlastnamephonetic,
                    'middlename' => $record->tutormiddlename,
                    'alternatename' => $record->tutoralternatename,
                ]),
                'course' => $record->courseid ? format_string((string)self::course_name((int)$record->courseid)) : '',
                'status' => $record->status,
                'timeassigned' => transform::datetime($record->timeassigned),
                'timemodified' => transform::datetime($record->timemodified),
            ];
        }

        if (!empty($data)) {
            writer::with_context($context)->export_data(
                [get_string('privacy:path', 'local_studenttutor'), get_string('privacy:assignments', 'local_studenttutor')],
                (object)['assignments' => $data]
            );
        }
    }

    /**
     * Export the tutoring history records of a user.
     *
     * @param int $userid The user id.
     * @param \context $context The system context.
     * @return void
     */
    private static function export_history(int $userid, \context $context) {
        global $DB;

        $sql = "SELECT h.id, h.courseid, h.activitytype, h.description, h.activity_date,
                       h.timecreated, h.timemodified,
                       st.firstname AS studentfirstname, st.lastname AS studentlastname,
                       st.firstnamephonetic AS studentfirstnamephonetic,
                       st.lastnamephonetic AS studentlastnamephonetic,
                       st.middlename AS studentmiddlename, st.alternatename AS studentalternatename,
                       tu.firstname AS tutorfirstname, tu.lastname AS tutorlastname,
                       tu.firstnamephonetic AS tutorfirstnamephonetic,
                       tu.lastnamephonetic AS tutorlastnamephonetic,
                       tu.middlename AS tutormiddlename, tu.alternatename AS tutoralternatename,
                       cb.firstname AS createdbyfirstname, cb.lastname AS createdbylastname,
                       cb.firstnamephonetic AS createdbyfirstnamephonetic,
                       cb.lastnamephonetic AS createdbylastnamephonetic,
                       cb.middlename AS createdbymiddlename, cb.alternatename AS createdbyalternatename
                  FROM {local_studenttutor_history} h
                  JOIN {user} st ON st.id = h.studentid
                  JOIN {user} tu ON tu.id = h.tutorid
                  LEFT JOIN {user} cb ON cb.id = h.createdby
                 WHERE h.studentid = :suserid OR h.tutorid = :tuserid OR h.createdby = :cuserid
              ORDER BY h.activity_date ASC, h.timecreated ASC";

        $params = ['suserid' => $userid, 'tuserid' => $userid, 'cuserid' => $userid];
        $records = $DB->get_records_sql($sql, $params);

        $data = [];
        foreach ($records as $record) {
            $data[] = (object)[
                'student' => fullname((object)[
                    'firstname' => $record->studentfirstname,
                    'lastname' => $record->studentlastname,
                    'firstnamephonetic' => $record->studentfirstnamephonetic,
                    'lastnamephonetic' => $record->studentlastnamephonetic,
                    'middlename' => $record->studentmiddlename,
                    'alternatename' => $record->studentalternatename,
                ]),
                'tutor' => fullname((object)[
                    'firstname' => $record->tutorfirstname,
                    'lastname' => $record->tutorlastname,
                    'firstnamephonetic' => $record->tutorfirstnamephonetic,
                    'lastnamephonetic' => $record->tutorlastnamephonetic,
                    'middlename' => $record->tutormiddlename,
                    'alternatename' => $record->tutoralternatename,
                ]),
                'createdby' => $record->createdbyfirstname ? fullname((object)[
                    'firstname' => $record->createdbyfirstname,
                    'lastname' => $record->createdbylastname,
                    'firstnamephonetic' => $record->createdbyfirstnamephonetic,
                    'lastnamephonetic' => $record->createdbylastnamephonetic,
                    'middlename' => $record->createdbymiddlename,
                    'alternatename' => $record->createdbyalternatename,
                ]) : '',
                'course' => $record->courseid ? format_string((string)self::course_name((int)$record->courseid)) : '',
                'activitytype' => $record->activitytype,
                'description' => format_text($record->description, FORMAT_PLAIN),
                'activitydate' => $record->activity_date
                    ? transform::datetime($record->activity_date)
                    : transform::datetime($record->timecreated),
                'timecreated' => transform::datetime($record->timecreated),
                'timemodified' => transform::datetime($record->timemodified),
            ];
        }

        if (!empty($data)) {
            writer::with_context($context)->export_data(
                [get_string('privacy:path', 'local_studenttutor'), get_string('privacy:history', 'local_studenttutor')],
                (object)['history' => $data]
            );
        }
    }

    /**
     * Get the display name of a course without breaking on missing courses.
     *
     * @param int $courseid The course id.
     * @return string
     */
    private static function course_name(int $courseid): string {
        global $DB;

        $name = $DB->get_field('course', 'fullname', ['id' => $courseid]);

        return $name === false ? '' : $name;
    }

    /**
     * Delete every row in which any of the given users is referenced.
     *
     * @param array $userids The user ids.
     * @return void
     */
    private static function delete_rows_for_users(array $userids) {
        global $DB;

        if (empty($userids)) {
            return;
        }

        foreach (self::USER_REFERENCE_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
                $DB->delete_records_select($table, $column . ' ' . $insql, $params);
            }
        }
    }
}
