<?php
/**
 * Dashboard Pedagógico Inteligente para Tutores EAD
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

// Determine user type
$is_admin = has_capability('local/studenttutor:manageassignments', $context);
$is_tutor = local_studenttutor_is_tutor($USER->id);

// Page setup
$PAGE->set_url('/local/studenttutor/dashboard_pedagogical.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('pedagogical_dashboard', 'local_studenttutor'));
$PAGE->set_heading(get_string('pedagogical_dashboard', 'local_studenttutor'));
$PAGE->set_pagelayout('standard');

// Include required JavaScript and CSS
$PAGE->requires->jquery();
$PAGE->requires->js('/local/studenttutor/dashboard/js/pedagogical.js');
$PAGE->requires->css('/local/studenttutor/dashboard/css/pedagogical.css');

// Get pedagogical data
$pedagogical_data = local_studenttutor_get_pedagogical_data($USER->id, $is_admin);

echo $OUTPUT->header();
?>

<div class="pedagogical-dashboard">
    <!-- Welcome Section with Context-Aware Content -->
    <div class="welcome-section">
        <div class="welcome-content">
            <?php if ($is_tutor && !$is_admin): ?>
                <h1><i class="fa fa-graduation-cap"></i> Olá, Tutor <?php echo $USER->firstname; ?>!</h1>
                <p class="welcome-subtitle">Seus estudantes precisam de você. Vamos acompanhar o progresso juntos.</p>
            <?php else: ?>
                <h1><i class="fa fa-chart-line"></i> Gestão Pedagógica Inteligente</h1>
                <p class="welcome-subtitle">Visão completa da tutoria e desempenho acadêmico</p>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions Contextual -->
        <div class="quick-actions-contextual">
            <?php if ($is_tutor && !$is_admin): ?>
                <button class="btn btn-primary" onclick="location.href='add_history.php'">
                    <i class="fa fa-plus"></i> Registrar Atendimento
                </button>
                <button class="btn btn-secondary" onclick="showMyStudents()">
                    <i class="fa fa-users"></i> Meus Estudantes
                </button>
            <?php else: ?>
                <button class="btn btn-primary" onclick="location.href='assign.php'">
                    <i class="fa fa-user-plus"></i> Nova Atribuição
                </button>
                <button class="btn btn-secondary" onclick="showReports()">
                    <i class="fa fa-chart-bar"></i> Relatórios
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert System - Pedagogical Focus -->
    <div class="alert-system">
        <?php if (!empty($pedagogical_data['alerts'])): ?>
            <?php foreach ($pedagogical_data['alerts'] as $alert): ?>
                <div class="alert alert-<?php echo $alert['type']; ?>">
                    <div class="alert-icon">
                        <i class="fa fa-<?php echo $alert['icon']; ?>"></i>
                    </div>
                    <div class="alert-content">
                        <h4><?php echo $alert['title']; ?></h4>
                        <p><?php echo $alert['message']; ?></p>
                        <div class="alert-actions">
                            <?php foreach ($alert['actions'] as $action): ?>
                                <button class="btn btn-sm btn-outline-primary" onclick="<?php echo $action['onclick']; ?>">
                                    <?php echo $action['label']; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Key Performance Indicators - Pedagogical -->
    <div class="kpi-grid-pedagogical">
        <div class="kpi-card engagement">
            <div class="kpi-header">
                <h3>Engajamento dos Estudantes</h3>
                <span class="kpi-period">Últimos 7 dias</span>
            </div>
            <div class="kpi-value">
                <span class="value"><?php echo number_format($pedagogical_data['engagement_rate'], 1); ?>%</span>
                <span class="trend <?php echo $pedagogical_data['engagement_trend'] > 0 ? 'up' : 'down'; ?>">
                    <i class="fa fa-arrow-<?php echo $pedagogical_data['engagement_trend'] > 0 ? 'up' : 'down'; ?>"></i>
                    <?php echo abs($pedagogical_data['engagement_trend']); ?>%
                </span>
            </div>
            <div class="kpi-detail">
                <div class="progress-ring">
                    <svg width="60" height="60">
                        <circle cx="30" cy="30" r="25" stroke="#e3e3e3" stroke-width="3" fill="none"/>
                        <circle cx="30" cy="30" r="25" stroke="#28a745" stroke-width="3" 
                                fill="none" stroke-dasharray="157" 
                                stroke-dashoffset="<?php echo 157 - (157 * $pedagogical_data['engagement_rate'] / 100); ?>"
                                transform="rotate(-90 30 30)"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="kpi-card response-time">
            <div class="kpi-header">
                <h3>Tempo de Resposta Médio</h3>
                <span class="kpi-period">Tutores</span>
            </div>
            <div class="kpi-value">
                <span class="value"><?php echo $pedagogical_data['avg_response_time']; ?></span>
                <span class="unit">horas</span>
            </div>
            <div class="kpi-benchmark">
                Meta: ≤ 24h
                <span class="status <?php echo $pedagogical_data['avg_response_time'] <= 24 ? 'good' : 'attention'; ?>">
                    <i class="fa fa-<?php echo $pedagogical_data['avg_response_time'] <= 24 ? 'check' : 'exclamation-triangle'; ?>"></i>
                </span>
            </div>
        </div>

        <div class="kpi-card at-risk">
            <div class="kpi-header">
                <h3>Estudantes em Risco</h3>
                <span class="kpi-period">Requerem atenção</span>
            </div>
            <div class="kpi-value">
                <span class="value"><?php echo $pedagogical_data['at_risk_count']; ?></span>
                <span class="total">/ <?php echo $pedagogical_data['total_students']; ?></span>
            </div>
            <?php if ($pedagogical_data['at_risk_count'] > 0): ?>
                <div class="kpi-action">
                    <button class="btn btn-sm btn-warning" onclick="showAtRiskStudents()">
                        <i class="fa fa-exclamation-triangle"></i> Ver Detalhes
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="kpi-card satisfaction">
            <div class="kpi-header">
                <h3>Satisfação dos Estudantes</h3>
                <span class="kpi-period">Avaliações recentes</span>
            </div>
            <div class="kpi-value">
                <div class="stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fa fa-star <?php echo $i <= $pedagogical_data['satisfaction_stars'] ? 'active' : ''; ?>"></i>
                    <?php endfor; ?>
                </div>
                <span class="rating"><?php echo number_format($pedagogical_data['satisfaction_rating'], 1); ?>/5</span>
            </div>
            <div class="kpi-detail">
                <?php echo $pedagogical_data['satisfaction_count']; ?> avaliações
            </div>
        </div>
    </div>

    <!-- Student Management Section -->
    <div class="student-management-section">
        <div class="section-header">
            <h2><i class="fa fa-users"></i> Gestão de Estudantes</h2>
            <div class="section-controls">
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all">Todos</button>
                    <button class="filter-tab" data-filter="active">Ativos</button>
                    <button class="filter-tab" data-filter="at-risk">Em Risco</button>
                    <button class="filter-tab" data-filter="excellent">Excelentes</button>
                </div>
                <button class="btn btn-outline-primary" onclick="exportStudentData()">
                    <i class="fa fa-download"></i> Exportar
                </button>
            </div>
        </div>

        <div class="student-grid">
            <?php foreach ($pedagogical_data['students'] as $student): ?>
                <div class="student-card" data-status="<?php echo $student['status']; ?>">
                    <div class="student-header">
                        <div class="student-avatar">
                            <img src="<?php echo $student['avatar_url']; ?>" alt="<?php echo $student['name']; ?>">
                            <span class="status-indicator status-<?php echo $student['status']; ?>"></span>
                        </div>
                        <div class="student-basic">
                            <h4><?php echo $student['name']; ?></h4>
                            <p class="course"><?php echo $student['course']; ?></p>
                            <span class="last-activity">
                                Último acesso: <?php echo $student['last_access']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="student-metrics">
                        <div class="metric">
                            <span class="label">Progresso</span>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $student['progress']; ?>%"></div>
                            </div>
                            <span class="value"><?php echo $student['progress']; ?>%</span>
                        </div>

                        <div class="metric">
                            <span class="label">Atendimentos</span>
                            <span class="value"><?php echo $student['sessions_count']; ?></span>
                        </div>

                        <div class="metric">
                            <span class="label">Última Nota</span>
                            <span class="value grade-<?php echo $student['grade_level']; ?>">
                                <?php echo $student['last_grade']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="student-actions">
                        <button class="btn btn-sm btn-primary" onclick="contactStudent(<?php echo $student['id']; ?>)">
                            <i class="fa fa-comment"></i> Contatar
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="viewProgress(<?php echo $student['id']; ?>)">
                            <i class="fa fa-chart-line"></i> Progresso
                        </button>
                        <button class="btn btn-sm btn-info" onclick="scheduleSession(<?php echo $student['id']; ?>)">
                            <i class="fa fa-calendar"></i> Agendar
                        </button>
                    </div>

                    <?php if ($student['has_alert']): ?>
                        <div class="student-alert">
                            <i class="fa fa-exclamation-triangle"></i>
                            <?php echo $student['alert_message']; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Analytics Section - Pedagogical Insights -->
    <div class="analytics-pedagogical">
        <div class="analytics-header">
            <h2><i class="fa fa-brain"></i> Insights Pedagógicos</h2>
        </div>

        <div class="insights-grid">
            <div class="insight-card learning-patterns">
                <h3><i class="fa fa-clock"></i> Padrões de Aprendizagem</h3>
                <div class="chart-container">
                    <canvas id="learningPatternsChart"></canvas>
                </div>
                <div class="insight-summary">
                    <p><strong>Pico de atividade:</strong> <?php echo $pedagogical_data['peak_activity_time']; ?></p>
                    <p><strong>Melhor dia:</strong> <?php echo $pedagogical_data['best_day']; ?></p>
                </div>
            </div>

            <div class="insight-card difficulty-areas">
                <h3><i class="fa fa-exclamation-circle"></i> Áreas de Dificuldade</h3>
                <div class="difficulty-list">
                    <?php foreach ($pedagogical_data['difficulty_areas'] as $area): ?>
                        <div class="difficulty-item">
                            <div class="difficulty-topic"><?php echo $area['topic']; ?></div>
                            <div class="difficulty-bar">
                                <div class="difficulty-fill" style="width: <?php echo $area['percentage']; ?>%"></div>
                            </div>
                            <div class="difficulty-count"><?php echo $area['student_count']; ?> estudantes</div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-sm btn-primary" onclick="createInterventionPlan()">
                    <i class="fa fa-lightbulb"></i> Criar Plano de Intervenção
                </button>
            </div>

            <div class="insight-card success-factors">
                <h3><i class="fa fa-trophy"></i> Fatores de Sucesso</h3>
                <div class="success-metrics">
                    <div class="success-item">
                        <div class="success-icon"><i class="fa fa-comments"></i></div>
                        <div class="success-content">
                            <h4>Comunicação Regular</h4>
                            <p>Estudantes com atendimento semanal têm <strong>85% mais</strong> chance de sucesso</p>
                        </div>
                    </div>
                    <div class="success-item">
                        <div class="success-icon"><i class="fa fa-clock"></i></div>
                        <div class="success-content">
                            <h4>Resposta Rápida</h4>
                            <p>Respostas em até 4h aumentam satisfação em <strong>40%</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recommended Actions Section -->
    <div class="recommendations-section">
        <div class="section-header">
            <h2><i class="fa fa-lightbulb"></i> Ações Recomendadas</h2>
            <span class="ai-badge">IA Pedagógica</span>
        </div>

        <div class="recommendations-grid">
            <?php foreach ($pedagogical_data['recommendations'] as $recommendation): ?>
                <div class="recommendation-card priority-<?php echo $recommendation['priority']; ?>">
                    <div class="recommendation-header">
                        <div class="recommendation-icon">
                            <i class="fa fa-<?php echo $recommendation['icon']; ?>"></i>
                        </div>
                        <div class="recommendation-meta">
                            <h4><?php echo $recommendation['title']; ?></h4>
                            <span class="priority-label">Prioridade: <?php echo ucfirst($recommendation['priority']); ?></span>
                        </div>
                    </div>
                    <div class="recommendation-content">
                        <p><?php echo $recommendation['description']; ?></p>
                        <div class="recommendation-impact">
                            <span class="impact-label">Impacto esperado:</span>
                            <span class="impact-value"><?php echo $recommendation['impact']; ?></span>
                        </div>
                    </div>
                    <div class="recommendation-actions">
                        <button class="btn btn-primary btn-sm" onclick="<?php echo $recommendation['action_function']; ?>">
                            <?php echo $recommendation['action_label']; ?>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="dismissRecommendation(<?php echo $recommendation['id']; ?>)">
                            Dispensar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal Templates -->
<div id="studentModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Dynamic content loaded via JavaScript -->
        </div>
    </div>
</div>

<!-- JavaScript Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializePedagogicalDashboard({
        userId: <?php echo $USER->id; ?>,
        isAdmin: <?php echo $is_admin ? 'true' : 'false'; ?>,
        isTutor: <?php echo $is_tutor ? 'true' : 'false'; ?>,
        contextPath: '<?php echo $CFG->wwwroot; ?>/local/studenttutor/'
    });
});
</script>

<?php echo $OUTPUT->footer(); ?>
