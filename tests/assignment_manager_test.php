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
 * Unit tests for assignment_manager.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studenttutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Assignment manager tests.
 *
 * @covers \local_studenttutor\assignment_manager
 */
class assignment_manager_test extends \advanced_testcase {
    /**
     * Create a new assignment storing the expected columns.
     */
    public function test_create_assignment_stores_expected_columns() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $admin = $this->getDataGenerator()->create_user();

        $assignmentid = assignment_manager::create_assignment(
            $student->id,
            $tutor->id,
            $course->id,
            $admin->id
        );

        $this->assertIsInt($assignmentid);
        $this->assertGreaterThan(0, $assignmentid);

        $record = assignment_manager::get_assignment($assignmentid);
        $this->assertEquals($student->id, $record->studentid);
        $this->assertEquals($tutor->id, $record->tutorid);
        $this->assertEquals($course->id, $record->courseid);
        $this->assertEquals($admin->id, $record->assignedby);
        $this->assertEquals(assignment_manager::STATUS_ACTIVE, $record->status);
        $this->assertGreaterThan(0, $record->timeassigned);
        $this->assertGreaterThan(0, $record->timemodified);
    }

    /**
     * Duplicate assignments are rejected with a translatable exception.
     */
    public function test_create_assignment_rejects_duplicates() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();

        assignment_manager::create_assignment($student->id, $tutor->id, $course->id);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('assignmentalreadyexists', 'local_studenttutor'));
        assignment_manager::create_assignment($student->id, $tutor->id, $course->id);
    }

    /**
     * A user cannot be their own tutor.
     */
    public function test_create_assignment_rejects_self_assignment() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse(assignment_manager::create_assignment($user->id, $user->id, $course->id));
        // The rejection is reported as developer diagnostics.
        $this->assertDebuggingCalled('Cannot assign user to themselves', DEBUG_DEVELOPER);
        $this->assertEquals(0, $this->count_assignments());
    }

    /**
     * Unknown users or courses are rejected.
     */
    public function test_create_assignment_rejects_invalid_data() {
        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse(assignment_manager::create_assignment(999999, $tutor->id, $course->id));
        $this->assertFalse(assignment_manager::create_assignment($student->id, 999999, $course->id));
        $this->assertFalse(assignment_manager::create_assignment($student->id, $tutor->id, 999999));
        // Every rejection is reported as developer diagnostics.
        $this->resetDebugging();
        $this->assertEquals(0, $this->count_assignments());
    }

    /**
     * The author of the assignment defaults to the acting user.
     */
    public function test_create_assignment_defaults_author_to_acting_user() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $actor = $this->getDataGenerator()->create_user();
        $this->setUser($actor);

        $assignmentid = assignment_manager::create_assignment($student->id, $tutor->id, $course->id);

        $record = assignment_manager::get_assignment($assignmentid);
        $this->assertEquals($actor->id, $record->assignedby);
    }

    /**
     * Updating an assignment changes the allowed fields only.
     */
    public function test_update_assignment() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $otherstudent = $this->getDataGenerator()->create_user();

        $assignmentid = assignment_manager::create_assignment($student->id, $tutor->id, $course->id);

        $sink = $this->redirectEvents();

        $this->assertTrue(assignment_manager::update_assignment($assignmentid, [
            'status' => assignment_manager::STATUS_COMPLETED,
            'studentid' => $otherstudent->id,
            'assignedby' => 999999,
        ]));

        $record = assignment_manager::get_assignment($assignmentid);
        $this->assertEquals(assignment_manager::STATUS_COMPLETED, $record->status);
        $this->assertEquals($otherstudent->id, $record->studentid);
        // Fields outside the allowed list must be ignored.
        $this->assertNotEquals(999999, $record->assignedby);

        $events = $sink->get_events();
        $sink->close();
        $this->assertNotEmpty($events);
        $this->assertInstanceOf(\local_studenttutor\event\assignment_updated::class, reset($events));
    }

    /**
     * Deleting an assignment removes the row.
     */
    public function test_delete_assignment() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();

        $assignmentid = $generator->create_assignment($student->id, $tutor->id, $course->id);

        $this->assertTrue(assignment_manager::delete_assignment($assignmentid));
        $this->assertEquals(0, $this->count_assignments());
        $this->assertFalse(assignment_manager::get_assignment($assignmentid));
    }

    /**
     * Existence check is scoped by student, tutor and course.
     */
    public function test_assignment_exists_scoping() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $othertutor = $this->getDataGenerator()->create_user();

        $assignmentid = $generator->create_assignment($student->id, $tutor->id, $course1->id);

        $this->assertTrue(assignment_manager::assignment_exists($student->id, $tutor->id, $course1->id));
        $this->assertFalse(assignment_manager::assignment_exists($student->id, $tutor->id, $course2->id));
        $this->assertFalse(assignment_manager::assignment_exists($student->id, $othertutor->id, $course1->id));
        $this->assertFalse(assignment_manager::assignment_exists(
            $student->id,
            $tutor->id,
            $course1->id,
            $assignmentid
        ));
    }

    /**
     * Course scoped and global assignments are both returned for the tutor.
     */
    public function test_get_tutor_students_including_global() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $tutor = $this->getDataGenerator()->create_user();
        $studentincourse = $this->getDataGenerator()->create_user();
        $globalstudent = $this->getDataGenerator()->create_user();
        $otherstudent = $this->getDataGenerator()->create_user();

        $generator->create_assignment($studentincourse->id, $tutor->id, $course->id);
        $generator->create_assignment($globalstudent->id, $tutor->id, 0);
        $generator->create_assignment($otherstudent->id, $tutor->id, $othercourse->id);

        $students = assignment_manager::get_tutor_students_including_global($tutor->id, $course->id);
        $ids = array_map(function ($assignment) {
            return (int)$assignment->studentid;
        }, $students);

        $this->assertContains((int)$studentincourse->id, $ids);
        $this->assertContains((int)$globalstudent->id, $ids);
        $this->assertNotContains((int)$otherstudent->id, $ids);
    }

    /**
     * The default sorting of the detailed listing must work against the real schema.
     *
     * Regression test: the default sort used to reference a column that does not
     * exist in local_studenttutor_assign, so the query failed and the method
     * silently returned an empty array.
     */
    public function test_get_all_assignments_with_details_default_sort() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course = $this->getDataGenerator()->create_course([], ['createsections' => true]);
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $generator->create_assignment($student->id, $tutor->id, $course->id);

        $result = assignment_manager::get_all_assignments_with_details();

        $this->assertCount(1, $result);
        $assignment = reset($result);
        $this->assertEquals($student->id, $assignment->studentid);
        $this->assertEquals($tutor->id, $assignment->tutorid);
        $this->assertObjectNotHasAttribute('title', $assignment);
        $this->assertTrue(property_exists($assignment, 'timeassigned'), 'timeassigned must be returned.');
        $this->assertTrue(property_exists($assignment, 'assignedby'), 'assignedby must be returned.');
        $this->assertGreaterThan(0, $assignment->timeassigned);
    }

    /**
     * The detail queries must return every name field of the joined users.
     *
     * Regression test: only firstname/lastname used to be selected, so fullname()
     * emitted "the following name fields are missing" developer warnings and the
     * display name ignored the phonetic, middle and alternate names configured by
     * the site.
     */
    public function test_detail_queries_return_all_name_fields() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $generator->create_assignment($student->id, $tutor->id, $course->id);

        $namefields = \core_user\fields::get_name_fields();
        $queries = [
            'get_assignments' => [
                'records' => assignment_manager::get_assignments(),
                'prefixes' => ['tutor_', 'student_'],
            ],
            'get_tutor_students_including_global' => [
                'records' => assignment_manager::get_tutor_students_including_global($tutor->id, $course->id),
                'prefixes' => ['tutor_', 'student_'],
            ],
            'get_all_assignments_with_details' => [
                'records' => assignment_manager::get_all_assignments_with_details(),
                'prefixes' => ['tutor_', 'student_'],
            ],
        ];

        foreach ($queries as $method => $query) {
            $this->assertCount(1, $query['records'], "{$method} must return the created assignment.");
            $record = reset($query['records']);

            foreach ($query['prefixes'] as $prefix) {
                foreach ($namefields as $field) {
                    $this->assertTrue(
                        property_exists($record, $prefix . $field),
                        "{$method} must return {$prefix}{$field}."
                    );
                }
            }

            // The complete object must resolve to the same name as the user record.
            $this->assertEquals(
                fullname($student),
                fullname(username_load_fields_from_object((object) [], $record, 'student_')),
                "{$method} must build the student display name correctly."
            );
        }
    }

    /**
     * Statistics reflect the stored assignments.
     */
    public function test_get_statistics() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course = $this->getDataGenerator()->create_course();
        $tutor = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        $generator->create_assignment($student1->id, $tutor->id, $course->id);
        $generator->create_assignment($student2->id, $tutor->id, $course->id);
        $generator->create_assignment($student2->id, $tutor->id, $course->id, 'inactive');

        $stats = assignment_manager::get_statistics();
        $this->assertEquals(3, $stats['total_assignments']);
        $this->assertEquals(2, $stats['active_assignments']);
        $this->assertEquals(1, $stats['unique_tutors']);
        $this->assertEquals(2, $stats['unique_students']);
    }

    /**
     * Count the assignment rows.
     *
     * @return int
     */
    private function count_assignments(): int {
        global $DB;

        return $DB->count_records('local_studenttutor_assign');
    }
}
