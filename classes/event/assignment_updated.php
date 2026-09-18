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
 * Assignment updated event
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studenttutor\event;

defined('MOODLE_INTERNAL') || die();

class assignment_updated extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_studenttutor_assign';
    }

    public static function get_name() {
        return get_string('event_assignment_updated', 'local_studenttutor');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' updated a student-tutor assignment with id '{$this->objectid}'.";
    }

    public function get_url() {
        return new \moodle_url('/local/studenttutor/index.php');
    }

    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['tutorid'])) {
            throw new \coding_exception('The \'tutorid\' value must be set in other.');
        }

        if (!isset($this->other['studentid'])) {
            throw new \coding_exception('The \'studentid\' value must be set in other.');
        }
    }
}
