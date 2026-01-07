<?php
/**
 * Complaints Module - Phase 4
 *
 * Placeholder for complaints system implementation
 *
 * @package    AttorneyHub
 * @subpackage Modules
 * @since      1.0.0
 */

class Attorney_Hub_Module_Complaints extends Attorney_Hub_Module {

	public function __construct() {
		$this->module_name = 'Complaints';
		parent::__construct();
	}

	protected function init() {
		// Register custom post type
		add_action('init', array($this, 'register_post_types'));
	}

	public static function register_post_types() {
		register_post_type('attorney_complaint', array(
			'label' => __('Attorney Complaints', 'attorney-hub'),
			'public' => false,
			'show_ui' => true,
			'supports' => array('title', 'editor', 'author'),
		));
	}
}
