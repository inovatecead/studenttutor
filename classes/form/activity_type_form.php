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
 * Form for creating and editing an activity type.
 *
 * Page scripts must not declare classes: they cannot be autoloaded and cannot be
 * reused, so the form lives in its own class file.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studenttutor\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating and editing an activity type.
 */
class activity_type_form extends \moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $activity_type = $this->_customdata['activity_type'];

        // Name.
        $mform->addElement('text', 'name', get_string('name', 'local_studenttutor'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('name', 'activity_type_name', 'local_studenttutor');

        // Shortname.
        $mform->addElement('text', 'shortname', get_string('shortname', 'local_studenttutor'), ['size' => 30]);
        $mform->setType('shortname', PARAM_ALPHANUMEXT);
        $mform->addRule('shortname', get_string('required'), 'required', null, 'client');
        $mform->addRule('shortname', get_string('err_alphanumeric', 'form'), 'alphanumeric', null, 'client');
        $mform->addHelpButton('shortname', 'activity_type_shortname', 'local_studenttutor');

        // Description.
        $mform->addElement(
            'textarea',
            'description',
            get_string('description', 'local_studenttutor'),
            ['rows' => 4, 'cols' => 60]
        );
        $mform->setType('description', PARAM_TEXT);
        $mform->addHelpButton('description', 'activity_type_description', 'local_studenttutor');

        // Icon (FontAwesome).
        $icon_options = [
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
        ];

        $mform->addElement('select', 'icon', get_string('icon', 'local_studenttutor'), $icon_options);
        $mform->setType('icon', PARAM_TEXT);
        $mform->addHelpButton('icon', 'activity_type_icon', 'local_studenttutor');

        // Colour.
        $color_options = [
            '#28a745' => get_string('color_green', 'local_studenttutor'),
            '#17a2b8' => get_string('color_blue', 'local_studenttutor'),
            '#ffc107' => get_string('color_yellow', 'local_studenttutor'),
            '#dc3545' => get_string('color_red', 'local_studenttutor'),
            '#6f42c1' => get_string('color_purple', 'local_studenttutor'),
            '#fd7e14' => get_string('color_orange', 'local_studenttutor'),
            '#20c997' => get_string('color_teal', 'local_studenttutor'),
            '#6c757d' => get_string('color_gray', 'local_studenttutor'),
        ];

        $mform->addElement('select', 'color', get_string('color', 'local_studenttutor'), $color_options);
        $mform->setType('color', PARAM_TEXT);
        $mform->addHelpButton('color', 'activity_type_color', 'local_studenttutor');

        // Active status.
        $mform->addElement('advcheckbox', 'active', get_string('active'));
        $mform->setDefault('active', 1);
        $mform->addHelpButton('active', 'activity_type_active', 'local_studenttutor');

        // Sort order.
        $mform->addElement('text', 'sortorder', get_string('sortorder', 'local_studenttutor'), ['size' => 10]);
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);
        $mform->addHelpButton('sortorder', 'activity_type_sortorder', 'local_studenttutor');

        // Hidden fields.
        if ($activity_type) {
            $mform->addElement('hidden', 'id', $activity_type->id);
            $mform->setType('id', PARAM_INT);
        }

        $this->add_action_buttons(
            true,
            $activity_type ? get_string('savechanges') : get_string('add_activity_type', 'local_studenttutor')
        );
    }

    /**
     * Validate the submitted data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array List of validation errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // The shortname must be unique, as it is what the history entries store.
        global $DB;

        if (!empty($data['id'])) {
            $existing = $DB->get_record_select(
                'local_studenttutor_activity_types',
                'shortname = ? AND id != ?',
                [$data['shortname'], $data['id']]
            );
        } else {
            $existing = $DB->get_record('local_studenttutor_activity_types', ['shortname' => $data['shortname']]);
        }

        if ($existing) {
            $errors['shortname'] = get_string('shortnameexists', 'local_studenttutor');
        }

        return $errors;
    }
}
