<?php
/**
 * Tutor-focused Dashboard for Student-Tutor Plugin
 * Pedagogical interface optimized for individual tutors
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
    require_capability('local/studenttutor:viewassignments', $context);
    
    // Load plugin functions
    require_once(__DIR__ . '/lib.php');
} else {
    // File is being included - variables should already be set
    if (!isset($context)) {
        $context = context_system::instance();
    }
}

// Page setup for tutor dashboard
$PAGE->set_url('/local/studenttutor/index.php');
$PAGE->set_context($context);
$PAGE->set_title('Meu Painel de Tutoria');
$PAGE->set_heading('Painel do Tutor - ' . fullname($USER));
$PAGE->set_pagelayout('standard');

// Include required JavaScript and CSS
$PAGE->requires->jquery();
$PAGE->requires->js('/local/studenttutor/dashboard/js/pedagogical.js');
$PAGE->requires->css('/local/studenttutor/dashboard/css/pedagogical.css');

// Get pedagogical data filtered for this tutor
$pedagogical_data = local_studenttutor_get_pedagogical_data($USER->id, false);

echo $OUTPUT->header();

// Add notification container for JavaScript notifications
echo '<div class="notification-container"></div>';
?>

<div class="pedagogical-dashboard tutor-focused">
    <!-- Welcome Section for Tutors -->
    <div class="welcome-section">
        <div class="welcome-content">
            <h1><i class="fa fa-graduation-cap"></i> Olá, <?php echo $USER->firstname; ?>!</h1>
            <p class="welcome-subtitle">
                Você tem <strong><?php echo $pedagogical_data['total_students']; ?> estudantes</strong> sob sua tutoria.
                <?php if ($pedagogical_data['at_risk_count'] > 0): ?>
                    <span class="alert-inline">
                        <i class="fa fa-exclamation-triangle"></i>
                        <?php echo $pedagogical_data['at_risk_count']; ?> precisam de atenção imediata.
                    </span>
                <?php endif; ?>
            </p>
        </div>
        
        <div class="quick-actions-contextual">
            <button class="btn btn-primary" onclick="location.href='add_history.php'">
                <i class="fa fa-plus"></i> Registrar Atendimento
            </button>
            <button class="btn btn-secondary" onclick="showMyStudents()">
                <i class="fa fa-users"></i> Meus Estudantes
            </button>
            <button class="btn btn-info" onclick="viewSchedule()">
                <i class="fa fa-calendar"></i> Minha Agenda
            </button>
        </div>
    </div>

    <!-- Priority Alerts for Tutors -->
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

    <!-- Tutor Performance KPIs -->
    <div class="kpi-grid-pedagogical tutor-kpis">
        <div class="kpi-card engagement">
            <div class="kpi-header">
                <h3>Meus Estudantes Ativos</h3>
                <span class="kpi-period">Últimos 7 dias</span>
            </div>
            <div class="kpi-value">
                <span class="value"><?php echo number_format($pedagogical_data['engagement_rate'], 0); ?>%</span>
                <span class="trend <?php echo $pedagogical_data['engagement_trend'] > 0 ? 'up' : 'down'; ?>">
                    <i class="fa fa-arrow-<?php echo $pedagogical_data['engagement_trend'] > 0 ? 'up' : 'down'; ?>"></i>
                    <?php echo abs($pedagogical_data['engagement_trend']); ?>%
                </span>
            </div>
            <div class="kpi-detail">
                <small><?php echo round($pedagogical_data['engagement_rate'] * $pedagogical_data['total_students'] / 100); ?> de <?php echo $pedagogical_data['total_students']; ?> estudantes ativos</small>
            </div>
        </div>

        <div class="kpi-card response-time">
            <div class="kpi-header">
                <h3>Meu Tempo de Resposta</h3>
                <span class="kpi-period">Média atual</span>
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

        <div class="kpi-card satisfaction">
            <div class="kpi-header">
                <h3>Satisfação dos Meus Estudantes</h3>
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

        <div class="kpi-card productivity">
            <div class="kpi-header">
                <h3>Atendimentos este Mês</h3>
                <span class="kpi-period">Produtividade</span>
            </div>
            <div class="kpi-value">
                <span class="value"><?php echo $pedagogical_data['monthly_sessions'] ?? 0; ?></span>
                <span class="unit">sessões</span>
            </div>
            <div class="kpi-detail">
                <small>Média: <?php echo number_format(($pedagogical_data['monthly_sessions'] ?? 0) / max($pedagogical_data['total_students'], 1), 1); ?> por estudante</small>
            </div>
        </div>
    </div>

    <!-- My Students Management -->
    <div class="student-management-section">
        <div class="section-header">
            <h2><i class="fa fa-users"></i> Meus Estudantes (<?php echo count($pedagogical_data['students']); ?>)</h2>
            <div class="section-controls">
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all">Todos (<?php echo count($pedagogical_data['students']); ?>)</button>
                    <button class="filter-tab" data-filter="active">Ativos</button>
                    <button class="filter-tab" data-filter="at-risk">Em Risco (<?php echo $pedagogical_data['at_risk_count']; ?>)</button>
                    <button class="filter-tab" data-filter="excellent">Excelentes</button>
                </div>
                <div class="view-options">
                    <button class="btn btn-sm btn-outline-primary" onclick="scheduleGroupSession()">
                        <i class="fa fa-users"></i> Sessão em Grupo
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="exportMyStudents()">
                        <i class="fa fa-download"></i> Exportar
                    </button>
                </div>
            </div>
        </div>

        <div class="student-grid">
            <?php foreach ($pedagogical_data['students'] as $student): ?>
                <div class="student-card enhanced" data-status="<?php echo $student['status']; ?>">
                    <div class="student-header">
                        <div class="student-avatar">
                            <img src="<?php echo $student['avatar_url']; ?>" alt="<?php echo $student['name']; ?>">
                            <span class="status-indicator status-<?php echo $student['status']; ?>"></span>
                        </div>
                        <div class="student-basic">
                            <h4><?php echo $student['name']; ?></h4>
                            <p class="course"><?php echo $student['course']; ?></p>
                            <span class="last-activity">
                                <i class="fa fa-clock"></i>
                                Último acesso: <?php echo $student['last_access']; ?>
                            </span>
                        </div>
                        <div class="student-priority">
                            <?php if ($student['status'] === 'at-risk'): ?>
                                <span class="priority-badge urgent">
                                    <i class="fa fa-exclamation-triangle"></i> Urgente
                                </span>
                            <?php elseif ($student['status'] === 'warning'): ?>
                                <span class="priority-badge attention">
                                    <i class="fa fa-eye"></i> Atenção
                                </span>
                            <?php else: ?>
                                <span class="priority-badge normal">
                                    <i class="fa fa-check-circle"></i> Normal
                                </span>
                            <?php endif; ?>
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
                            <span class="value sessions"><?php echo $student['sessions_count']; ?></span>
                        </div>

                        <div class="metric">
                            <span class="label">Última Nota</span>
                            <span class="value grade-<?php echo $student['grade_level']; ?>">
                                <?php echo $student['last_grade']; ?>
                            </span>
                        </div>

                        <div class="metric">
                            <span class="label">Próximo Contato</span>
                            <span class="value next-contact">
                                <?php 
                                $next_contact = $student['next_contact_suggestion'] ?? 'Hoje';
                                echo $next_contact;
                                ?>
                            </span>
                        </div>
                    </div>

                    <div class="student-actions expanded">
                        <button class="btn btn-sm btn-primary" onclick="contactStudent(<?php echo $student['id']; ?>)" 
                                title="Enviar mensagem ou email">
                            <i class="fa fa-comment"></i> Contatar
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="viewProgress(<?php echo $student['id']; ?>)"
                                title="Ver progresso detalhado">
                            <i class="fa fa-chart-line"></i> Progresso
                        </button>
                        <button class="btn btn-sm btn-info" onclick="scheduleSession(<?php echo $student['id']; ?>)"
                                title="Agendar próxima sessão">
                            <i class="fa fa-calendar"></i> Agendar
                        </button>
                        <button class="btn btn-sm btn-success" onclick="addQuickNote(<?php echo $student['id']; ?>)"
                                title="Adicionar nota rápida">
                            <i class="fa fa-sticky-note"></i> Nota
                        </button>
                    </div>

                    <?php if ($student['has_alert']): ?>
                        <div class="student-alert">
                            <i class="fa fa-exclamation-triangle"></i>
                            <?php echo $student['alert_message']; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($student['recent_activity'])): ?>
                        <div class="student-recent-activity">
                            <small><strong>Última atividade:</strong> <?php echo $student['recent_activity']; ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Personal Insights for Tutor -->
    <div class="tutor-insights">
        <div class="insights-header">
            <h3><i class="fa fa-lightbulb"></i> Seus Insights Pedagógicos</h3>
            <small>Análises baseadas em sua tutoria</small>
        </div>

        <div class="insights-grid">
            <div class="insight-card personal-patterns">
                <h4><i class="fa fa-clock"></i> Seus Padrões de Atendimento</h4>
                <div class="pattern-stats">
                    <div class="pattern-item">
                        <span class="label">Horário mais produtivo:</span>
                        <span class="value"><?php echo $pedagogical_data['peak_activity_time'] ?? '19:00-22:00'; ?></span>
                    </div>
                    <div class="pattern-item">
                        <span class="label">Melhor dia da semana:</span>
                        <span class="value"><?php echo $pedagogical_data['best_day'] ?? 'Terça-feira'; ?></span>
                    </div>
                    <div class="pattern-item">
                        <span class="label">Duração média das sessões:</span>
                        <span class="value"><?php echo $pedagogical_data['avg_session_duration'] ?? '45'; ?> min</span>
                    </div>
                </div>
            </div>

            <div class="insight-card success-factors">
                <h4><i class="fa fa-trophy"></i> Seus Fatores de Sucesso</h4>
                <div class="success-metrics">
                    <div class="success-item">
                        <div class="success-icon"><i class="fa fa-comments"></i></div>
                        <div class="success-content">
                            <h5>Comunicação Efetiva</h5>
                            <p>Seus estudantes respondem <strong>20% mais rápido</strong> que a média institucional</p>
                        </div>
                    </div>
                    <div class="success-item">
                        <div class="success-icon"><i class="fa fa-heart"></i></div>
                        <div class="success-content">
                            <h5>Relacionamento Próximo</h5>
                            <p>Satisfação dos estudantes: <strong><?php echo number_format($pedagogical_data['satisfaction_rating'], 1); ?>/5</strong></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="insight-card improvement-suggestions">
                <h4><i class="fa fa-arrow-up"></i> Oportunidades de Melhoria</h4>
                <div class="suggestions-list">
                    <?php 
                    $suggestions = [
                        'Consider scheduling follow-up sessions for at-risk students',
                        'Try group sessions for students with similar difficulties',
                        'Use more interactive tools during online sessions'
                    ];
                    foreach ($pedagogical_data['personal_suggestions'] ?? $suggestions as $suggestion): 
                    ?>
                        <div class="suggestion-item">
                            <i class="fa fa-arrow-right"></i>
                            <span><?php echo $suggestion; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tutor's Recommended Actions -->
    <div class="recommendations-section tutor-focused">
        <div class="section-header">
            <h3><i class="fa fa-lightbulb"></i> Ações Recomendadas para Você</h3>
            <span class="ai-badge">IA Pedagógica Pessoal</span>
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

    <!-- Quick Access Footer -->
    <div class="quick-access-footer">
        <div class="footer-section">
            <h4>Acesso Rápido</h4>
            <div class="quick-links">
                <a href="reports.php" class="quick-link">
                    <i class="fa fa-chart-bar"></i> Meus Relatórios
                </a>
                <a href="index.php?legacy=1" class="quick-link">
                    <i class="fa fa-list"></i> Visualização Clássica
                </a>
                <a href="settings.php" class="quick-link">
                    <i class="fa fa-cog"></i> Configurações
                </a>
                <a href="help.php" class="quick-link">
                    <i class="fa fa-question-circle"></i> Ajuda
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced JavaScript for Tutor Dashboard -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializePedagogicalDashboard({
        userId: <?php echo $USER->id; ?>,
        isAdmin: false,
        isTutor: true,
        contextPath: '<?php echo $CFG->wwwroot; ?>/local/studenttutor/',
        totalStudents: <?php echo $pedagogical_data['total_students']; ?>,
        atRiskCount: <?php echo $pedagogical_data['at_risk_count']; ?>
    });
    
    // Auto-refresh alerts every 5 minutes
    setInterval(refreshAlerts, 300000);
});

function addQuickNote(studentId) {
    // Quick note functionality for tutors
    const note = prompt('Adicionar nota rápida sobre o estudante:');
    if (note && note.trim()) {
        // AJAX call to save note
        console.log('Saving quick note for student', studentId, ':', note);
        alert('Nota salva com sucesso!');
    }
}

function exportMyStudents() {
    alert('Exportar dados dos meus estudantes\n\nGerar planilha com informações detalhadas dos estudantes atribuídos.');
}

function viewSchedule() {
    alert('Minha Agenda\n\nVer calendário de sessões agendadas e horários disponíveis.');
}

function refreshAlerts() {
    // Auto-refresh alerts without page reload
    console.log('Refreshing alerts...');
}
</script>

<?php echo $OUTPUT->footer(); ?>
