<?php
/**
 * MemberPress Integration Module
 *
 * Handles all integration between Attorney Hub and MemberPress plugin
 * Includes subscription syncing, capability management, and access control
 *
 * @package    AttorneyHub
 * @subpackage Integrations
 * @since      1.0.0
 * @author     Attorney Accountability Hub Team
 */

/**
 * Class Attorney_Hub_Integration_MemberPress
 *
 * Manages MemberPress integration and synchronization
 *
 * @package    AttorneyHub
 * @subpackage Integrations
 * @since      1.0.0
 */
class Attorney_Hub_Integration_MemberPress extends Attorney_Hub_Module {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->module_name = 'MemberPress Integration';
		parent::__construct();
	}

	/**
	 * Check MemberPress dependency
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	protected function check_dependencies() {
		return class_exists('MeprOptions');
	}

	/**
	 * Initialize MemberPress integration
	 *
	 * Hooks into MemberPress events to sync user data and update capabilities
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function init() {
		// Hook into MemberPress subscription events
		add_action('mepr-signup', array($this, 'on_user_signup'), 10, 1);
		add_action('mepr-user-updated', array($this, 'on_user_updated'), 10, 1);
		add_action('mepr-subscription-status-change', array($this, 'on_subscription_status_change'), 10, 3);
		add_action('mepr-transaction-status-change', array($this, 'on_transaction_status_change'), 10, 3);

		// Sync capabilities for existing users
		add_action('init', array($this, 'sync_existing_users'), 15);
	}

	/**
	 * Handle user signup through MemberPress
	 *
	 * Called when a user registers and purchases a membership
	 *
	 * @since 1.0.0
	 * 
	 * @param object $transaction The MemberPress transaction object
	 * @return void
	 */
	public function on_user_signup($transaction) {
		if (!$transaction || !isset($transaction->user_id)) {
			return;
		}

		$user_id = $transaction->user_id;

		// Update user capabilities based on the purchased membership
		Attorney_Hub_Capability_Manager::update_user_capabilities($user_id);

		// Clear user caches
		Attorney_Hub_Cache_Manager::clear_user_cache($user_id);

		// Log the signup event
		Attorney_Hub_Security::log_security_event('User signed up via MemberPress', array(
			'user_id' => $user_id,
			'transaction_id' => $transaction->id,
		));

		// Send welcome email
		$this->send_welcome_email($user_id);
	}

	/**
	 * Handle user profile update through MemberPress
	 *
	 * Called when a user's profile is updated
	 *
	 * @since 1.0.0
	 * 
	 * @param object $user The user object
	 * @return void
	 */
	public function on_user_updated($user) {
		if (!$user || !isset($user->ID)) {
			return;
		}

		$user_id = $user->ID;

		// Clear user caches when profile is updated
		Attorney_Hub_Cache_Manager::clear_user_cache($user_id);

		// Log the update event
		Attorney_Hub_Security::log_security_event('User profile updated via MemberPress', array(
			'user_id' => $user_id,
		));
	}

	/**
	 * Handle subscription status change
	 *
	 * Called when a subscription's status changes (active, expired, cancelled, etc.)
	 *
	 * @since 1.0.0
	 * 
	 * @param int    $subscription_id The subscription ID
	 * @param string $new_status The new status
	 * @param string $old_status The old status
	 * @return void
	 */
	public function on_subscription_status_change($subscription_id, $new_status, $old_status) {
		try {
			$subscription = new \MeprSubscription($subscription_id);
			$user_id = $subscription->user_id;

			// Update user capabilities based on new subscription status
			Attorney_Hub_Capability_Manager::update_user_capabilities($user_id);

			// Clear user caches
			Attorney_Hub_Cache_Manager::clear_user_cache($user_id);

			// Log the status change
			Attorney_Hub_Security::log_security_event('Subscription status changed', array(
				'user_id' => $user_id,
				'subscription_id' => $subscription_id,
				'old_status' => $old_status,
				'new_status' => $new_status,
			));

			// Send status change notification
			$this->send_status_change_email($user_id, $new_status, $old_status);
		} catch (Exception $e) {
			// Log error if subscription couldn't be loaded
			error_log('Attorney Hub MemberPress Integration Error: ' . $e->getMessage());
		}
	}

	/**
	 * Handle transaction status change
	 *
	 * Called when a transaction's status changes
	 *
	 * @since 1.0.0
	 * 
	 * @param int    $transaction_id The transaction ID
	 * @param string $new_status The new status
	 * @param string $old_status The old status
	 * @return void
	 */
	public function on_transaction_status_change($transaction_id, $new_status, $old_status) {
		try {
			$transaction = new \MeprTransaction($transaction_id);
			$user_id = $transaction->user_id;

			// Update user capabilities based on transaction status
			Attorney_Hub_Capability_Manager::update_user_capabilities($user_id);

			// Clear user caches
			Attorney_Hub_Cache_Manager::clear_user_cache($user_id);

			// Log the status change
			Attorney_Hub_Security::log_security_event('Transaction status changed', array(
				'user_id' => $user_id,
				'transaction_id' => $transaction_id,
				'old_status' => $old_status,
				'new_status' => $new_status,
			));
		} catch (Exception $e) {
			// Log error if transaction couldn't be loaded
			error_log('Attorney Hub MemberPress Integration Error: ' . $e->getMessage());
		}
	}

	/**
	 * Sync capabilities for existing users
	 *
	 * Updates capabilities for all users on plugin activation
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function sync_existing_users() {
		// Check if we've already done the initial sync
		if (get_transient('attorney_hub_user_sync_done')) {
			return;
		}

		// Get all users
		$users = get_users(array(
			'fields' => 'ID',
			'number' => -1,
		));

		// Update capabilities for each user
		foreach ($users as $user_id) {
			Attorney_Hub_Capability_Manager::update_user_capabilities($user_id);
		}

		// Set transient so we don't sync again
		set_transient('attorney_hub_user_sync_done', true, DAY_IN_SECONDS);
	}

	/**
	 * Send welcome email to new member
	 *
	 * @since 1.0.0
	 * 
	 * @param int $user_id The user ID
	 * @return void
	 */
	private function send_welcome_email($user_id) {
		$user = get_user_by('id', $user_id);

		if (!$user) {
			return;
		}

		$membership_slug = Attorney_Hub_Capability_Manager::get_user_membership_slug($user_id);
		$membership_name = Attorney_Hub_Capability_Manager::get_membership_name($membership_slug);

		$subject = sprintf(__('Welcome to %s - %s', 'attorney-hub'), get_bloginfo('name'), $membership_name);

		$message = sprintf(
			__("Welcome to %s!\n\n" .
			   "Thank you for joining as a %s member. You now have access to:\n\n",
			   'attorney-hub'),
			get_bloginfo('name'),
			$membership_name
		);

		// Add membership-specific features
		$features = $this->get_membership_features($membership_slug);
		foreach ($features as $feature) {
			$message .= '- ' . $feature . "\n";
		}

		$message .= sprintf(
			__("\n\nVisit our directory: %s\n\n" .
			   "If you have any questions, please contact support.\n\n" .
			   "Best regards,\n%s Team",
			   'attorney-hub'),
			site_url('/directory'),
			get_bloginfo('name')
		);

		// Send email
		wp_mail($user->user_email, $subject, $message);
	}

	/**
	 * Send subscription status change email
	 *
	 * @since 1.0.0
	 * 
	 * @param int    $user_id The user ID
	 * @param string $new_status The new subscription status
	 * @param string $old_status The old subscription status
	 * @return void
	 */
	private function send_status_change_email($user_id, $new_status, $old_status) {
		$user = get_user_by('id', $user_id);

		if (!$user) {
			return;
		}

		$status_labels = array(
			'active' => __('Active', 'attorney-hub'),
			'expired' => __('Expired', 'attorney-hub'),
			'cancelled' => __('Cancelled', 'attorney-hub'),
			'suspended' => __('Suspended', 'attorney-hub'),
		);

		$new_status_label = isset($status_labels[$new_status]) ? $status_labels[$new_status] : $new_status;
		$old_status_label = isset($status_labels[$old_status]) ? $status_labels[$old_status] : $old_status;

		$subject = sprintf(__('Membership Status Updated - %s', 'attorney-hub'), $new_status_label);

		$message = sprintf(
			__("Hello %s,\n\n" .
			   "Your membership status has been updated from %s to %s.\n\n" .
			   "If you believe this is an error, please contact our support team.\n\n" .
			   "View your membership: %s\n\n" .
			   "Best regards,\n%s Team",
			   'attorney-hub'),
			$user->display_name,
			$old_status_label,
			$new_status_label,
			site_url('/user-dashboard'),
			get_bloginfo('name')
		);

		// Send email
		wp_mail($user->user_email, $subject, $message);
	}

	/**
	 * Get features available for a membership tier
	 *
	 * @since 1.0.0
	 * 
	 * @param string $membership_slug The membership slug
	 * @return array Array of feature descriptions
	 */
	private function get_membership_features($membership_slug) {
		$features = array(
			'free-member' => array(
				__('Browse directory of attorneys', 'attorney-hub'),
				__('View attorney profiles and ratings', 'attorney-hub'),
			),
			'verified-reviewer' => array(
				__('All Free Member features', 'attorney-hub'),
				__('Submit reviews for attorneys', 'attorney-hub'),
				__('File complaints against attorneys', 'attorney-hub'),
				__('Access to verified reviewer badge', 'attorney-hub'),
			),
			'attorney-pro' => array(
				__('All Verified Reviewer features', 'attorney-hub'),
				__('Claim and manage your attorney listing', 'attorney-hub'),
				__('Edit your profile and credentials', 'attorney-hub'),
				__('View complaints filed against you', 'attorney-hub'),
				__('Priority support', 'attorney-hub'),
			),
		);

		return isset($features[$membership_slug]) ? $features[$membership_slug] : array();
	}

	/**
	 * Get user's current membership product
	 *
	 * Returns the MemberPress product object for the user's active membership
	 *
	 * @since 1.0.0
	 * 
	 * @param int $user_id The user ID
	 * @return object|false The product object or false if not found
	 */
	public static function get_user_membership_product($user_id) {
		if (!class_exists('MeprSubscription')) {
			return false;
		}

		$subscriptions = \MeprSubscription::get_active_subscriptions($user_id);

		if (empty($subscriptions)) {
			return false;
		}

		$subscription = reset($subscriptions);

		return $subscription->product();
	}
}
