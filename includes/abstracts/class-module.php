<?php
/**
 * Abstract Base Module Class
 *
 * All plugin modules should extend this class to ensure consistent initialization
 * and dependency checking
 *
 * @package    AttorneyHub
 * @subpackage Abstracts
 * @since      1.0.0
 * @author     Attorney Accountability Hub Team
 */

/**
 * Class Attorney_Hub_Module
 *
 * Abstract base class for all Attorney Hub modules
 *
 * @package    AttorneyHub
 * @subpackage Abstracts
 * @since      1.0.0
 * @abstract
 */
abstract class Attorney_Hub_Module {

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $module_name = '';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = ATTORNEY_HUB_VERSION;

	/**
	 * Constructor
	 *
	 * Initializes the module if all dependencies are met
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ($this->check_dependencies()) {
			$this->init();
		}
	}

	/**
	 * Initialize module
	 *
	 * Child classes must implement this method to define module functionality
	 * This is called only if all dependencies are met
	 *
	 * @since 1.0.0
	 * @return void
	 */
	abstract protected function init();

	/**
	 * Check module dependencies
	 *
	 * Override this method in child classes to check for required plugins, functions, etc.
	 * Return false if dependencies are not met
	 *
	 * @since 1.0.0
	 * @return bool True if all dependencies are met, false otherwise
	 */
	protected function check_dependencies() {
		return true;
	}

	/**
	 * Get module name
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name() {
		return $this->module_name;
	}

	/**
	 * Get module version
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
}
