<?php
/**
 * Security Utilities Class
 *
 * Handles all security-related operations including input sanitization,
 * output escaping, nonce verification, and file upload validation
 *
 * @package    AttorneyHub
 * @subpackage Core
 * @since      1.0.0
 * @author     Attorney Accountability Hub Team
 */

/**
 * Class Attorney_Hub_Security
 *
 * Provides security utilities for input/output validation and protection
 *
 * @package    AttorneyHub
 * @subpackage Core
 * @since      1.0.0
 */
class Attorney_Hub_Security extends Attorney_Hub_Module {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->module_name = 'Security';
		parent::__construct();
	}

	/**
	 * Initialize security module
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function init() {
		// Register security-related hooks if needed
	}

	/**
	 * Verify nonce for form submissions
	 *
	 * Checks if a nonce is valid for the given action
	 *
	 * @since 1.0.0
	 * 
	 * @param string $nonce The nonce value to verify
	 * @param string $action The action name for the nonce
	 * @return bool True if nonce is valid, false otherwise
	 */
	public static function verify_nonce($nonce, $action) {
		return wp_verify_nonce($nonce, $action) !== false;
	}

	/**
	 * Create nonce for form submissions
	 *
	 * Generates a nonce for use in forms
	 *
	 * @since 1.0.0
	 * 
	 * @param string $action The action name for the nonce
	 * @return string The generated nonce
	 */
	public static function create_nonce($action) {
		return wp_create_nonce($action);
	}

	/**
	 * Log security event
	 *
	 * Logs security-related events for debugging and auditing
	 *
	 * @since 1.0.0
	 * 
	 * @param string $message The event message
	 * @param array  $context Additional context data
	 * @return void
	 */
	public static function log_security_event($message, $context = array()) {
		if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			error_log('[Attorney Hub Security] ' . $message . ' Context: ' . wp_json_encode($context));
		}
	}

	/**
	 * Sanitize input data
	 *
	 * Sanitizes input based on specified type
	 *
	 * @since 1.0.0
	 * 
	 * @param mixed  $data The data to sanitize
	 * @param string $type The type of data (int, float, text, email, url, html, textarea)
	 * @return mixed Sanitized data
	 * 
	 * @example
	 * ```php
	 * $user_id = Attorney_Hub_Security::sanitize_input($_POST['user_id'], 'int');
	 * $email = Attorney_Hub_Security::sanitize_input($_POST['email'], 'email');
	 * ```
	 */
	public static function sanitize_input($data, $type = 'text') {
		switch ($type) {
			case 'int':
				return (int) $data;

			case 'float':
				return (float) $data;

			case 'text':
				return sanitize_text_field($data);

			case 'email':
				return sanitize_email($data);

			case 'url':
				return esc_url_raw($data);

			case 'html':
				return wp_kses_post($data);

			case 'textarea':
				return sanitize_textarea_field($data);

			default:
				return sanitize_text_field($data);
		}
	}

	/**
	 * Validate file upload
	 *
	 * Validates uploaded file for size, type, and errors
	 *
	 * @since 1.0.0
	 * 
	 * @param array $file The $_FILES array element for the uploaded file
	 * @return array|WP_Error Array with file information on success, WP_Error on failure
	 * 
	 * @example
	 * ```php
	 * $validated = Attorney_Hub_Security::validate_file_upload($_FILES['document']);
	 * if (is_wp_error($validated)) {
	 *     // Handle error
	 * }
	 * ```
	 */
	public static function validate_file_upload($file) {
		// Check if file was uploaded without errors
		if (!empty($file['error'])) {
			$error_messages = array(
				UPLOAD_ERR_INI_SIZE => __('The uploaded file exceeds the upload_max_filesize directive', 'attorney-hub'),
				UPLOAD_ERR_FORM_SIZE => __('The uploaded file exceeds the MAX_FILE_SIZE directive', 'attorney-hub'),
				UPLOAD_ERR_PARTIAL => __('The uploaded file was only partially uploaded', 'attorney-hub'),
				UPLOAD_ERR_NO_FILE => __('No file was uploaded', 'attorney-hub'),
				UPLOAD_ERR_NO_TMP_DIR => __('Missing temporary folder', 'attorney-hub'),
				UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk', 'attorney-hub'),
				UPLOAD_ERR_EXTENSION => __('File upload stopped by extension', 'attorney-hub'),
			);

			$error_msg = isset($error_messages[$file['error']]) ? 
				$error_messages[$file['error']] : 
				__('Unknown upload error', 'attorney-hub');

			return new WP_Error('file_upload_error', $error_msg);
		}

		// Check file size (max 5MB)
		$max_size = 5 * 1024 * 1024;
		if ($file['size'] > $max_size) {
			return new WP_Error(
				'file_too_large',
				sprintf(
					__('File size exceeds maximum allowed size of %s', 'attorney-hub'),
					wp_convert_bytes_to_hr($max_size)
				)
			);
		}

		// Check file type
		$allowed_types = array('pdf', 'jpg', 'jpeg', 'png', 'gif');
		$file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

		if (!in_array($file_type, $allowed_types, true)) {
			return new WP_Error(
				'invalid_file_type',
				sprintf(
					__('File type not allowed. Allowed types: %s', 'attorney-hub'),
					implode(', ', $allowed_types)
				)
			);
		}

		return $file;
	}

	/**
	 * Check user capability
	 *
	 * Determines if a user has the required capability
	 *
	 * @since 1.0.0
	 * 
	 * @param int    $user_id The user ID to check
	 * @param string $capability The capability to check
	 * @return bool True if user has capability, false otherwise
	 */
	public static function user_has_capability($user_id, $capability) {
		if (!$user_id) {
			return false;
		}

		$user = get_user_by('id', $user_id);

		return $user && $user->has_cap($capability);
	}

	/**
	 * Escape HTML content for safe output
	 *
	 * @since 1.0.0
	 * 
	 * @param string $text Text to escape
	 * @return string Escaped text
	 */
	public static function escape_html($text) {
		return esc_html($text);
	}

	/**
	 * Escape HTML attribute for safe output
	 *
	 * @since 1.0.0
	 * 
	 * @param string $text Text to escape
	 * @return string Escaped text
	 */
	public static function escape_attr($text) {
		return esc_attr($text);
	}

	/**
	 * Escape URL for safe output
	 *
	 * @since 1.0.0
	 * 
	 * @param string $url URL to escape
	 * @return string Escaped URL
	 */
	public static function escape_url($url) {
		return esc_url($url);
	}
}
