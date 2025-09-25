/**
 * Frontend JavaScript for Simple Conditional Fields for CF7
 * Author: Pascal GAULT - inRage
 */

(function($) {
    'use strict';

    const SCF7C_Frontend = {

        // Initialize
        init: function() {
            this.bindEvents();
            this.processAllForms();
        },

        // Bind events
        bindEvents: function() {
            // Handle form changes
            $(document).on('change input', '.wpcf7-form input, .wpcf7-form select, .wpcf7-form textarea',
                this.handleFieldChange.bind(this));

            // Handle form initialization
            $(document).on('wpcf7loaded', this.handleFormLoaded.bind(this));
        },

        // Process all CF7 forms on page
        processAllForms: function() {
            $('.wpcf7-form').each((index, form) => {
                this.processForm($(form));
            });
        },

        // Process individual form
        processForm: function($form) {
            try {
                const formId = this.getFormId($form);

                if (!formId) {
                    return;
                }

                // Get conditions from localized script data
                const formDataVar = 'scf7c_form_' + formId;
                if (!window[formDataVar] || !window[formDataVar].conditions) {
                    return;
                }

                const conditions = window[formDataVar].conditions;

                // Validate conditions array
                if (!Array.isArray(conditions)) {
                    return;
                }

                // Store conditions on form element
                $form.data('scf7c-conditions', conditions);

                // Apply initial conditions
                this.applyConditions($form);
            } catch (error) {
                console.error('SCF7C: Error processing form:', error);
            }
        },

        // Get form ID
        getFormId: function($form) {
            const $container = $form.closest('.wpcf7');
            if ($container.length) {
                const id = $container.attr('id');
                return id ? id.replace('wpcf7-f', '').replace(/-.+$/, '') : null;
            }
            return null;
        },

        // Handle field change
        handleFieldChange: function(e) {
            const $form = $(e.target).closest('.wpcf7-form');
            this.applyConditions($form);
        },

        // Handle form loaded event
        handleFormLoaded: function(e) {
            const $form = $(e.target).find('.wpcf7-form');
            this.processForm($form);
        },

        // Apply all conditions for a form
        applyConditions: function($form) {
            const conditions = $form.data('scf7c-conditions');

            if (!conditions || !Array.isArray(conditions)) {
                return;
            }

            conditions.forEach(condition => {
                this.applyCondition($form, condition);
            });
        },

        // Apply single condition
        applyCondition: function($form, condition) {
            try {
                // Validate condition object
                if (!condition || typeof condition !== 'object') {
                    return;
                }

                const { show_field, if_field, operator, if_value } = condition;

                // Validate required properties
                if (!show_field || !if_field || !operator) {
                    return;
                }

                // Get field elements
                const $showField = this.getFieldElement($form, show_field);
                const $ifField = this.getFieldElement($form, if_field);

                if (!$showField.length || !$ifField.length) {
                    return;
                }

                // Get current value of condition field
                const currentValue = this.getFieldValue($ifField);

                // Check if condition is met
                const conditionMet = this.evaluateCondition(currentValue, operator, if_value);

                // Show or hide target field
                if (conditionMet) {
                    this.showField($showField);
                } else {
                    this.hideField($showField);
                }
            } catch (error) {
                console.error('SCF7C: Error applying condition:', error, condition);
            }
        },

        // Get field element by name (for groups, look for data-scf7c-group)
        getFieldElement: function($form, fieldName) {
            // First try to find a group with this name
            let $field = $form.find(`[data-scf7c-group="${fieldName}"]`);

            // If no group found, try normal field selectors
            if (!$field.length) {
                $field = $form.find(`[name="${fieldName}"]`);

                if (!$field.length) {
                    $field = $form.find(`[name="${fieldName}[]"]`);
                }

                // For regular fields, get the wrapper
                if ($field.length) {
                    const $wrapper = $field.closest('.wpcf7-form-control-wrap');
                    return $wrapper.length ? $wrapper : $field.parent();
                }
            }

            return $field;
        },

        // Get field value
        getFieldValue: function($field) {
            const $input = $field.find('input, select, textarea').first();

            if (!$input.length) {
                return '';
            }

            const type = $input.attr('type');

            if (type === 'checkbox' || type === 'radio') {
                return $field.find('input:checked').val() || '';
            }

            return $input.val() || '';
        },

        // Evaluate condition
        evaluateCondition: function(fieldValue, operator, conditionValue) {
            switch (operator) {
                case 'equals':
                    return fieldValue === conditionValue;

                case 'not_equals':
                    return fieldValue !== conditionValue;

                case 'contains':
                    return fieldValue.toString().indexOf(conditionValue) !== -1;

                case 'not_contains':
                    return fieldValue.toString().indexOf(conditionValue) === -1;

                case 'is_empty':
                    return !fieldValue || fieldValue.toString().trim() === '';

                case 'not_empty':
                    return fieldValue && fieldValue.toString().trim() !== '';

                case 'greater_than':
                    return !isNaN(fieldValue) && !isNaN(conditionValue) &&
                           parseFloat(fieldValue) > parseFloat(conditionValue);

                case 'less_than':
                    return !isNaN(fieldValue) && !isNaN(conditionValue) &&
                           parseFloat(fieldValue) < parseFloat(conditionValue);

                default:
                    return false;
            }
        },

        // Show field
        showField: function($field) {
            $field.show().removeClass('scf7c-hidden');

            // Enable inputs
            $field.find('input, select, textarea').prop('disabled', false);
        },

        // Hide field
        hideField: function($field) {
            $field.hide().addClass('scf7c-hidden');

            // Clear and disable inputs to prevent submission
            $field.find('input, select, textarea').each(function() {
                const $input = $(this);

                // Clear value
                if ($input.is('input[type="checkbox"], input[type="radio"]')) {
                    $input.prop('checked', false);
                } else {
                    $input.val('');
                }

                // Disable input
                $input.prop('disabled', true);
            });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        SCF7C_Frontend.init();
    });

})(jQuery);