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
 * Student Tutor Dashboard JavaScript Module
 *
 * @module     local_studenttutor/dashboard
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    'use strict';

    /**
     * Dashboard management object
     */
    var Dashboard = {
        
        /**
         * Chart instances storage
         */
        charts: {},
        
        /**
         * Refresh interval ID
         */
        refreshInterval: null,
        
        /**
         * Initialize dashboard
         */
        init: function() {
            this.initCharts();
            this.setupEventListeners();
            this.startAutoRefresh();
            this.animateMetrics();
        },
        
        /**
         * Initialize Chart.js charts
         */
        initCharts: function() {
            // Initialize assignment chart
            this.initAssignmentChart();
            
            // Initialize performance chart if exists
            if ($('#performanceChart').length) {
                this.initPerformanceChart();
            }
        },
        
        /**
         * Initialize assignment activity chart
         */
        initAssignmentChart: function() {
            var ctx = document.getElementById('assignmentChart');
            if (!ctx) return;
            
            // Sample data - in real implementation, this would come from AJAX
            var chartData = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Assignments Created',
                    data: [12, 19, 8, 15, 22, 18],
                    backgroundColor: 'rgba(102, 126, 234, 0.2)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Assignments Completed',
                    data: [8, 15, 6, 12, 18, 15],
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            };
            
            var config = {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Assignment Activity Over Time'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
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
            };
            
            this.charts.assignment = new Chart(ctx, config);
        },
        
        /**
         * Initialize performance chart (donut chart)
         */
        initPerformanceChart: function() {
            var ctx = document.getElementById('performanceChart');
            if (!ctx) return;
            
            var chartData = {
                labels: ['Completed', 'In Progress', 'Pending', 'Overdue'],
                datasets: [{
                    data: [45, 25, 20, 10],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            };
            
            var config = {
                type: 'doughnut',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            };
            
            this.charts.performance = new Chart(ctx, config);
        },
        
        /**
         * Setup event listeners
         */
        setupEventListeners: function() {
            var self = this;
            
            // Time filter change
            $('.time-filter select').on('change', function() {
                var period = $(this).val();
                self.updateDashboard(period);
            });
            
            // Refresh button
            $('.btn-refresh').on('click', function(e) {
                e.preventDefault();
                self.refreshDashboard();
            });
            
            // Export button
            $('.btn-export').on('click', function(e) {
                e.preventDefault();
                self.exportData();
            });
            
            // Metric card clicks for drill-down
            $('.metric-card').on('click', function() {
                var metric = $(this).data('metric');
                self.showMetricDetails(metric);
            });
        },
        
        /**
         * Start auto refresh
         */
        startAutoRefresh: function() {
            var self = this;
            // Refresh every 5 minutes
            this.refreshInterval = setInterval(function() {
                self.refreshDashboard();
            }, 300000);
        },
        
        /**
         * Animate metric counters
         */
        animateMetrics: function() {
            $('.metric-value').each(function() {
                var $this = $(this);
                var target = parseInt($this.text());
                if (isNaN(target)) return;
                
                $this.text('0');
                $this.prop('Counter', 0).animate({
                    Counter: target
                }, {
                    duration: 1500,
                    easing: 'swing',
                    step: function(now) {
                        $this.text(Math.ceil(now));
                    }
                });
            });
        },
        
        /**
         * Update dashboard with new time period
         */
        updateDashboard: function(period) {
            var self = this;
            
            // Show loading state
            this.showLoading();
            
            // Make AJAX call to get new data
            Ajax.call([{
                methodname: 'local_studenttutor_get_dashboard_data',
                args: { period: period },
                done: function(response) {
                    self.updateMetrics(response.metrics);
                    self.updateCharts(response.charts);
                    self.updateTables(response.tables);
                    self.hideLoading();
                },
                fail: function(error) {
                    Notification.exception(error);
                    self.hideLoading();
                }
            }]);
        },
        
        /**
         * Refresh entire dashboard
         */
        refreshDashboard: function() {
            var period = $('.time-filter select').val() || '30';
            this.updateDashboard(period);
        },
        
        /**
         * Update metric cards
         */
        updateMetrics: function(metrics) {
            $.each(metrics, function(key, value) {
                var $metric = $('.metric-card[data-metric="' + key + '"]');
                if ($metric.length) {
                    $metric.find('.metric-value').text(value.value);
                    
                    var $change = $metric.find('.metric-change');
                    if (value.change !== undefined) {
                        $change.removeClass('change-positive change-negative change-neutral');
                        if (value.change > 0) {
                            $change.addClass('change-positive').text('↑ +' + value.change + '%');
                        } else if (value.change < 0) {
                            $change.addClass('change-negative').text('↓ ' + value.change + '%');
                        } else {
                            $change.addClass('change-neutral').text('→ ' + value.change + '%');
                        }
                    }
                }
            });
        },
        
        /**
         * Update chart data
         */
        updateCharts: function(chartData) {
            var self = this;
            
            // Update assignment chart
            if (chartData.assignment && this.charts.assignment) {
                this.charts.assignment.data = chartData.assignment;
                this.charts.assignment.update('animate');
            }
            
            // Update performance chart
            if (chartData.performance && this.charts.performance) {
                this.charts.performance.data = chartData.performance;
                this.charts.performance.update('animate');
            }
        },
        
        /**
         * Update data tables
         */
        updateTables: function(tableData) {
            // Update recent assignments table
            if (tableData.recent_assignments) {
                this.updateRecentAssignmentsTable(tableData.recent_assignments);
            }
            
            // Update top tutors list
            if (tableData.top_tutors) {
                this.updateTopTutorsList(tableData.top_tutors);
            }
        },
        
        /**
         * Update recent assignments table
         */
        updateRecentAssignmentsTable: function(assignments) {
            var $tbody = $('#recentAssignmentsTable tbody');
            $tbody.empty();
            
            $.each(assignments, function(index, assignment) {
                var statusClass = 'status-' + assignment.status.toLowerCase().replace(' ', '-');
                var row = '<tr>' +
                    '<td>' + assignment.title + '</td>' +
                    '<td>' + assignment.tutor + '</td>' +
                    '<td>' + assignment.student + '</td>' +
                    '<td><span class="status-badge ' + statusClass + '">' + assignment.status + '</span></td>' +
                    '<td>' + assignment.created + '</td>' +
                    '</tr>';
                $tbody.append(row);
            });
        },
        
        /**
         * Update top tutors list
         */
        updateTopTutorsList: function(tutors) {
            var $list = $('.top-tutors-list');
            $list.empty();
            
            $.each(tutors, function(index, tutor) {
                var rank = index + 1;
                var rankClass = rank <= 3 ? 'rank-' + rank : '';
                
                var item = '<div class="tutor-item">' +
                    '<div class="tutor-rank ' + rankClass + '">' + rank + '</div>' +
                    '<div class="tutor-info">' +
                    '<div class="tutor-name">' + tutor.name + '</div>' +
                    '<div class="tutor-assignments">' + tutor.assignments + ' assignments</div>' +
                    '</div>' +
                    '</div>';
                $list.append(item);
            });
        },
        
        /**
         * Show metric details in modal/drill-down
         */
        showMetricDetails: function(metric) {
            // This would open a detailed view for the specific metric
            console.log('Show details for metric:', metric);
        },
        
        /**
         * Export dashboard data
         */
        exportData: function() {
            // Trigger export functionality
            window.location.href = M.cfg.wwwroot + '/local/studenttutor/export.php?type=dashboard';
        },
        
        /**
         * Show loading state
         */
        showLoading: function() {
            $('.metric-value').html('<span class="loading-spinner"></span>');
            $('.chart-container').addClass('loading');
        },
        
        /**
         * Hide loading state
         */
        hideLoading: function() {
            $('.chart-container').removeClass('loading');
        },
        
        /**
         * Destroy dashboard and cleanup
         */
        destroy: function() {
            // Clear refresh interval
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
            
            // Destroy charts
            $.each(this.charts, function(key, chart) {
                if (chart && typeof chart.destroy === 'function') {
                    chart.destroy();
                }
            });
            
            // Remove event listeners
            $('.time-filter select').off('change');
            $('.btn-refresh').off('click');
            $('.btn-export').off('click');
            $('.metric-card').off('click');
        }
    };
    
    return {
        init: function() {
            Dashboard.init();
        },
        
        destroy: function() {
            Dashboard.destroy();
        },
        
        refresh: function() {
            Dashboard.refreshDashboard();
        },
        
        updatePeriod: function(period) {
            Dashboard.updateDashboard(period);
        }
    };
});
