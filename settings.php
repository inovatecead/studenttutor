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
 * Settings for the Nexo Tutoria Acadêmica plugin
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category(
        'local_studenttutor',
        get_string('pluginname', 'local_studenttutor')
    ));

    $settings = new admin_settingpage(
        'local_studenttutor_settings',
        get_string('settings', 'local_studenttutor')
    );

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading(
            'local_studenttutor_general',
            get_string('general_settings', 'local_studenttutor'),
            get_string('general_settings_desc', 'local_studenttutor')
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_studenttutor/enabled',
            get_string('enable_plugin', 'local_studenttutor'),
            get_string('enable_plugin_desc', 'local_studenttutor'),
            1
        ));

        $settings->add(new admin_setting_configtext(
            'local_studenttutor/maxassignments',
            get_string('max_assignments', 'local_studenttutor'),
            get_string('max_assignments_desc', 'local_studenttutor'),
            10,
            PARAM_INT
        ));

        // Configuração do papel de tutor
        $settings->add(new admin_setting_configtext(
            'local_studenttutor/tutor_role',
            get_string('tutor_role', 'local_studenttutor'),
            get_string('tutor_role_desc', 'local_studenttutor'),
            'tutortematico',
            PARAM_TEXT
        ));

        // Configuração adicional: permitir múltiplos papéis
        $settings->add(new admin_setting_configtext(
            'local_studenttutor/additional_tutor_roles',
            get_string('additional_tutor_roles', 'local_studenttutor'),
            get_string('additional_tutor_roles_desc', 'local_studenttutor'),
            '',
            PARAM_TEXT
        ));
    }

    $ADMIN->add('local_studenttutor', new admin_externalpage(
        'local_studenttutor_activity_types',
        get_string('manage_activity_types', 'local_studenttutor'),
        new moodle_url('/local/studenttutor/manage_activity_types.php'),
        'local/studenttutor:manageassignments'
    ));

    $ADMIN->add('local_studenttutor', $settings);
}
