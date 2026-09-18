/**
 * Student Tutor Dashboard JavaScript
 * Advanced analytics and interactive features
 */

class StudentTutorDashboard {
    constructor() {
        this.charts = {};
        this.data = window.dashboardData || {};
        this.baseURL = window.baseURL || '';
        this.sesskey = window.sesskey || '';
        this.initialized = false;
        
        this.init();
    }
    
    init() {
        if (this.initialized) return;
        
        console.log('Initializing Student Tutor Dashboard...');
        
        // Wait for DOM and Chart.js to be ready
        if (typeof Chart === 'undefined') {
            console.log('Chart.js not loaded yet, waiting...');
            setTimeout(() => this.init(), 100);
            return;
        }
        
        this.setupCharts();
        this.setupEventListeners();
        this.animateElements();
        this.setupRealTimeUpdates();
        
        this.initialized = true;
        console.log('Dashboard initialized successfully');
    }
    
    setupCharts() {
        // Set Chart.js defaults
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.color = '#666';
        
        this.createAssignmentsChart();
        this.createCoursesChart();
        this.createTutorPerformanceChart();
        this.createActivityChart();
    }
    
    createAssignmentsChart() {
        const ctx = document.getElementById('assignmentsChart');
        if (!ctx) return;
        
        const data = this.data.assignments_timeline || this.generateMockTimelineData();
        
        this.charts.assignments = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => this.formatDate(item.date)),
                datasets: [{
                    label: 'Novas Atribuições',
                    data: data.map(item => item.count),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxTicksLimit: 7
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }
    
    createCoursesChart() {
        const ctx = document.getElementById('coursesChart');
        if (!ctx) return;
        
        const data = this.data.course_distribution || this.generateMockCourseData();
        
        this.charts.courses = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(data).map(key => data[key].name || `Curso ${key}`),
                datasets: [{
                    data: Object.values(data).map(item => item.assignment_count || item),
                    backgroundColor: [
                        '#667eea', '#764ba2', '#f093fb', '#f5576c',
                        '#4facfe', '#00f2fe', '#43e97b', '#38f9d7',
                        '#ffecd2', '#fcb69f', '#a8edea', '#fed6e3'
                    ],
                    borderWidth: 0,
                    hoverBorderWidth: 3,
                    hoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
    
    createTutorPerformanceChart() {
        const ctx = document.getElementById('tutorPerformanceChart');
        if (!ctx) return;
        
        const data = this.data.tutor_performance || this.generateMockTutorData();
        
        this.charts.tutorPerformance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.values(data).map(tutor => this.truncateName(tutor.name)),
                datasets: [{
                    label: 'Estudantes',
                    data: Object.values(data).map(tutor => tutor.student_count),
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: '#667eea',
                    borderWidth: 1,
                    borderRadius: 4
                }, {
                    label: 'Performance (%)',
                    data: Object.values(data).map(tutor => tutor.performance_score),
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: '#28a745',
                    borderWidth: 1,
                    borderRadius: 4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 45
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Número de Estudantes'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Performance (%)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
    
    createActivityChart() {
        const ctx = document.getElementById('activityChart');
        if (!ctx) return;
        
        const data = this.generateMockActivityData();
        
        this.charts.activity = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Criações',
                    data: data.creations,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true
                }, {
                    label: 'Atualizações',
                    data: data.updates,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 2,
                    fill: true
                }, {
                    label: 'Exclusões',
                    data: data.deletions,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }
    
    setupEventListeners() {
        // Time range filter
        const timeRange = document.getElementById('time-range');
        if (timeRange) {
            timeRange.addEventListener('change', (e) => {
                this.updateTimeRange(e.target.value);
            });
        }
        
        // Metric cards hover effects
        document.querySelectorAll('.metric-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0) scale(1)';
            });
        });
        
        // Chart controls
        document.querySelectorAll('.chart-controls .btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const chartType = e.target.closest('.chart-card').id;
                const period = e.target.textContent.toLowerCase();
                this.updateChart(chartType, period);
            });
        });
    }
    
    animateElements() {
        // Animate metric cards
        document.querySelectorAll('.metric-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
        
        // Animate charts
        setTimeout(() => {
            document.querySelectorAll('.chart-card').forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.8s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });
        }, 500);
        
        // Animate counters
        this.animateCounters();
    }
    
    animateCounters() {
        document.querySelectorAll('.metric-content h3').forEach(counter => {
            const target = parseInt(counter.textContent.replace(/[^\d]/g, ''));
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    counter.textContent = target.toLocaleString();
                    clearInterval(timer);
                } else {
                    counter.textContent = Math.floor(current).toLocaleString();
                }
            }, 30);
        });
    }
    
    setupRealTimeUpdates() {
        // Update dashboard every 5 minutes
        setInterval(() => {
            this.refreshData();
        }, 5 * 60 * 1000);
        
        // Update activity feed every minute
        setInterval(() => {
            this.updateActivityFeed();
        }, 60 * 1000);
    }
    
    // Utility Methods
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR', { 
            month: 'short', 
            day: 'numeric' 
        });
    }
    
    truncateName(name, maxLength = 12) {
        if (name.length <= maxLength) return name;
        return name.substring(0, maxLength) + '...';
    }
    
    generateMockTimelineData() {
        const data = [];
        for (let i = 29; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            data.push({
                date: date.toISOString().split('T')[0],
                count: Math.floor(Math.random() * 10) + 1
            });
        }
        return data;
    }
    
    generateMockCourseData() {
        return {
            'Matemática': { name: 'Matemática', assignment_count: 45 },
            'Física': { name: 'Física', assignment_count: 32 },
            'Química': { name: 'Química', assignment_count: 28 },
            'Biologia': { name: 'Biologia', assignment_count: 24 },
            'História': { name: 'História', assignment_count: 18 },
            'Geografia': { name: 'Geografia', assignment_count: 15 }
        };
    }
    
    generateMockTutorData() {
        return [
            { name: 'João Silva', student_count: 12, performance_score: 92 },
            { name: 'Maria Santos', student_count: 10, performance_score: 88 },
            { name: 'Pedro Oliveira', student_count: 8, performance_score: 85 },
            { name: 'Ana Costa', student_count: 9, performance_score: 90 },
            { name: 'Carlos Lima', student_count: 7, performance_score: 87 }
        ];
    }
    
    generateMockActivityData() {
        const labels = [];
        const creations = [];
        const updates = [];
        const deletions = [];
        
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            labels.push(date.toLocaleDateString('pt-BR', { weekday: 'short' }));
            creations.push(Math.floor(Math.random() * 20) + 5);
            updates.push(Math.floor(Math.random() * 15) + 2);
            deletions.push(Math.floor(Math.random() * 5) + 1);
        }
        
        return { labels, creations, updates, deletions };
    }
    
    // Dashboard Actions
    updateTimeRange(range) {
        console.log('Updating time range to:', range, 'days');
        // TODO: Implement AJAX call to refresh data
        this.showLoading();
        
        setTimeout(() => {
            this.hideLoading();
            this.refreshCharts();
        }, 1000);
    }
    
    updateChart(chartType, period) {
        console.log('Updating chart:', chartType, 'for period:', period);
        // TODO: Implement chart data update
    }
    
    refreshData() {
        console.log('Refreshing dashboard data...');
        // TODO: Implement AJAX data refresh
    }
    
    updateActivityFeed() {
        console.log('Updating activity feed...');
        // TODO: Implement activity feed update
    }
    
    refreshCharts() {
        Object.values(this.charts).forEach(chart => {
            chart.update('active');
        });
    }
    
    showLoading() {
        document.querySelectorAll('.chart-card, .metric-card').forEach(card => {
            card.classList.add('loading');
        });
    }
    
    hideLoading() {
        document.querySelectorAll('.chart-card, .metric-card').forEach(card => {
            card.classList.remove('loading');
        });
    }
}

// Global Functions for Dashboard Actions
function exportDashboard() {
    console.log('Exporting dashboard...');
    window.print();
}

function bulkAssign() {
    console.log('Opening bulk assignment...');
    // TODO: Implement bulk assignment modal
}

function generateReport() {
    console.log('Generating report...');
    window.location.href = window.baseURL + '/local/studenttutor/reports.php';
}

function importData() {
    console.log('Opening import dialog...');
    // TODO: Implement import modal
}

function manageSettings() {
    console.log('Opening settings...');
    window.location.href = window.baseURL + '/local/studenttutor/settings.php';
}

function viewLogs() {
    console.log('Opening logs...');
    // TODO: Implement logs viewer
}

function viewAllTutors() {
    console.log('Viewing all tutors...');
    window.location.href = window.baseURL + '/local/studenttutor/index.php?filter_role=tutor';
}

function viewAllActivity() {
    console.log('Viewing all activity...');
    // TODO: Implement activity viewer
}

function resolveAlert(alertId) {
    console.log('Resolving alert:', alertId);
    // TODO: Implement alert resolution
}

function switchChart(chartName, period) {
    console.log('Switching chart:', chartName, 'to period:', period);
    
    // Update button states
    const chartCard = document.querySelector(`#${chartName}Chart`).closest('.chart-card');
    chartCard.querySelectorAll('.chart-controls .btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.toLowerCase() === period) {
            btn.classList.add('active');
        }
    });
    
    // TODO: Update chart data based on period
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Small delay to ensure all resources are loaded
    setTimeout(() => {
        window.dashboard = new StudentTutorDashboard();
    }, 100);
});

// Export for global access
window.StudentTutorDashboard = StudentTutorDashboard;
