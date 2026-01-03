<?php
/**
 * PSR-4 Autoloader for Attorney Hub Plugin
 *
 * Automatically loads class files based on namespace and class name
 *
 * @package    AttorneyHub
 * @subpackage Core
 * @since      1.0.0
 * @author     Attorney Accountability Hub Team
 */

/**
 * Class Attorney_Hub_Autoloader
 *
 * Implements PSR-4 autoloading for Attorney Hub classes
 *
 * @package    AttorneyHub
 * @subpackage Core
 * @since      1.0.0
 */
class Attorney_Hub_Autoloader {

	/**
	 * Initialize autoloader
	 *
	 * Registers the autoload function with PHP's spl_autoload_register
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		spl_autoload_register(array(__CLASS__, 'autoload'));
	}

	/**
	 * Autoload class files
	 *
	 * Converts class names to file paths and requires the appropriate file
	 * 
	 * Naming conventions:
	 * - Attorney_Hub_ClassName -> includes/class-class-name.php
	 * - Attorney_Hub_Module_ClassName -> includes/modules/class-class-name.php
	 * - Attorney_Hub_Integration_SystemName -> includes/integrations/class-system-name.php
	 * - Attorney_Hub_Interface_Name -> includes/interfaces/interface-name.php
	 * - Attorney_Hub_Trait_Name -> includes/traits/trait-name.php
	 *
	 * @since 1.0.0
	 * 
	 * @param string $class_name The class name being requested
	 * @return void
	 */
	public static function autoload($class_name) {
		// Check if class starts with Attorney_Hub_
		if (strpos($class_name, 'Attorney_Hub_') !== 0) {
			return;
		}

		// Determine the directory and filename based on class type
		$base_path = ATTORNEY_HUB_INCLUDES_PATH;
		$subdirectory = '';

		if (strpos($class_name, 'Attorney_Hub_Interface_') === 0) {
			$subdirectory = 'interfaces/';
			$file_name = str_replace('Attorney_Hub_Interface_', '', $class_name);
			$file_prefix = 'interface-';
		} elseif (strpos($class_name, 'Attorney_Hub_Trait_') === 0) {
			$subdirectory = 'traits/';
			$file_name = str_replace('Attorney_Hub_Trait_', '', $class_name);
			$file_prefix = 'trait-';
		} elseif (strpos($class_name, 'Attorney_Hub_Abstract_') === 0) {
			$subdirectory = 'abstracts/';
			$file_name = str_replace('Attorney_Hub_Abstract_', '', $class_name);
			$file_prefix = 'class-';
		} elseif (strpos($class_name, 'Attorney_Hub_Core_') === 0) {
			$subdirectory = 'core/';
			$file_name = str_replace('Attorney_Hub_Core_', '', $class_name);
			$file_prefix = 'class-';
		} elseif (strpos($class_name, 'Attorney_Hub_Integration_') === 0) {
			$subdirectory = 'integrations/';
			$file_name = str_replace('Attorney_Hub_Integration_', '', $class_name);
			$file_prefix = 'class-';
		} elseif (strpos($class_name, 'Attorney_Hub_Module_') === 0) {
			$subdirectory = 'modules/';
			$file_name = str_replace('Attorney_Hub_Module_', '', $class_name);
			$file_prefix = 'class-';
		} elseif (strpos($class_name, 'Attorney_Hub_Admin_') === 0) {
			$subdirectory = 'admin/';
			$file_name = str_replace('Attorney_Hub_Admin_', '', $class_name);
			$file_prefix = 'class-';
		} elseif (strpos($class_name, 'Attorney_Hub_Public_') === 0) {
			$subdirectory = 'public/';
			$file_name = str_replace('Attorney_Hub_Public_', '', $class_name);
			$file_prefix = 'class-';
		} elseif (strpos($class_name, 'Attorney_Hub_Utility_') === 0) {
			$subdirectory = 'utilities/';
			$file_name = str_replace('Attorney_Hub_Utility_', '', $class_name);
			$file_prefix = 'class-';
		} else {
			// Root level classes in includes/
			$file_name = str_replace('Attorney_Hub_', '', $class_name);
			$file_prefix = 'class-';
		}

		// Convert class name parts to kebab-case for filename
		$file_name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $file_name));
		$file_path = $base_path . $subdirectory . $file_prefix . $file_name . '.php';

		// Load the file if it exists
		if (file_exists($file_path)) {
			require_once $file_path;
		}
	}
}
