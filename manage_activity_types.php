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
 * Manage activity types for the Nexo Tutoria Acadêmica plugin
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/lib.php');

use local_studenttutor\activity_type_manager;

require_login();
admin_externalpage_setup('local_studenttutor_activity_types');

$context = context_system::instance();
require_capability('local/studenttutor:manageassignments', $context);

// Handle actions
$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'delete':
            if ($id && activity_type_manager::delete_activity_type($id)) {
                \core\notification::success(get_string('activity_type_deleted', 'local_studenttutor'));
            } else {
                \core\notification::error(get_string('activity_type_delete_error', 'local_studenttutor'));
            }
            break;

        case 'toggle':
            if ($id) {
                $type = $DB->get_record('local_studenttutor_activity_types', ['id' => $id]);
                if ($type) {
                    $new_status = $type->active ? 0 : 1;
                    if (activity_type_manager::update_activity_type($id, ['active' => $new_status])) {
                        $message = $new_status ? 'activity_type_enabled' : 'activity_type_disabled';
                        \core\notification::success(get_string($message, 'local_studenttutor'));
                    }
                }
            }
            break;

        case 'reorder':
            $order = optional_param_array('order', [], PARAM_INT);
            if (!empty($order) && activity_type_manager::reorder_activity_types($order)) {
                \core\notification::success(get_string('activity_types_reordered', 'local_studenttutor'));
            }
            break;
    }

    redirect(new moodle_url('/local/studenttutor/manage_activity_types.php'));
}

$PAGE->set_url(new moodle_url('/local/studenttutor/manage_activity_types.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('manage_activity_types', 'local_studenttutor'));
$PAGE->set_heading(get_string('manage_activity_types', 'local_studenttutor'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('manage_activity_types', 'local_studenttutor'));

// Action buttons
echo html_writer::start_tag('div', ['class' => 'mb-3']);
echo html_writer::link(
    new moodle_url('/local/studenttutor/edit_activity_type.php'),
    get_string('add_activity_type', 'local_studenttutor'),
    ['class' => 'btn btn-primary mr-2']
);
echo html_writer::link(
    new moodle_url('/local/studenttutor/manage_activity_types.php', ['action' => 'export', 'sesskey' => sesskey()]),
    get_string('export_csv', 'local_studenttutor'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_tag('div');

// Get all activity types
$activity_types = activity_type_manager::get_activity_types(false);
$statistics = activity_type_manager::get_statistics();

if (empty($activity_types)) {
    echo $OUTPUT->notification(get_string('no_activity_types', 'local_studenttutor'), 'info');
} else {
    echo html_writer::start_tag('div', ['class' => 'activity-types-list']);

    // Create sortable table
    $table = new html_table();
    $table->head = [
        get_string('order', 'local_studenttutor'),
        get_string('preview', 'local_studenttutor'),
        get_string('name', 'local_studenttutor'),
        get_string('shortname', 'local_studenttutor'),
        get_string('description', 'local_studenttutor'),
        get_string('usage_count', 'local_studenttutor'),
        get_string('status', 'local_studenttutor'),
        get_string('actions', 'local_studenttutor'),
    ];
    $table->attributes['class'] = 'generaltable activity-types-table';
    $table->id = 'activity-types-table';

    foreach ($activity_types as $type) {
        $usage_count = 0;
        foreach ($statistics as $stat) {
            if ($stat->shortname === $type->shortname) {
                $usage_count = $stat->usage_count;
                break;
            }
        }

        // Preview with icon and color
        $preview = html_writer::tag(
            'span',
            html_writer::tag('i', '', ['class' => 'fa ' . $type->icon, 'style' => 'color: ' . $type->color]),
            ['class' => 'activity-type-preview mr-2']
        ) . html_writer::tag('span', $type->name, ['style' => 'color: ' . $type->color]);

        // Status badge
        $status_class = $type->active ? 'badge-success' : 'badge-secondary';
        $status_text = $type->active ? get_string('active') : get_string('inactive');
        $status = html_writer::tag('span', $status_text, ['class' => 'badge ' . $status_class]);

        // Actions
        $actions = [];

        // Edit button
        $actions[] = html_writer::link(
            new moodle_url('/local/studenttutor/edit_activity_type.php', ['id' => $type->id]),
            html_writer::tag('i', '', ['class' => 'fa fa-edit']),
            ['class' => 'btn btn-sm btn-outline-primary', 'title' => get_string('edit')]
        );

        // Toggle active/inactive
        $toggle_icon = $type->active ? 'fa-eye-slash' : 'fa-eye';
        $toggle_title = $type->active ? get_string('disable') : get_string('enable');
        $actions[] = html_writer::link(
            new moodle_url(
                '/local/studenttutor/manage_activity_types.php',
                ['action' => 'toggle', 'id' => $type->id, 'sesskey' => sesskey()]
            ),
            html_writer::tag('i', '', ['class' => 'fa ' . $toggle_icon]),
            ['class' => 'btn btn-sm btn-outline-secondary', 'title' => $toggle_title]
        );

        // Delete button (only if not used)
        if ($usage_count == 0) {
            $actions[] = html_writer::link(
                new moodle_url(
                    '/local/studenttutor/manage_activity_types.php',
                    ['action' => 'delete', 'id' => $type->id, 'sesskey' => sesskey()]
                ),
                html_writer::tag('i', '', ['class' => 'fa fa-trash']),
                [
                    'class' => 'btn btn-sm btn-outline-danger',
                    'title' => get_string('delete'),
                    'onclick' => 'return confirm("' . get_string('confirm_delete_activity_type', 'local_studenttutor') . '");',
                ]
            );
        }

        $table->data[] = [
            html_writer::tag('span', $type->sortorder, ['class' => 'sortorder-handle', 'data-id' => $type->id]),
            $preview,
            $type->name,
            $type->shortname,
            format_text($type->description, FORMAT_HTML),
            $usage_count,
            $status,
            html_writer::tag('div', implode(' ', $actions), ['class' => 'btn-group']),
        ];
    }

    echo html_writer::table($table);
    echo html_writer::end_tag('div');
}

// Add JavaScript for sortable functionality
$PAGE->requires->js_call_amd('local_studenttutor/activity_types_manager', 'init');

// Statistics section
if (!empty($statistics)) {
    echo html_writer::tag('h3', get_string('activity_types_statistics', 'local_studenttutor'), ['class' => 'mt-4']);

    echo html_writer::start_tag('div', ['class' => 'row']);
    foreach ($statistics as $stat) {
        echo html_writer::start_tag('div', ['class' => 'col-md-3 mb-3']);
        echo html_writer::start_tag('div', ['class' => 'card']);
        echo html_writer::start_tag('div', ['class' => 'card-body text-center']);

        echo html_writer::tag('i', '', [
            'class' => 'fa ' . $stat->icon . ' fa-2x mb-2',
            'style' => 'color: ' . $stat->color,
        ]);
        echo html_writer::tag('h5', $stat->name, ['class' => 'card-title']);
        echo html_writer::tag(
            'p',
            $stat->usage_count . ' ' . get_string('uses', 'local_studenttutor'),
            ['class' => 'card-text']
        );

        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    }
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();
