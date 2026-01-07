<?php
/**
 * Attorney Accountability Hub Plugin
 *
 * A unified directory and membership platform for attorney accountability
 *
 * @package    AttorneyHub
 * @subpackage Core
 * @since      1.0.0
 * @author     Attorney Accountability Hub Team
 *
 * @wordpress-plugin
 * Plugin Name:       Attorney Accountability Hub
 * Plugin URI:        https://attorney-accountability-hub.com
 * Description:       A unified directory and membership platform for attorney accountability
 * Version:           1.0.0
 * Author:            Attorney Accountability Hub Team
 * Author URI:        https://attorney-accountability-hub.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       attorney-hub
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires WP:       5.8
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
	exit('Direct access to this file is not allowed.');
}

// Define plugin constants
define('ATTORNEY_HUB_VERSION', '1.0.0');
define('ATTORNEY_HUB_PLUGIN_FILE', __FILE__);
define('ATTORNEY_HUB_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ATTORNEY_HUB_PATH', plugin_dir_path(__FILE__));
define('ATTORNEY_HUB_URL', plugin_dir_url(__FILE__));
define('ATTORNEY_HUB_ASSETS_URL', ATTORNEY_HUB_URL . 'assets/');
define('ATTORNEY_HUB_INCLUDES_PATH', ATTORNEY_HUB_PATH . 'includes/');
define('ATTORNEY_HUB_DASHBOARD_ID', 1269);

// Load simple functional files first (before class-based architecture)
require_once ATTORNEY_HUB_INCLUDES_PATH . 'helpers.php';
require_once ATTORNEY_HUB_INCLUDES_PATH . 'capabilities.php';
require_once ATTORNEY_HUB_INCLUDES_PATH . 'dashboard-tabs.php';
require_once ATTORNEY_HUB_INCLUDES_PATH . 'complaints.php';

// Enqueue dashboard assets
add_action('wp_enqueue_scripts', 'aah_enqueue_dashboard_assets');
function aah_enqueue_dashboard_assets() {
    if (is_page(ATTORNEY_HUB_DASHBOARD_ID)) {
        wp_enqueue_style(
            'aah-dashboard', 
            ATTORNEY_HUB_URL . 'assets/css/dashboard.css', 
            [], 
            ATTORNEY_HUB_VERSION
        );
        
        wp_enqueue_script(
            'aah-dashboard', 
            ATTORNEY_HUB_URL . 'assets/js/dashboard.js', 
            ['jquery'], 
            ATTORNEY_HUB_VERSION, 
            true
        );
        
        wp_localize_script('aah-dashboard', 'aahData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aah_nonce')
        ]);
    }
}

/**
 * Include the autoloader
 */
require_once ATTORNEY_HUB_INCLUDES_PATH . 'class-autoloader.php';

/**
 * Main plugin class - Singleton pattern
 *
 * Manages plugin initialization, dependencies, and core functionality
 *
 * @package    AttorneyHub
 * @subpackage Core
 * @since      1.0.0
 * @author     Attorney Accountability Hub Team
 */
class Attorney_Hub {

	/**
	 * Plugin instance
	 *
	 * @var Attorney_Hub|null
	 */
	private static $instance = null;

	/**
	 * Whether dependencies are met
	 *
	 * @var bool
	 */
	private $dependencies_met = true;

	/**
	 * Get plugin instance (Singleton)
	 *
	 * @return Attorney_Hub
	 */
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - Initialize plugin
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Initialize autoloader
		Attorney_Hub_Autoloader::init();

		// Check dependencies
		$this->check_dependencies();

		// Hook initialization
		add_action('plugins_loaded', array($this, 'init'), 10);
	}

	/**
	 * Check plugin dependencies
	 *
	 * Verifies that required WordPress version, PHP version, and plugins are installed
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function check_dependencies() {
		// Check WordPress version
		if (version_compare(get_bloginfo('version'), '5.8', '<')) {
			$this->dependencies_met = false;
			add_action('admin_notices', array($this, 'wp_version_notice'));
			return;
		}

		// Check PHP version
		if (version_compare(PHP_VERSION, '7.4', '<')) {
			$this->dependencies_met = false;
			add_action('admin_notices', array($this, 'php_version_notice'));
			return;
		}

		// Check if Directorist is active
		if (!class_exists('Directorist\Directorist')) {
			$this->dependencies_met = false;
			add_action('admin_notices', array($this, 'directorist_notice'));
			return;
		}

		// Check if MemberPress is active
		if (!class_exists('MeprOptions')) {
			$this->dependencies_met = false;
			add_action('admin_notices', array($this, 'memberpress_notice'));
			return;
		}
	}

	/**
	 * Initialize plugin modules
	 *
	 * Called on plugins_loaded hook after all dependencies verified
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		if (!$this->dependencies_met) {
			return;
		}

		// Register plugin activation/deactivation hooks
		register_activation_hook(ATTORNEY_HUB_PLUGIN_FILE, array($this, 'activate_plugin'));
		register_deactivation_hook(ATTORNEY_HUB_PLUGIN_FILE, array($this, 'deactivate_plugin'));

		// Initialize core modules
		$this->load_modules();
	}

	/**
	 * Load all plugin modules
	 *
	 * Instantiates all module classes to register their functionality
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_modules() {
		// Core system modules
		new Attorney_Hub_Core();
		new Attorney_Hub_Security();
		new Attorney_Hub_Capability_Manager();
		new Attorney_Hub_Cache_Manager();

		// Integration modules
		new Attorney_Hub_Integration_MemberPress();
		new Attorney_Hub_Integration_Directorist();

		// Feature modules
		new Attorney_Hub_Module_Dashboard();
		new Attorney_Hub_Module_Complaints();
		new Attorney_Hub_Module_Claim();
		new Attorney_Hub_Module_Reviews();
	}

	/**
	 * Plugin activation hook
	 *
	 * Runs when plugin is activated - creates custom post types and flushes rewrite rules
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activate_plugin() {
		// Register custom post types
		Attorney_Hub_Module_Complaints::register_post_types();

		// Flush rewrite rules
		flush_rewrite_rules();

		// Set activation flag
		set_transient('attorney_hub_activated', true, 30);
	}

	/**
	 * Plugin deactivation hook
	 *
	 * Runs when plugin is deactivated - cleans up rewrite rules
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function deactivate_plugin() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Show WordPress version notice
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function wp_version_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e('Attorney Accountability Hub', 'attorney-hub'); ?></strong>
				<?php esc_html_e('requires WordPress 5.8 or higher to function properly.', 'attorney-hub'); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Show PHP version notice
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function php_version_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e('Attorney Accountability Hub', 'attorney-hub'); ?></strong>
				<?php esc_html_e('requires PHP 7.4 or higher to function properly.', 'attorney-hub'); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Show Directorist missing notice
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function directorist_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e('Attorney Accountability Hub', 'attorney-hub'); ?></strong>
				<?php esc_html_e('requires the Directorist plugin to be installed and activated.', 'attorney-hub'); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Show MemberPress missing notice
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function memberpress_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e('Attorney Accountability Hub', 'attorney-hub'); ?></strong>
				<?php esc_html_e('requires the MemberPress plugin to be installed and activated.', 'attorney-hub'); ?>
			</p>
		</div>
		<?php
	}
}

/**
 * Initialize the plugin
 *
 * @since 1.0.0
 * @return Attorney_Hub
 */
function attorney_hub() {
	return Attorney_Hub::get_instance();
}

// Bootstrap the plugin
attorney_hub();
