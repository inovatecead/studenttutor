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
 * Activity Type manager class for Student-Tutor Assignment plugin.
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studenttutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for managing activity types.
 */
class activity_type_manager {

    /**
     * Get all active activity types
     *
     * @param bool $activeonly Whether to return only active types
     * @return array Array of activity type objects
     */
    public static function get_activity_types($activeonly = true) {
        global $DB;

        $conditions = array();
        if ($activeonly) {
            $conditions['active'] = 1;
        }

        return $DB->get_records('local_studenttutor_activity_types', $conditions, 'sortorder ASC, name ASC');
    }

    /**
     * Get activity types as options array for form selects
     *
     * @param bool $activeonly Whether to return only active types
     * @return array Array of id => name pairs
     */
    public static function get_activity_types_options($activeonly = true) {
        $types = self::get_activity_types($activeonly);
        $options = array();
        
        foreach ($types as $type) {
            $options[$type->shortname] = $type->name;
        }
        
        return $options;
    }

    /**
     * Get a single activity type by shortname
     *
     * @param string $shortname The shortname of the activity type
     * @return object|false Activity type object or false if not found
     */
    public static function get_activity_type($shortname) {
        global $DB;
        return $DB->get_record('local_studenttutor_activity_types', array('shortname' => $shortname));
    }

    /**
     * Create a new activity type
     *
     * @param array $data Activity type data
     * @return int|false Activity type ID or false on failure
     */
    public static function create_activity_type($data) {
        global $DB;

        // Validate required fields
        if (empty($data['name']) || empty($data['shortname'])) {
            return false;
        }

        // Check if shortname already exists
        if ($DB->record_exists('local_studenttutor_activity_types', array('shortname' => $data['shortname']))) {
            return false;
        }

        $record = new \stdClass();
        $record->name = $data['name'];
        $record->shortname = $data['shortname'];
        $record->description = $data['description'] ?? '';
        $record->icon = $data['icon'] ?? 'fa-circle';
        $record->color = $data['color'] ?? '#007bff';
        $record->active = $data['active'] ?? 1;
        $record->sortorder = $data['sortorder'] ?? self::get_next_sortorder();
        $record->timecreated = time();
        $record->timemodified = time();

        try {
            return $DB->insert_record('local_studenttutor_activity_types', $record);
        } catch (\Exception $e) {
            debugging('Error creating activity type: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Update an existing activity type
     *
     * @param int $id Activity type ID
     * @param array $data Updated data
     * @return bool Success status
     */
    public static function update_activity_type($id, $data) {
        global $DB;

        $record = $DB->get_record('local_studenttutor_activity_types', array('id' => $id));
        if (!$record) {
            return false;
        }

        // Check if shortname conflicts with another record
        if (isset($data['shortname']) && $data['shortname'] !== $record->shortname) {
            if ($DB->record_exists_select('local_studenttutor_activity_types', 
                'shortname = ? AND id != ?', array($data['shortname'], $id))) {
                return false;
            }
        }

        $updatedata = new \stdClass();
        $updatedata->id = $id;
        $updatedata->timemodified = time();

        $allowedfields = ['name', 'shortname', 'description', 'icon', 'color', 'active', 'sortorder'];
        foreach ($allowedfields as $field) {
            if (isset($data[$field])) {
                $updatedata->$field = $data[$field];
            }
        }

        try {
            return $DB->update_record('local_studenttutor_activity_types', $updatedata);
        } catch (\Exception $e) {
            debugging('Error updating activity type: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Delete an activity type
     *
     * @param int $id Activity type ID
     * @return bool Success status
     */
    public static function delete_activity_type($id) {
        global $DB;

        $record = $DB->get_record('local_studenttutor_activity_types', array('id' => $id));
        if (!$record) {
            return false;
        }

        // Check if this type is being used in history entries
        $usage_count = $DB->count_records('local_studenttutor_history', array('activitytype' => $record->shortname));
        if ($usage_count > 0) {
            // Instead of deleting, deactivate it
            return self::update_activity_type($id, array('active' => 0));
        }

        try {
            return $DB->delete_records('local_studenttutor_activity_types', array('id' => $id));
        } catch (\Exception $e) {
            debugging('Error deleting activity type: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Reorder activity types
     *
     * @param array $order Array of id => sortorder pairs
     * @return bool Success status
     */
    public static function reorder_activity_types($order) {
        global $DB;

        try {
            foreach ($order as $id => $sortorder) {
                $DB->update_record('local_studenttutor_activity_types', 
                    (object)array('id' => $id, 'sortorder' => $sortorder, 'timemodified' => time()));
            }
            return true;
        } catch (\Exception $e) {
            debugging('Error reordering activity types: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Get the next available sort order
     *
     * @return int Next sort order value
     */
    private static function get_next_sortorder() {
        global $DB;
        
        $max = $DB->get_field_sql('SELECT MAX(sortorder) FROM {local_studenttutor_activity_types}');
        return ($max ? $max + 1 : 1);
    }

    /**
     * Get activity type statistics
     *
     * @return array Statistics array
     */
    public static function get_statistics() {
        global $DB;

        $sql = "SELECT at.shortname, at.name, at.color, at.icon, COUNT(h.id) as usage_count
                FROM {local_studenttutor_activity_types} at
                LEFT JOIN {local_studenttutor_history} h ON h.activitytype = at.shortname
                WHERE at.active = 1
                GROUP BY at.id, at.shortname, at.name, at.color, at.icon
                ORDER BY usage_count DESC, at.sortorder ASC";

        return $DB->get_records_sql($sql);
    }

    /**
     * Export activity types to CSV
     *
     * @return string CSV content
     */
    public static function export_to_csv() {
        $types = self::get_activity_types(false);
        
        $csv = "ID,Name,Shortname,Description,Icon,Color,Active,Sort Order,Created,Modified\n";
        
        foreach ($types as $type) {
            $csv .= sprintf('"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $type->id,
                str_replace('"', '""', $type->name),
                $type->shortname,
                str_replace('"', '""', $type->description),
                $type->icon,
                $type->color,
                $type->active ? 'Yes' : 'No',
                $type->sortorder,
                userdate($type->timecreated),
                userdate($type->timemodified)
            );
        }
        
        return $csv;
    }

    /**
     * Get activity type by shortname
     *
     * @param string $shortname The shortname of the activity type
     * @return object|false Activity type object or false if not found
     */
    public static function get_activity_type_by_shortname($shortname) {
        global $DB;
        
        return $DB->get_record('local_studenttutor_activity_types', array('shortname' => $shortname));
    }
}
