<?php
/**
 * Capability Manager Class
 *
 * Manages user capabilities and permissions based on membership tier
 * Coordinates between MemberPress membership levels and Attorney Hub feature access
 *
 * @package    AttorneyHub
 * @subpackage Core
 * @since      1.0.0
 * @author     Attorney Accountability Hub Team
 */

/**
 * Class Attorney_Hub_Capability_Manager
 *
 * Handles all capability and permission checks for the plugin
 *
 * @package    AttorneyHub
 * @subpackage Core
 * @since      1.0.0
 */
class Attorney_Hub_Capability_Manager extends Attorney_Hub_Module {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->module_name = 'Capability Manager';
		parent::__construct();
	}

	/**
	 * Initialize capability manager
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function init() {
		// Register capabilities on init
		add_action('init', array($this, 'register_capabilities'), 5);
	}

	/**
	 * Check if user has a specific capability
	 *
	 * First checks WordPress capabilities, then checks membership-based capabilities
	 *
	 * @since 1.0.0
	 * 
	 * @param int    $user_id The user ID to check
	 * @param string $capability The capability to check
	 * @return bool True if user has capability, false otherwise
	 * 
	 * @example
	 * ```php
	 * if (Attorney_Hub_Capability_Manager::user_can($user_id, 'file_attorney_complaint')) {
	 *     // User can file complaints
	 * }
	 * ```
	 */
	public static function user_can($user_id, $capability) {
		// Get the user
		$user = get_user_by('id', $user_id);

		if (!$user) {
			return false;
		}

		// Check if user has the specific WordPress capability
		if ($user->has_cap($capability)) {
			return true;
		}

		// Check membership-based capabilities
		$membership_slug = self::get_user_membership_slug($user_id);

		switch ($capability) {
			case 'file_attorney_complaint':
				return in_array($membership_slug, array('verified-reviewer', 'attorney-pro'), true);

			case 'submit_attorney_review':
				return in_array($membership_slug, array('verified-reviewer', 'attorney-pro'), true);

			case 'claim_attorney_listing':
				return $membership_slug === 'attorney-pro';

			case 'manage_attorney_profile':
				return $membership_slug === 'attorney-pro';

			case 'view_admin_data':
				return user_can($user_id, 'manage_options');

			default:
				return false;
		}
	}

	/**
	 * Get user's membership level slug
	 *
	 * Queries MemberPress to determine the user's active membership tier
	 *
	 * @since 1.0.0
	 * 
	 * @param int $user_id The user ID
	 * @return string The membership slug (free-member, verified-reviewer, attorney-pro)
	 */
	public static function get_user_membership_slug($user_id) {
		// Check if MemberPress is active
		if (!class_exists('MeprOptions')) {
			return 'free-member';
		}

		// Get user's active subscriptions
		$subscriptions = \MeprSubscription::get_active_subscriptions($user_id);

		if (empty($subscriptions)) {
			return 'free-member';
		}

		// Get the first active subscription's product
		$subscription = reset($subscriptions);
		$product = $subscription->product();

		if (!$product) {
			return 'free-member';
		}

		// Get product slug
		$product_slug = $product->slug;

		// Map product slug to membership slug
		$membership_map = array(
			'free-member' => 'free-member',
			'verified-reviewer' => 'verified-reviewer',
			'attorney-pro' => 'attorney-pro',
		);

		return isset($membership_map[$product_slug]) ? $membership_map[$product_slug] : 'free-member';
	}

	/**
	 * Register custom capabilities for Attorney Hub
	 *
	 * Adds Attorney Hub capabilities to relevant WordPress roles
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_capabilities() {
		$roles = wp_roles();

		if (!$roles) {
			return;
		}

		// Define capabilities to register
		$capabilities = array(
			'file_attorney_complaint',
			'submit_attorney_review',
			'claim_attorney_listing',
			'manage_attorney_profile',
			'view_admin_data',
		);

		// Add capabilities to administrator and editor roles
		foreach (array('administrator', 'editor') as $role_name) {
			$role = $roles->get_role($role_name);

			if ($role) {
				foreach ($capabilities as $capability) {
					$role->add_cap($capability);
				}
			}
		}
	}

	/**
	 * Update user capabilities based on membership status
	 *
	 * Called when membership status changes to sync capabilities
	 *
	 * @since 1.0.0
	 * 
	 * @param int $user_id The user ID
	 * @return void
	 */
	public static function update_user_capabilities($user_id) {
		$user = get_user_by('id', $user_id);

		if (!$user) {
			return;
		}

		// Capabilities that are tied to membership
		$membership_capabilities = array(
			'file_attorney_complaint',
			'submit_attorney_review',
			'claim_attorney_listing',
			'manage_attorney_profile',
		);

		// Remove all membership-based capabilities first
		foreach ($membership_capabilities as $capability) {
			$user->remove_cap($capability);
		}

		// Add capabilities based on current membership level
		$membership_slug = self::get_user_membership_slug($user_id);

		switch ($membership_slug) {
			case 'verified-reviewer':
				$user->add_cap('file_attorney_complaint');
				$user->add_cap('submit_attorney_review');
				break;

			case 'attorney-pro':
				$user->add_cap('file_attorney_complaint');
				$user->add_cap('submit_attorney_review');
				$user->add_cap('claim_attorney_listing');
				$user->add_cap('manage_attorney_profile');
				break;
		}

		// Update the user in database
		wp_update_user($user);
	}

	/**
	 * Get membership name for display
	 *
	 * Returns human-readable membership tier name
	 *
	 * @since 1.0.0
	 * 
	 * @param string $membership_slug The membership slug
	 * @return string The membership name
	 */
	public static function get_membership_name($membership_slug) {
		$names = array(
			'free-member' => __('Free Member', 'attorney-hub'),
			'verified-reviewer' => __('Verified Reviewer', 'attorney-hub'),
			'attorney-pro' => __('Attorney Pro', 'attorney-hub'),
		);

		return isset($names[$membership_slug]) ? $names[$membership_slug] : $membership_slug;
	}

	/**
	 * Get all capabilities required by a feature
	 *
	 * Returns array of capabilities needed for a specific feature
	 *
	 * @since 1.0.0
	 * 
	 * @param string $feature The feature name
	 * @return array Array of required capabilities
	 */
	public static function get_feature_capabilities($feature) {
		$feature_caps = array(
			'complaints' => array('file_attorney_complaint'),
			'reviews' => array('submit_attorney_review'),
			'claim_listing' => array('claim_attorney_listing'),
			'edit_profile' => array('manage_attorney_profile'),
			'view_complaints_against_me' => array('manage_attorney_profile'),
		);

		return isset($feature_caps[$feature]) ? $feature_caps[$feature] : array();
	}
}
