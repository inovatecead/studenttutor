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
 * Dynamic student loading for assignment form
 *
 * @module     local_studenttutor/assign_dynamic
 * @package    local_studenttutor
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    
    var module = {
        
        /**
         * Initialize the dynamic student loading
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind events to course selection
         */
        bindEvents: function() {
            var self = this;
            
            console.log('Binding events for assign_dynamic module');
            
            // Aguardar o DOM estar pronto
            $(document).ready(function() {
                console.log('DOM ready, looking for course field');
                
                // Try different possible selectors for the course field (autocomplete may change the structure)
                var courseSelect = $('#id_courseid');
                if (courseSelect.length === 0) {
                    courseSelect = $('select[name="courseid"]');
                }
                if (courseSelect.length === 0) {
                    courseSelect = $('input[name="courseid"]');
                }
                
                console.log('Found course select:', courseSelect.length, courseSelect);
                
                if (courseSelect.length > 0) {
                    // Listen for multiple events (autocomplete fields may use different events)
                    courseSelect.on('change input autocomplete:select', function() {
                        var courseid = $(this).val();
                        console.log('Course changed to:', courseid);
                        
                        // Add delay to ensure value is set
                        setTimeout(function() {
                            if (courseid && courseid !== '0' && courseid !== '') {
                                self.loadStudents(courseid);
                            } else {
                                self.clearStudents();
                            }
                        }, 100);
                    });
                    
                    // Load students on page load if course is already selected
                    setTimeout(function() {
                        var initialCourse = courseSelect.val();
                        console.log('Initial course value:', initialCourse);
                        if (initialCourse && initialCourse !== '0' && initialCourse !== '') {
                            self.loadStudents(initialCourse);
                        }
                    }, 500); // Wait for autocomplete to initialize
                } else {
                    console.error('Course field not found with any of the expected selectors');
                }
            });
        },
        
        /**
         * Load students for selected course via AJAX
         */
        loadStudents: function(courseid) {
            console.log('loadStudents called with courseid:', courseid);
            
            // Show loading indicator
            this.showLoading();
            
            // Use direct HTTP request (skip web service for now)
            this.loadStudentsDirectly(courseid);
        },
        
        /**
         * Fallback method using direct HTTP request
         */
        loadStudentsDirectly: function(courseid) {
            var self = this;
            
            console.log('Loading students directly for course:', courseid);
            console.log('AJAX URL:', M.cfg.wwwroot + '/local/studenttutor/ajax_get_students.php');
            
            $.ajax({
                url: M.cfg.wwwroot + '/local/studenttutor/ajax_get_students.php',
                type: 'POST',
                data: {
                    courseid: courseid,
                    sesskey: M.cfg.sesskey
                },
                dataType: 'json',
                success: function(response) {
                    console.log('AJAX success response:', response);
                    if (response.success) {
                        console.log('Found students:', response.students.length);
                        self.displayStudents(response.students, response.summary);
                    } else {
                        console.error('Server error:', response.error);
                        Notification.addNotification({
                            message: 'Erro ao carregar estudantes: ' + response.error,
                            type: 'error'
                        });
                        self.clearStudents();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', {xhr: xhr, status: status, error: error});
                    console.error('Response text:', xhr.responseText);
                    Notification.addNotification({
                        message: 'Erro de conexão ao carregar estudantes',
                        type: 'error'
                    });
                    self.clearStudents();
                }
            });
        },
        
        /**
         * Display students with checkboxes
         */
        displayStudents: function(students, summary) {
            var container = this.getStudentContainer();
            var html = '';
            
            if (students && students.length > 0) {
                // Group students by category
                var categories = {
                    'without_tutors': [],
                    'with_one_tutor': [],
                    'with_multiple_tutors': []
                };
                
                students.forEach(function(student) {
                    if (categories[student.category]) {
                        categories[student.category].push(student);
                    }
                });
                
                // Create enhanced summary display
                var summaryText = (summary ? summary.without_tutors + ' sem tutores, ' +
                                 summary.with_one_tutor + ' com 1 tutor, ' +
                                 summary.with_multiple_tutors + ' com múltiplos tutores' :
                                 students.length + ' estudantes carregados');
                
                if (summary && (summary.with_groups || summary.without_groups)) {
                    summaryText += ' | ' + (summary.with_groups || 0) + ' em grupos, ' + (summary.without_groups || 0) + ' sem grupo';
                }
                
                html += '<div class="students-summary alert alert-info" style="text-align: center; font-weight: bold;">' + summaryText + '</div>';
                
                // Render each category
                var categoryLabels = {
                    'without_tutors': '✅ Sem Tutores',
                    'with_one_tutor': '📚 Com 1 Tutor',
                    'with_multiple_tutors': '👥 Com Múltiplos Tutores'
                };
                
                var categoryClasses = {
                    'without_tutors': 'category-no-tutor',
                    'with_one_tutor': 'category-one-tutor',
                    'with_multiple_tutors': 'category-multiple-tutors'
                };
                
                var categoryColors = {
                    'without_tutors': '#28a745',
                    'with_one_tutor': '#ffc107',
                    'with_multiple_tutors': '#dc3545'
                };
                
                html += '<div class="students-by-category">';
                
                Object.keys(categories).forEach(function(category) {
                    var categoryStudents = categories[category];
                    
                    if (categoryStudents.length > 0) {
                        html += '<div class="category-section ' + categoryClasses[category] + '" style="margin-bottom: 20px; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden;">';
                        html += '<h5 class="category-header" style="margin: 0; padding: 12px 15px; font-size: 1.1em; font-weight: bold; color: white; background: ' + categoryColors[category] + ';">';
                        html += categoryLabels[category] + ' (' + categoryStudents.length + ')';
                        html += '</h5>';
                        html += '<div class="students-list" style="padding: 10px; background: #f8f9fa;">';
                        
                        categoryStudents.forEach(function(student) {
                            var tutorInfo = '';
                            if (student.tutor_count > 0) {
                                tutorInfo = ' | Tutor(es): ' + student.tutor_names.join(', ');
                            }
                            
                            var groupInfo = '';
                            if (student.has_groups) {
                                groupInfo = ' | 🏫 ' + student.group_display;
                            } else {
                                groupInfo = ' | 🚫 Sem grupo';
                            }
                            
                            var studentClass = student.has_groups ? 'has-group' : 'no-group';
                            var borderColor = student.has_groups ? '#4caf50' : '#ff9800';
                            
                            html += '<div class="student-item ' + studentClass + '" style="padding: 10px 12px; margin: 4px 0; background: white; border: 1px solid #e9ecef; border-left: 4px solid ' + borderColor + '; border-radius: 6px; transition: all 0.2s ease;">';
                            html += '<label style="margin-bottom: 0; cursor: pointer; display: block; line-height: 1.3;">';
                            html += '<input type="checkbox" name="student_checkbox" value="' + student.id + '" class="form-check-input student-checkbox" style="margin-right: 12px; margin-top: 2px; transform: scale(1.2);"> ';
                            html += '<div class="student-name" style="font-weight: 600; color: #333; margin-bottom: 4px;">' + student.name + '</div>';
                            html += '<div class="student-details" style="font-size: 0.85em; color: #666;">';
                            html += '<small class="text-muted">' + groupInfo + tutorInfo + '</small>';
                            html += '</div>';
                            html += '</label>';
                            html += '</div>';
                        });
                        
                        html += '</div></div>';
                    }
                });
                
                html += '</div>';
                
                html += '<div style="margin-top: 10px; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">';
                html += '<strong>Selecionados: <span id="selected-count">0</span></strong>';
                html += '</div>';
            } else {
                html += '<div class="alert alert-info">';
                html += '<p>Nenhum estudante encontrado neste curso.</p>';
                html += '</div>';
            }
            
            container.html(html);
            this.bindSelectAllEvent();
            this.updateHiddenField();
            this.hideLoading();
        },
        
        /**
         * Clear students display
         */
        clearStudents: function() {
            var container = this.getStudentContainer();
            container.html('<p class="text-muted">Selecione um curso para ver os estudantes disponíveis.</p>');
            this.hideLoading();
        },
        
        /**
         * Get or create student container
         */
        getStudentContainer: function() {
            console.log('Looking for students container');
            
            var container = $('#students-selection-area');
            console.log('Found #students-selection-area:', container.length);
            
            if (container.length === 0) {
                console.log('Container not found, looking for #students-dynamic-container');
                var dynamicContainer = $('#students-dynamic-container');
                console.log('Found #students-dynamic-container:', dynamicContainer.length);
                
                if (dynamicContainer.length > 0) {
                    // Create container in the predefined area
                    container = $('<div id="students-selection-area"></div>');
                    dynamicContainer.append(container);
                    console.log('Created new container');
                } else {
                    // Fallback: create after course selection
                    var courseField = $('#id_courseid').closest('.form-group');
                    if (courseField.length > 0) {
                        container = $('<div id="students-selection-area"></div>');
                        courseField.after(container);
                        console.log('Created container after course field');
                    } else {
                        console.error('Cannot find course field to append container');
                        return $('<div></div>'); // Return empty div to prevent errors
                    }
                }
            }
            return container;
        },
        
        /**
         * Update hidden field with selected student IDs
         */
        updateHiddenField: function() {
            var selectedIds = [];
            $('.student-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            // Update hidden field with JSON
            $('input[name="studentids_json"]').val(JSON.stringify(selectedIds));
            
            // Update counter
            $('#selected-count').text(selectedIds.length);
        },
        
        /**
         * Show loading indicator
         */
        showLoading: function() {
            var container = this.getStudentContainer();
            container.html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Carregando estudantes...</div>');
        },
        
        /**
         * Hide loading indicator
         */
        hideLoading: function() {
            // Loading is hidden when content is replaced
        },
        
        /**
         * Bind select all functionality
         */
        bindSelectAllEvent: function() {
            var self = this;
            
            $('#select_all_students').on('change', function() {
                var isChecked = $(this).is(':checked');
                $('.student-checkbox').prop('checked', isChecked);
                self.updateHiddenField();
            });
            
            // Update "select all" when individual checkboxes change
            $(document).on('change', '.student-checkbox', function() {
                var totalCheckboxes = $('.student-checkbox').length;
                var checkedCheckboxes = $('.student-checkbox:checked').length;
                $('#select_all_students').prop('checked', totalCheckboxes === checkedCheckboxes);
                self.updateHiddenField();
            });
        }
    };
    
    return module;
});
