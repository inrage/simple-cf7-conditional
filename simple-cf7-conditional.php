<?php
/**
 * Plugin Name: Simple Conditional Fields for Contact Form 7
 * Plugin URI: https://www.inrage.fr
 * Description: A simple and intuitive plugin to add conditional fields to Contact Form 7 forms with visual interface.
 * Version: 1.0.0
 * Author: Pascal GAULT - inRage
 * Author URI: https://www.inrage.fr
 * Text Domain: simple-cf7-conditional
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8.2
 * Requires PHP: 7.4
 * Requires Plugins: contact-form-7
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('SCF7C_VERSION', '1.0.0');
define('SCF7C_PLUGIN_FILE', __FILE__);
define('SCF7C_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCF7C_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class SimpleCF7Conditional
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'init']);
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        // Load text domain for translations
        $this->load_textdomain();

        // Check if Contact Form 7 is active
        if (!class_exists('WPCF7')) {
            add_action('admin_notices', [$this, 'cf7_missing_notice']);
            return;
        }

        // Load plugin files
        $this->load_includes();

        // Initialize components
        $this->init_hooks();
    }

    /**
     * Load text domain for translations
     * Note: For WordPress.org hosted plugins, translations are automatically loaded
     */
    private function load_textdomain()
    {
        // WordPress.org automatically loads translations for hosted plugins
        // This is kept for manual installations and development
        if (!function_exists('wp_get_environment_type') || wp_get_environment_type() !== 'production') {
            load_plugin_textdomain(
                'simple-cf7-conditional',
                false,
                dirname(plugin_basename(SCF7C_PLUGIN_FILE)) . '/languages'
            );
        }
    }

    /**
     * Load required files
     */
    private function load_includes()
    {
        require_once SCF7C_PLUGIN_DIR . 'includes/class-admin.php';
        require_once SCF7C_PLUGIN_DIR . 'includes/class-frontend.php';
        require_once SCF7C_PLUGIN_DIR . 'includes/class-conditions.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        // Initialize admin interface
        if (is_admin()) {
            new SimpleCF7Conditional_Admin();
        }

        // Initialize frontend
        new SimpleCF7Conditional_Frontend();

        // Add group shortcode
        $this->add_group_shortcode();
    }

    /**
     * Add group shortcode for CF7
     */
    private function add_group_shortcode()
    {
        // Transform groups in form output instead of using shortcode handler
        add_filter('wpcf7_form_elements', [$this, 'transform_group_tags']);

        // Add group button to CF7 editor
        if (class_exists('WPCF7_TagGenerator')) {
            add_action('wpcf7_admin_init', function() {
                $tag_generator = WPCF7_TagGenerator::get_instance();
                $tag_generator->add('group', __('group', 'simple-cf7-conditional'),
                    [$this, 'group_tag_generator']);
            }, 50);
        }
    }

    /**
     * Transform group tags in form HTML
     */
    public function transform_group_tags($form_html)
    {
        // Transform [group name] to opening div
        $form_html = preg_replace(
            '/\[group\s+([^\]]+)([^\]]*)\]/',
            '<div class="scf7c-group" data-group-name="$1" data-scf7c-group="$1">',
            $form_html
        );

        // Transform [/group] to closing div
        $form_html = str_replace('[/group]', '</div>', $form_html);

        return $form_html;
    }

    /**
     * Group tag generator for CF7 admin
     */
    public function group_tag_generator($contact_form, $args = '')
    {
        $args = wp_parse_args($args, array());
        ?>
        <div class="control-box">
            <fieldset>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($args['content'] . '-name'); ?>"><?php echo esc_html(__('Name', 'simple-cf7-conditional')); ?></label>
                            </th>
                            <td>
                                <input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr($args['content'] . '-name'); ?>" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
        </div>

        <div class="insert-box">
            <input type="text" name="group" class="tag code" readonly="readonly" onfocus="this.select()" />
            <div class="submitbox">
                <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', 'simple-cf7-conditional')); ?>" />
            </div>
            <br class="clear" />
            <p class="description">
                <label for="<?php echo esc_attr($args['content'] . '-values'); ?>"><?php
                    /* translators: %s: HTML code example showing group syntax */
                    echo sprintf(esc_html(__('To make fields inside this group conditional, wrap them like this: %s', 'simple-cf7-conditional')), '<br><code>' . esc_html('[group my-group]...[/group]') . '</code>'); ?></label>
            </p>
        </div>
        <?php
    }

    /**
     * Notice when CF7 is missing
     */
    public function cf7_missing_notice()
    {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                printf(
                    /* translators: %s: Plugin name */
                    esc_html__('%s requires Contact Form 7 plugin to be installed and activated.', 'simple-cf7-conditional'),
                    '<strong>' . esc_html__('Simple Conditional Fields for Contact Form 7', 'simple-cf7-conditional') . '</strong>'
                );
                ?>
            </p>
        </div>
        <?php
    }
}

// Initialize plugin
new SimpleCF7Conditional();