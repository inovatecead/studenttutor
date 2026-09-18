/**
 * Administrative Dashboard JavaScript functionality
 * Handles interactive features for the admin dashboard
 *
 * @package    local_studenttutor
 * @copyright  2025 Your Organization  
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Global dashboard configuration
var AdminDashboard = {
    contextPath: '',
    refreshInterval: 300000, // 5 minutes
    autoRefreshEnabled: true,
    filters: {
        tutorPerformance: 'all',
        riskLevel: 'all',
        timeRange: '30days'
    }
};

/**
 * Initialize the administrative dashboard
 */
function initializeAdminDashboard(config) {
    AdminDashboard.contextPath = config.contextPath || '';
    AdminDashboard.totalStudents = config.totalStudents || 0;
    AdminDashboard.activeTutors = config.activeTutors || 0;
    
    // Initialize components
    initializeKPICards();
    initializeTutorPerformanceTable();
    initializeAtRiskStudentsTable();  
    initializeFilters();
    initializeAlerts();
    initializeRefreshSystem();
    
    console.log('Admin Dashboard initialized successfully');
}

/**
 * Initialize KPI cards with interactive features
 */
function initializeKPICards() {
    // Add hover effects and click handlers for KPI cards
    $('.kpi-card').each(function() {
        var $card = $(this);
        
        $card.on('mouseenter', function() {
            $(this).addClass('kpi-hover');
        }).on('mouseleave', function() {
            $(this).removeClass('kpi-hover');
        });

        // Click to expand details
        $card.find('.kpi-value').on('click', function(e) {
            e.preventDefault();
            var kpiType = $card.attr('class').match(/kpi-card\s+(\w+)/)[1];
            showKPIDetails(kpiType);
        });
    });

    // Animate counters on load
    animateKPICounters();
}

/**
 * Animate KPI counter values
 */
function animateKPICounters() {
    $('.kpi-value .value').each(function() {
        var $counter = $(this);
        var target = parseInt($counter.text().replace(/[^\d]/g, ''));
        
        if (target && target > 0) {
            $counter.text('0');
            $({ Counter: 0 }).animate({ Counter: target }, {
                duration: 2000,
                easing: 'swing',
                step: function() {
                    $counter.text(Math.ceil(this.Counter));
                }
            });
        }
    });
}

/**
 * Show detailed KPI information
 */
function showKPIDetails(kpiType) {
    var details = {
        'students': {
            title: 'Detalhes dos Estudantes',
            content: 'Total de estudantes ativos no sistema, distribuídos entre os cursos disponíveis.'
        },
        'tutors': {
            title: 'Detalhes dos Tutores',
            content: 'Tutores ativos que realizaram pelo menos uma sessão nos últimos 30 dias.'
        },
        'sessions': {
            title: 'Detalhes das Sessões',
            content: 'Total de sessões de tutoria realizadas este mês, incluindo presenciais e online.'
        },
        'satisfaction': {
            title: 'Detalhes da Satisfação',
            content: 'Média das avaliações dos estudantes sobre as sessões de tutoria recebidas.'
        }
    };

    if (details[kpiType]) {
        showModal(details[kpiType].title, details[kpiType].content);
    }
}

/**
 * Initialize tutor performance table functionality
 */
function initializeTutorPerformanceTable() {
    // Add sorting functionality
    $('.performance-table th.sortable').on('click', function() {
        var column = $(this).data('sort');
        var direction = $(this).hasClass('sort-asc') ? 'desc' : 'asc';
        
        sortTutorTable(column, direction);
        
        // Update sort indicators
        $('.performance-table th').removeClass('sort-asc sort-desc');
        $(this).addClass('sort-' + direction);
    });

    // Add row selection
    $('.performance-table tbody tr').on('click', function() {
        var tutorId = $(this).data('tutor-id');
        if (tutorId) {
            showTutorDetails(tutorId);
        }
    });
}

/**
 * Sort tutor performance table
 */
function sortTutorTable(column, direction) {
    var $tbody = $('.performance-table tbody');
    var rows = $tbody.find('tr').get();

    rows.sort(function(a, b) {
        var aVal = $(a).find('[data-sort="' + column + '"]').text();
        var bVal = $(b).find('[data-sort="' + column + '"]').text();

        if ($.isNumeric(aVal) && $.isNumeric(bVal)) {
            return direction === 'asc' ? aVal - bVal : bVal - aVal;
        } else {
            return direction === 'asc' ? 
                aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
        }
    });

    $.each(rows, function(index, row) {
        $tbody.append(row);
    });
}

/**
 * Show detailed tutor information
 */
function showTutorDetails(tutorId) {
    // This would typically load data via AJAX
    var modalContent = `
        <div class="tutor-details-modal">
            <h4>Detalhes do Tutor</h4>
            <p>Carregando informações detalhadas do tutor...</p>
            <div class="loading-spinner">
                <i class="fa fa-spinner fa-spin"></i>
            </div>
        </div>
    `;
    
    showModal('Tutor #' + tutorId, modalContent);
    
    // Simulate AJAX load
    setTimeout(function() {
        loadTutorDetailsAjax(tutorId);
    }, 1000);
}

/**
 * Initialize at-risk students table
 */
function initializeAtRiskStudentsTable() {
    $('.at-risk-table tbody tr').on('click', function() {
        var studentId = $(this).data('student-id');
        if (studentId) {
            showStudentInterventionOptions(studentId);
        }
    });

    // Add filtering for risk levels
    $('.risk-filter').on('change', function() {
        var riskLevel = $(this).val();
        filterAtRiskStudents(riskLevel);
    });
}

/**
 * Show intervention options for at-risk student
 */
function showStudentInterventionOptions(studentId) {
    var modalContent = `
        <div class="student-intervention-modal">
            <h4>Opções de Intervenção</h4>
            <div class="intervention-options">
                <button class="btn btn-primary" onclick="assignUrgentTutor(${studentId})">
                    <i class="fa fa-user-plus"></i> Atribuir Tutor Urgente
                </button>
                <button class="btn btn-warning" onclick="scheduleIntervention(${studentId})">
                    <i class="fa fa-calendar"></i> Agendar Intervenção
                </button>
                <button class="btn btn-info" onclick="contactStudent(${studentId})">
                    <i class="fa fa-envelope"></i> Contactar Estudante
                </button>
                <button class="btn btn-secondary" onclick="viewStudentHistory(${studentId})">
                    <i class="fa fa-history"></i> Ver Histórico
                </button>
            </div>
        </div>
    `;
    
    showModal('Estudante em Risco #' + studentId, modalContent);
}

/**
 * Initialize dashboard filters
 */
function initializeFilters() {
    // Time range filter
    $('.time-range-filter').on('change', function() {
        var timeRange = $(this).val();
        AdminDashboard.filters.timeRange = timeRange;
        refreshDashboardData();
    });

    // Performance filter
    $('.performance-filter').on('change', function() {
        var performance = $(this).val();
        AdminDashboard.filters.tutorPerformance = performance;
        filterTutorsByPerformance(performance);
    });

    // Course filter
    $('.course-filter').on('change', function() {
        var courseId = $(this).val();
        filterByCourse(courseId);
    });
}

/**
 * Initialize alert system
 */
function initializeAlerts() {
    // Auto-dismiss alerts after 10 seconds
    $('.alert.auto-dismiss').each(function() {
        var $alert = $(this);
        setTimeout(function() {
            $alert.fadeOut(500);
        }, 10000);
    });

    // Dismiss button functionality
    $('.alert .dismiss-btn').on('click', function() {
        $(this).closest('.alert').fadeOut(300);
    });

    // Alert action buttons
    $('.alert .alert-action').on('click', function(e) {
        e.preventDefault();
        var action = $(this).data('action');
        executeAlertAction(action);
    });
}

/**
 * Execute alert actions
 */
function executeAlertAction(action) {
    switch(action) {
        case 'viewInactiveTutors':
            viewInactiveTutors();
            break;
        case 'reviewAtRiskStudents':
            reviewAtRiskStudents();
            break;
        case 'analyzeUsageTrends':
            analyzeUsageTrends();
            break;
        case 'planTutorTraining':
            planTutorTraining();
            break;
        default:
            console.log('Unknown alert action:', action);
    }
}

/**
 * Initialize auto-refresh system
 */
function initializeRefreshSystem() {
    // Auto-refresh toggle
    $('.auto-refresh-toggle').on('change', function() {
        AdminDashboard.autoRefreshEnabled = $(this).is(':checked');
        if (AdminDashboard.autoRefreshEnabled) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });

    // Manual refresh button
    $('.refresh-btn').on('click', function() {
        refreshDashboardData();
    });

    // Start auto-refresh by default
    if (AdminDashboard.autoRefreshEnabled) {
        startAutoRefresh();
    }
}

/**
 * Start automatic refresh
 */
function startAutoRefresh() {
    if (AdminDashboard.refreshTimer) {
        clearInterval(AdminDashboard.refreshTimer);
    }
    
    AdminDashboard.refreshTimer = setInterval(function() {
        refreshDashboardData();
    }, AdminDashboard.refreshInterval);
    
    console.log('Auto-refresh started');
}

/**
 * Stop automatic refresh
 */
function stopAutoRefresh() {
    if (AdminDashboard.refreshTimer) {
        clearInterval(AdminDashboard.refreshTimer);
        AdminDashboard.refreshTimer = null;
    }
    console.log('Auto-refresh stopped');
}

/**
 * Refresh dashboard data
 */
function refreshDashboardData() {
    showLoadingIndicator();
    
    // This would typically be an AJAX call to refresh data
    setTimeout(function() {
        hideLoadingIndicator();
        updateLastRefreshTime();
        showNotification('Dashboard atualizado com sucesso', 'success');
    }, 2000);
}

/**
 * Show loading indicator
 */
function showLoadingIndicator() {
    $('.dashboard-loading').show();
    $('.refresh-btn i').addClass('fa-spin');
}

/**
 * Hide loading indicator
 */
function hideLoadingIndicator() {
    $('.dashboard-loading').hide();
    $('.refresh-btn i').removeClass('fa-spin');
}

/**
 * Update last refresh time display
 */
function updateLastRefreshTime() {
    var now = new Date();
    var timeString = now.toLocaleTimeString('pt-BR');
    $('.last-refresh-time').text('Última atualização: ' + timeString);
}

/**
 * Show modal dialog
 */
function showModal(title, content) {
    var modalHtml = `
        <div class="modal-overlay" id="adminModal">
            <div class="modal-dialog">
                <div class="modal-header">
                    <h3>${title}</h3>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal()">Fechar</button>
                </div>
            </div>
        </div>
    `;
    
    $('body').append(modalHtml);
    $('#adminModal').fadeIn(300);
}

/**
 * Close modal dialog
 */
function closeModal() {
    $('#adminModal').fadeOut(300, function() {
        $(this).remove();
    });
}

/**
 * Show notification
 */
function showNotification(message, type) {
    type = type || 'info';
    
    var notification = $(`
        <div class="notification notification-${type}">
            <i class="fa fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `);
    
    $('.notification-container').append(notification);
    
    notification.slideDown(300);
    
    // Auto-remove after 5 seconds
    setTimeout(function() {
        notification.slideUp(300, function() {
            $(this).remove();
        });
    }, 5000);
    
    // Manual close
    notification.find('.notification-close').on('click', function() {
        notification.slideUp(300, function() {
            $(this).remove();
        });
    });
}

/**
 * Get notification icon based on type
 */
function getNotificationIcon(type) {
    var icons = {
        'success': 'check-circle',
        'error': 'exclamation-triangle', 
        'warning': 'exclamation-circle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Admin-specific action functions
function viewInactiveTutors() {
    window.location.href = AdminDashboard.contextPath + 'reports.php?filter=inactive_tutors';
}

function reviewAtRiskStudents() {
    window.location.href = AdminDashboard.contextPath + 'reports.php?filter=at_risk_students';
}

function analyzeUsageTrends() {
    showModal('Análise de Tendências de Uso', `
        <div class="trends-analysis">
            <p>Analisando padrões de uso do sistema...</p>
            <div class="analysis-charts">
                <canvas id="usageTrendChart" width="400" height="200"></canvas>
            </div>
        </div>
    `);
}

function planTutorTraining() {
    showModal('Planejamento de Capacitação', `
        <div class="training-planner">
            <h4>Criar Programa de Capacitação</h4>
            <form class="training-form">
                <div class="form-group">
                    <label>Título do Treinamento:</label>
                    <input type="text" class="form-control" placeholder="Ex: Técnicas Avançadas de Tutoria Online">
                </div>
                <div class="form-group">
                    <label>Público-alvo:</label>
                    <select class="form-control">
                        <option>Todos os tutores</option>
                        <option>Tutores com baixa avaliação</option>
                        <option>Tutores novos (menos de 6 meses)</option>
                        <option>Tutores específicos</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Data do Treinamento:</label>
                    <input type="date" class="form-control">
                </div>
                <button type="button" class="btn btn-primary">Criar Programa</button>
            </form>
        </div>
    `);
}

function assignUrgentTutor(studentId) {
    showNotification('Atribuindo tutor urgente para o estudante #' + studentId, 'info');
    closeModal();
}

function scheduleIntervention(studentId) {
    showNotification('Agendando intervenção para o estudante #' + studentId, 'info');
    closeModal();
}

function contactStudent(studentId) {
    window.location.href = AdminDashboard.contextPath + 'contact.php?student=' + studentId;
}

function viewStudentHistory(studentId) {
    window.location.href = AdminDashboard.contextPath + 'student_history.php?student=' + studentId;
}

// Export Dashboard for external use
window.AdminDashboard = AdminDashboard;
window.initializeAdminDashboard = initializeAdminDashboard;
