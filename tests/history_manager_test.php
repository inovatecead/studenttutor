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
 * Unit tests for history_manager.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_studenttutor\history_manager
 */

namespace local_studenttutor;

defined('MOODLE_INTERNAL') || die();

/**
 * History manager tests.
 *
 * @covers \local_studenttutor\history_manager
 */
class history_manager_test extends \advanced_testcase {
    /**
     * Every service argument must land in its own column.
     *
     * Regression test: the arguments used to be passed in the wrong order, so
     * the tutoring notes ended up stored in the course column.
     */
    public function test_add_history_entry_maps_arguments_to_columns() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $generator->create_activity_type('Retorno de dúvidas', 'retorno_duvidas');

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $author = $this->getDataGenerator()->create_user();
        $activitydate = time() - DAYSECS;

        $entryid = history_manager::add_history_entry(
            $student->id,
            $tutor->id,
            'retorno_duvidas',
            'Descrição real da tutoria',
            $course->id,
            $author->id,
            $activitydate
        );

        $this->assertIsInt($entryid);
        $record = history_manager::get_history_entry($entryid);

        $this->assertEquals($student->id, $record->studentid);
        $this->assertEquals($tutor->id, $record->tutorid);
        $this->assertEquals('retorno_duvidas', $record->activitytype);
        $this->assertEquals('Descrição real da tutoria', $record->description);
        $this->assertEquals($course->id, $record->courseid);
        $this->assertEquals($author->id, $record->createdby);
        $this->assertEquals($activitydate, $record->activity_date);
        $this->assertGreaterThan(0, $record->timecreated);
        $this->assertGreaterThan(0, $record->timemodified);
    }

    /**
     * The author defaults to the acting user and the date to the current time.
     */
    public function test_add_history_entry_defaults() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $generator->create_activity_type('Tipo padrão', 'tipopadrao');

        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $actor = $this->getDataGenerator()->create_user();
        $this->setUser($actor);

        $before = time();
        $entryid = history_manager::add_history_entry($student->id, $tutor->id, 'tipopadrao', 'Registro');
        $after = time();

        $record = history_manager::get_history_entry($entryid);
        $this->assertEquals($actor->id, $record->createdby);
        $this->assertGreaterThanOrEqual($before, $record->activity_date);
        $this->assertLessThanOrEqual($after, $record->activity_date);
    }

    /**
     * Unknown activity types are rejected.
     */
    public function test_add_history_entry_rejects_unknown_activity_type() {
        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();

        $this->assertFalse(history_manager::add_history_entry(
            $student->id,
            $tutor->id,
            'tipo_que_nao_existe',
            'Descrição'
        ));
        // The rejection is reported as developer diagnostics.
        $this->assertDebuggingCalled('Invalid activity type: tipo_que_nao_existe', DEBUG_DEVELOPER);
        $this->assertEquals(0, $this->count_history());
    }

    /**
     * An empty description is rejected.
     */
    public function test_add_history_entry_rejects_empty_description() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $generator->create_activity_type('Tipo válido', 'tipovalido');

        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();

        $this->assertFalse(history_manager::add_history_entry($student->id, $tutor->id, 'tipovalido', '   '));
        // The rejection is reported as developer diagnostics.
        $this->assertDebuggingCalled('Description cannot be empty', DEBUG_DEVELOPER);
        $this->assertEquals(0, $this->count_history());
    }

    /**
     * Unknown users are rejected.
     */
    public function test_add_history_entry_rejects_unknown_users() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $generator->create_activity_type('Tipo válido', 'tipovalido');

        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();

        $this->assertFalse(history_manager::add_history_entry(999999, $tutor->id, 'tipovalido', 'Descrição'));
        $this->assertFalse(history_manager::add_history_entry($student->id, 999999, 'tipovalido', 'Descrição'));
        // Both rejections are reported as developer diagnostics.
        $this->resetDebugging();
        $this->assertEquals(0, $this->count_history());
    }

    /**
     * Updating an entry only changes the allowed fields.
     */
    public function test_update_history_entry() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $generator->create_activity_type('Tipo A', 'tipoa');
        $generator->create_activity_type('Tipo B', 'tipob');

        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $activitydate = time() - (2 * DAYSECS);

        $entryid = $generator->create_history_entry($student->id, $tutor->id, 'tipoa', 'Descrição original');

        $this->assertTrue(history_manager::update_history_entry($entryid, [
            'activitytype' => 'tipob',
            'description' => 'Descrição atualizada',
            'activity_date' => $activitydate,
            'studentid' => $otheruser->id,
        ]));

        $record = history_manager::get_history_entry($entryid);
        $this->assertEquals('tipob', $record->activitytype);
        $this->assertEquals('Descrição atualizada', $record->description);
        $this->assertEquals($activitydate, $record->activity_date);
        // studentid is not an allowed field, so it must stay untouched.
        $this->assertEquals($student->id, $record->studentid);
    }

    /**
     * Updating an unknown entry returns false.
     */
    public function test_update_history_entry_unknown_id() {
        $this->resetAfterTest();

        $this->assertFalse(history_manager::update_history_entry(999999, ['description' => 'x']));
    }

    /**
     * Deleting an entry removes the row.
     */
    public function test_delete_history_entry() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $entryid = $generator->create_history_entry();

        $this->assertTrue(history_manager::delete_history_entry($entryid));
        $this->assertEquals(0, $this->count_history());
        $this->assertFalse(history_manager::get_history_entry($entryid));
    }

    /**
     * Filters scope the returned entries.
     */
    public function test_get_history_entries_filters() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $generator->create_activity_type('Tipo A', 'tipoa');
        $generator->create_activity_type('Tipo B', 'tipob');

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $otherstudent = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();

        $generator->create_history_entry($student->id, $tutor->id, 'tipoa', 'Entrada 1', $course1->id);
        $generator->create_history_entry($student->id, $tutor->id, 'tipob', 'Entrada 2', $course1->id);
        $generator->create_history_entry($otherstudent->id, $tutor->id, 'tipoa', 'Entrada 3', $course2->id);

        $this->assertCount(3, history_manager::get_history_entries());
        $this->assertCount(2, history_manager::get_history_entries(['studentid' => $student->id]));
        $this->assertCount(2, history_manager::get_history_entries(['courseid' => $course1->id]));
        $this->assertCount(2, history_manager::get_history_entries(['activitytype' => 'tipoa']));
        $this->assertCount(
            1,
            history_manager::get_history_entries(['studentid' => $student->id, 'activitytype' => 'tipob'])
        );
        $this->assertCount(
            3,
            history_manager::get_history_entries(['datefrom' => time() - HOURSECS])
        );
        $this->assertCount(
            0,
            history_manager::get_history_entries(['datefrom' => time() + DAYSECS])
        );
    }

    /**
     * The pair history returns the entries of one student-tutor pair.
     */
    public function test_get_pair_history() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $student = $this->getDataGenerator()->create_user();
        $otherstudent = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();

        $generator->create_history_entry($student->id, $tutor->id, 'tipopar', 'Do par');
        $generator->create_history_entry($otherstudent->id, $tutor->id, 'tipopar', 'De outro par');

        $this->assertCount(1, history_manager::get_pair_history($student->id, $tutor->id));
        $this->assertCount(0, history_manager::get_pair_history($student->id, $otherstudent->id));
    }

    /**
     * The detailed listing only returns entries with an active assignment.
     *
     * This documents the behaviour inherited from the original implementation:
     * a history entry whose student-tutor pair has no active assignment is not
     * returned by get_history_with_details().
     */
    public function test_get_history_with_details_requires_active_assignment() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();

        $generator->create_history_entry($student->id, $tutor->id, 'tipodetalhe', 'Sem atribuição', $course->id);
        $this->assertCount(0, history_manager::get_history_with_details());

        $generator->create_assignment($student->id, $tutor->id, $course->id);
        $details = history_manager::get_history_with_details();

        $this->assertCount(1, $details);
        $entry = reset($details);
        $this->assertEquals($student->id, $entry->studentid);
        $this->assertEquals($course->id, $entry->courseid);
        $this->assertTrue(property_exists($entry, 'course_name'));
        $this->assertFalse(property_exists($entry, 'title'), 'The title column does not exist.');
    }

    /**
     * The detail queries must return every name field of the joined users.
     *
     * Regression test: only firstname/lastname used to be selected, so fullname()
     * warned about missing name fields and the site settings for phonetic, middle
     * and alternate names were ignored.
     */
    public function test_detail_queries_return_all_name_fields() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $generator->create_assignment($student->id, $tutor->id, $course->id);
        $generator->create_history_entry($student->id, $tutor->id, 'tipodetalhe', 'Registro', $course->id);

        $namefields = \core_user\fields::get_name_fields();
        $queries = [
            'get_history_entries' => [
                'records' => history_manager::get_history_entries(),
                'prefixes' => ['tutor_', 'student_', 'createdby_'],
            ],
            'get_history_with_details' => [
                'records' => history_manager::get_history_with_details(),
                'prefixes' => ['tutor_', 'student_'],
            ],
        ];

        foreach ($queries as $method => $query) {
            $this->assertCount(1, $query['records'], "{$method} must return the created entry.");
            $record = reset($query['records']);

            foreach ($query['prefixes'] as $prefix) {
                foreach ($namefields as $field) {
                    $this->assertTrue(
                        property_exists($record, $prefix . $field),
                        "{$method} must return {$prefix}{$field}."
                    );
                }
            }

            $this->assertEquals(
                fullname($tutor),
                fullname(username_load_fields_from_object((object) [], $record, 'tutor_')),
                "{$method} must build the tutor display name correctly."
            );
        }
    }

    /**
     * Activity statistics aggregate the stored entries.
     */
    public function test_get_activity_statistics() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $generator->create_activity_type('Tipo A', 'tipoestat');
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();

        $generator->create_history_entry($student->id, $tutor->id, 'tipoestat', 'Um');
        $generator->create_history_entry($student->id, $tutor->id, 'tipoestat', 'Dois');

        $stats = history_manager::get_activity_statistics();
        $this->assertEquals(2, $stats['total_activities']);
        $this->assertArrayHasKey('tipoestat', $stats['by_type']);
        $this->assertEquals(2, $stats['by_type']['tipoestat']->count);
    }

    /**
     * Count the history rows.
     *
     * @return int
     */
    private function count_history(): int {
        global $DB;

        return $DB->count_records('local_studenttutor_history');
    }
}
