/**
 * Simple JavaScript for dynamic student loading (vanilla JS)
 * This is a fallback in case AMD module doesn't work
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Simple dynamic loader initialized');
    
    // Try different possible IDs for the course field (autocomplete may change the ID)
    var courseSelect = document.getElementById('id_courseid') || 
                      document.querySelector('select[name="courseid"]') ||
                      document.querySelector('input[name="courseid"]');
    
    console.log('Found course select:', courseSelect);
    
    if (courseSelect) {
        // For autocomplete fields, we need to listen for both 'change' and 'input' events
        var eventTypes = ['change', 'input'];
        
        eventTypes.forEach(function(eventType) {
            courseSelect.addEventListener(eventType, function() {
                var courseid = this.value;
                console.log('Course changed to:', courseid, 'via', eventType);
                
                // Add a small delay to ensure the value has been set
                setTimeout(function() {
                    if (courseid && courseid !== '0' && courseid !== '') {
                        loadStudentsSimple(courseid);
                    } else {
                        clearStudentsSimple();
                    }
                }, 100);
            });
        });
        
        // Also check for autocomplete specific events
        if (courseSelect.addEventListener) {
            courseSelect.addEventListener('autocomplete:select', function() {
                var courseid = this.value;
                console.log('Course selected via autocomplete:', courseid);
                
                setTimeout(function() {
                    if (courseid && courseid !== '0' && courseid !== '') {
                        loadStudentsSimple(courseid);
                    } else {
                        clearStudentsSimple();
                    }
                }, 100);
            });
        }
        
        // Load on page load if already selected
        setTimeout(function() {
            var initialValue = courseSelect.value;
            if (initialValue && initialValue !== '0' && initialValue !== '') {
                loadStudentsSimple(initialValue);
            }
        }, 500); // Wait for autocomplete to initialize
    } else {
        console.error('Course field not found with any of the expected selectors');
    }
});

function loadStudentsSimple(courseid) {
    console.log('Loading students for course:', courseid);
    
    var container = getStudentContainerSimple();
    container.innerHTML = '<div style="text-align: center; padding: 20px;"><i>Carregando estudantes...</i></div>';
    
    // Create AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open('POST', M.cfg.wwwroot + '/local/studenttutor/ajax_get_students.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            console.log('AJAX response status:', xhr.status);
            console.log('AJAX response text:', xhr.responseText);
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    console.log('Parsed response:', response);
                    
                    if (response.success) {
                        displayStudentsSimple(response.students);
                    } else {
                        container.innerHTML = '<div class="alert alert-danger">Erro: ' + response.error + '</div>';
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    container.innerHTML = '<div class="alert alert-danger">Erro ao processar resposta do servidor</div>';
                }
            } else {
                console.error('HTTP error:', xhr.status);
                container.innerHTML = '<div class="alert alert-danger">Erro de conexão (' + xhr.status + ')</div>';
            }
        }
    };
    
    var data = 'courseid=' + encodeURIComponent(courseid) + '&sesskey=' + encodeURIComponent(M.cfg.sesskey);
    xhr.send(data);
}

function displayStudentsSimple(students) {
    console.log('Displaying students:', students);
    
    var container = getStudentContainerSimple();
    
    if (!students || students.length === 0) {
        container.innerHTML = '<p class="text-muted">Nenhum estudante encontrado neste curso.</p>';
        return;
    }
    
    var html = '<div class="students-selection">';
    html += '<h5>Selecionar Estudantes (' + students.length + ' disponíveis):</h5>';
    
    // Add legend
    html += '<div style="margin-bottom: 15px; padding: 10px; background: #e9ecef; border-radius: 5px;">';
    html += '<strong>Legenda:</strong> ';
    html += '<span style="color: #28a745;">●</span> Sem tutores ';
    html += '<span style="color: #ffc107;">●</span> 1 tutor ';
    html += '<span style="color: #dc3545;">●</span> Múltiplos tutores';
    html += '</div>';
    
    html += '<div class="students-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 10px 0; background: #f9f9f9;">';
    
    // Select all checkbox
    html += '<div style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">';
    html += '<label><input type="checkbox" id="select_all_students_simple"> <strong>Selecionar todos</strong></label>';
    html += '</div>';
    
    // Individual students
    students.forEach(function(student) {
        html += '<div style="margin-bottom: 8px; padding: 8px; border-left: 3px solid ';
        
        // Color coding based on tutor status
        if (student.tutor_count === 0) {
            html += '#28a745'; // Green - no tutors (available)
        } else if (student.tutor_count === 1) {
            html += '#ffc107'; // Yellow - one tutor
        } else {
            html += '#dc3545'; // Red - multiple tutors
        }
        
        html += '; background-color: #f8f9fa;">';
        html += '<label style="cursor: pointer; display: block; margin: 0;">';
        html += '<input type="checkbox" name="student_checkbox" value="' + student.id + '" class="student-checkbox-simple" style="margin-right: 8px;"> ';
        html += '<strong>' + student.name + '</strong>';
        
        if (student.email) {
            html += ' <small style="color: #666;">(' + student.email + ')</small>';
        }
        
        // Show tutor status
        if (student.tutor_count === 0) {
            html += '<br><small style="color: #28a745; font-weight: bold;">✓ Sem tutores - Disponível</small>';
        } else if (student.tutor_count === 1) {
            html += '<br><small style="color: #856404; font-weight: bold;">⚠ 1 tutor: ' + student.tutor_names[0] + '</small>';
        } else {
            html += '<br><small style="color: #721c24; font-weight: bold;">⚠ ' + student.tutor_count + ' tutores: ' + student.tutor_names.slice(0, 2).join(', ');
            if (student.tutor_count > 2) {
                html += ' +' + (student.tutor_count - 2) + ' mais';
            }
            html += '</small>';
        }
        
        html += '</label>';
        html += '</div>';
    });
    
    html += '</div>';
    
    // Add statistics summary
    var noTutors = students.filter(function(s) { return s.tutor_count === 0; }).length;
    var oneTutor = students.filter(function(s) { return s.tutor_count === 1; }).length;
    var multipleTutors = students.filter(function(s) { return s.tutor_count > 1; }).length;
    
    html += '<div style="margin-top: 10px; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px;">';
    html += '<strong>Resumo:</strong> ';
    html += noTutors + ' sem tutores, ';
    html += oneTutor + ' com 1 tutor, ';
    html += multipleTutors + ' com múltiplos tutores';
    html += '</div>';
    
    html += '<div style="margin-top: 10px; padding: 10px; background: #e8f4fd; border: 1px solid #bee5eb;">';
    html += '<strong>Selecionados: <span id="selected-count-simple">0</span></strong>';
    html += '</div>';
    html += '</div>';
    
    container.innerHTML = html;
    
    // Bind events
    bindSelectAllEventSimple();
    bindStudentCheckboxEventsSimple();
}

function clearStudentsSimple() {
    var container = getStudentContainerSimple();
    container.innerHTML = '<p class="text-muted">Selecione um curso para ver os estudantes disponíveis.</p>';
    updateHiddenFieldSimple();
}

function getStudentContainerSimple() {
    var container = document.getElementById('students-selection-area');
    
    if (!container) {
        // Try to find the dynamic container
        var dynamicContainer = document.getElementById('students-dynamic-container');
        if (dynamicContainer) {
            container = document.createElement('div');
            container.id = 'students-selection-area';
            dynamicContainer.appendChild(container);
        } else {
            // Fallback: create after course field
            var courseField = document.getElementById('id_courseid');
            if (courseField) {
                var courseGroup = courseField.closest('.form-group') || courseField.parentNode;
                container = document.createElement('div');
                container.id = 'students-selection-area';
                container.style.marginTop = '15px';
                courseGroup.parentNode.insertBefore(container, courseGroup.nextSibling);
            }
        }
    }
    
    return container || document.body; // Fallback to prevent errors
}

function bindSelectAllEventSimple() {
    var selectAll = document.getElementById('select_all_students_simple');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.student-checkbox-simple');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAll.checked;
            });
            updateHiddenFieldSimple();
        });
    }
}

function bindStudentCheckboxEventsSimple() {
    var checkboxes = document.querySelectorAll('.student-checkbox-simple');
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            updateHiddenFieldSimple();
        });
    });
}

function updateHiddenFieldSimple() {
    var selectedIds = [];
    var checkboxes = document.querySelectorAll('.student-checkbox-simple:checked');
    
    checkboxes.forEach(function(checkbox) {
        selectedIds.push(checkbox.value);
    });
    
    // Update hidden field
    var hiddenField = document.querySelector('input[name="studentids_json"]');
    if (hiddenField) {
        hiddenField.value = JSON.stringify(selectedIds);
    }
    
    // Update counter
    var counter = document.getElementById('selected-count-simple');
    if (counter) {
        counter.textContent = selectedIds.length;
    }
    
    console.log('Selected student IDs:', selectedIds);
}
