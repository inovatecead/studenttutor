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
 * Edit activity type for Student-Tutor plugin
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');

use local_studenttutor\activity_type_manager;

$id = optional_param('id', 0, PARAM_INT);

require_login();

$context = context_system::instance();
require_capability('local/studenttutor:manageassignments', $context);

$PAGE->set_url(new moodle_url('/local/studenttutor/edit_activity_type.php'), array('id' => $id));
$PAGE->set_context($context);

$activity_type = null;
if ($id) {
    $activity_type = $DB->get_record('local_studenttutor_activity_types', array('id' => $id), '*', MUST_EXIST);
    $PAGE->set_title(get_string('edit_activity_type', 'local_studenttutor'));
    $PAGE->set_heading(get_string('edit_activity_type', 'local_studenttutor'));
} else {
    $PAGE->set_title(get_string('add_activity_type', 'local_studenttutor'));
    $PAGE->set_heading(get_string('add_activity_type', 'local_studenttutor'));
}

class activity_type_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $activity_type = $this->_customdata['activity_type'];
        
        // Name
        $mform->addElement('text', 'name', get_string('name', 'local_studenttutor'), array('size' => 60));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('name', 'activity_type_name', 'local_studenttutor');
        
        // Shortname
        $mform->addElement('text', 'shortname', get_string('shortname', 'local_studenttutor'), array('size' => 30));
        $mform->setType('shortname', PARAM_ALPHANUMEXT);
        $mform->addRule('shortname', get_string('required'), 'required', null, 'client');
        $mform->addRule('shortname', get_string('alphanumeric', 'core'), 'alphanumeric', null, 'client');
        $mform->addHelpButton('shortname', 'activity_type_shortname', 'local_studenttutor');
        
        // Description
        $mform->addElement('textarea', 'description', get_string('description', 'local_studenttutor'), 
            array('rows' => 4, 'cols' => 60));
        $mform->setType('description', PARAM_TEXT);
        $mform->addHelpButton('description', 'activity_type_description', 'local_studenttutor');
        
        // Icon (FontAwesome)
        $icon_options = array(
            'fa-users' => get_string('icon_users', 'local_studenttutor'),
            'fa-envelope' => get_string('icon_envelope', 'local_studenttutor'),
            'fa-comment' => get_string('icon_comment', 'local_studenttutor'),
            'fa-clipboard-check' => get_string('icon_clipboard', 'local_studenttutor'),
            'fa-compass' => get_string('icon_compass', 'local_studenttutor'),
            'fa-phone' => get_string('icon_phone', 'local_studenttutor'),
            'fa-video' => get_string('icon_video', 'local_studenttutor'),
            'fa-file-alt' => get_string('icon_file', 'local_studenttutor'),
            'fa-chart-line' => get_string('icon_chart', 'local_studenttutor'),
            'fa-lightbulb' => get_string('icon_lightbulb', 'local_studenttutor'),
            'fa-graduation-cap' => get_string('icon_graduation', 'local_studenttutor'),
            'fa-ellipsis-h' => get_string('icon_other', 'local_studenttutor'),
        );
        
        $mform->addElement('select', 'icon', get_string('icon', 'local_studenttutor'), $icon_options);
        $mform->setType('icon', PARAM_TEXT);
        $mform->addHelpButton('icon', 'activity_type_icon', 'local_studenttutor');
        
        // Color
        $color_options = array(
            '#28a745' => get_string('color_green', 'local_studenttutor'),
            '#17a2b8' => get_string('color_blue', 'local_studenttutor'),
            '#ffc107' => get_string('color_yellow', 'local_studenttutor'),
            '#dc3545' => get_string('color_red', 'local_studenttutor'),
            '#6f42c1' => get_string('color_purple', 'local_studenttutor'),
            '#fd7e14' => get_string('color_orange', 'local_studenttutor'),
            '#20c997' => get_string('color_teal', 'local_studenttutor'),
            '#6c757d' => get_string('color_gray', 'local_studenttutor'),
        );
        
        $mform->addElement('select', 'color', get_string('color', 'local_studenttutor'), $color_options);
        $mform->setType('color', PARAM_TEXT);
        $mform->addHelpButton('color', 'activity_type_color', 'local_studenttutor');
        
        // Active status
        $mform->addElement('advcheckbox', 'active', get_string('active', 'local_studenttutor'));
        $mform->setDefault('active', 1);
        $mform->addHelpButton('active', 'activity_type_active', 'local_studenttutor');
        
        // Sort order
        $mform->addElement('text', 'sortorder', get_string('sortorder', 'local_studenttutor'), array('size' => 10));
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);
        $mform->addHelpButton('sortorder', 'activity_type_sortorder', 'local_studenttutor');
        
        // Hidden fields
        if ($activity_type) {
            $mform->addElement('hidden', 'id', $activity_type->id);
            $mform->setType('id', PARAM_INT);
        }
        
        $this->add_action_buttons(true, $activity_type ? get_string('savechanges') : get_string('add_activity_type', 'local_studenttutor'));
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Check if shortname already exists (for new records or different records)
        global $DB;
        $conditions = array('shortname' => $data['shortname']);
        if (!empty($data['id'])) {
            $existing = $DB->get_record_select('local_studenttutor_activity_types', 
                'shortname = ? AND id != ?', array($data['shortname'], $data['id']));
        } else {
            $existing = $DB->get_record('local_studenttutor_activity_types', $conditions);
        }
        
        if ($existing) {
            $errors['shortname'] = get_string('shortnameexists', 'local_studenttutor');
        }
        
        return $errors;
    }
}

$form = new activity_type_form(null, array('activity_type' => $activity_type));

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
        \core\notification::error(get_string('activity_type_save_error', 'local_studenttutor') . ': ' . $e->getMessage());
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
