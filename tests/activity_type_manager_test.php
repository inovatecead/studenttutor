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
 * Unit tests for activity_type_manager.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_studenttutor\activity_type_manager
 */

namespace local_studenttutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Activity type manager tests.
 *
 * @covers \local_studenttutor\activity_type_manager
 */
class activity_type_manager_test extends \advanced_testcase {
    /**
     * Required fields are enforced.
     */
    public function test_create_activity_type_requires_name_and_shortname() {
        $this->resetAfterTest();

        $this->assertFalse(activity_type_manager::create_activity_type(['name' => '', 'shortname' => 'algo']));
        $this->assertFalse(activity_type_manager::create_activity_type(['name' => 'Algo', 'shortname' => '']));
        $this->assertEquals(0, $this->count_types());
    }

    /**
     * The shortname must be unique.
     */
    public function test_create_activity_type_shortname_is_unique() {
        $this->resetAfterTest();

        $first = activity_type_manager::create_activity_type([
            'name' => 'Primeiro',
            'shortname' => 'unico',
        ]);
        $this->assertIsInt($first);

        $this->assertFalse(activity_type_manager::create_activity_type([
            'name' => 'Segundo',
            'shortname' => 'unico',
        ]));
        $this->assertEquals(1, $this->count_types());
    }

    /**
     * Defaults are applied to the optional fields.
     */
    public function test_create_activity_type_defaults() {
        $this->resetAfterTest();

        $id = activity_type_manager::create_activity_type([
            'name' => 'Com padrões',
            'shortname' => 'compadroes',
        ]);

        $record = activity_type_manager::get_activity_type_by_shortname('compadroes');
        $this->assertEquals($id, $record->id);
        $this->assertEquals('fa-circle', $record->icon);
        $this->assertEquals('#007bff', $record->color);
        $this->assertEquals(1, $record->active);
        $this->assertGreaterThan(0, $record->timecreated);
        $this->assertGreaterThan(0, $record->timemodified);
    }

    /**
     * Listing can be restricted to active types.
     */
    public function test_get_activity_types_active_filter() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $generator->create_activity_type('Ativo', 'ativo', 1);
        $generator->create_activity_type('Inativo', 'inativo', 0);

        $this->assertCount(2, activity_type_manager::get_activity_types(false));
        $this->assertCount(1, activity_type_manager::get_activity_types(true));

        $options = activity_type_manager::get_activity_types_options(true);
        $this->assertArrayHasKey('ativo', $options);
        $this->assertArrayNotHasKey('inativo', $options);
        $this->assertEquals('Ativo', $options['ativo']);
    }

    /**
     * Updating an activity type.
     */
    public function test_update_activity_type() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $id = $generator->create_activity_type('Original', 'origem');

        $this->assertTrue(activity_type_manager::update_activity_type($id, [
            'name' => 'Renomeado',
            'active' => 0,
            'id' => 999999,
        ]));

        $record = activity_type_manager::get_activity_type_by_shortname('origem');
        $this->assertEquals($id, $record->id);
        $this->assertEquals('Renomeado', $record->name);
        $this->assertEquals(0, $record->active);
    }

    /**
     * A shortname conflict blocks the update.
     */
    public function test_update_activity_type_rejects_shortname_conflict() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $generator->create_activity_type('Primeiro', 'primeiro');
        $second = $generator->create_activity_type('Segundo', 'segundo');

        $this->assertFalse(activity_type_manager::update_activity_type($second, ['shortname' => 'primeiro']));
        $this->assertEquals('segundo', activity_type_manager::get_activity_type_by_shortname('segundo')->shortname);
    }

    /**
     * An unknown activity type cannot be updated.
     */
    public function test_update_activity_type_unknown_id() {
        $this->resetAfterTest();

        $this->assertFalse(activity_type_manager::update_activity_type(999999, ['name' => 'x']));
    }

    /**
     * Unused activity types are deleted.
     */
    public function test_delete_activity_type_when_unused() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $id = $generator->create_activity_type('Descartável', 'descartavel');

        $this->assertTrue(activity_type_manager::delete_activity_type($id));
        $this->assertFalse(activity_type_manager::get_activity_type_by_shortname('descartavel'));
        $this->assertEquals(0, $this->count_types());
    }

    /**
     * Activity types in use are deactivated instead of deleted.
     *
     * Documents the intentional behaviour of delete_activity_type(): deleting a
     * type that is referenced by history entries would destroy tutoring records,
     * so the type is only disabled.
     */
    public function test_delete_activity_type_when_used_is_deactivated() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $id = $generator->create_activity_type('Em uso', 'emuso');
        $generator->create_history_entry(null, null, 'emuso', 'Registro que usa o tipo');

        $this->assertTrue(activity_type_manager::delete_activity_type($id));

        $record = activity_type_manager::get_activity_type_by_shortname('emuso');
        $this->assertNotFalse($record, 'The activity type must not be deleted while in use.');
        $this->assertEquals(0, $record->active);
        $this->assertEquals(1, $this->count_types());
    }

    /**
     * An unknown activity type cannot be deleted.
     */
    public function test_delete_activity_type_unknown_id() {
        $this->resetAfterTest();

        $this->assertFalse(activity_type_manager::delete_activity_type(999999));
    }

    /**
     * Reordering updates the sort order.
     */
    public function test_reorder_activity_types() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $first = $generator->create_activity_type('Primeiro', 'primeiro', 1, 1);
        $second = $generator->create_activity_type('Segundo', 'segundo', 1, 2);

        $this->assertTrue(activity_type_manager::reorder_activity_types([
            $first => 20,
            $second => 10,
        ]));

        $this->assertEquals(20, activity_type_manager::get_activity_type_by_shortname('primeiro')->sortorder);
        $this->assertEquals(10, activity_type_manager::get_activity_type_by_shortname('segundo')->sortorder);

        $types = activity_type_manager::get_activity_types(true);
        $this->assertEquals($second, reset($types)->id, 'The lowest sort order must come first.');
    }

    /**
     * Statistics report the number of uses per type.
     */
    public function test_get_statistics() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $generator->create_activity_type('Contado', 'contado');
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $generator->create_history_entry($student->id, $tutor->id, 'contado', 'Um');
        $generator->create_history_entry($student->id, $tutor->id, 'contado', 'Dois');

        $stats = activity_type_manager::get_statistics();
        $this->assertNotEmpty($stats);

        $found = null;
        foreach ($stats as $stat) {
            if ($stat->shortname === 'contado') {
                $found = $stat;
            }
        }
        $this->assertNotNull($found, 'The activity type must appear in the statistics.');
        $this->assertEquals(2, $found->usage_count);
    }

    /**
     * Count the activity types.
     *
     * @return int
     */
    private function count_types(): int {
        global $DB;

        return $DB->count_records('local_studenttutor_activity_types');
    }
}
