/**
 * Initialize Moodle-style autocomplete filters for the index page
 */

require(['jquery', 'core/form-autocomplete'], function($, Autocomplete) {
    
    $(document).ready(function() {
        console.log('Initializing Moodle autocomplete filters');
        
        initializeFilters();
        addMoodleAutocompleteStyles();
    });

    // Main function to initialize Moodle autocomplete filters
    function initializeFilters() {
        console.log('Starting filter initialization...');
        
        // Initialize each filter field with simple Moodle autocomplete
        var filters = [
            {name: 'filter_tutor', placeholder: 'Digite para buscar tutor...'},
            {name: 'filter_student', placeholder: 'Digite para buscar estudante...'},
            {name: 'filter_course', placeholder: 'Digite para buscar curso...'}
        ];
        
        filters.forEach(function(filter) {
            var $select = $('select[name="' + filter.name + '"]');
            if ($select.length) {
                setupSimpleAutocomplete($select, filter.placeholder);
            }
        });
    }
    
    // Setup simple Moodle autocomplete without custom search
    function setupSimpleAutocomplete($select, placeholder) {
        console.log('Setting up simple autocomplete for:', $select.attr('name'));
        
        try {
            // Use standard Moodle autocomplete without custom search function
            Autocomplete.enhance($select[0], false, '', null, {
                placeholder: placeholder,
                caseSensitive: false,
                showSuggestions: true,
                tags: false,
                ajax: false,
                valuekeep: true,
                noSelectionString: '',
                multiple: false
            });
            
            console.log('Standard Moodle autocomplete initialized for:', $select.attr('name'));
            
        } catch (error) {
            console.log('Failed to initialize Moodle autocomplete:', error);
        }
    }
    
    // Add CSS styles for Moodle autocomplete
    function addMoodleAutocompleteStyles() {
        var style = document.createElement('style');
        style.textContent = `
            /* Moodle autocomplete styling for filters */
            .form-autocomplete-original {
                min-height: 38px;
            }
            
            .form-autocomplete-selection {
                min-height: 38px !important;
                border: 1px solid #ced4da !important;
                border-radius: 4px !important;
                padding: 6px 8px !important;
                background-color: #fff !important;
            }
            
            .form-autocomplete-selection:focus-within {
                border-color: #007bff !important;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
            }
            
            .form-autocomplete-suggestions {
                max-height: 200px !important;
                overflow-y: auto !important;
                border: 1px solid #ced4da !important;
                border-top: none !important;
                background: white !important;
                z-index: 1000 !important;
                border-radius: 0 0 4px 4px !important;
            }
            
            .form-autocomplete-suggestion {
                padding: 8px 12px !important;
                cursor: pointer !important;
                border-bottom: 1px solid #eee !important;
                color: #495057 !important;
            }
            
            .form-autocomplete-suggestion:hover,
            .form-autocomplete-suggestion.highlighted {
                background-color: #f8f9fa !important;
                color: #495057 !important;
            }
            
            .form-autocomplete-suggestion:last-child {
                border-bottom: none !important;
            }
            
            /* Filter form styling */
            .filters-form {
                background: #f8f9fa !important;
                border: 1px solid #dee2e6 !important;
                border-radius: 8px !important;
            }
            
            .filters-form label {
                font-size: 14px !important;
                margin-bottom: 5px !important;
                font-weight: 600 !important;
                color: #495057 !important;
            }
            
            /* Make sure autocomplete fields have consistent width */
            .filters-form .form-autocomplete-selection {
                min-width: 200px !important;
            }
            
            /* Course filter should be wider */
            select[name="filter_course"] + .form-autocomplete-selection {
                min-width: 250px !important;
            }
            
            /* Button styling */
            .filters-form .btn {
                margin-top: 0 !important;
                height: 38px !important;
                line-height: 1.5 !important;
            }
            
            /* Responsive adjustments */
            @media (max-width: 768px) {
                .filters-form {
                    flex-direction: column !important;
                    align-items: stretch !important;
                }
                
                .filters-form > div {
                    margin-bottom: 15px !important;
                }
                
                .form-autocomplete-selection {
                    min-width: 100% !important;
                }
            }
        `;
        document.head.appendChild(style);
    }
});
