<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin interface for Simple Conditional Fields for CF7
 */
class SimpleCF7Conditional_Admin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_filter('wpcf7_editor_panels', [$this, 'add_conditional_panel']);
        add_action('wpcf7_after_save', [$this, 'save_conditions']);
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook_suffix)
    {
        // Read and sanitize admin page parameter (no nonce needed for GET parameters)
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';

        // Only load on CF7 edit pages
        if ($page !== 'wpcf7') {
            return;
        }

        // Verify user capabilities
        if (!current_user_can('wpcf7_edit_contact_forms')) {
            return;
        }

        wp_enqueue_style(
            'scf7c-admin-style',
            SCF7C_PLUGIN_URL . 'assets/css/admin.css',
            [],
            SCF7C_VERSION
        );

        wp_enqueue_script(
            'scf7c-admin-script',
            SCF7C_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'jquery-ui-sortable'],
            SCF7C_VERSION,
            true
        );

        // Localize script with form fields
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameters in admin context
        if (isset($_GET['post'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameters in admin context
            $form_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
            if (!$form_id) {
                return;
            }

            $form = wpcf7_contact_form($form_id);
            if ($form) {
                $fields = $this->get_form_fields($form);
                wp_localize_script('scf7c-admin-script', 'scf7c_admin', [
                    'fields' => $fields,
                    'conditions' => $this->get_form_conditions($form_id),
                    'i18n' => [
                        'addRule' => __('Add Rule', 'simple-cf7-conditional'),
                        'deleteRule' => __('Delete rule', 'simple-cf7-conditional'),
                        'noRules' => __('No rules yet. Click "Add Rule" to get started.', 'simple-cf7-conditional'),
                        'conditionalRule' => __('Conditional Rule', 'simple-cf7-conditional'),
                        'showGroup' => __('Show Group', 'simple-cf7-conditional'),
                        'whenField' => __('When Field', 'simple-cf7-conditional'),
                        'condition' => __('Condition', 'simple-cf7-conditional'),
                        'value' => __('Value', 'simple-cf7-conditional'),
                        'selectGroup' => __('-- Select group --', 'simple-cf7-conditional'),
                        'selectField' => __('-- Select field --', 'simple-cf7-conditional'),
                        'enterValue' => __('Enter value...', 'simple-cf7-conditional'),
                        'simpleConditionalFields' => __('Simple Conditional Fields', 'simple-cf7-conditional'),
                        // Operators
                        'equals' => __('equals', 'simple-cf7-conditional'),
                        'notEquals' => __('not equals', 'simple-cf7-conditional'),
                        'contains' => __('contains', 'simple-cf7-conditional'),
                        'notContains' => __('does not contain', 'simple-cf7-conditional'),
                        'isEmpty' => __('is empty', 'simple-cf7-conditional'),
                        'notEmpty' => __('is not empty', 'simple-cf7-conditional'),
                        'greaterThan' => __('greater than', 'simple-cf7-conditional'),
                        'lessThan' => __('less than', 'simple-cf7-conditional'),
                        'pleaseEnterGroupName' => __('Please enter a group name', 'simple-cf7-conditional')
                    ],
                    'nonce' => wp_create_nonce('scf7c_admin_nonce'),
                    'ajax_url' => admin_url('admin-ajax.php')
                ]);
            }
        }
    }

    /**
     * Add conditional panel to CF7 editor
     */
    public function add_conditional_panel($panels)
    {
        if (current_user_can('wpcf7_edit_contact_forms')) {
            // Get current form ID and conditions count with validation
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameters in admin context
            $form_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
            $conditions_count = 0;

            // Verify user can edit this specific form
            if ($form_id && current_user_can('wpcf7_edit_contact_form', $form_id)) {
                $conditions = $this->get_form_conditions($form_id);
                $conditions_count = count($conditions);
            }

            // Build title with count if there are conditions
            $title = __('Simple Conditional Fields', 'simple-cf7-conditional');
            if ($conditions_count > 0) {
                $title .= ' (' . $conditions_count . ')';
            }

            $panels['scf7c-conditional-panel'] = [
                'title' => $title,
                'callback' => [$this, 'render_conditional_panel']
            ];
        }
        return $panels;
    }

    /**
     * Render the conditional panel
     */
    public function render_conditional_panel($form)
    {
        $form_id = $form->id();
        $conditions = $this->get_form_conditions($form_id);
        ?>
        <div class="scf7c-panel">
            <div class="scf7c-header">
                <h2><?php esc_html_e('Simple Conditional Fields', 'simple-cf7-conditional'); ?></h2>
                <p><?php esc_html_e('Create visual rules to show/hide groups of fields', 'simple-cf7-conditional'); ?></p>
            </div>

            <div class="scf7c-workspace">
                <!-- Sidebar with available groups and fields -->
                <div class="scf7c-sidebar">
                    <h3><?php esc_html_e('Available Elements', 'simple-cf7-conditional'); ?></h3>

                    <div class="scf7c-groups-list">
                        <h4><?php esc_html_e('Groups', 'simple-cf7-conditional'); ?></h4>
                        <div id="scf7c-available-groups"></div>
                    </div>

                    <div class="scf7c-fields-list">
                        <h4><?php esc_html_e('Fields', 'simple-cf7-conditional'); ?></h4>
                        <div id="scf7c-available-fields"></div>
                    </div>
                </div>

                <!-- Main canvas -->
                <div class="scf7c-canvas">
                    <div class="scf7c-canvas-header">
                        <h3><?php esc_html_e('Conditional Rules', 'simple-cf7-conditional'); ?></h3>
                        <button type="button" id="scf7c-add-rule" class="scf7c-btn-primary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php esc_html_e('Add Rule', 'simple-cf7-conditional'); ?>
                        </button>
                    </div>

                    <div id="scf7c-rules-container" class="scf7c-rules-area">
                        <div class="scf7c-empty-canvas">
                            <div class="scf7c-empty-icon">📋</div>
                            <p><?php esc_html_e('No rules yet. Click "Add Rule" to get started.', 'simple-cf7-conditional'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden field to store conditions -->
            <input type="hidden" id="scf7c-conditions-data" name="scf7c_conditions_data" value="<?php echo esc_attr(json_encode($conditions)); ?>">
        </div>
        <?php
    }

    /**
     * Get form fields and groups for select options
     */
    private function get_form_fields($form)
    {
        $form_tags = $form->scan_form_tags();
        $fields = [];
        $groups = [];

        foreach ($form_tags as $tag) {
            if (!empty($tag['name'])) {
                if ($tag['type'] === 'group') {
                    $groups[] = [
                        'name' => $tag['name'],
                        'type' => 'group'
                    ];
                } else {
                    $fields[] = [
                        'name' => $tag['name'],
                        'type' => $tag['type']
                    ];
                }
            }
        }

        return [
            'fields' => $fields,
            'groups' => $groups
        ];
    }

    /**
     * Get stored conditions for a form
     */
    private function get_form_conditions($form_id)
    {
        $form_id = absint($form_id);
        if (!$form_id || !current_user_can('wpcf7_edit_contact_form', $form_id)) {
            return [];
        }

        return get_post_meta($form_id, '_scf7c_conditions', true) ?: [];
    }

    /**
     * Convert conditions array to text format
     */
    private function conditions_to_text($conditions)
    {
        if (empty($conditions)) {
            return '';
        }

        $text = '';
        foreach ($conditions as $condition) {
            $text .= sprintf(
                "show [%s] if [%s] %s \"%s\"\n",
                $condition['show_field'],
                $condition['if_field'],
                $condition['operator'],
                $condition['if_value']
            );
        }

        return $text;
    }

    /**
     * Save conditions when form is saved
     */
    public function save_conditions($contact_form)
    {
        // Verify nonce
        $nonce = isset($_POST['_wpnonce']) ? wp_unslash($_POST['_wpnonce']) : '';
        if (!wp_verify_nonce($nonce, 'wpcf7-save-contact-form_' . $contact_form->id())) {
            return;
        }

        // Verify user capabilities
        if (!current_user_can('wpcf7_edit_contact_form', $contact_form->id())) {
            return;
        }

        if (!isset($_POST['scf7c_conditions_data'])) {
            return;
        }

        $form_id = $contact_form->id();
        $conditions_data = isset($_POST['scf7c_conditions_data']) ? wp_unslash($_POST['scf7c_conditions_data']) : '';
        $conditions = json_decode($conditions_data, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($conditions)) {
            // Sanitize conditions before saving
            $sanitized_conditions = array_map([$this, 'sanitize_condition'], $conditions);
            update_post_meta($form_id, '_scf7c_conditions', $sanitized_conditions);
        }
    }

    /**
     * Sanitize a single condition
     */
    private function sanitize_condition($condition)
    {
        if (!is_array($condition)) {
            return [];
        }

        return [
            'show_field' => sanitize_text_field($condition['show_field'] ?? ''),
            'if_field' => sanitize_text_field($condition['if_field'] ?? ''),
            'operator' => sanitize_key($condition['operator'] ?? 'equals'),
            'if_value' => sanitize_text_field($condition['if_value'] ?? '')
        ];
    }
}