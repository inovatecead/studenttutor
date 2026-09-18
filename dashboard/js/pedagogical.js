/**
 * Pedagogical Dashboard JavaScript Functions
 */

let dashboardConfig = {};

/**
 * Initialize the pedagogical dashboard
 */
function initializePedagogicalDashboard(config) {
    dashboardConfig = config;
    
    // Initialize filter tabs
    initializeFilterTabs();
    
    // Initialize chart placeholders
    initializeChartPlaceholders();
    
    // Initialize tooltips and other interactive elements
    initializeInteractiveElements();
    
    console.log('Pedagogical Dashboard initialized');
}

/**
 * Initialize filter tabs for student management
 */
function initializeFilterTabs() {
    const filterTabs = document.querySelectorAll('.filter-tab');
    const studentCards = document.querySelectorAll('.student-card');
    
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            filterTabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Filter students
            const filter = this.getAttribute('data-filter');
            filterStudents(filter, studentCards);
        });
    });
}

/**
 * Filter students based on status
 */
function filterStudents(filter, studentCards) {
    studentCards.forEach(card => {
        if (filter === 'all') {
            card.style.display = 'block';
        } else {
            const status = card.getAttribute('data-status');
            if (status === filter) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        }
    });
}

/**
 * Initialize chart placeholders
 */
function initializeChartPlaceholders() {
    // Learning Patterns Chart
    const learningChart = document.getElementById('learningPatternsChart');
    if (learningChart) {
        createPlaceholderChart(learningChart, 'Horários de maior atividade dos estudantes');
    }
}

/**
 * Create placeholder chart
 */
function createPlaceholderChart(canvas, title) {
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    
    // Clear canvas
    ctx.clearRect(0, 0, width, height);
    
    // Set styles
    ctx.fillStyle = '#f8f9fc';
    ctx.fillRect(0, 0, width, height);
    
    ctx.fillStyle = '#858796';
    ctx.font = '14px -apple-system, BlinkMacSystemFont, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    
    // Draw placeholder text
    ctx.fillText(title, width / 2, height / 2);
    
    // Draw simple bars as placeholder
    ctx.fillStyle = '#4e73df';
    const barWidth = 20;
    const barSpacing = 10;
    for (let i = 0; i < 7; i++) {
        const x = 50 + (i * (barWidth + barSpacing));
        const barHeight = Math.random() * 60 + 20;
        const y = height - 40 - barHeight;
        ctx.fillRect(x, y, barWidth, barHeight);
    }
}

/**
 * Initialize interactive elements
 */
function initializeInteractiveElements() {
    // Add hover effects to KPI cards
    const kpiCards = document.querySelectorAll('.kpi-card');
    kpiCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

/**
 * Show at-risk students modal
 */
function showAtRiskStudents() {
    // This would open a modal with detailed at-risk student information
    alert('Funcionalidade: Mostrar estudantes em risco\n\nImplementar modal com lista detalhada dos estudantes que precisam de atenção imediata.');
}

/**
 * Contact student
 */
function contactStudent(studentId) {
    // This would open communication interface
    alert(`Funcionalidade: Contatar estudante ID ${studentId}\n\nImplementar interface de comunicação (email, chat, etc.)`);
}

/**
 * View student progress
 */
function viewProgress(studentId) {
    // This would show detailed progress analytics
    alert(`Funcionalidade: Ver progresso do estudante ID ${studentId}\n\nImplementar gráficos detalhados de progresso acadêmico.`);
}

/**
 * Schedule session with student
 */
function scheduleSession(studentId) {
    // This would open scheduling interface
    alert(`Funcionalidade: Agendar sessão com estudante ID ${studentId}\n\nImplementar calendário de agendamento.`);
}

/**
 * Show my students (for tutors)
 */
function showMyStudents() {
    // Filter to show only assigned students
    alert('Funcionalidade: Meus Estudantes\n\nFiltrar para mostrar apenas estudantes atribuídos ao tutor atual.');
}

/**
 * Show reports
 */
function showReports() {
    // Navigate to reports page
    window.location.href = dashboardConfig.contextPath + 'reports.php';
}

/**
 * Create intervention plan
 */
function createInterventionPlan() {
    alert('Funcionalidade: Criar Plano de Intervenção\n\nImplementar assistente para criar planos pedagógicos personalizados baseados nas dificuldades identificadas.');
}

/**
 * Schedule group session
 */
function scheduleGroupSession() {
    alert('Funcionalidade: Agendar Sessão em Grupo\n\nImplementar agendamento de sessões coletivas para estudantes em risco.');
}

/**
 * Optimize schedule
 */
function optimizeSchedule() {
    alert('Funcionalidade: Otimizar Horários\n\nAnálise de dados para sugerir melhores horários de atendimento baseado nos padrões de atividade dos estudantes.');
}

/**
 * Create supplementary material
 */
function createSupplementaryMaterial() {
    alert('Funcionalidade: Criar Material Complementar\n\nAssistente para criar materiais focados nas áreas de maior dificuldade identificadas.');
}

/**
 * Dismiss recommendation
 */
function dismissRecommendation(recommendationId) {
    // Hide the recommendation card
    const card = document.querySelector(`[data-recommendation-id="${recommendationId}"]`);
    if (card) {
        card.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            card.remove();
        }, 300);
    }
}

/**
 * Export student data
 */
function exportStudentData() {
    alert('Funcionalidade: Exportar Dados\n\nGerar relatório Excel/CSV com dados dos estudantes filtrados.');
}

/**
 * Show response time details
 */
function showResponseTimeDetails() {
    alert('Funcionalidade: Detalhes do Tempo de Resposta\n\nMostrar breakdown detalhado dos tempos de resposta por tutor e período.');
}

// CSS Animation for fade out
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(-20px); }
    }
`;
document.head.appendChild(style);

// Export functions for global access
window.initializePedagogicalDashboard = initializePedagogicalDashboard;
window.showAtRiskStudents = showAtRiskStudents;
window.contactStudent = contactStudent;
window.viewProgress = viewProgress;
window.scheduleSession = scheduleSession;
window.showMyStudents = showMyStudents;
window.showReports = showReports;
window.createInterventionPlan = createInterventionPlan;
window.scheduleGroupSession = scheduleGroupSession;
window.optimizeSchedule = optimizeSchedule;
window.createSupplementaryMaterial = createSupplementaryMaterial;
window.dismissRecommendation = dismissRecommendation;
window.exportStudentData = exportStudentData;
window.showResponseTimeDetails = showResponseTimeDetails;
