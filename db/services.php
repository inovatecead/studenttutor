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
 * Web services definition for local_studenttutor plugin
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    // Assignment functions
    'local_studenttutor_get_assignments' => [
        'classname'     => 'local_studenttutor\external\get_assignments',
        'methodname'    => 'execute',
        'classpath'     => '',
        'description'   => 'Get student-tutor assignments with details',
        'type'          => 'read',
        'capabilities'  => 'local/studenttutor:viewassignments',
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'local_studenttutor_create_assignment' => [
        'classname'     => 'local_studenttutor\external\create_assignment',
        'methodname'    => 'execute',
        'classpath'     => '',
        'description'   => 'Create a new student-tutor assignment',
        'type'          => 'write',
        'capabilities'  => 'local/studenttutor:manageassignments',
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    // History functions
    'local_studenttutor_get_history' => [
        'classname'     => 'local_studenttutor\external\get_history',
        'methodname'    => 'execute',
        'classpath'     => '',
        'description'   => 'Get tutoring history entries with details',
        'type'          => 'read',
        'capabilities'  => 'local/studenttutor:viewhistory',
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'local_studenttutor_add_history' => [
        'classname'     => 'local_studenttutor\external\add_history',
        'methodname'    => 'execute',
        'classpath'     => '',
        'description'   => 'Add a new tutoring history entry',
        'type'          => 'write',
        'capabilities'  => 'local/studenttutor:managehistory',
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

];

// Define services
$services = [
    'Nexo Tutoria Acadêmica API' => [
        'functions' => [
            'local_studenttutor_get_assignments',
            'local_studenttutor_create_assignment',
            'local_studenttutor_get_history',
            'local_studenttutor_add_history',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'studenttutor_api',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];
