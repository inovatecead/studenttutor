<?php
/**
 * Student Tutor Dashboard - Interactive Analytics & Management
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
require_capability('local/studenttutor:viewassignments', $context);

// Page setup
$PAGE->set_url('/local/studenttutor/dashboard.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('dashboard', 'local_studenttutor'));
$PAGE->set_heading(get_string('dashboard', 'local_studenttutor'));
$PAGE->set_pagelayout('admin');

// Include required JavaScript and CSS
$PAGE->requires->jquery();
$PAGE->requires->js('/local/studenttutor/dashboard/js/dashboard.js');
$PAGE->requires->css('/local/studenttutor/dashboard/css/dashboard.css');

// Get dashboard data
$dashboard_data = local_studenttutor_get_dashboard_data();

echo $OUTPUT->header();
?>

<div class="studenttutor-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="header-content">
            <h1><i class="fa fa-tachometer-alt"></i> Student Tutor Dashboard</h1>
            <p class="dashboard-subtitle">Painel de controle e análise do sistema de tutoria</p>
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
            <button class="btn btn-primary" onclick="exportDashboard()">
                <i class="fa fa-download"></i> Exportar Relatório
            </button>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="metrics-grid">
        <div class="metric-card primary">
            <div class="metric-icon">
                <i class="fa fa-users"></i>
            </div>
            <div class="metric-content">
                <h3><?php echo $dashboard_data['total_assignments']; ?></h3>
                <p>Atribuições Ativas</p>
                <span class="metric-change positive">
                    +<?php echo $dashboard_data['assignments_growth']; ?>% este mês
                </span>
            </div>
        </div>

        <div class="metric-card success">
            <div class="metric-icon">
                <i class="fa fa-user-graduate"></i>
            </div>
            <div class="metric-content">
                <h3><?php echo $dashboard_data['total_students']; ?></h3>
                <p>Estudantes Ativos</p>
                <span class="metric-change positive">
                    +<?php echo $dashboard_data['students_growth']; ?>% este mês
                </span>
            </div>
        </div>

        <div class="metric-card warning">
            <div class="metric-icon">
                <i class="fa fa-chalkboard-teacher"></i>
            </div>
            <div class="metric-content">
                <h3><?php echo $dashboard_data['total_tutors']; ?></h3>
                <p>Tutores Ativos</p>
                <span class="metric-change neutral">
                    <?php echo $dashboard_data['tutors_growth']; ?>% este mês
                </span>
            </div>
        </div>

        <div class="metric-card info">
            <div class="metric-icon">
                <i class="fa fa-percentage"></i>
            </div>
            <div class="metric-content">
                <h3><?php echo number_format($dashboard_data['efficiency_rate'], 1); ?>%</h3>
                <p>Taxa de Eficiência</p>
                <span class="metric-change positive">
                    +<?php echo $dashboard_data['efficiency_growth']; ?>% este mês
                </span>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics Section -->
    <div class="analytics-section">
        <div class="chart-container">
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fa fa-chart-line"></i> Tendência de Atribuições</h3>
                    <div class="chart-controls">
                        <button class="btn btn-sm btn-outline-primary active" onclick="switchChart('assignments', 'daily')">Diário</button>
                        <button class="btn btn-sm btn-outline-primary" onclick="switchChart('assignments', 'weekly')">Semanal</button>
                        <button class="btn btn-sm btn-outline-primary" onclick="switchChart('assignments', 'monthly')">Mensal</button>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="assignmentsChart" width="400" height="200"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fa fa-chart-pie"></i> Distribuição por Curso</h3>
                </div>
                <div class="chart-body">
                    <canvas id="coursesChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="chart-container">
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fa fa-chart-bar"></i> Performance por Tutor</h3>
                </div>
                <div class="chart-body">
                    <canvas id="tutorPerformanceChart" width="400" height="200"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fa fa-chart-area"></i> Atividade por Período</h3>
                </div>
                <div class="chart-body">
                    <canvas id="activityChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables Section -->
    <div class="tables-section">
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fa fa-trophy"></i> Top Tutores</h3>
                <button class="btn btn-sm btn-outline-primary" onclick="viewAllTutors()">Ver Todos</button>
            </div>
            <div class="table-body">
                <div class="top-tutors-list">
                    <?php foreach ($dashboard_data['top_tutors'] as $index => $tutor): ?>
                    <div class="tutor-item <?php echo $index < 3 ? 'top-' . ($index + 1) : ''; ?>">
                        <div class="tutor-rank">
                            <?php if ($index === 0): ?>
                                <i class="fa fa-crown gold"></i>
                            <?php elseif ($index === 1): ?>
                                <i class="fa fa-medal silver"></i>
                            <?php elseif ($index === 2): ?>
                                <i class="fa fa-medal bronze"></i>
                            <?php else: ?>
                                <span class="rank-number"><?php echo $index + 1; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="tutor-info">
                            <strong><?php echo $tutor['name']; ?></strong>
                            <span class="tutor-stats">
                                <?php echo $tutor['student_count']; ?> estudantes • 
                                <?php echo number_format($tutor['satisfaction_rate'], 1); ?>% satisfação
                            </span>
                        </div>
                        <div class="tutor-score">
                            <?php echo $tutor['score']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h3><i class="fa fa-exclamation-triangle"></i> Alertas e Notificações</h3>
                <span class="alert-count"><?php echo count($dashboard_data['alerts']); ?></span>
            </div>
            <div class="table-body">
                <div class="alerts-list">
                    <?php if (empty($dashboard_data['alerts'])): ?>
                        <div class="no-alerts">
                            <i class="fa fa-check-circle"></i>
                            <p>Nenhum alerta no momento</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($dashboard_data['alerts'] as $alert): ?>
                        <div class="alert-item <?php echo $alert['type']; ?>">
                            <div class="alert-icon">
                                <i class="fa fa-<?php echo $alert['icon']; ?>"></i>
                            </div>
                            <div class="alert-content">
                                <strong><?php echo $alert['title']; ?></strong>
                                <p><?php echo $alert['message']; ?></p>
                                <small><?php echo $alert['time']; ?></small>
                            </div>
                            <div class="alert-action">
                                <button class="btn btn-sm btn-outline-primary" onclick="resolveAlert(<?php echo $alert['id']; ?>)">
                                    Resolver
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="quick-actions">
        <div class="actions-card">
            <h3><i class="fa fa-rocket"></i> Ações Rápidas</h3>
            <div class="actions-grid">
                <button class="action-btn primary" onclick="location.href='assign.php'">
                    <i class="fa fa-plus"></i>
                    Nova Atribuição
                </button>
                <button class="action-btn success" onclick="bulkAssign()">
                    <i class="fa fa-users"></i>
                    Atribuição em Lote
                </button>
                <button class="action-btn warning" onclick="generateReport()">
                    <i class="fa fa-chart-bar"></i>
                    Gerar Relatório
                </button>
                <button class="action-btn info" onclick="importData()">
                    <i class="fa fa-upload"></i>
                    Importar Dados
                </button>
                <button class="action-btn secondary" onclick="manageSettings()">
                    <i class="fa fa-cog"></i>
                    Configurações
                </button>
                <button class="action-btn dark" onclick="viewLogs()">
                    <i class="fa fa-list"></i>
                    Ver Logs
                </button>
            </div>
        </div>
    </div>

    <!-- Recent Activity Feed -->
    <div class="activity-feed">
        <div class="feed-card">
            <h3><i class="fa fa-clock"></i> Atividade Recente</h3>
            <div class="feed-list">
                <?php foreach ($dashboard_data['recent_activities'] as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo $activity['type']; ?>">
                        <i class="fa fa-<?php echo $activity['icon']; ?>"></i>
                    </div>
                    <div class="activity-content">
                        <p><?php echo $activity['description']; ?></p>
                        <small><?php echo $activity['time']; ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-outline-primary btn-block" onclick="viewAllActivity()">
                Ver Toda Atividade
            </button>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<!-- Hidden data for JavaScript -->
<script type="text/javascript">
    window.dashboardData = <?php echo json_encode($dashboard_data); ?>;
    window.baseURL = '<?php echo $CFG->wwwroot; ?>';
    window.sesskey = '<?php echo $OUTPUT->sesskey ?? ''; ?>';
</script>

<?php
echo $OUTPUT->footer();
?>
