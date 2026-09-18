<?php
/**
 * Dashboard API for real-time data
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/studenttutor/lib.php');

// Security checks
require_login();
$context = context_system::instance();
require_capability('local/studenttutor:view', $context);

// Only accept AJAX requests
if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

// Set JSON content type
header('Content-Type: application/json');

// Get action parameter
$action = optional_param('action', '', PARAM_ALPHA);

try {
    switch ($action) {
        case 'metrics':
            echo json_encode(get_dashboard_metrics());
            break;
            
        case 'charts':
            echo json_encode(get_dashboard_charts());
            break;
            
        case 'activities':
            $limit = optional_param('limit', 10, PARAM_INT);
            echo json_encode(get_recent_activities($limit));
            break;
            
        case 'alerts':
            echo json_encode(get_dashboard_alerts());
            break;
            
        case 'performance':
            $period = optional_param('period', 30, PARAM_INT);
            echo json_encode(get_performance_data($period));
            break;
            
        default:
            throw new moodle_exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Get current dashboard metrics
 */
function get_dashboard_metrics() {
    global $DB;
    
    $metrics = [];
    
    // Total students
    $metrics['total_students'] = $DB->count_records('user', ['deleted' => 0]);
    
    // Total tutors
    $metrics['total_tutors'] = $DB->count_records_sql("
        SELECT COUNT(DISTINCT userid) 
        FROM {local_studenttutor_assign} 
        WHERE timemodified > ?
    ", [time() - (30 * 24 * 3600)]);
    
    // Active assignments (last 30 days)
    $metrics['active_assignments'] = $DB->count_records_sql("
        SELECT COUNT(*) 
        FROM {local_studenttutor_assign} 
        WHERE timemodified > ?
    ", [time() - (30 * 24 * 3600)]);
    
    // Students without tutor
    $metrics['students_without_tutor'] = $DB->count_records_sql("
        SELECT COUNT(DISTINCT u.id)
        FROM {user} u
        LEFT JOIN {local_studenttutor_assign} a ON u.id = a.studentid
        WHERE u.deleted = 0 AND a.id IS NULL
    ");
    
    // Activity trend (compared to previous period)
    $current_period = $DB->count_records_sql("
        SELECT COUNT(*) 
        FROM {local_studenttutor_history} 
        WHERE timecreated > ?
    ", [time() - (7 * 24 * 3600)]);
    
    $previous_period = $DB->count_records_sql("
        SELECT COUNT(*) 
        FROM {local_studenttutor_history} 
        WHERE timecreated BETWEEN ? AND ?
    ", [time() - (14 * 24 * 3600), time() - (7 * 24 * 3600)]);
    
    $metrics['activity_trend'] = $previous_period > 0 ? 
        (($current_period - $previous_period) / $previous_period) * 100 : 0;
    
    return $metrics;
}

/**
 * Get chart data
 */
function get_dashboard_charts() {
    global $DB;
    
    $charts = [];
    
    // Activities by type
    $activity_types = $DB->get_records_sql("
        SELECT at.name, COUNT(h.id) as count
        FROM {local_studenttutor_activity_type} at
        LEFT JOIN {local_studenttutor_history} h ON at.id = h.activity_type_id
        WHERE h.timecreated > ?
        GROUP BY at.id, at.name
        ORDER BY count DESC
    ", [time() - (30 * 24 * 3600)]);
    
    $charts['activities_by_type'] = [
        'labels' => array_column($activity_types, 'name'),
        'data' => array_column($activity_types, 'count')
    ];
    
    // Weekly activity trend
    $weekly_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $start = strtotime("-$i days", strtotime('today'));
        $end = $start + (24 * 3600);
        
        $count = $DB->count_records_sql("
            SELECT COUNT(*) 
            FROM {local_studenttutor_history} 
            WHERE timecreated BETWEEN ? AND ?
        ", [$start, $end]);
        
        $weekly_data[] = [
            'date' => date('Y-m-d', $start),
            'count' => $count
        ];
    }
    
    $charts['weekly_trend'] = [
        'labels' => array_column($weekly_data, 'date'),
        'data' => array_column($weekly_data, 'count')
    ];
    
    // Tutor performance
    $tutor_performance = $DB->get_records_sql("
        SELECT u.firstname, u.lastname, COUNT(h.id) as activities
        FROM {user} u
        INNER JOIN {local_studenttutor_assign} a ON u.id = a.userid
        LEFT JOIN {local_studenttutor_history} h ON a.studentid = h.studentid
        WHERE h.timecreated > ?
        GROUP BY u.id, u.firstname, u.lastname
        ORDER BY activities DESC
        LIMIT 10
    ", [time() - (30 * 24 * 3600)]);
    
    $charts['tutor_performance'] = [
        'labels' => array_map(function($tutor) {
            return $tutor->firstname . ' ' . $tutor->lastname;
        }, $tutor_performance),
        'data' => array_column($tutor_performance, 'activities')
    ];
    
    return $charts;
}

/**
 * Get recent activities
 */
function get_recent_activities($limit = 10) {
    global $DB;
    
    $activities = $DB->get_records_sql("
        SELECT h.*, 
               s.firstname as student_firstname, s.lastname as student_lastname,
               t.firstname as tutor_firstname, t.lastname as tutor_lastname,
               at.name as activity_name
        FROM {local_studenttutor_history} h
        INNER JOIN {user} s ON h.studentid = s.id
        INNER JOIN {local_studenttutor_assign} a ON h.studentid = a.studentid
        INNER JOIN {user} t ON a.userid = t.id
        LEFT JOIN {local_studenttutor_activity_type} at ON h.activity_type_id = at.id
        ORDER BY h.timecreated DESC
        LIMIT ?
    ", [$limit]);
    
    $result = [];
    foreach ($activities as $activity) {
        $result[] = [
            'id' => $activity->id,
            'student' => $activity->student_firstname . ' ' . $activity->student_lastname,
            'tutor' => $activity->tutor_firstname . ' ' . $activity->tutor_lastname,
            'activity' => $activity->activity_name ?: 'Atividade Geral',
            'description' => $activity->description,
            'date' => date('d/m/Y H:i', $activity->timecreated),
            'timestamp' => $activity->timecreated
        ];
    }
    
    return $result;
}

/**
 * Get dashboard alerts
 */
function get_dashboard_alerts() {
    global $DB;
    
    $alerts = [];
    
    // Students without tutor
    $students_without_tutor = $DB->count_records_sql("
        SELECT COUNT(DISTINCT u.id)
        FROM {user} u
        LEFT JOIN {local_studenttutor_assign} a ON u.id = a.studentid
        WHERE u.deleted = 0 AND a.id IS NULL
    ");
    
    if ($students_without_tutor > 0) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => 'fa-exclamation-triangle',
            'title' => 'Estudantes sem tutor',
            'message' => "Existem $students_without_tutor estudantes sem tutor atribuído",
            'action' => 'assign.php',
            'action_text' => 'Atribuir tutores'
        ];
    }
    
    // Inactive tutors (no activity in 7 days)
    $inactive_tutors = $DB->get_records_sql("
        SELECT DISTINCT u.id, u.firstname, u.lastname
        FROM {user} u
        INNER JOIN {local_studenttutor_assign} a ON u.id = a.userid
        LEFT JOIN {local_studenttutor_history} h ON a.studentid = h.studentid 
                                                 AND h.timecreated > ?
        WHERE h.id IS NULL
        GROUP BY u.id, u.firstname, u.lastname
    ", [time() - (7 * 24 * 3600)]);
    
    if (!empty($inactive_tutors)) {
        $count = count($inactive_tutors);
        $alerts[] = [
            'type' => 'info',
            'icon' => 'fa-clock',
            'title' => 'Tutores inativos',
            'message' => "$count tutores não registraram atividades nos últimos 7 dias",
            'action' => 'reports.php',
            'action_text' => 'Ver relatório'
        ];
    }
    
    // Low activity week
    $this_week_activities = $DB->count_records_sql("
        SELECT COUNT(*) 
        FROM {local_studenttutor_history} 
        WHERE timecreated > ?
    ", [time() - (7 * 24 * 3600)]);
    
    $last_week_activities = $DB->count_records_sql("
        SELECT COUNT(*) 
        FROM {local_studenttutor_history} 
        WHERE timecreated BETWEEN ? AND ?
    ", [time() - (14 * 24 * 3600), time() - (7 * 24 * 3600)]);
    
    if ($last_week_activities > 0 && $this_week_activities < ($last_week_activities * 0.7)) {
        $decrease = round((($last_week_activities - $this_week_activities) / $last_week_activities) * 100);
        $alerts[] = [
            'type' => 'warning',
            'icon' => 'fa-arrow-down',
            'title' => 'Baixa atividade',
            'message' => "Atividades diminuíram $decrease% esta semana",
            'action' => 'dashboard.php',
            'action_text' => 'Analisar dados'
        ];
    }
    
    return $alerts;
}

/**
 * Get performance data for a specific period
 */
function get_performance_data($period_days = 30) {
    global $DB;
    
    $data = [];
    
    // Average activities per day
    $total_activities = $DB->count_records_sql("
        SELECT COUNT(*) 
        FROM {local_studenttutor_history} 
        WHERE timecreated > ?
    ", [time() - ($period_days * 24 * 3600)]);
    
    $data['avg_activities_per_day'] = round($total_activities / $period_days, 2);
    
    // Most active day of week
    $daily_stats = $DB->get_records_sql("
        SELECT DAYOFWEEK(FROM_UNIXTIME(timecreated)) as day_of_week, 
               COUNT(*) as count
        FROM {local_studenttutor_history} 
        WHERE timecreated > ?
        GROUP BY DAYOFWEEK(FROM_UNIXTIME(timecreated))
        ORDER BY count DESC
    ", [time() - ($period_days * 24 * 3600)]);
    
    $days = ['', 'Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    $most_active = reset($daily_stats);
    $data['most_active_day'] = $most_active ? $days[$most_active->day_of_week] : 'N/A';
    
    // Growth rate
    $current_activities = $DB->count_records_sql("
        SELECT COUNT(*) 
        FROM {local_studenttutor_history} 
        WHERE timecreated > ?
    ", [time() - ($period_days * 24 * 3600)]);
    
    $previous_activities = $DB->count_records_sql("
        SELECT COUNT(*) 
        FROM {local_studenttutor_history} 
        WHERE timecreated BETWEEN ? AND ?
    ", [time() - (2 * $period_days * 24 * 3600), time() - ($period_days * 24 * 3600)]);
    
    $data['growth_rate'] = $previous_activities > 0 ? 
        round((($current_activities - $previous_activities) / $previous_activities) * 100, 1) : 0;
    
    return $data;
}
?>
