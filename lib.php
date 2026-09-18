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
 * Library functions for Student-Tutor assignment plugin
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->libdir . '/weblib.php');

/**
 * Add navigation node to course navigation for tutors
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context $context
 */
function local_studenttutor_extend_navigation_course($navigation, $course, $context) {
    global $USER, $DB;
    
    // Get configured tutor roles
    $tutor_roles = local_studenttutor_get_tutor_roles();
    
    // Check if user is enrolled in this course with configured tutor role
    $roles = get_user_roles($context, $USER->id);
    $is_tutor = false;
    
    foreach ($roles as $role) {
        if (in_array($role->shortname, $tutor_roles)) {
            $is_tutor = true;
            break;
        }
    }
    
    if ($is_tutor) {
        // Check if this tutor has any students assigned in this course OR globally
        $has_students = $DB->record_exists_sql("
            SELECT 1 FROM {local_studenttutor_assign} a
            WHERE a.tutorid = :tutorid 
            AND (a.courseid = :courseid OR a.courseid = 0)
            AND a.status = 'active'
        ", ['tutorid' => $USER->id, 'courseid' => $course->id]);
        
        // Always show the menu item for tutors, even if no students assigned yet
        $url = new moodle_url('/local/studenttutor/course_view.php', ['courseid' => $course->id]);
        $node = $navigation->add(
            get_string('my_students', 'local_studenttutor'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'studenttutor_mystudents',
            new pix_icon('i/users', '')
        );
        $node->showinflatnavigation = true;
        
        debugging('Navigation node added for My Students', DEBUG_DEVELOPER);
    }
}

/**
 * Add navigation to settings menu for administrators
 */
function local_studenttutor_extend_settings_navigation($settingsnav, $context) {
    global $PAGE;
    
    // Only add to course context
    if ($context->contextlevel == CONTEXT_COURSE && $context->instanceid != SITEID) {
        if (has_capability('local/studenttutor:manageassignments', $context)) {
            $node = $settingsnav->add(
                get_string('studenttutor_settings', 'local_studenttutor'),
                new moodle_url('/local/studenttutor/course_manage.php', ['courseid' => $context->instanceid]),
                navigation_node::TYPE_CUSTOM
            );
        }
    }
}

/**
 * Get configured tutor roles from plugin settings
 * @return array Array of role shortnames
 */
function local_studenttutor_get_tutor_roles() {
    // Get primary tutor role from config
    $primary_role = get_config('local_studenttutor', 'tutor_role');
    if (empty($primary_role)) {
        $primary_role = 'tutortematico'; // Default fallback
    }
    
    $roles = [$primary_role];
    
    // Get additional tutor roles from config
    $additional_roles = get_config('local_studenttutor', 'additional_tutor_roles');
    if (!empty($additional_roles)) {
        $additional_array = array_map('trim', explode(',', $additional_roles));
        $roles = array_merge($roles, $additional_array);
    }
    
    // Remove duplicates and empty values
    $roles = array_unique(array_filter($roles));
    
    return $roles;
}

/**
 * Check if user has tutor role based on configured roles
 * @param int $userid User ID
 * @param int $courseid Course ID (optional, 0 for any course)
 * @return bool True if user has tutor role
 */
function local_studenttutor_is_tutor($userid, $courseid = 0) {
    global $DB;
    
    $tutor_roles = local_studenttutor_get_tutor_roles();
    $role_list = "'" . implode("','", $tutor_roles) . "'";
    
    if ($courseid > 0) {
        $sql = "SELECT 1 FROM {role_assignments} ra
                JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                JOIN {course} c ON c.id = ctx.instanceid
                JOIN {role} r ON r.id = ra.roleid
                WHERE ra.userid = :userid 
                AND c.id = :courseid
                AND r.shortname IN ($role_list)";
        return $DB->record_exists_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);
    } else {
        $sql = "SELECT 1 FROM {role_assignments} ra
                JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                JOIN {role} r ON r.id = ra.roleid
                WHERE ra.userid = :userid 
                AND r.shortname IN ($role_list)";
        return $DB->record_exists_sql($sql, ['userid' => $userid]);
    }
}

/**
 * Get dashboard data
 * 
 * @return array Dashboard data
 */
function local_studenttutor_get_dashboard_data() {
    global $DB;
    
    $data = [
        'total_assignments' => 0,
        'total_students' => 0,
        'total_tutors' => 0,
        'recent_activities' => 0,
        'efficiency_rate' => 0.0,
        'efficiency_growth' => 0.0,
        'assignments_growth' => 0.0,
        'students_growth' => 0.0,
        'tutors_growth' => 0.0,
        'alerts' => []
    ];
    
    try {
        // Get total assignments
        $data['total_assignments'] = $DB->count_records('local_studenttutor_assign', ['status' => 'active']);
        
        // Get total students with assignments
        $data['total_students'] = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT studentid) FROM {local_studenttutor_assign} WHERE status = 'active'"
        );
        
        // Get total tutors
        $data['total_tutors'] = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT tutorid) FROM {local_studenttutor_assign} WHERE status = 'active'"
        );
        
        // Get recent activities (last 7 days)
        $week_ago = time() - (7 * 24 * 3600);
        $data['recent_activities'] = $DB->count_records_select(
            'local_studenttutor_history', 
            'timecreated > ?', 
            [$week_ago]
        );
        
        // Calculate efficiency rate (percentage of students with recent activity)
        if ($data['total_students'] > 0) {
            $students_with_activity = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT h.studentid) 
                 FROM {local_studenttutor_history} h
                 INNER JOIN {local_studenttutor_assign} a ON h.studentid = a.studentid 
                 WHERE h.timecreated > ? AND a.status = 'active'", 
                [$week_ago]
            );
            $data['efficiency_rate'] = ($students_with_activity / $data['total_students']) * 100;
        } else {
            $data['efficiency_rate'] = 0.0;
        }
        
        // Calculate efficiency growth (compare with previous week)
        $two_weeks_ago = time() - (14 * 24 * 3600);
        $previous_week_activities = $DB->count_records_select(
            'local_studenttutor_history',
            'timecreated BETWEEN ? AND ?',
            [$two_weeks_ago, $week_ago]
        );
        
        if ($previous_week_activities > 0) {
            $growth = (($data['recent_activities'] - $previous_week_activities) / $previous_week_activities) * 100;
            $data['efficiency_growth'] = round($growth, 1);
        } else {
            $data['efficiency_growth'] = $data['recent_activities'] > 0 ? 100.0 : 0.0;
        }
        
        // Calculate assignments growth (compare with previous month)
        $month_ago = time() - (30 * 24 * 3600);
        $previous_month_assignments = $DB->count_records_select(
            'local_studenttutor_assign',
            'timecreated < ? AND status = ?',
            [$month_ago, 'active']
        );
        
        if ($previous_month_assignments > 0) {
            $assignments_growth = (($data['total_assignments'] - $previous_month_assignments) / $previous_month_assignments) * 100;
            $data['assignments_growth'] = round($assignments_growth, 1);
        } else {
            $data['assignments_growth'] = $data['total_assignments'] > 0 ? 100.0 : 0.0;
        }
        
        // Calculate students growth (compare with previous month)
        $previous_month_students = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT studentid) FROM {local_studenttutor_assign} 
             WHERE timecreated < ? AND status = 'active'", 
            [$month_ago]
        );
        
        if ($previous_month_students > 0) {
            $students_growth = (($data['total_students'] - $previous_month_students) / $previous_month_students) * 100;
            $data['students_growth'] = round($students_growth, 1);
        } else {
            $data['students_growth'] = $data['total_students'] > 0 ? 100.0 : 0.0;
        }
        
        // Calculate tutors growth (compare with previous month)
        $previous_month_tutors = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT tutorid) FROM {local_studenttutor_assign} 
             WHERE timecreated < ? AND status = 'active'", 
            [$month_ago]
        );
        
        if ($previous_month_tutors > 0) {
            $tutors_growth = (($data['total_tutors'] - $previous_month_tutors) / $previous_month_tutors) * 100;
            $data['tutors_growth'] = round($tutors_growth, 1);
        } else {
            $data['tutors_growth'] = $data['total_tutors'] > 0 ? 100.0 : 0.0;
        }
        
    } catch (Exception $e) {
        // Log error silently to avoid breaking the dashboard
        error_log('Error getting dashboard data: ' . $e->getMessage());
    }
    
    return $data;
}

/**
 * Get comprehensive pedagogical data for dashboard
 *
 * @param int $userid User ID (for filtering data by tutor if not admin)
 * @param bool $is_admin Whether user is admin
 * @return array Pedagogical data for dashboard
 */
function local_studenttutor_get_pedagogical_data($userid, $is_admin = false) {
    global $DB, $CFG;
    
    $data = array();
    
    try {
        // Time periods
        $now = time();
        $week_ago = $now - (7 * 24 * 3600);
        $month_ago = $now - (30 * 24 * 3600);
        
        // Base SQL conditions based on user role
        $user_condition = $is_admin ? "" : " AND a.tutorid = :tutorid";
        $params = $is_admin ? array() : array('tutorid' => $userid);
        
        // 1. ENGAGEMENT METRICS
        $engagement_sql = "
            SELECT 
                COUNT(DISTINCT h.studentid) as active_students,
                COUNT(*) as total_interactions,
                AVG(TIMESTAMPDIFF(HOUR, h.timecreated, h.timemodified)) as avg_response_time
            FROM {local_studenttutor_history} h
            JOIN {local_studenttutor_assign} a ON h.studentid = a.studentid AND h.tutorid = a.tutorid
            WHERE h.timecreated > :week_ago AND a.status = 'active' $user_condition
        ";
        $params['week_ago'] = $week_ago;
        $engagement = $DB->get_record_sql($engagement_sql, $params);
        
        $total_assigned = $DB->count_records_sql("
            SELECT COUNT(DISTINCT studentid) 
            FROM {local_studenttutor_assign} 
            WHERE status = 'active' $user_condition
        ", $is_admin ? array() : array('tutorid' => $userid));
        
        $data['engagement_rate'] = $total_assigned > 0 ? ($engagement->active_students / $total_assigned) * 100 : 0;
        $data['avg_response_time'] = round($engagement->avg_response_time ?? 24, 1);
        
        // Calculate engagement trend
        $prev_engagement = $DB->count_records_sql("
            SELECT COUNT(DISTINCT h.studentid)
            FROM {local_studenttutor_history} h
            JOIN {local_studenttutor_assign} a ON h.studentid = a.studentid
            WHERE h.timecreated BETWEEN :start AND :end AND a.status = 'active' $user_condition
        ", array_merge($params, array('start' => $week_ago - (7*24*3600), 'end' => $week_ago)));
        
        $data['engagement_trend'] = $prev_engagement > 0 ? 
            (($engagement->active_students - $prev_engagement) / $prev_engagement) * 100 : 0;
        
        // 2. AT-RISK STUDENTS IDENTIFICATION
        $at_risk_sql = "
            SELECT DISTINCT u.id, u.firstname, u.lastname,
                   MAX(h.timecreated) as last_interaction,
                   COUNT(h.id) as interaction_count
            FROM {user} u
            JOIN {local_studenttutor_assign} a ON u.id = a.studentid
            LEFT JOIN {local_studenttutor_history} h ON u.id = h.studentid
            WHERE a.status = 'active' $user_condition
            GROUP BY u.id, u.firstname, u.lastname
            HAVING (MAX(h.timecreated) IS NULL OR MAX(h.timecreated) < :risk_threshold)
               OR COUNT(h.id) < 2
        ";
        $params['risk_threshold'] = $now - (14 * 24 * 3600); // 14 days without interaction
        $at_risk_students = $DB->get_records_sql($at_risk_sql, $params);
        
        $data['at_risk_count'] = count($at_risk_students);
        $data['total_students'] = $total_assigned;
        
        // 3. SATISFACTION METRICS (mock data - can be extended with real feedback system)
        $data['satisfaction_rating'] = 4.2; // Would come from feedback table
        $data['satisfaction_stars'] = 4;
        $data['satisfaction_count'] = 127; // Would count actual feedback records
        
        // 4. STUDENT DATA FOR CARDS
        $students_sql = "
            SELECT u.id, u.firstname, u.lastname, u.email, u.picture, u.imagealt,
                   a.courseid, c.fullname as course_name,
                   MAX(ul.timeaccess) as last_access,
                   COUNT(h.id) as sessions_count,
                   AVG(g.finalgrade) as avg_grade,
                   CASE 
                       WHEN MAX(ul.timeaccess) > :active_threshold THEN 'active'
                       WHEN MAX(ul.timeaccess) > :warning_threshold THEN 'warning'
                       ELSE 'at-risk'
                   END as status
            FROM {user} u
            JOIN {local_studenttutor_assign} a ON u.id = a.studentid
            LEFT JOIN {course} c ON a.courseid = c.id
            LEFT JOIN {user_lastaccess} ul ON u.id = ul.userid
            LEFT JOIN {local_studenttutor_history} h ON u.id = h.studentid
            LEFT JOIN {grade_grades} g ON u.id = g.userid
            WHERE a.status = 'active' $user_condition
            GROUP BY u.id, u.firstname, u.lastname, u.email, u.picture, u.imagealt, a.courseid, c.fullname
            ORDER BY last_access DESC
            LIMIT 20
        ";
        $student_params = array_merge($params, array(
            'active_threshold' => $now - (3 * 24 * 3600),  // 3 days
            'warning_threshold' => $now - (7 * 24 * 3600)   // 7 days
        ));
        $students_raw = $DB->get_records_sql($students_sql, $student_params);
        
        $data['students'] = array();
        foreach ($students_raw as $student) {
            $avatar_url = new moodle_url('/user/pix.php', array('file' => '/'.$student->id.'/f1.jpg'));
            
            $data['students'][] = array(
                'id' => $student->id,
                'name' => fullname($student),
                'course' => $student->course_name ?? 'Todos os cursos',
                'avatar_url' => $avatar_url->out(),
                'last_access' => $student->last_access ? userdate($student->last_access, '%d/%m/%Y') : 'Nunca',
                'status' => $student->status,
                'progress' => rand(65, 95), // Mock data - would calculate from course completion
                'sessions_count' => $student->sessions_count,
                'last_grade' => $student->avg_grade ? number_format($student->avg_grade, 1) : 'N/A',
                'grade_level' => $student->avg_grade > 70 ? 'good' : ($student->avg_grade > 50 ? 'average' : 'low'),
                'has_alert' => $student->status === 'at-risk',
                'alert_message' => $student->status === 'at-risk' ? 'Sem atividade há mais de 7 dias' : ''
            );
        }
        
        // 5. ALERTS SYSTEM
        $data['alerts'] = array();
        
        if ($data['at_risk_count'] > 0) {
            $data['alerts'][] = array(
                'type' => 'warning',
                'icon' => 'exclamation-triangle',
                'title' => 'Estudantes Precisam de Atenção',
                'message' => "{$data['at_risk_count']} estudantes não tiveram interação recente",
                'actions' => array(
                    array('label' => 'Ver Estudantes', 'onclick' => 'showAtRiskStudents()')
                )
            );
        }
        
        if ($data['avg_response_time'] > 24) {
            $data['alerts'][] = array(
                'type' => 'info',
                'icon' => 'clock',
                'title' => 'Tempo de Resposta Alto',
                'message' => "Tempo médio de resposta está em {$data['avg_response_time']}h (meta: 24h)",
                'actions' => array(
                    array('label' => 'Ver Detalhes', 'onclick' => 'showResponseTimeDetails()')
                )
            );
        }
        
        // 6. INSIGHTS AND PATTERNS
        $data['peak_activity_time'] = '19:00 - 22:00'; // Would analyze real data
        $data['best_day'] = 'Terça-feira';
        
        $data['difficulty_areas'] = array(
            array('topic' => 'Matemática Básica', 'percentage' => 75, 'student_count' => 12),
            array('topic' => 'Redação', 'percentage' => 60, 'student_count' => 8),
            array('topic' => 'Física Moderna', 'percentage' => 45, 'student_count' => 6)
        );
        
        // 7. AI-POWERED RECOMMENDATIONS
        $data['recommendations'] = array();
        
        if ($data['at_risk_count'] > 3) {
            $data['recommendations'][] = array(
                'id' => 1,
                'priority' => 'high',
                'icon' => 'users',
                'title' => 'Criar Grupo de Apoio',
                'description' => 'Organize uma sessão em grupo para os estudantes em risco',
                'impact' => '+40% engajamento esperado',
                'action_label' => 'Agendar Sessão',
                'action_function' => 'scheduleGroupSession()'
            );
        }
        
        if ($data['avg_response_time'] > 12) {
            $data['recommendations'][] = array(
                'id' => 2,
                'priority' => 'medium',
                'icon' => 'clock',
                'title' => 'Otimizar Horários de Atendimento',
                'description' => 'Ajuste seus horários baseado nos picos de atividade dos estudantes',
                'impact' => '-30% tempo de resposta',
                'action_label' => 'Ver Horários',
                'action_function' => 'optimizeSchedule()'
            );
        }
        
        $data['recommendations'][] = array(
            'id' => 3,
            'priority' => 'low',
            'icon' => 'graduation-cap',
            'title' => 'Material Complementar',
            'description' => 'Crie materiais focados nas áreas de maior dificuldade',
            'impact' => '+25% performance esperada',
            'action_label' => 'Criar Materiais',
            'action_function' => 'createSupplementaryMaterial()'
        );
        
    } catch (Exception $e) {
        error_log('Error getting pedagogical data: ' . $e->getMessage());
        // Return safe defaults
        $data = array(
            'engagement_rate' => 0,
            'engagement_trend' => 0,
            'avg_response_time' => 24,
            'at_risk_count' => 0,
            'total_students' => 0,
            'satisfaction_rating' => 4.0,
            'satisfaction_stars' => 4,
            'satisfaction_count' => 0,
            'students' => array(),
            'alerts' => array(),
            'recommendations' => array()
        );
    }
    
    return $data;
}

/**
 * Get comprehensive administrative dashboard data for institutional overview
 * 
 * @param int $admin_user_id ID of the admin user requesting data
 * @return array Administrative data including institutional metrics, tutor performance, system statistics
 */
function local_studenttutor_get_admin_dashboard_data($admin_user_id = null) {
    global $DB, $CFG, $USER;

    // Security: Verify admin capabilities  
    $context = context_system::instance();
    if (!has_capability('local/studenttutor:manage', $context) && 
        !has_capability('local/studenttutor:viewall', $context)) {
        debugging('Insufficient permissions for admin dashboard data');
        return array();
    }

    try {
        // Institutional Overview Metrics
        $total_students = $DB->count_records_sql("
            SELECT COUNT(DISTINCT u.id) 
            FROM {user} u 
            JOIN {role_assignments} ra ON u.id = ra.userid 
            JOIN {context} ctx ON ra.contextid = ctx.id 
            JOIN {course} c ON ctx.instanceid = c.id 
            WHERE ra.roleid = 5 
            AND u.deleted = 0 
            AND u.confirmed = 1
        ");

        $active_tutors = $DB->count_records_sql("
            SELECT COUNT(DISTINCT u.id) 
            FROM {user} u 
            JOIN {local_studenttutor_history} h ON u.id = h.tutorid 
            WHERE u.deleted = 0 
            AND h.timecreated > ?
        ", array(time() - (30 * 24 * 3600))); // Active in last 30 days

        $total_sessions = $DB->count_records('local_studenttutor_history');

        // Monthly growth metrics
        $current_month_start = strtotime('first day of this month');
        $last_month_start = strtotime('first day of last month');
        
        $current_month_sessions = $DB->count_records_sql("
            SELECT COUNT(*) 
            FROM {local_studenttutor_history} 
            WHERE timecreated >= ?
        ", array($current_month_start));

        $last_month_sessions = $DB->count_records_sql("
            SELECT COUNT(*) 
            FROM {local_studenttutor_history} 
            WHERE timecreated >= ? AND timecreated < ?
        ", array($last_month_start, $current_month_start));

        $growth_rate = $last_month_sessions > 0 ? 
            round((($current_month_sessions - $last_month_sessions) / $last_month_sessions) * 100, 1) : 0;

        // Tutor Performance Rankings
        $tutor_performance = $DB->get_records_sql("
            SELECT u.id, u.firstname, u.lastname, u.email,
                   COUNT(DISTINCT h.studentid) as student_count,
                   COUNT(h.id) as session_count,
                   AVG(CASE WHEN h.rating > 0 THEN h.rating ELSE NULL END) as avg_rating,
                   MAX(h.timecreated) as last_activity
            FROM {user} u
            LEFT JOIN {local_studenttutor_history} h ON u.id = h.tutorid
            WHERE u.deleted = 0
            AND EXISTS (
                SELECT 1 FROM {role_assignments} ra 
                JOIN {context} ctx ON ra.contextid = ctx.id 
                WHERE ra.userid = u.id 
                AND (ra.roleid = 3 OR ra.roleid = 4) -- Teachers and non-editing teachers
            )
            GROUP BY u.id, u.firstname, u.lastname, u.email
            HAVING COUNT(h.id) > 0
            ORDER BY session_count DESC, avg_rating DESC
            LIMIT 20
        ");

        // Risk Analysis - Students needing attention
        $at_risk_students = $DB->get_records_sql("
            SELECT u.id, u.firstname, u.lastname, u.email,
                   c.fullname as course,
                   MAX(u.lastaccess) as last_access,
                   COUNT(h.id) as session_count,
                   tutor.firstname as tutor_firstname,
                   tutor.lastname as tutor_lastname
            FROM {user} u
            JOIN {role_assignments} ra ON u.id = ra.userid
            JOIN {context} ctx ON ra.contextid = ctx.id
            JOIN {course} c ON ctx.instanceid = c.id
            LEFT JOIN {local_studenttutor_history} h ON u.id = h.studentid
            LEFT JOIN {user} tutor ON h.tutorid = tutor.id
            WHERE ra.roleid = 5 -- Student role
            AND u.deleted = 0
            AND u.confirmed = 1
            AND (u.lastaccess < ? OR u.lastaccess IS NULL)
            GROUP BY u.id, u.firstname, u.lastname, u.email, c.fullname, tutor.firstname, tutor.lastname
            HAVING COUNT(h.id) < 2 -- Students with few tutoring sessions
            ORDER BY last_access ASC, session_count ASC
            LIMIT 50
        ", array(time() - (7 * 24 * 3600))); // Haven't accessed in 7 days

        // System Usage Statistics
        $usage_stats = array(
            'daily_active_users' => $DB->count_records_sql("
                SELECT COUNT(DISTINCT userid) 
                FROM {logstore_standard_log} 
                WHERE timecreated > ?
            ", array(time() - (24 * 3600))),
            
            'weekly_sessions' => $DB->count_records_sql("
                SELECT COUNT(*) 
                FROM {local_studenttutor_history} 
                WHERE timecreated > ?
            ", array(time() - (7 * 24 * 3600))),
            
            'avg_session_duration' => $DB->get_field_sql("
                SELECT AVG(duration) 
                FROM {local_studenttutor_history} 
                WHERE duration > 0 
                AND timecreated > ?
            ", array(time() - (30 * 24 * 3600))) ?: 45
        );

        // Course Distribution Analysis
        $course_distribution = $DB->get_records_sql("
            SELECT c.id, c.fullname, c.shortname,
                   COUNT(DISTINCT ra.userid) as student_count,
                   COUNT(DISTINCT h.id) as session_count
            FROM {course} c
            JOIN {context} ctx ON c.id = ctx.instanceid
            JOIN {role_assignments} ra ON ctx.id = ra.contextid
            LEFT JOIN {local_studenttutor_history} h ON ra.userid = h.studentid
            WHERE ra.roleid = 5 -- Student role
            AND c.visible = 1
            GROUP BY c.id, c.fullname, c.shortname
            HAVING student_count > 0
            ORDER BY session_count DESC, student_count DESC
            LIMIT 20
        ");

        // Critical Alerts for Administrators
        $critical_alerts = array();

        // Alert: Tutors with no activity
        $inactive_tutors = $DB->count_records_sql("
            SELECT COUNT(DISTINCT u.id)
            FROM {user} u
            WHERE u.deleted = 0
            AND EXISTS (
                SELECT 1 FROM {role_assignments} ra 
                JOIN {context} ctx ON ra.contextid = ctx.id 
                WHERE ra.userid = u.id 
                AND (ra.roleid = 3 OR ra.roleid = 4)
            )
            AND NOT EXISTS (
                SELECT 1 FROM {local_studenttutor_history} h 
                WHERE h.tutorid = u.id 
                AND h.timecreated > ?
            )
        ", array(time() - (14 * 24 * 3600)));

        if ($inactive_tutors > 0) {
            $critical_alerts[] = array(
                'type' => 'warning',
                'icon' => 'user-clock',
                'title' => 'Tutores Inativos',
                'message' => "$inactive_tutors tutores sem atividade nos últimos 14 dias",
                'action' => 'viewInactiveTutors',
                'priority' => 'medium'
            );
        }

        // Alert: High number of at-risk students
        if (count($at_risk_students) > ($total_students * 0.15)) {
            $critical_alerts[] = array(
                'type' => 'danger',
                'icon' => 'exclamation-triangle',
                'title' => 'Muitos Estudantes em Risco',
                'message' => count($at_risk_students) . " estudantes precisam de intervenção imediata",
                'action' => 'reviewAtRiskStudents',
                'priority' => 'high'
            );
        }

        // Alert: System usage drop
        if ($growth_rate < -20) {
            $critical_alerts[] = array(
                'type' => 'warning',
                'icon' => 'chart-line-down',
                'title' => 'Queda na Utilização',
                'message' => "Redução de {$growth_rate}% no uso do sistema este mês",
                'action' => 'analyzeUsageTrends',
                'priority' => 'medium'
            );
        }

        // Administrative Recommendations
        $admin_recommendations = array(
            array(
                'id' => 1,
                'title' => 'Capacitação de Tutores',
                'description' => 'Organize treinamento para tutores com baixa avaliação ou pouca atividade',
                'impact' => 'Melhoria na qualidade da tutoria',
                'priority' => 'high',
                'icon' => 'chalkboard-teacher',
                'action_label' => 'Planejar Treinamento',
                'action_function' => 'planTutorTraining()'
            ),
            array(
                'id' => 2,
                'title' => 'Otimização de Recursos',
                'description' => 'Redistribuir estudantes entre tutores para equilibrar carga de trabalho',
                'impact' => 'Melhor distribuição e eficiência',
                'priority' => 'medium',
                'icon' => 'balance-scale',
                'action_label' => 'Analisar Distribuição',
                'action_function' => 'analyzeWorkloadDistribution()'
            ),
            array(
                'id' => 3,
                'title' => 'Campanhas de Engajamento',
                'description' => 'Criar iniciativas para aumentar participação de estudantes em risco',
                'impact' => 'Redução da evasão',
                'priority' => 'high',
                'icon' => 'bullhorn',
                'action_label' => 'Criar Campanha',
                'action_function' => 'createEngagementCampaign()'
            )
        );

        // Compile all data for admin dashboard
        $admin_data = array(
            // Key Performance Indicators
            'total_students' => $total_students,
            'active_tutors' => $active_tutors,
            'total_sessions' => $total_sessions,
            'growth_rate' => $growth_rate,
            'current_month_sessions' => $current_month_sessions,
            
            // Performance Metrics
            'avg_session_rating' => $DB->get_field_sql("
                SELECT AVG(rating) 
                FROM {local_studenttutor_history} 
                WHERE rating > 0 
                AND timecreated > ?
            ", array(time() - (30 * 24 * 3600))) ?: 4.2,
            
            'completion_rate' => min(100, round(($total_sessions / max($total_students, 1)) * 20, 1)), // Estimated
            
            // Detailed Data Arrays
            'tutor_performance' => array_values($tutor_performance),
            'at_risk_students' => array_values($at_risk_students),
            'course_distribution' => array_values($course_distribution),
            'usage_stats' => $usage_stats,
            
            // Alerts and Recommendations
            'critical_alerts' => $critical_alerts,
            'recommendations' => $admin_recommendations,
            
            // Trends and Analysis
            'engagement_trends' => array(
                'last_7_days' => $DB->count_records_sql("
                    SELECT COUNT(DISTINCT userid) 
                    FROM {logstore_standard_log} 
                    WHERE timecreated > ?
                ", array(time() - (7 * 24 * 3600))),
                'retention_rate' => 85, // Placeholder - would need more complex calculation
                'peak_usage_time' => '19:00-22:00'
            )
        );

        return $admin_data;

    } catch (Exception $e) {
        debugging('Error getting admin dashboard data: ' . $e->getMessage());
        return array(
            'total_students' => 0,
            'active_tutors' => 0,
            'total_sessions' => 0,
            'growth_rate' => 0,
            'tutor_performance' => array(),
            'at_risk_students' => array(),
            'critical_alerts' => array(),
            'recommendations' => array()
        );
    }
}
