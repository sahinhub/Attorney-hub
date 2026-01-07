<?php
/**
 * Dashboard Manager Module
 *
 * Handles dashboard unification between MemberPress and Directorist
 * Creates custom dashboard tabs and manages content display
 *
 * @package    AttorneyHub
 * @subpackage Modules
 * @since      1.0.0
 * @author     Attorney Accountability Hub Team
 */

/**
 * Class Attorney_Hub_Module_Dashboard
 *
 * Manages the unified dashboard experience
 *
 * @package    AttorneyHub
 * @subpackage Modules
 * @since      1.0.0
 */
class Attorney_Hub_Module_Dashboard extends Attorney_Hub_Module {

	/**
	 * Dashboard page ID
	 *
	 * @var int
	 */
	const DASHBOARD_PAGE_ID = 1269;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->module_name = 'Dashboard';
		parent::__construct();
	}

	/**
	 * Check dependencies for dashboard module
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	protected function check_dependencies() {
		// Dashboard needs both Directorist and MemberPress
		return class_exists('Directorist\Directorist') && class_exists('MeprOptions');
	}

	/**
	 * Initialize dashboard functionality
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function init() {
		// Debug: Check if module is being initialized
		if (WP_DEBUG && current_user_can('administrator')) {
			error_log('Attorney Hub: Dashboard module initialized');
		}

		// Redirect MemberPress account page to Directorist dashboard
		add_action('template_redirect', array($this, 'redirect_memberpress_account'));

		// Add custom tabs to dashboard - using only the primary Directorist hooks
		add_filter('directorist_dashboard_nav_items', array($this, 'add_custom_dashboard_tabs'), 20);

		// Render tab content
		add_action('directorist_dashboard_content', array($this, 'render_tab_content'), 10);

		// Enqueue dashboard styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_dashboard_styles'));
	}

	/**
	 * Redirect MemberPress account page to Directorist dashboard
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function redirect_memberpress_account() {
		// Only redirect if we're on a MemberPress account page
		if (!is_page() || !is_user_logged_in()) {
			return;
		}

		global $post;

		if (!$post) {
			return;
		}

		// Check if this is a MemberPress account page
		$has_memberpress_shortcode = strpos($post->post_content, '[mepr-account') !== false;

		if ($has_memberpress_shortcode) {
			// Get the Directorist dashboard URL
			$dashboard_url = get_permalink(self::DASHBOARD_PAGE_ID);

			if ($dashboard_url) {
				wp_safe_remote_get($dashboard_url);
				exit;
			}
		}
	}

	/**
	 * Add custom tabs to Directorist dashboard
	 *
	 * Adds Membership, Billing History, Complaints tabs based on user tier
	 *
	 * @since 1.0.0
	 * 
	 * @param array $nav_items Existing navigation items
	 * @return array
	 */
	public function add_custom_dashboard_tabs($nav_items) {
		// Debug: Log that this function is being called
		if (WP_DEBUG && current_user_can('administrator')) {
			error_log('Attorney Hub: add_custom_dashboard_tabs function called');
			error_log('Attorney Hub: Current nav_items: ' . print_r(array_keys($nav_items), true));
		}

		$user_id = get_current_user_id();

		// Add Membership tab
		$nav_items['ah_membership'] = array(
			'label' => __('Membership', 'attorney-hub'),
			'icon'  => '', // No icon to bypass Directorist's icon system
			'target' => 'dashboard_membership',
		);

		// Add Billing History tab
		$nav_items['ah_billing'] = array(
			'label' => __('Billing History', 'attorney-hub'),
			'icon'  => '', // No icon to bypass Directorist's icon system
			'target' => 'dashboard_billing',
		);

		// Add My Complaints tab
		if (function_exists('aah_can_file_complaints') && aah_can_file_complaints($user_id)) {
			$nav_items['ah_complaints'] = array(
				'label' => __('My Complaints', 'attorney-hub'),
				'icon'  => '', // No icon to bypass Directorist's icon system
				'target' => 'dashboard_complaints',
			);
		}

		// Add Complaints Against Me tab
		if (function_exists('aah_is_attorney_pro') && aah_is_attorney_pro($user_id)) {
			$nav_items['ah_complaints_received'] = array(
				'label' => __('Complaints Against Me', 'attorney-hub'),
				'icon'  => '', // No icon to bypass Directorist's icon system
				'target' => 'dashboard_complaints_received',
			);
		}

		// Debug: Log the updated nav_items
		if (WP_DEBUG && current_user_can('administrator')) {
			error_log('Attorney Hub: Updated nav_items: ' . print_r(array_keys($nav_items), true));
		}

		return $nav_items;
	}

	/**
	 * Render custom tab content
	 *
	 * Outputs the content for each custom tab
	 *
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	public function render_tab_content() {
		$custom_tabs = array(
			'dashboard_membership'          => 'tab-membership.php',
			'dashboard_billing'             => 'tab-billing.php',
			'dashboard_complaints'          => 'tab-complaints.php',
			'dashboard_complaints_received' => 'tab-complaints-received.php',
		);

		foreach ($custom_tabs as $id => $template) {
			echo '<div class="directorist-tab__pane" id="' . esc_attr($id) . '" style="display:none;">';
			if (function_exists('aah_get_template')) {
				aah_get_template($template);
			}
			echo '</div>';
		}
	}

	/**
	 * Render Membership tab content
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_membership_tab() {
		$user_id = get_current_user_id();
		?>
		<div class="attorney-hub-membership-tab">
			<h3><?php esc_html_e('Membership Information', 'attorney-hub'); ?></h3>
			<?php
			$membership_slug = Attorney_Hub_Capability_Manager::get_user_membership_slug($user_id);
			$membership_name = Attorney_Hub_Capability_Manager::get_membership_name($membership_slug);

			if (!empty($membership_slug)) {
				echo '<div class="attorney-hub-membership-info">';
				echo '<p><strong>' . esc_html__('Current Tier:', 'attorney-hub') . '</strong> ' . esc_html($membership_name) . '</p>';

				// Get subscription info
				if (class_exists('MeprSubscription')) {
					$subscriptions = \MeprSubscription::get_active_subscriptions($user_id);

					if (!empty($subscriptions)) {
						$subscription = reset($subscriptions);
						echo '<p><strong>' . esc_html__('Status:', 'attorney-hub') . '</strong> Active</p>';
						if (!empty($subscription->expires_at)) {
							$expires = date_i18n(get_option('date_format'), strtotime($subscription->expires_at));
							echo '<p><strong>' . esc_html__('Expires:', 'attorney-hub') . '</strong> ' . esc_html($expires) . '</p>';
						}
					}
				}

				echo '</div>';
			} else {
				echo '<p>' . esc_html__('No active membership.', 'attorney-hub') . '</p>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render Billing History tab content
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_billing_tab() {
		$user_id = get_current_user_id();
		?>
		<div class="attorney-hub-billing-tab">
			<h3><?php esc_html_e('Billing History', 'attorney-hub'); ?></h3>
			<?php
			if (class_exists('MeprTransaction')) {
				$transactions = \MeprTransaction::get_all_user_transactions($user_id);

				if (!empty($transactions)) {
					?>
					<table class="attorney-hub-table">
						<thead>
							<tr>
								<th><?php esc_html_e('Date', 'attorney-hub'); ?></th>
								<th><?php esc_html_e('Description', 'attorney-hub'); ?></th>
								<th><?php esc_html_e('Amount', 'attorney-hub'); ?></th>
								<th><?php esc_html_e('Status', 'attorney-hub'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($transactions as $transaction) {
								$date = date_i18n(get_option('date_format'), strtotime($transaction->created_at));
								?>
								<tr>
									<td><?php echo esc_html($date); ?></td>
									<td><?php echo esc_html($transaction->payment_method); ?></td>
									<td><?php echo esc_html($transaction->amount); ?></td>
									<td><?php echo esc_html(ucfirst($transaction->status)); ?></td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
					<?php
				} else {
					echo '<p>' . esc_html__('No billing history found.', 'attorney-hub') . '</p>';
				}
			} else {
				echo '<p>' . esc_html__('Billing information not available.', 'attorney-hub') . '</p>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render My Complaints tab content
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_my_complaints_tab() {
		$user_id = get_current_user_id();
		?>
		<div class="attorney-hub-my-complaints-tab">
			<h3><?php esc_html_e('Complaints I Filed', 'attorney-hub'); ?></h3>
			<?php
			$complaints = $this->get_user_complaints($user_id);

			if (!empty($complaints)) {
				?>
				<table class="attorney-hub-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Date Filed', 'attorney-hub'); ?></th>
							<th><?php esc_html_e('Attorney', 'attorney-hub'); ?></th>
							<th><?php esc_html_e('Status', 'attorney-hub'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($complaints as $complaint) {
							$attorney = get_post(intval(get_post_meta($complaint->ID, '_attorney_id', true)));
							$status = get_post_status($complaint->ID);
							$date = date_i18n(get_option('date_format'), strtotime($complaint->post_date));
							$attorney_name = $attorney ? $attorney->post_title : __('Unknown', 'attorney-hub');
							?>
							<tr>
								<td><?php echo esc_html($date); ?></td>
								<td><?php echo esc_html($attorney_name); ?></td>
								<td><?php echo esc_html(ucfirst($status)); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				<?php
			} else {
				echo '<p>' . esc_html__('You have not filed any complaints.', 'attorney-hub') . '</p>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render Complaints Against Me tab content
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_complaints_against_me_tab() {
		$user_id = get_current_user_id();
		?>
		<div class="attorney-hub-complaints-against-me-tab">
			<h3><?php esc_html_e('Complaints Against My Profile', 'attorney-hub'); ?></h3>
			<?php
			$complaints = $this->get_complaints_against_user($user_id);

			if (!empty($complaints)) {
				?>
				<table class="attorney-hub-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Date Filed', 'attorney-hub'); ?></th>
							<th><?php esc_html_e('Status', 'attorney-hub'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($complaints as $complaint) {
							$status = get_post_status($complaint->ID);
							$date = date_i18n(get_option('date_format'), strtotime($complaint->post_date));
							?>
							<tr>
								<td><?php echo esc_html($date); ?></td>
								<td><?php echo esc_html(ucfirst($status)); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				<?php
			} else {
				echo '<p>' . esc_html__('No complaints have been filed against you.', 'attorney-hub') . '</p>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Get complaints filed by a user
	 *
	 * @since 1.0.0
	 * 
	 * @param int $user_id The user ID
	 * @return array Array of complaint posts
	 */
	private function get_user_complaints($user_id) {
		$args = array(
			'post_type' => 'attorney_complaint',
			'post_status' => array('pending', 'publish', 'resolved', 'dismissed'),
			'author' => $user_id,
			'posts_per_page' => -1,
		);

		$query = new WP_Query($args);

		return $query->posts;
	}

	/**
	 * Get complaints against a user's listings
	 *
	 * @since 1.0.0
	 * 
	 * @param int $user_id The user ID
	 * @return array Array of complaint posts
	 */
	private function get_complaints_against_user($user_id) {
		// Get user's listings
		$listings = get_posts(array(
			'post_type' => 'at_biz_dir',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => '_author_id',
					'value' => $user_id,
					'compare' => '=',
				),
			),
		));

		if (empty($listings)) {
			return array();
		}

		$listing_ids = wp_list_pluck($listings, 'ID');

		// Get complaints against these listings
		$args = array(
			'post_type' => 'attorney_complaint',
			'post_status' => array('pending', 'publish', 'resolved', 'dismissed'),
			'meta_query' => array(
				array(
					'key' => '_attorney_id',
					'value' => $listing_ids,
					'compare' => 'IN',
				),
			),
			'posts_per_page' => -1,
		);

		$query = new WP_Query($args);

		return $query->posts;
	}

	/**
	 * Enqueue dashboard styles
	 *

	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_dashboard_styles() {
		if (is_page(self::DASHBOARD_PAGE_ID)) {
			wp_enqueue_style(
				'attorney-hub-dashboard',
				ATTORNEY_HUB_ASSETS_URL . 'css/dashboard.css',
				array(),
				ATTORNEY_HUB_VERSION
			);
		}
	}
}
