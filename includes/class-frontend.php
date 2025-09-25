<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend functionality for Simple Conditional Fields for CF7
 */
class SimpleCF7Conditional_Frontend
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_filter('wpcf7_contact_form_properties', [$this, 'add_conditions_to_form_properties'], 10, 2);
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts()
    {
        // Don't load in admin
        if (is_admin()) {
            return;
        }

        // Only enqueue if CF7 is present and page has forms with conditions
        if (!$this->should_load_scripts()) {
            return;
        }

        wp_enqueue_script(
            'scf7c-frontend-script',
            SCF7C_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery', 'contact-form-7'],
            SCF7C_VERSION,
            true
        );
    }

    /**
     * Add conditions data to form properties (like CF7CF does)
     */
    public function add_conditions_to_form_properties($properties, $wpcf7form)
    {
        // Only modify frontend forms, not admin (like CF7CF does)
        if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
            $form_id = $wpcf7form->id();
            $conditions = get_post_meta($form_id, '_scf7c_conditions', true);

            if (!empty($conditions)) {
                // Add form data to JavaScript localization instead of inline script
                wp_localize_script('scf7c-frontend-script', 'scf7c_form_' . $form_id, array(
                    'conditions' => $conditions,
                    'form_id' => $form_id
                ));

                // Add hidden input for form identification
                $properties['form'] = $properties['form'] . sprintf(
                    '<input type="hidden" name="scf7c_form_id" value="%d">',
                    absint($form_id)
                );
            }
        }

        return $properties;
    }

    /**
     * Check if scripts should be loaded
     */
    private function should_load_scripts()
    {
        global $post;

        // Check if Contact Form 7 is available
        if (!function_exists('wpcf7_contact_form')) {
            return false;
        }

        // Always load if there are CF7 forms with conditions in the database
        // This handles ACF field cases and dynamic loading
        $forms_with_conditions = get_posts([
            'post_type' => 'wpcf7_contact_form',
            'meta_query' => [
                [
                    'key' => '_scf7c_conditions',
                    'compare' => 'EXISTS'
                ]
            ],
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);

        return !empty($forms_with_conditions);
    }

    /**
     * Check if page has CF7 form
     */
    private function has_cf7_form()
    {
        global $post;

        if (!$post) {
            return false;
        }

        // Check if post content contains CF7 shortcode
        return has_shortcode($post->post_content, 'contact-form-7');
    }
}