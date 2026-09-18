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
 * Privacy provider tests.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_studenttutor\privacy\provider
 */

namespace local_studenttutor\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider tests.
 *
 * @covers \local_studenttutor\privacy\provider
 */
class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * The metadata describes the two tables holding personal data.
     */
    public function test_get_metadata() {
        $collection = new collection('local_studenttutor');
        $newcollection = provider::get_metadata($collection);
        $items = $newcollection->get_collection();

        $this->assertCount(2, $items);

        $tables = [];
        foreach ($items as $item) {
            $tables[] = $item->get_name();
        }
        $this->assertContains('local_studenttutor_assign', $tables);
        $this->assertContains('local_studenttutor_history', $tables);
        // Configuration data must not be declared as personal data.
        $this->assertNotContains('local_studenttutor_activity_types', $tables);
    }

    /**
     * Every declared string exists, in both languages.
     */
    public function test_metadata_strings_exist() {
        $collection = new collection('local_studenttutor');
        $newcollection = provider::get_metadata($collection);

        foreach ($newcollection->get_collection() as $item) {
            $summary = $item->get_summary();
            $this->assertTrue(
                get_string_manager()->string_exists($summary, 'local_studenttutor'),
                'Missing metadata summary string: ' . $summary
            );

            foreach ($item->get_privacy_fields() as $field => $key) {
                $this->assertTrue(
                    get_string_manager()->string_exists($key, 'local_studenttutor'),
                    'Missing field string for ' . $field . ': ' . $key
                );
            }
        }
    }

    /**
     * The plugin ships a complete pt_br translation of the English strings.
     *
     * The plugin language files are read directly because the pt_br language
     * pack is not necessarily installed in the test environment.
     */
    public function test_pt_br_language_file_is_in_sync() {
        global $CFG;

        $en = $this->load_language_file($CFG->dirroot . '/local/studenttutor/lang/en/local_studenttutor.php');
        $ptbr = $this->load_language_file($CFG->dirroot . '/local/studenttutor/lang/pt_br/local_studenttutor.php');

        $enkeys = array_keys($en);
        $ptbrkeys = array_keys($ptbr);
        sort($enkeys);
        sort($ptbrkeys);

        $this->assertSame($enkeys, $ptbrkeys, 'Both language files must declare exactly the same keys.');

        foreach ($ptbr as $key => $value) {
            $this->assertNotSame('', trim($value), 'Empty pt_br value for ' . $key);
        }
    }

    /**
     * Load a plugin language file and return the strings it declares.
     *
     * @param string $path Absolute path to the language file.
     * @return array
     */
    private function load_language_file(string $path): array {
        $string = [];
        include($path);
        return $string;
    }

    /**
     * Contexts are returned only for users with data.
     */
    public function test_get_contexts_for_userid() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $generator->create_assignment($student->id, $tutor->id, 0);

        $context = \context_system::instance();

        $contextlist = provider::get_contexts_for_userid($student->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals([$context->id], $contextlist->get_contextids());

        $contextlist = provider::get_contexts_for_userid($tutor->id);
        $this->assertCount(1, $contextlist);

        $contextlist = provider::get_contexts_for_userid($other->id);
        $this->assertCount(0, $contextlist);
    }

    /**
     * All users referenced by the plugin are discovered in the system context.
     */
    public function test_get_users_in_context() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $this->setAdminUser();

        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $generator->create_assignment($student->id, $tutor->id, 0);
        $generator->create_history_entry($student->id, $tutor->id);

        $userlist = new userlist(\context_system::instance(), 'local_studenttutor');
        provider::get_users_in_context($userlist);

        $userids = array_map('intval', $userlist->get_userids());
        $this->assertContains((int)$student->id, $userids);
        $this->assertContains((int)$tutor->id, $userids);
    }

    /**
     * Users are not listed for contexts other than the system one.
     */
    public function test_get_users_in_course_context_returns_nothing() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $generator->create_assignment($student->id, $tutor->id, $course->id);

        $userlist = new userlist(\context_course::instance($course->id), 'local_studenttutor');
        provider::get_users_in_context($userlist);

        $this->assertCount(0, $userlist->get_userids());
    }

    /**
     * The export contains the assignments and history of the user.
     */
    public function test_export_user_data() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();

        $generator->create_assignment($student->id, $tutor->id, $course->id);
        $generator->create_history_entry($student->id, $tutor->id, 'tipopriv', 'Relato do estudante', $course->id);
        $generator->create_assignment($other->id, $tutor->id, $course->id);

        $context = \context_system::instance();
        $this->export_context_data_for_user($student->id, $context, 'local_studenttutor');

        $writer = writer::with_context($context);

        $assignments = $writer->get_data([
            get_string('privacy:path', 'local_studenttutor'),
            get_string('privacy:assignments', 'local_studenttutor'),
        ]);
        $this->assertNotEmpty($assignments);
        $this->assertCount(1, $assignments->assignments);
        $this->assertEquals(format_string($course->fullname), reset($assignments->assignments)->course);

        $history = $writer->get_data([
            get_string('privacy:path', 'local_studenttutor'),
            get_string('privacy:history', 'local_studenttutor'),
        ]);
        $this->assertNotEmpty($history);
        $this->assertCount(1, $history->history);
        $this->assertEquals('Relato do estudante', reset($history->history)->description);
    }

    /**
     * No data is exported for an unrelated user.
     */
    public function test_export_user_data_without_records() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $unrelated = $this->getDataGenerator()->create_user();
        $generator->create_assignment($student->id, $tutor->id, 0);

        $context = \context_system::instance();
        $this->assertFalse(writer::with_context($context)->has_any_data());

        $this->export_context_data_for_user($unrelated->id, $context, 'local_studenttutor');
        $this->assertFalse(writer::with_context($context)->has_any_data());
    }

    /**
     * Deleting a user removes only the rows referencing that user.
     */
    public function test_delete_data_for_user() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $otherstudent = $this->getDataGenerator()->create_user();
        $othertutor = $this->getDataGenerator()->create_user();

        $generator->create_assignment($student->id, $tutor->id, $course->id);
        $generator->create_history_entry($student->id, $tutor->id, 'tipodel', 'Do estudante', $course->id);
        $generator->create_assignment($otherstudent->id, $othertutor->id, $course->id);
        $generator->create_history_entry($otherstudent->id, $othertutor->id, 'tipodel', 'Do outro', $course->id);

        $context = \context_system::instance();
        $contextlist = new contextlist();
        $contextlist->add_system_context();
        $approved = new approved_contextlist($student, 'local_studenttutor', $contextlist->get_contextids());
        provider::delete_data_for_user($approved);

        $this->assertEquals(1, $DB->count_records('local_studenttutor_assign'));
        $this->assertEquals(1, $DB->count_records('local_studenttutor_history'));
        $this->assertEquals(0, $DB->count_records('local_studenttutor_assign', ['studentid' => $student->id]));
        $this->assertTrue($DB->record_exists('local_studenttutor_assign', ['studentid' => $otherstudent->id]));
    }

    /**
     * Deleting a list of users removes their rows.
     */
    public function test_delete_data_for_users() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course = $this->getDataGenerator()->create_course();
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $student3 = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();

        $generator->create_assignment($student1->id, $tutor->id, $course->id);
        $generator->create_assignment($student2->id, $tutor->id, $course->id);
        $generator->create_assignment($student3->id, $tutor->id, $course->id);

        $context = \context_system::instance();
        $userlist = new userlist($context, 'local_studenttutor');
        $userlist->add_users([$student1->id, $student2->id]);
        $approved = new approved_userlist($context, 'local_studenttutor', $userlist->get_userids());
        provider::delete_data_for_users($approved);

        $this->assertEquals(1, $DB->count_records('local_studenttutor_assign'));
        $this->assertTrue($DB->record_exists('local_studenttutor_assign', ['studentid' => $student3->id]));
    }

    /**
     * Deleting in the system context removes every row.
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $generator->create_assignment($student->id, $tutor->id, 0);
        $generator->create_history_entry($student->id, $tutor->id);

        provider::delete_data_for_all_users_in_context(\context_system::instance());

        $this->assertEquals(0, $DB->count_records('local_studenttutor_assign'));
        $this->assertEquals(0, $DB->count_records('local_studenttutor_history'));
    }

    /**
     * Deleting in an unrelated context keeps the data.
     */
    public function test_delete_data_for_all_users_in_other_context() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $generator->create_assignment($student->id, $tutor->id, $course->id);

        provider::delete_data_for_all_users_in_context(\context_course::instance($course->id));

        $this->assertEquals(1, $DB->count_records('local_studenttutor_assign'));
    }
}
