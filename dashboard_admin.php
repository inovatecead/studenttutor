<?php
/**
 * Administrative Dashboard for Student-Tutor Plugin
 * Comprehensive overview for administrators and managers
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Check if this file is being accessed directly or included
if (!defined('MOODLE_INTERNAL')) {
    // Direct access - initialize Moodle environment
    require_once(__DIR__ . '/../../config.php');
    require_login();

    // Set up context and security
    $context = context_system::instance();
    require_capability('local/studenttutor:manageassignments', $context);
    
    // Load plugin functions
    require_once(__DIR__ . '/lib.php');
} else {
    // File is being included - variables should already be set
    if (!isset($context)) {
        $context = context_system::instance();
    }
}

// Page setup for admin dashboard
$PAGE->set_url('/local/studenttutor/index.php');
$PAGE->set_context($context);
$PAGE->set_title('Gestão de Tutoria - Visão Administrativa');
$PAGE->set_heading('Central de Gestão de Tutoria');
$PAGE->set_pagelayout('admin');

// Include required JavaScript and CSS
$PAGE->requires->jquery();
$PAGE->requires->js('/local/studenttutor/dashboard/js/admin_dashboard.js');
$PAGE->requires->css('/local/studenttutor/dashboard/css/admin_dashboard.css');

// Get comprehensive admin data
$admin_data = local_studenttutor_get_admin_dashboard_data();

echo $OUTPUT->header();

// Add notification container for JavaScript notifications
echo '<div class="notification-container"></div>';
?>

<div class="admin-dashboard">
    <!-- Header Section with Quick Actions -->
    <div class="dashboard-header">
        <div class="header-content">
            <h1><i class="fa fa-tachometer-alt"></i> Central de Gestão de Tutoria</h1>
            <p class="dashboard-subtitle">Visão completa da gestão pedagógica institucional</p>
        </div>
        
        <div class="header-actions">
            <div class="time-filter">
                <select id="time-range" class="form-control">
                    <option value="7">Últimos 7 dias</option>
                    <option value="30" selected>Últimos 30 dias</option>
                    <option value="90">Últimos 90 dias</option>
                    <option value="365">Último ano</option>
                </select>
            </div>
            <button class="btn btn-primary" onclick="location.href='assign.php'">
                <i class="fa fa-user-plus"></i> Nova Atribuição
            </button>
            <button class="btn btn-secondary" onclick="exportFullReport()">
                <i class="fa fa-download"></i> Exportar Relatório Completo
            </button>
        </div>
    </div>

    <!-- Critical Alerts for Administrators -->
    <?php if (!empty($admin_data['critical_alerts'])): ?>
    <div class="critical-alerts">
        <h3><i class="fa fa-exclamation-triangle"></i> Alertas Críticos</h3>
        <div class="alerts-grid">
            <?php foreach ($admin_data['critical_alerts'] as $alert): ?>
                <div class="alert-card severity-<?php echo $alert['severity']; ?>">
                    <div class="alert-icon">
                        <i class="fa fa-<?php echo $alert['icon']; ?>"></i>
                    </div>
                    <div class="alert-content">
                        <h4><?php echo $alert['title']; ?></h4>
                        <p><?php echo $alert['message']; ?></p>
                        <div class="alert-actions">
                            <button class="btn btn-sm btn-primary" onclick="<?php echo $alert['action']; ?>">
                                <?php echo $alert['action_label']; ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Administrative KPI Dashboard -->
    <div class="admin-kpi-grid">
        <!-- Institutional Metrics -->
        <div class="kpi-section">
            <h3><i class="fa fa-university"></i> Métricas Institucionais</h3>
            <div class="kpi-cards-row">
                <div class="kpi-card primary">
                    <div class="kpi-header">
                        <h4>Total de Atribuições</h4>
                        <span class="kpi-trend <?php echo $admin_data['assignments_trend'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo ($admin_data['assignments_trend'] >= 0 ? '+' : '') . $admin_data['assignments_trend']; ?>%
                        </span>
                    </div>
                    <div class="kpi-value">
                        <span class="value"><?php echo $admin_data['total_assignments']; ?></span>
                        <span class="subtitle">atribuições ativas</span>
                    </div>
                    <div class="kpi-detail">
                        <small><?php echo $admin_data['new_assignments_month']; ?> novas este mês</small>
                    </div>
                </div>

                <div class="kpi-card success">
                    <div class="kpi-header">
                        <h4>Estudantes Atendidos</h4>
                        <span class="kpi-trend positive">+<?php echo $admin_data['students_growth']; ?>%</span>
                    </div>
                    <div class="kpi-value">
                        <span class="value"><?php echo $admin_data['total_students']; ?></span>
                        <span class="subtitle">estudantes únicos</span>
                    </div>
                    <div class="kpi-detail">
                        <small><?php echo $admin_data['students_coverage']; ?>% de cobertura</small>
                    </div>
                </div>

                <div class="kpi-card warning">
                    <div class="kpi-header">
                        <h4>Tutores Ativos</h4>
                        <span class="kpi-trend neutral"><?php echo $admin_data['tutors_trend']; ?>%</span>
                    </div>
                    <div class="kpi-value">
                        <span class="value"><?php echo $admin_data['active_tutors']; ?></span>
                        <span class="subtitle">tutores trabalhando</span>
                    </div>
                    <div class="kpi-detail">
                        <small>Média: <?php echo $admin_data['avg_students_per_tutor']; ?> estudantes/tutor</small>
                    </div>
                </div>

                <div class="kpi-card info">
                    <div class="kpi-header">
                        <h4>Taxa de Retenção</h4>
                        <span class="kpi-trend positive">+<?php echo $admin_data['retention_improvement']; ?>%</span>
                    </div>
                    <div class="kpi-value">
                        <span class="value"><?php echo number_format($admin_data['retention_rate'], 1); ?>%</span>
                        <span class="subtitle">retenção de estudantes</span>
                    </div>
                    <div class="kpi-detail">
                        <small>Meta: <?php echo $admin_data['retention_target']; ?>%</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="kpi-section">
            <h3><i class="fa fa-chart-line"></i> Performance e Qualidade</h3>
            <div class="kpi-cards-row">
                <div class="kpi-card performance">
                    <div class="kpi-header">
                        <h4>Satisfação Média</h4>
                    </div>
                    <div class="kpi-value">
                        <div class="rating-display">
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa fa-star <?php echo $i <= $admin_data['avg_satisfaction_stars'] ? 'active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-value"><?php echo number_format($admin_data['avg_satisfaction'], 1); ?>/5</span>
                        </div>
                    </div>
                    <div class="kpi-detail">
                        <small>Baseado em <?php echo $admin_data['total_evaluations']; ?> avaliações</small>
                    </div>
                </div>

                <div class="kpi-card efficiency">
                    <div class="kpi-header">
                        <h4>Tempo Resposta Médio</h4>
                        <span class="status-indicator <?php echo $admin_data['response_time_status']; ?>">
                            <i class="fa fa-<?php echo $admin_data['response_time_status'] == 'good' ? 'check' : 'exclamation-triangle'; ?>"></i>
                        </span>
                    </div>
                    <div class="kpi-value">
                        <span class="value"><?php echo $admin_data['avg_response_time']; ?>h</span>
                        <span class="subtitle">tempo médio</span>
                    </div>
                    <div class="kpi-detail">
                        <small>Meta: ≤24h | Melhor: <?php echo $admin_data['best_response_time']; ?>h</small>
                    </div>
                </div>

                <div class="kpi-card risk">
                    <div class="kpi-header">
                        <h4>Estudantes em Risco</h4>
                    </div>
                    <div class="kpi-value">
                        <span class="value alert-number"><?php echo $admin_data['at_risk_students']; ?></span>
                        <span class="subtitle">precisam atenção</span>
                    </div>
                    <div class="kpi-detail">
                        <button class="btn btn-sm btn-warning" onclick="viewAtRiskStudents()">
                            <i class="fa fa-eye"></i> Ver Detalhes
                        </button>
                    </div>
                </div>

                <div class="kpi-card workload">
                    <div class="kpi-header">
                        <h4>Distribuição de Carga</h4>
                    </div>
                    <div class="kpi-value">
                        <div class="workload-indicator">
                            <div class="workload-bar">
                                <div class="workload-fill" style="width: <?php echo $admin_data['workload_balance']; ?>%"></div>
                            </div>
                            <span class="balance-percentage"><?php echo $admin_data['workload_balance']; ?>%</span>
                        </div>
                    </div>
                    <div class="kpi-detail">
                        <small><?php echo $admin_data['overloaded_tutors']; ?> tutores sobrecarregados</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tutor Performance Ranking -->
    <div class="performance-section">
        <div class="section-header">
            <h3><i class="fa fa-trophy"></i> Ranking de Performance dos Tutores</h3>
            <div class="performance-controls">
                <select id="performance-metric" class="form-control">
                    <option value="overall">Performance Geral</option>
                    <option value="satisfaction">Satisfação dos Estudantes</option>
                    <option value="response_time">Tempo de Resposta</option>
                    <option value="retention">Taxa de Retenção</option>
                </select>
                <button class="btn btn-outline-primary" onclick="exportTutorPerformance()">
                    <i class="fa fa-download"></i> Exportar Ranking
                </button>
            </div>
        </div>

        <div class="tutors-performance-grid">
            <?php foreach ($admin_data['top_tutors'] as $index => $tutor): ?>
                <div class="tutor-performance-card rank-<?php echo $index + 1; ?>">
                    <div class="tutor-rank">
                        <?php if ($index < 3): ?>
                            <div class="medal medal-<?php echo ['gold', 'silver', 'bronze'][$index]; ?>">
                                <i class="fa fa-medal"></i>
                                <span><?php echo $index + 1; ?></span>
                            </div>
                        <?php else: ?>
                            <div class="rank-number"><?php echo $index + 1; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tutor-info">
                        <div class="tutor-avatar">
                            <img src="<?php echo $tutor['avatar_url']; ?>" alt="<?php echo $tutor['name']; ?>">
                        </div>
                        <div class="tutor-details">
                            <h4><?php echo $tutor['name']; ?></h4>
                            <p class="tutor-role"><?php echo $tutor['role']; ?></p>
                        </div>
                    </div>

                    <div class="tutor-metrics">
                        <div class="metric">
                            <span class="label">Estudantes</span>
                            <span class="value"><?php echo $tutor['student_count']; ?></span>
                        </div>
                        <div class="metric">
                            <span class="label">Satisfação</span>
                            <span class="value"><?php echo number_format($tutor['satisfaction'], 1); ?>★</span>
                        </div>
                        <div class="metric">
                            <span class="label">Resposta</span>
                            <span class="value"><?php echo $tutor['avg_response']; ?>h</span>
                        </div>
                        <div class="metric">
                            <span class="label">Score</span>
                            <span class="value score"><?php echo $tutor['performance_score']; ?></span>
                        </div>
                    </div>

                    <div class="tutor-actions">
                        <button class="btn btn-sm btn-primary" onclick="viewTutorDetails(<?php echo $tutor['id']; ?>)">
                            <i class="fa fa-eye"></i> Ver Detalhes
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="manageTutorAssignments(<?php echo $tutor['id']; ?>)">
                            <i class="fa fa-users"></i> Gerenciar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Analytics and Insights -->
    <div class="analytics-section">
        <div class="section-header">
            <h3><i class="fa fa-chart-bar"></i> Analytics e Insights Estratégicos</h3>
        </div>

        <div class="analytics-grid">
            <div class="analytics-card">
                <h4><i class="fa fa-clock"></i> Padrões Temporais</h4>
                <div class="chart-container">
                    <canvas id="temporalPatternsChart"></canvas>
                </div>
                <div class="chart-insights">
                    <p><strong>Pico de atividade:</strong> <?php echo $admin_data['peak_activity']; ?></p>
                    <p><strong>Melhor dia:</strong> <?php echo $admin_data['best_day']; ?></p>
                </div>
            </div>

            <div class="analytics-card">
                <h4><i class="fa fa-graduation-cap"></i> Distribuição por Curso</h4>
                <div class="chart-container">
                    <canvas id="courseDistributionChart"></canvas>
                </div>
                <div class="course-stats">
                    <?php foreach ($admin_data['top_courses'] as $course): ?>
                        <div class="course-item">
                            <span class="course-name"><?php echo $course['name']; ?></span>
                            <span class="course-count"><?php echo $course['student_count']; ?> estudantes</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="analytics-card">
                <h4><i class="fa fa-exclamation-triangle"></i> Áreas de Atenção</h4>
                <div class="attention-areas">
                    <?php foreach ($admin_data['attention_areas'] as $area): ?>
                        <div class="attention-item priority-<?php echo $area['priority']; ?>">
                            <div class="attention-icon">
                                <i class="fa fa-<?php echo $area['icon']; ?>"></i>
                            </div>
                            <div class="attention-content">
                                <h5><?php echo $area['title']; ?></h5>
                                <p><?php echo $area['description']; ?></p>
                                <button class="btn btn-sm btn-outline-primary" onclick="<?php echo $area['action']; ?>">
                                    <?php echo $area['action_label']; ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Management Tools -->
    <div class="management-tools">
        <div class="section-header">
            <h3><i class="fa fa-tools"></i> Ferramentas de Gestão Rápida</h3>
        </div>

        <div class="tools-grid">
            <button class="tool-button" onclick="bulkAssignWizard()">
                <i class="fa fa-users-cog"></i>
                <span>Atribuição em Lote</span>
                <small>Assinar múltiplos estudantes</small>
            </button>

            <button class="tool-button" onclick="balanceWorkload()">
                <i class="fa fa-balance-scale"></i>
                <span>Balancear Carga</span>
                <small>Otimizar distribuição</small>
            </button>

            <button class="tool-button" onclick="generateReports()">
                <i class="fa fa-file-alt"></i>
                <span>Relatórios Automáticos</span>
                <small>Gerar relatórios detalhados</small>
            </button>

            <button class="tool-button" onclick="manageSettings()">
                <i class="fa fa-cog"></i>
                <span>Configurações</span>
                <small>Ajustes do sistema</small>
            </button>

            <button class="tool-button" onclick="viewLegacyInterface()">
                <i class="fa fa-list"></i>
                <span>Interface Clássica</span>
                <small>Visualização em tabela</small>
            </button>

            <button class="tool-button" onclick="systemHealth()">
                <i class="fa fa-heartbeat"></i>
                <span>Saúde do Sistema</span>
                <small>Status e diagnósticos</small>
            </button>
        </div>
    </div>
</div>

<!-- JavaScript Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeAdminDashboard({
        userId: <?php echo $USER->id; ?>,
        contextPath: '<?php echo $CFG->wwwroot; ?>/local/studenttutor/',
        data: <?php echo json_encode($admin_data['chart_data']); ?>
    });
});
</script>

<?php echo $OUTPUT->footer(); ?>
