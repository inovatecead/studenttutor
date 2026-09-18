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
 * Assignment list page for the Nexo Tutoria Acadêmica plugin.
 * Shows pedagogical dashboard for tutors and administrative dashboard for admins
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/lib.php');

use local_studenttutor\assignment_manager;

require_login();

$context = context_system::instance();
require_capability('local/studenttutor:viewassignments', $context);

// Perfil do usuário nesta página de gestão de atribuições.
$is_admin = has_capability('local/studenttutor:manageassignments', $context);
$is_tutor = local_studenttutor_is_tutor($USER->id);

// Sem permissão de gestão e sem vínculo de tutoria não há atribuições a exibir.
if (!$is_admin && !$is_tutor) {
    redirect(
        new moodle_url('/local/studenttutor/reports.php'),
        get_string('limited_access_redirect', 'local_studenttutor'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

// Check for legacy mode parameter (to access old interface if needed)
$legacy_mode = optional_param('legacy', 0, PARAM_INT);

if ($legacy_mode) {
    // Continue with original index.php functionality for backward compatibility
    // This allows access to the old interface via ?legacy=1 parameter

    // Legacy mode - preserve original functionality
    $user_context_filter = null;

    // If user is not admin, filter by their assignments only
    if (!$is_admin) {
        // Check if user is a tutor (has configured tutor role in any course)
        $user_is_tutor = local_studenttutor_is_tutor($USER->id);

        if ($user_is_tutor) {
            $user_context_filter = 'tutor';
        }
    }
} else {
    // Lista de atribuições: gestores veem todas, tutores veem apenas as próprias.
    $user_context_filter = $is_admin ? null : 'tutor';
}

// Get filter parameters
$filter_tutor = optional_param('filter_tutor', 0, PARAM_INT);
$filter_student = optional_param('filter_student', 0, PARAM_INT);
$filter_course = optional_param('filter_course', 0, PARAM_INT);
$search_term = optional_param('search', '', PARAM_TEXT);

// Pagination parameters
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);

// Handle assignment deletion
$delete_id = optional_param('delete', 0, PARAM_INT);
$confirm_delete = optional_param('confirm', '', PARAM_ALPHA);

if ($delete_id && $confirm_delete === 'yes' && confirm_sesskey()) {
    require_capability('local/studenttutor:manageassignments', $context);

    if (assignment_manager::delete_assignment($delete_id)) {
        redirect(
            new moodle_url('/local/studenttutor/index.php'),
            get_string('assignment_deleted', 'local_studenttutor'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        redirect(
            new moodle_url('/local/studenttutor/index.php'),
            get_string('error_deleting_assignment', 'local_studenttutor'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

$PAGE->set_url(
    new moodle_url('/local/studenttutor/index.php'),
    [
        'filter_tutor' => $filter_tutor,
        'filter_student' => $filter_student,
        'filter_course' => $filter_course,
        'search' => $search_term,
        'page' => $page,
        'perpage' => $perpage,
    ]
);
$PAGE->set_context($context);
$PAGE->set_title(get_string('assignments_title', 'local_studenttutor'));
$PAGE->set_heading(get_string('assignments_title', 'local_studenttutor'));

// Add JavaScript to initialize autocomplete filters
$PAGE->requires->js_call_amd('core/form-autocomplete', 'init');

// Add custom JavaScript for filter autocomplete (AMD style)
$PAGE->requires->js_amd_inline('
require(["jquery", "core/form-autocomplete"], function($, Autocomplete) {
    $(document).ready(function() {
        console.log("Initializing Moodle autocomplete filters");

        // Convert select elements marked with data-autocomplete to Moodle autocomplete
        $("select[data-autocomplete=\"true\"]").each(function() {
            var $select = $(this);
            var placeholder = $select.attr("data-placeholder") || "Digite para buscar...";
            var multiple = $select.attr("data-multiple") === "true";

            console.log("Converting select to autocomplete:", $select.attr("name"));

            // Get options from the original select
            var options = [];
            $select.find("option").each(function() {
                var $option = $(this);
                options.push({
                    value: $option.val(),
                    label: $option.text()
                });
            });

            // Create autocomplete configuration
            var config = {
                ajax: false,
                multiple: multiple,
                placeholder: placeholder,
                tags: false,
                showSuggestions: true,
                caseSensitive: false,
                noSelectionString: options[0] ? options[0].label : "Selecione..."
            };

            // Initialize Moodle autocomplete
            try {
                Autocomplete.enhance($select[0], false, "", config, options);
                console.log("Autocomplete enhanced for:", $select.attr("name"));
            } catch (e) {
                console.error("Error enhancing autocomplete:", e);
            }
        });
    });
});
');

// Add custom CSS for better filter styling
$PAGE->requires->css('/local/studenttutor/styles/assign_form.css');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('manage_assignments', 'local_studenttutor'));

// Navigation buttons
$buttons = [];
$buttons[] = $OUTPUT->single_button(
    new moodle_url('/local/studenttutor/assign.php'),
    get_string('add_assignment', 'local_studenttutor'),
    'get'
);
$buttons[] = $OUTPUT->single_button(
    new moodle_url('/local/studenttutor/reports.php'),
    get_string('view_history', 'local_studenttutor'),
    'get'
);

echo html_writer::div(implode(' ', $buttons), 'buttons');

// Add filters
echo html_writer::start_tag('div', ['class' => 'filters-form', 'style' => 'margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9;']);
echo html_writer::tag('h4', get_string('filters', 'local_studenttutor'), ['style' => 'margin-top: 0;']);

echo html_writer::start_tag('form', ['method' => 'get', 'action' => '', 'style' => 'display: flex; gap: 15px; align-items: end; flex-wrap: wrap;']);

// Tutor filter
$tutor_roles = local_studenttutor_get_tutor_roles();
[$role_sql, $role_params] = $DB->get_in_or_equal($tutor_roles);
$tutors = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
    FROM {user} u
    JOIN {role_assignments} ra ON ra.userid = u.id
    JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = ?
    JOIN {role} r ON r.id = ra.roleid
    WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
    AND r.shortname $role_sql
    ORDER BY u.lastname, u.firstname
", array_merge([50], $role_params));
$tutor_options = [0 => get_string('all_tutors', 'local_studenttutor')];
foreach ($tutors as $tutor) {
    $tutor_options[$tutor->id] = fullname($tutor);
}

echo html_writer::start_tag('div');
echo html_writer::tag('label', get_string('tutor', 'local_studenttutor'), ['style' => 'display: block; font-weight: bold; margin-bottom: 5px;']);

// Create select with data attributes for autocomplete conversion
echo html_writer::select($tutor_options, 'filter_tutor', $filter_tutor, false, [
    'data-autocomplete' => 'true',
    'data-placeholder' => 'Digite para buscar um tutor...',
    'data-multiple' => 'false',
    'class' => 'form-autocomplete-original',
    'style' => 'min-width: 200px;',
]);
echo html_writer::end_tag('div');

// Student filter
$students = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
    FROM {user} u
    JOIN {role_assignments} ra ON ra.userid = u.id
    JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = ?
    JOIN {role} r ON r.id = ra.roleid
    WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
    AND r.shortname = ?
    ORDER BY u.lastname, u.firstname
", [50, 'student']);
$student_options = [0 => get_string('all_students', 'local_studenttutor')];
foreach ($students as $student) {
    $student_options[$student->id] = fullname($student);
}

echo html_writer::start_tag('div');
echo html_writer::tag('label', get_string('student', 'local_studenttutor'), ['style' => 'display: block; font-weight: bold; margin-bottom: 5px;']);

// Create select with data attributes for autocomplete conversion
echo html_writer::select($student_options, 'filter_student', $filter_student, false, [
    'data-autocomplete' => 'true',
    'data-placeholder' => 'Digite para buscar um estudante...',
    'data-multiple' => 'false',
    'class' => 'form-autocomplete-original',
    'style' => 'min-width: 200px;',
]);
echo html_writer::end_tag('div');

// Course filter
$courses = $DB->get_records('course', ['visible' => 1], 'fullname', 'id, fullname');
$course_options = [0 => get_string('all_courses', 'local_studenttutor')];
foreach ($courses as $course) {
    if ($course->id != SITEID) {
        $course_options[$course->id] = $course->fullname;
    }
}

echo html_writer::start_tag('div');
echo html_writer::tag('label', get_string('course', 'local_studenttutor'), ['style' => 'display: block; font-weight: bold; margin-bottom: 5px;']);

// Create select with data attributes for autocomplete conversion
echo html_writer::select($course_options, 'filter_course', $filter_course, false, [
    'data-autocomplete' => 'true',
    'data-placeholder' => 'Digite para buscar um curso...',
    'data-multiple' => 'false',
    'class' => 'form-autocomplete-original',
    'style' => 'min-width: 250px;',
]);
echo html_writer::end_tag('div');

// Filter buttons
echo html_writer::start_tag('div', ['style' => 'display: flex; gap: 10px;']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('filter', 'local_studenttutor'), 'class' => 'btn btn-primary']);
echo html_writer::link(new moodle_url('/local/studenttutor/index.php'), get_string('clear', 'local_studenttutor'), ['class' => 'btn btn-secondary']);
echo html_writer::end_tag('div');

echo html_writer::end_tag('form');
echo html_writer::end_tag('div');

// Display current assignments
echo html_writer::start_tag('div', ['class' => 'assignments-list']);

// Build filters for assignment query
$filters = [];
if ($filter_tutor > 0) {
    $filters['tutorid'] = $filter_tutor;
}
if ($filter_student > 0) {
    $filters['studentid'] = $filter_student;
}
if ($filter_course > 0) {
    $filters['courseid'] = $filter_course;
}
if (!empty($search_term)) {
    $filters['search'] = $search_term;
}

// Apply context filter for non-admin users
if ($user_context_filter == 'tutor') {
    $filters['tutorid'] = $USER->id;
}

// Get all assignments first to get total count
$all_assignments = assignment_manager::get_all_assignments_with_details($filters);
$total_count = count($all_assignments);

// Apply manual pagination by slicing the array
$offset = $page * $perpage;
$assignments = array_slice($all_assignments, $offset, $perpage, true);

if ($assignments) {
    // Display results counter
    $start_item = ($page * $perpage) + 1;
    $end_item = min(($page + 1) * $perpage, $total_count);
    echo html_writer::tag(
        'p',
        "Mostrando {$start_item}-{$end_item} de {$total_count} resultados",
        ['class' => 'text-muted mb-3']
    );

    $table = new html_table();
    $table->attributes['class'] = 'table table-striped table-responsive';
    $table->head = [
        'Tutor',
        'Estudante',
        'Curso',
        'Data Atribuição',
        'Status',
        'Ações',
    ];

    foreach ($assignments as $assignment) {
        $tutor_name = fullname(username_load_fields_from_object((object)[], $assignment, 'tutor_'));
        $student_name = fullname(username_load_fields_from_object((object)[], $assignment, 'student_'));
        $course_name = $assignment->course_name ? $assignment->course_name : 'Todos os cursos';
        $date_assigned = userdate($assignment->timeassigned);

        // Improved status display with badges
        $status_class = $assignment->status == 'active' ? 'badge-success' : 'badge-secondary';
        $status_text = $assignment->status == 'active' ? 'Ativo' : 'Inativo';
        $status = html_writer::tag('span', $status_text, ['class' => "badge $status_class"]);

        $actions = '';
        if (has_capability('local/studenttutor:manageassignments', $context)) {
            // Add edit button
            $actions .= html_writer::link(
                new moodle_url('/local/studenttutor/assign.php', ['id' => $assignment->id]),
                'Editar',
                ['class' => 'btn btn-sm btn-secondary me-2', 'title' => 'Editar atribuição']
            );

            // Add delete button with improved confirmation
            $delete_url = new moodle_url('/local/studenttutor/index.php', [
                'delete' => $assignment->id,
                'confirm' => 'yes',
                'sesskey' => sesskey(),
            ]);
            $actions .= html_writer::link(
                $delete_url,
                'Excluir',
                [
                    'class' => 'btn btn-sm btn-danger',
                    'title' => 'Excluir atribuição',
                    'onclick' => 'return confirm("Tem certeza que deseja excluir esta atribuição?\\n\\nEsta ação não pode ser desfeita!");',
                ]
            );
        }

        $table->data[] = [
            $tutor_name,
            $student_name,
            $course_name,
            $date_assigned,
            $status,
            $actions,
        ];
    }

    echo html_writer::table($table);

    // Add pagination if needed
    if ($total_count > $perpage) {
        $pagingbar_url = new moodle_url('/local/studenttutor/index.php', [
            'filter_tutor' => $filter_tutor,
            'filter_student' => $filter_student,
            'filter_course' => $filter_course,
            'search' => $search_term,
            'perpage' => $perpage,
        ]);
        echo $OUTPUT->paging_bar($total_count, $page, $perpage, $pagingbar_url);
    }
} else {
    echo html_writer::div('Nenhuma atribuição encontrada.', 'alert alert-info');
}

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
