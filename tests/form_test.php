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
 * Tests for the plugin forms.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studenttutor;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/studenttutor/lib.php');

use local_studenttutor\form\activity_type_form;
use local_studenttutor\form\assignment_form;
use local_studenttutor\form\course_history_form;
use local_studenttutor\form\edit_history_form;

/**
 * Tests for the plugin forms.
 *
 * The forms used to be declared inside the page scripts, where they could not be
 * autoloaded. These tests keep them autoloadable and buildable.
 *
 * @covers \local_studenttutor\form\assignment_form
 * @covers \local_studenttutor\form\course_history_form
 * @covers \local_studenttutor\form\edit_history_form
 * @covers \local_studenttutor\form\activity_type_form
 */
class form_test extends \advanced_testcase {
    /**
     * Every form must be autoloadable and build its definition without errors.
     */
    public function test_forms_are_autoloadable_and_buildable() {
        global $DB, $PAGE, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Forms are normally built from a page, so the page url must be valid.
        $PAGE->set_url('/local/studenttutor/index.php');

        $generator = $this->getDataGenerator()->get_plugin_generator('local_studenttutor');
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $tutor = $this->getDataGenerator()->create_user();
        $generator->create_assignment($student->id, $tutor->id, $course->id);

        // The admin is made the tutor of one student so that the "add history entry"
        // form has a student to offer.
        $generator->create_assignment($student->id, $USER->id, $course->id);

        $historyid = $generator->create_history_entry($student->id, $tutor->id, 'tipoteste', 'Registro', $course->id);
        $history = $DB->get_record('local_studenttutor_history', ['id' => $historyid], '*', MUST_EXIST);

        $forms = [
            'activity_type_form (create)' => new activity_type_form(null, ['activity_type' => null]),
            'activity_type_form (edit)' => new activity_type_form(null, ['activity_type' => (object)['id' => 1]]),
            'assignment_form (create)' => new assignment_form(null, ['assignment' => null, 'courseid' => $course->id]),
            'course_history_form' => new course_history_form(null, ['courseid' => $course->id, 'studentid' => $student->id]),
            'edit_history_form' => new edit_history_form(null, ['history_entry' => $history]),
        ];

        foreach ($forms as $name => $form) {
            $this->assertInstanceOf(\moodleform::class, $form, "{$name} must be a moodleform.");
            $this->assertNotEmpty($form->render(), "{$name} must render.");
        }
    }

    /**
     * The submit buttons and field names of the forms must not change.
     *
     * These names are the contract used by the page scripts and by the JavaScript
     * that populates the student list.
     */
    public function test_form_fields_are_stable() {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $PAGE->set_url('/local/studenttutor/index.php');

        $html = (new activity_type_form(null, ['activity_type' => null]))->render();
        foreach (['name', 'shortname', 'description', 'icon', 'color', 'active', 'sortorder'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html, "activity_type_form must keep the {$field} field.");
        }

        $html = (new assignment_form(null, ['assignment' => null]))->render();
        foreach (['courseid', 'tutorid', 'studentids_json'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html, "assignment_form must keep the {$field} field.");
        }
    }
}
