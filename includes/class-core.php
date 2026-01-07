<?php
/**
 * Core Module Class
 *
 * Handles basic plugin functionality including CSS/JS loading,
 * textdomain loading, and general plugin setup
 *
 * @package    AttorneyHub
 * @subpackage Core
 * @since      1.0.0
 * @author     Attorney Accountability Hub Team
 */

/**
 * Class Attorney_Hub_Core
 *
 * Core module for Attorney Hub plugin
 *
 * @package    AttorneyHub
 * @subpackage Core
 * @since      1.0.0
 */
class Attorney_Hub_Core extends Attorney_Hub_Module {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->module_name = 'Core';
		parent::__construct();
	}

	/**
	 * Initialize core functionality
	 *
	 * Registers hooks for loading styles, scripts, and translations
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function init() {
		// Load plugin text domain for translations
		add_action('init', array($this, 'load_textdomain'), 5);

		// Enqueue frontend styles and scripts
		add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));

		// Enqueue admin styles and scripts
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

		// Register custom post types
		add_action('init', array($this, 'register_custom_post_types'));

		// Add plugin action links
		add_filter('plugin_action_links_' . ATTORNEY_HUB_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
	}

	/**
	 * Load plugin text domain for translations
	 *
	 * Allows the plugin strings to be translated
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'attorney-hub',
			false,
			dirname(ATTORNEY_HUB_PLUGIN_BASENAME) . '/languages'
		);
	}

	/**
	 * Enqueue frontend styles and scripts
	 *
	 * Loads CSS and JavaScript needed on the frontend
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_frontend_scripts() {
		// Enqueue frontend stylesheet
		wp_enqueue_style(
			'attorney-hub-frontend',
			ATTORNEY_HUB_ASSETS_URL . 'css/frontend.css',
			array(),
			ATTORNEY_HUB_VERSION,
			'all'
		);

		// Enqueue frontend script
		wp_enqueue_script(
			'attorney-hub-frontend',
			ATTORNEY_HUB_ASSETS_URL . 'js/frontend.js',
			array('jquery'),
			ATTORNEY_HUB_VERSION,
			true
		);

		// Localize script with nonce and AJAX URL
		wp_localize_script(
			'attorney-hub-frontend',
			'attorneyHubFrontend',
			array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('attorney_hub_nonce'),
				'siteUrl' => site_url(),
			)
		);
	}

	/**
	 * Enqueue admin styles and scripts
	 *
	 * Loads CSS and JavaScript needed in WordPress admin
	 *
	 * @since 1.0.0
	 * 
	 * @param string $hook_suffix The current admin page hook suffix
	 * @return void
	 */
	public function enqueue_admin_scripts($hook_suffix) {
		// Enqueue admin stylesheet
		wp_enqueue_style(
			'attorney-hub-admin',
			ATTORNEY_HUB_ASSETS_URL . 'css/admin.css',
			array(),
			ATTORNEY_HUB_VERSION,
			'all'
		);

		// Enqueue admin script
		wp_enqueue_script(
			'attorney-hub-admin',
			ATTORNEY_HUB_ASSETS_URL . 'js/admin.js',
			array('jquery'),
			ATTORNEY_HUB_VERSION,
			true
		);

		// Localize admin script
		wp_localize_script(
			'attorney-hub-admin',
			'attorneyHubAdmin',
			array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('attorney_hub_admin_nonce'),
			)
		);
	}

	/**
	 * Register custom post types
	 *
	 * Registers any custom post types needed by the plugin
	 * Additional post types are registered by specific modules
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_custom_post_types() {
		// Additional post types registered by their respective modules
		do_action('attorney_hub_register_post_types');
	}

	/**
	 * Add plugin action links to plugins list
	 *
	 * Adds helpful links to the plugin in the WordPress plugins list
	 *
	 * @since 1.0.0
	 * 
	 * @param array $links Plugin action links
	 * @return array
	 */
	public function add_plugin_action_links($links) {
		$plugin_links = array(
			'<a href="' . admin_url('admin.php?page=attorney-hub-settings') . '">' . 
			esc_html__('Settings', 'attorney-hub') . 
			'</a>',
		);

		return array_merge($plugin_links, $links);
	}
}
