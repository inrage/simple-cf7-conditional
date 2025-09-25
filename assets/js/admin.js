/**
 * Admin JavaScript for Simple Conditional Fields for CF7
 * Author: Pascal GAULT - inRage
 */

jQuery(document).ready(function($) {
    'use strict';

    const SCF7C_Admin = {

        // Get operators with translations
        getOperators: function() {
            if (typeof scf7c_admin !== 'undefined' && scf7c_admin.i18n) {
                return [
                    { value: 'equals', label: scf7c_admin.i18n.equals },
                    { value: 'not_equals', label: scf7c_admin.i18n.notEquals },
                    { value: 'contains', label: scf7c_admin.i18n.contains },
                    { value: 'not_contains', label: scf7c_admin.i18n.notContains },
                    { value: 'is_empty', label: scf7c_admin.i18n.isEmpty },
                    { value: 'not_empty', label: scf7c_admin.i18n.notEmpty },
                    { value: 'greater_than', label: scf7c_admin.i18n.greaterThan },
                    { value: 'less_than', label: scf7c_admin.i18n.lessThan }
                ];
            }
            // Fallback to English
            return [
                { value: 'equals', label: 'equals' },
                { value: 'not_equals', label: 'not equals' },
                { value: 'contains', label: 'contains' },
                { value: 'not_contains', label: 'does not contain' },
                { value: 'is_empty', label: 'is empty' },
                { value: 'not_empty', label: 'is not empty' },
                { value: 'greater_than', label: 'greater than' },
                { value: 'less_than', label: 'less than' }
            ];
        },

        // Initialize
        init: function() {
            this.currentFormCode = '';
            this.bindEvents();
            this.scanFormFields();
            this.loadConditions();
            this.watchFormChanges();

            // Handle CF7 active-tab functionality
            this.handleActiveTab();
        },

        // Bind events
        bindEvents: function() {
            // Add rule button with validation
            $('#scf7c-add-rule').on('click', this.validateAndAddRule.bind(this));

            // Dynamic events for rules
            $(document).on('click', '.scf7c-rule-delete', this.deleteRule.bind(this));
            $(document).on('change', '.scf7c-rule-control', this.updateConditionsData.bind(this));

            // Sidebar element clicks
            $(document).on('click', '.scf7c-element-card[data-type="group"]', this.addRuleFromGroup.bind(this));

            // Handle tab changes to populate sidebar when our tab is shown
            $(document).on('click', '#scf7c-conditional-panel-tab', () => {
                setTimeout(() => {
                    this.scanFormFields();
                    this.populateSidebar();
                    this.updateAddRuleButton();
                }, 100);
            });

            // Initial population
            this.populateSidebar();
            this.updateAddRuleButton();
        },

        // Populate sidebar with available elements
        populateSidebar: function() {
            this.populateGroups();
            this.populateFields();
        },

        // Populate groups in sidebar
        populateGroups: function() {
            const $container = $('#scf7c-available-groups');
            const groups = this.currentGroups || [];

            if (groups.length === 0) {
                $container.html('<p style="color: #6c757d; font-size: 12px;">No groups found in form</p>');
                return;
            }

            let html = '';
            groups.forEach(group => {
                html += `
                    <div class="scf7c-element-card" data-type="group" data-name="${group.name}" title="Click to create a rule for this group">
                        <div class="scf7c-element-icon group">G</div>
                        <div class="scf7c-element-info">
                            <div class="scf7c-element-name">${group.name}</div>
                            <div class="scf7c-element-type">group</div>
                        </div>
                    </div>
                `;
            });

            $container.html(html);
        },

        // Populate fields in sidebar
        populateFields: function() {
            const $container = $('#scf7c-available-fields');
            const fields = this.currentFields || [];

            if (fields.length === 0) {
                $container.html('<p style="color: #6c757d; font-size: 12px;">No fields found in form</p>');
                return;
            }

            let html = '';
            fields.forEach(field => {
                html += `
                    <div class="scf7c-element-card">
                        <div class="scf7c-element-icon field">F</div>
                        <div class="scf7c-element-info">
                            <div class="scf7c-element-name">${field.name}</div>
                            <div class="scf7c-element-type">${field.type}</div>
                        </div>
                    </div>
                `;
            });

            $container.html(html);
        },

        // Load existing conditions
        loadConditions: function() {
            const conditions = JSON.parse($('#scf7c-conditions-data').val() || '[]');

            if (conditions.length === 0) {
                this.showEmptyCanvas();
                return;
            }

            conditions.forEach(condition => {
                this.addRule(condition);
            });
        },

        // Show empty canvas
        showEmptyCanvas: function() {
            const noRulesText = (typeof scf7c_admin !== 'undefined' && scf7c_admin.i18n)
                ? scf7c_admin.i18n.noRules
                : 'No rules yet. Click "Add Rule" to get started.';

            $('#scf7c-rules-container').html(`
                <div class="scf7c-empty-canvas">
                    <div class="scf7c-empty-icon">📋</div>
                    <p>${noRulesText}</p>
                </div>
            `);
        },

        // Add new rule
        addRule: function(ruleData = null) {
            // Remove empty canvas
            $('.scf7c-empty-canvas').remove();

            const ruleHtml = this.getRuleTemplate(ruleData);
            $('#scf7c-rules-container').append(ruleHtml);

            this.updateConditionsData();
        },

        // Delete rule
        deleteRule: function(e) {
            e.preventDefault();

            if (confirm('Are you sure you want to delete this rule?')) {
                $(e.target).closest('.scf7c-rule-card').remove();

                // Show empty canvas if no rules left
                if ($('.scf7c-rule-card').length === 0) {
                    this.showEmptyCanvas();
                }

                this.updateConditionsData();
            }
        },

        // Get rule template HTML
        getRuleTemplate: function(data = null) {
            const showField = data && data.show_field ? data.show_field : '';
            const ifField = data && data.if_field ? data.if_field : '';
            const operator = data && data.operator ? data.operator : 'equals';
            const ifValue = data && data.if_value !== undefined ? data.if_value : '';

            const i18n = (typeof scf7c_admin !== 'undefined' && scf7c_admin.i18n) ? scf7c_admin.i18n : {};

            return `
                <div class="scf7c-rule-card">
                    <div class="scf7c-rule-header">
                        <div class="scf7c-rule-title">${i18n.conditionalRule || 'Conditional Rule'}</div>
                        <button type="button" class="scf7c-rule-delete" title="${i18n.deleteRule || 'Delete rule'}">×</button>
                    </div>
                    <div class="scf7c-rule-body">
                        <div class="scf7c-rule-section">
                            <label class="scf7c-rule-label">${i18n.showGroup || 'Show Group'}</label>
                            <select class="scf7c-rule-control" data-field="show_field">
                                ${this.getGroupOptions(showField)}
                            </select>
                        </div>
                        <div class="scf7c-rule-section">
                            <label class="scf7c-rule-label">${i18n.whenField || 'When Field'}</label>
                            <select class="scf7c-rule-control" data-field="if_field">
                                ${this.getFieldOptions(ifField)}
                            </select>
                        </div>
                        <div class="scf7c-rule-section">
                            <label class="scf7c-rule-label">${i18n.condition || 'Condition'}</label>
                            <select class="scf7c-rule-control" data-field="operator">
                                ${this.getOperatorOptions(operator)}
                            </select>
                        </div>
                        <div class="scf7c-rule-section">
                            <label class="scf7c-rule-label">${i18n.value || 'Value'}</label>
                            <input type="text" class="scf7c-rule-control" data-field="if_value" value="${ifValue}" placeholder="${i18n.enterValue || 'Enter value...'}">
                        </div>
                    </div>
                </div>
            `;
        },


        // Get operator options HTML
        getOperatorOptions: function(selectedValue = 'equals') {
            let options = '';
            const operators = this.getOperators();

            operators.forEach(op => {
                const selected = op.value === selectedValue ? 'selected' : '';
                options += `<option value="${op.value}" ${selected}>${op.label}</option>`;
            });

            return options;
        },

        // Watch for form content changes (inspired by CF7 Conditional Fields)
        watchFormChanges: function() {
            const $formEditor = $('#wpcf7-form');

            if ($formEditor.length) {
                // Scan initial fields
                this.scanFormFields();

                // Watch for changes (like CF7CF does)
                $formEditor.on('change focusout', () => {
                    if (!this.currentFormCode || this.currentFormCode !== $formEditor.val()) {
                        this.currentFormCode = $formEditor.val();
                        this.scanFormFields();
                        this.refreshFieldOptions();
                    }
                });
            }
        },

        // Scan form fields and groups (inspired by CF7CF scanFormTags function)
        scanFormFields: function() {
            const $formEditor = $('#wpcf7-form');

            if (!$formEditor.length) {
                return;
            }

            const formCode = $formEditor.val();

            // Scan fields (exclude group, step, repeater, submit)
            const fieldMatches = [...formCode.matchAll(/\[(?!group|step|repeater|submit)[^\]]*?\*?\s+([^\]\s]+)/g)];
            const fields = [];

            fieldMatches.forEach(match => {
                const fieldName = match[1];

                if (!fieldName || fields.find(f => f.name === fieldName)) {
                    return;
                }

                const fullMatch = match[0];
                const typeMatch = fullMatch.match(/\[([^\]\s\*]+)/);
                const fieldType = typeMatch ? typeMatch[1] : 'unknown';

                fields.push({
                    name: fieldName,
                    type: fieldType
                });
            });

            // Scan groups
            const groupMatches = [...formCode.matchAll(/\[group\s+([^\]\s]+)/g)];
            const groups = [];

            groupMatches.forEach(match => {
                const groupName = match[1];

                if (!groupName || groups.find(g => g.name === groupName)) {
                    return;
                }

                groups.push({
                    name: groupName,
                    type: 'group'
                });
            });

            // Store current fields and groups
            this.currentFields = fields;
            this.currentGroups = groups;
        },

        // Refresh field options in existing rules and sidebar
        refreshFieldOptions: function() {
            // Update sidebar
            this.populateSidebar();

            // Update add rule button state
            this.updateAddRuleButton();

            // Update existing rules
            $('.scf7c-rule-card').each((index, rule) => {
                const $rule = $(rule);
                const $showField = $rule.find('[data-field="show_field"]');
                const $ifField = $rule.find('[data-field="if_field"]');

                // Store current values
                const currentShowValue = $showField.val();
                const currentIfValue = $ifField.val();

                // Update options
                $showField.html(this.getGroupOptions(currentShowValue));
                $ifField.html(this.getFieldOptions(currentIfValue));
            });
        },

        // Get group options HTML (for "show" select)
        getGroupOptions: function(selectedValue = '') {
            const i18n = (typeof scf7c_admin !== 'undefined' && scf7c_admin.i18n) ? scf7c_admin.i18n : {};
            let options = `<option value="">${i18n.selectGroup || '-- Select group --'}</option>`;

            // Use scanned groups if available, fallback to server-side groups
            const groups = this.currentGroups || (typeof scf7c_admin !== 'undefined' && scf7c_admin.groups ? scf7c_admin.groups : []);

            groups.forEach(group => {
                const selected = group.name === selectedValue ? 'selected' : '';
                options += `<option value="${group.name}" ${selected}>${group.name}</option>`;
            });

            return options;
        },

        // Get field options HTML (for "if" select - condition fields)
        getFieldOptions: function(selectedValue = '') {
            const i18n = (typeof scf7c_admin !== 'undefined' && scf7c_admin.i18n) ? scf7c_admin.i18n : {};
            let options = `<option value="">${i18n.selectField || '-- Select field --'}</option>`;

            // Use scanned fields if available, fallback to server-side fields
            const fields = this.currentFields || (typeof scf7c_admin !== 'undefined' && scf7c_admin.fields ? scf7c_admin.fields : []);

            fields.forEach(field => {
                const selected = field.name === selectedValue ? 'selected' : '';
                options += `<option value="${field.name}" ${selected}>${field.name} (${field.type})</option>`;
            });

            return options;
        },

        // Validate and add rule
        validateAndAddRule: function(e) {
            e.preventDefault();

            const groups = this.currentGroups || [];
            const fields = this.currentFields || [];

            if (groups.length === 0) {
                alert('You need to add at least one [group] to your form before creating conditional rules.\n\nExample: [group my-group]...[/group]');
                return;
            }

            if (fields.length === 0) {
                alert('You need to add at least one field to your form before creating conditional rules.');
                return;
            }

            this.addRule();
        },

        // Update add rule button state
        updateAddRuleButton: function() {
            const $button = $('#scf7c-add-rule');
            const groups = this.currentGroups || [];
            const fields = this.currentFields || [];

            if (groups.length === 0 || fields.length === 0) {
                $button.prop('disabled', true);
                $button.css('opacity', '0.5');

                if (groups.length === 0) {
                    $button.attr('title', 'Add at least one [group] to your form first');
                } else {
                    $button.attr('title', 'Add at least one field to your form first');
                }
            } else {
                $button.prop('disabled', false);
                $button.css('opacity', '1');
                $button.attr('title', 'Add new conditional rule');
            }
        },

        // Handle CF7 active-tab functionality
        handleActiveTab: function() {
            // Check if our tab is the active tab on page load
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('active-tab');

            // Check if our tab is active or visible
            const $ourTab = $('#scf7c-conditional-panel');
            const $ourTabButton = $('#scf7c-conditional-panel-tab');

            if (activeTab === 'scf7c-conditional-panel' ||
                $ourTab.is(':visible') ||
                $ourTabButton.hasClass('active')) {

                // Our tab is active, populate immediately
                setTimeout(() => {
                    this.scanFormFields();
                    this.populateSidebar();
                    this.updateAddRuleButton();
                }, 200);
            }

            // Also listen for CF7 tab changes
            $(document).on('wpcf7:tab:change', (e, tabId) => {
                if (tabId === 'scf7c-conditional-panel') {
                    setTimeout(() => {
                        this.scanFormFields();
                        this.populateSidebar();
                        this.updateAddRuleButton();
                    }, 100);
                }
            });
        },

        // Update tab title with rules count
        updateTabTitle: function() {
            const validRulesCount = $('.scf7c-rule-card').length;
            const $tabButton = $('#scf7c-conditional-panel-tab');

            if ($tabButton.length) {
                const i18n = (typeof scf7c_admin !== 'undefined' && scf7c_admin.i18n) ? scf7c_admin.i18n : {};
                let baseTitle = i18n.simpleConditionalFields || 'Simple Conditional Fields';

                if (validRulesCount > 0) {
                    baseTitle += ` (${validRulesCount})`;
                }

                // Check if tab has an <a> tag or just text
                const $link = $tabButton.find('a');
                if ($link.length) {
                    $link.text(baseTitle);
                } else {
                    $tabButton.text(baseTitle);
                }
            }
        },

        // Add rule from group click
        addRuleFromGroup: function(e) {
            e.preventDefault();

            const groupName = $(e.currentTarget).data('name');
            const groups = this.currentGroups || [];
            const fields = this.currentFields || [];

            // Validation
            if (fields.length === 0) {
                alert('You need to add at least one field to your form before creating conditional rules.');
                return;
            }

            // Create rule with pre-selected group
            const ruleData = {
                show_field: groupName,
                if_field: '',
                operator: 'equals',
                if_value: ''
            };

            this.addRule(ruleData);

            // Scroll to the new rule
            setTimeout(() => {
                const $newRule = $('.scf7c-rule-card').last();
                $newRule[0].scrollIntoView({ behavior: 'smooth', block: 'center' });

                // Highlight the new rule briefly
                $newRule.css('border-color', '#667eea');
                setTimeout(() => {
                    $newRule.css('border-color', '');
                }, 1000);
            }, 100);
        },

        // Update hidden conditions data
        updateConditionsData: function() {
            const conditions = [];

            $('.scf7c-rule-card').each(function() {
                const $rule = $(this);

                const condition = {
                    show_field: $rule.find('[data-field="show_field"]').val(),
                    if_field: $rule.find('[data-field="if_field"]').val(),
                    operator: $rule.find('[data-field="operator"]').val(),
                    if_value: $rule.find('[data-field="if_value"]').val()
                };

                // Only add valid conditions
                if (condition.show_field && condition.if_field) {
                    conditions.push(condition);
                }
            });

            $('#scf7c-conditions-data').val(JSON.stringify(conditions));

            // Update tab title
            this.updateTabTitle();
        }
    };

    // Initialize when DOM is ready - only if we're on a CF7 edit page
    if (typeof scf7c_admin !== 'undefined' || $('#scf7c-conditional-panel').length > 0) {
        SCF7C_Admin.init();
    }

    // Enhanced Group Tag Generator
    const SCF7C_GroupTagGenerator = {
        init: function() {
            // Override the tag generation for groups
            $(document).on('change', 'form.tag-generator-panel input[name="name"]', function() {
                const $form = $(this).closest('form.tag-generator-panel');

                // Check if this is our group tag generator
                if ($form.find('input[name="group"]').length) {
                    const groupName = $(this).val();
                    if (groupName) {
                        const tag = '[group ' + groupName + ']...[/group]';
                        $form.find('input.tag').val(tag);
                    }
                }
            });

            // Custom insert behavior for group tags
            $(document).on('click', 'form.tag-generator-panel .insert-tag', function(e) {
                const $form = $(this).closest('form.tag-generator-panel');

                // Check if this is our group tag generator
                if ($form.find('input[name="group"]').length) {
                    const groupName = $form.find('input[name="name"]').val();

                    if (!groupName) {
                        const message = (typeof scf7c_admin !== 'undefined' && scf7c_admin.i18n)
                            ? scf7c_admin.i18n.pleaseEnterGroupName
                            : 'Please enter a group name';
                        alert(message);
                        e.preventDefault();
                        return false;
                    }

                    // Let CF7 handle the insertion but intercept afterward to modify
                    setTimeout(() => {
                        const $textarea = $('#wpcf7-form');
                        if ($textarea.length) {
                            let content = $textarea.val();

                            // Replace single [group name] with [group name]...[/group] structure
                            const singleTagPattern = new RegExp('\\[group\\s+' + groupName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\](?!.*\\[/group\\])', 'g');
                            content = content.replace(singleTagPattern, '[group ' + groupName + ']...[/group]');

                            $textarea.val(content);

                            // Position cursor between the tags
                            const tagStart = content.lastIndexOf('[group ' + groupName + ']');
                            if (tagStart !== -1) {
                                const newPos = tagStart + ('[group ' + groupName + ']').length;
                                $textarea[0].setSelectionRange(newPos, newPos);
                                $textarea.focus();
                            }
                        }
                    }, 50);
                }
            });
        }
    };

    // Initialize Group Tag Generator
    SCF7C_GroupTagGenerator.init();

});