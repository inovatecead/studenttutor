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
 * Tests for the web services of the Nexo Tutoria Acadêmica plugin.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_studenttutor\external\create_assignment
 * @covers     \local_studenttutor\external\add_history
 * @covers     \local_studenttutor\external\get_assignments
 * @covers     \local_studenttutor\external\get_history
 */

namespace local_studenttutor;

use local_studenttutor\external\add_history;
use local_studenttutor\external\create_assignment;
use local_studenttutor\external\get_assignments;
use local_studenttutor\external\get_history;

defined('MOODLE_INTERNAL') || die();

/**
 * Web service tests.
 *
 * The external classes include lib/externallib.php, which requires the test to
 * run in an isolated process.
 *
 * @covers \local_studenttutor\external\create_assignment
 * @covers \local_studenttutor\external\add_history
 * @covers \local_studenttutor\external\get_assignments
 * @covers \local_studenttutor\external\get_history
 * @runTestsInSeparateProcesses
 */
class external_test extends \advanced_testcase {
    /**
     * Load lib/externallib.php inside the isolated test process.
     */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();

        // Including this library requires an isolated process, which this class
        // requests through the @runTestsInSeparateProcesses annotation.
        require_once($CFG->libdir . '/externallib.php');
    }

    /**
     * The tutor and student arguments must not be swapped.
     *
     * Regression test: the external function used to pass its arguments in the
     * opposite order expected by the manager, creating assignments with the
     * tutor stored as the student.
     */
    public function test_create_assignment_does_not_swap_roles() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();

        $result = create_assignment::execute($tutor->id, $student->id, $course->id);

        $this->assertTrue($result['success']);
        $record = assignment_manager::get_assignment($result['assignmentid']);
        $this->assertEquals($student->id, $record->studentid);
        $this->assertEquals($tutor->id, $record->tutorid);
        $this->assertEquals($course->id, $record->courseid);
    }

    /**
     * The author of the assignment cannot be forged by the caller.
     */
    public function test_create_assignment_ignores_client_supplied_author() {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $attacker = $this->getDataGenerator()->create_user();

        $result = create_assignment::execute($tutor->id, $student->id, $course->id, $attacker->id);

        $this->assertTrue($result['success']);
        $record = assignment_manager::get_assignment($result['assignmentid']);
        $this->assertEquals($USER->id, $record->assignedby);
        $this->assertNotEquals($attacker->id, $record->assignedby);
    }

    /**
     * Creating an assignment requires the management capability.
     */
    public function test_create_assignment_requires_capability() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\required_capability_exception::class);
        create_assignment::execute($tutor->id, $student->id, $course->id);
    }

    /**
     * Adding a history entry maps every argument to the right column.
     */
    public function test_add_history_maps_arguments() {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $generator->create_activity_type('Retorno de dúvidas', 'retorno_duvidas');

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();

        $result = add_history::execute(
            $student->id,
            $tutor->id,
            'retorno_duvidas',
            'Título ignorado',
            'Descrição real da tutoria',
            $course->id
        );

        $this->assertTrue($result['success']);

        $record = history_manager::get_history_entry($result['historyid']);
        $this->assertEquals('retorno_duvidas', $record->activitytype);
        $this->assertEquals('Descrição real da tutoria', $record->description);
        $this->assertEquals($course->id, $record->courseid);
        $this->assertEquals($USER->id, $record->createdby);
        $this->assertEquals($student->id, $record->studentid);
        $this->assertEquals($tutor->id, $record->tutorid);
    }

    /**
     * Adding a history entry requires the management capability.
     */
    public function test_add_history_requires_capability() {
        $this->resetAfterTest();

        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\required_capability_exception::class);
        add_history::execute($student->id, $tutor->id, 'tipox', 'Título', 'Descrição');
    }

    /**
     * get_assignments must honour its contract and return usable data.
     *
     * Regression test: the default sort order referenced a column that does not
     * exist, so the query failed silently and the response returned an empty
     * list; the mapped fields timecreated/createdby did not exist either.
     */
    public function test_get_assignments_contract() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $generator->create_assignment($student->id, $tutor->id, $course->id);

        $result = $this->call_ajax('local_studenttutor_get_assignments');

        $this->assertFalse(
            $result['error'],
            'The web service must not fail with the default sorting: ' . $this->exception_message($result)
        );
        $assignments = $result['data']['assignments'];
        $this->assertCount(1, $assignments);

        $assignment = reset($assignments);
        $this->assertEquals($student->id, $assignment['studentid']);
        $this->assertEquals($tutor->id, $assignment['tutorid']);
        $this->assertGreaterThan(0, $assignment['timecreated']);
        $this->assertGreaterThan(0, $assignment['createdby']);
        $this->assertEquals(fullname(username_load_fields_from_object((object) [], $student, '')), $assignment['student_name']);
        $this->assertEquals(fullname(username_load_fields_from_object((object) [], $tutor, '')), $assignment['tutor_name']);
        $this->assertEquals($course->fullname, $assignment['course_name']);
    }

    /**
     * get_history must honour its contract, including the underscore shortnames.
     *
     * Regression test: the activity type filter used PARAM_ALPHA, which strips
     * the underscore and therefore never matched the real shortnames; the
     * response also declared a "title" field whose column does not exist.
     */
    public function test_get_history_contract_and_activity_filter() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $generator->create_assignment($student->id, $tutor->id, $course->id);
        $generator->create_history_entry($student->id, $tutor->id, 'retorno_duvidas', 'Registro', $course->id);

        $result = $this->call_ajax('local_studenttutor_get_history', [
            'filters' => ['activitytype' => 'retorno_duvidas'],
        ]);

        $this->assertFalse($result['error'], 'The web service must not fail: ' . $this->exception_message($result));
        $history = $result['data']['history'];
        $this->assertCount(1, $history, 'The activity type filter must accept shortnames with underscores.');

        $entry = reset($history);
        $this->assertEquals('retorno_duvidas', $entry['activitytype']);
        $this->assertEquals('Registro', $entry['description']);
        $this->assertEquals($course->id, $entry['courseid']);
        $this->assertEquals($student->id, $entry['studentid']);
        $this->assertEquals(fullname(username_load_fields_from_object((object) [], $student, '')), $entry['student_name']);
        $this->assertEquals(fullname(username_load_fields_from_object((object) [], $tutor, '')), $entry['tutor_name']);
        $this->assertArrayNotHasKey('title', $entry);
    }

    /**
     * Reading assignments requires the view capability.
     */
    public function test_get_assignments_requires_capability() {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $result = $this->call_ajax('local_studenttutor_get_assignments');

        $this->assertTrue($result['error']);
        $this->assertEquals('nopermissions', $result['exception']->errorcode, $this->exception_message($result));
    }

    /**
     * Calls a plugin web service the same way the JavaScript layer does.
     *
     * These functions declare no "loginrequired" flag in db/services.php, so Moodle
     * defaults it to true, and \core_external\external_api::call_external_function()
     * then requires a session key whenever it is not reached through the web service
     * server (WS_SERVER). Supplying the key is what the browser sends along with an
     * AJAX request, so this mirrors the real call path instead of bypassing it.
     *
     * @param string $function Name of the web service function.
     * @param array $args Arguments to pass on.
     * @return array Result as returned by call_external_function().
     */
    private function call_ajax(string $function, array $args = []): array {
        $_POST['sesskey'] = sesskey();

        try {
            return \external_api::call_external_function($function, $args);
        } finally {
            unset($_POST['sesskey']);
        }
    }

    /**
     * Human readable detail of a failed external call.
     *
     * @param array $result Result returned by call_external_function().
     * @return string
     */
    private function exception_message(array $result): string {
        if (empty($result['exception'])) {
            return 'no exception reported';
        }

        $exception = $result['exception'];
        $errorcode = isset($exception->errorcode) ? $exception->errorcode : '?';
        $message = isset($exception->message) ? $exception->message : '?';

        return 'errorcode ' . $errorcode . ': ' . $message;
    }
}
