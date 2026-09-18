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
 * Edit activity type for the Nexo Tutoria Acadêmica plugin
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');

use local_studenttutor\activity_type_manager;
use local_studenttutor\form\activity_type_form;

$id = optional_param('id', 0, PARAM_INT);

require_login();

$context = context_system::instance();
require_capability('local/studenttutor:manageassignments', $context);

$PAGE->set_url(new moodle_url('/local/studenttutor/edit_activity_type.php'), ['id' => $id]);
$PAGE->set_context($context);

$activity_type = null;
if ($id) {
    $activity_type = $DB->get_record('local_studenttutor_activity_types', ['id' => $id], '*', MUST_EXIST);
    $PAGE->set_title(get_string('edit_activity_type', 'local_studenttutor'));
    $PAGE->set_heading(get_string('edit_activity_type', 'local_studenttutor'));
} else {
    $PAGE->set_title(get_string('add_activity_type', 'local_studenttutor'));
    $PAGE->set_heading(get_string('add_activity_type', 'local_studenttutor'));
}

// The form lives in classes/form/activity_type_form.php and is autoloaded.
$form = new activity_type_form(null, ['activity_type' => $activity_type]);

if ($activity_type) {
    $form->set_data($activity_type);
}

$return_url = new moodle_url('/local/studenttutor/manage_activity_types.php');

if ($form->is_cancelled()) {
    redirect($return_url);
} else if ($data = $form->get_data()) {
    try {
        if ($activity_type) {
            // Update existing
            if (activity_type_manager::update_activity_type($data->id, (array)$data)) {
                \core\notification::success(get_string('activity_type_updated', 'local_studenttutor'));
            } else {
                \core\notification::error(get_string('activity_type_update_error', 'local_studenttutor'));
            }
        } else {
            // Create new
            if (activity_type_manager::create_activity_type((array)$data)) {
                \core\notification::success(get_string('activity_type_created', 'local_studenttutor'));
            } else {
                \core\notification::error(get_string('activity_type_create_error', 'local_studenttutor'));
            }
        }

        redirect($return_url);
    } catch (Exception $e) {
        debugging('Exception in edit_activity_type: ' . $e->getMessage(), DEBUG_DEVELOPER);
        \core\notification::error(get_string('activity_type_save_error', 'local_studenttutor'));
    }
}

echo $OUTPUT->header();

// Breadcrumbs
$PAGE->navbar->add(get_string('manage_activity_types', 'local_studenttutor'), $return_url);
$PAGE->navbar->add($activity_type ? get_string('edit_activity_type', 'local_studenttutor') : get_string('add_activity_type', 'local_studenttutor'));

echo $OUTPUT->heading($activity_type ? get_string('edit_activity_type', 'local_studenttutor') : get_string('add_activity_type', 'local_studenttutor'));

if ($activity_type) {
    echo $OUTPUT->notification(get_string('editing_activity_type', 'local_studenttutor', $activity_type->name), 'info');
}

$form->display();

echo $OUTPUT->footer();
