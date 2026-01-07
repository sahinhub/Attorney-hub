<?php
/**
 * Dashboard Manager Module - DIAGNOSTIC VERSION
 */

class Attorney_Hub_Module_Dashboard extends Attorney_Hub_Module {

	public function __construct() {
		$this->module_name = 'Dashboard';
		
		// CRITICAL: Log constructor execution
		error_log('=================================');
		error_log('ATTORNEY HUB: Dashboard constructor called');
		error_log('=================================');
		
		parent::__construct();
	}

	protected function check_dependencies() {
		error_log('ATTORNEY HUB: Checking dependencies...');
		
		$directorist_exists = class_exists('Directorist\Directorist');
		$memberpress_exists = class_exists('MeprOptions');
		
		error_log('ATTORNEY HUB: Directorist exists: ' . ($directorist_exists ? 'YES' : 'NO'));
		error_log('ATTORNEY HUB: MemberPress exists: ' . ($memberpress_exists ? 'YES' : 'NO'));
		
		// BYPASS dependencies for testing
		// return $directorist_exists && $memberpress_exists;
		
		// FOR TESTING: Always return true
		error_log('ATTORNEY HUB: Dependencies check result: TRUE (forced for testing)');
		return true;
	}

	protected function init() {
		error_log('ATTORNEY HUB: Dashboard init() called!');
		
		// Test if hooks work
		add_action('init', function() {
			error_log('ATTORNEY HUB: WordPress init hook fired');
		});
		
		// Add custom tabs - try ALL possible hook names
		$hooks = array(
			'atbdp_user_nav_items',
			'atbdp_dashboard_nav_items',
			'directorist_dashboard_nav_items',
			'atbdp_dashboard_nav',
		);
		
		foreach ($hooks as $hook) {
			add_filter($hook, array($this, 'add_custom_dashboard_tabs'), 5);
			error_log('ATTORNEY HUB: Added filter for hook: ' . $hook);
		}

		// Render tab content - try multiple hooks
		$content_hooks = array(
			'atbdp_dashboard_tab_content',
			'directorist_dashboard_tab_content',
		);
		
		foreach ($content_hooks as $hook) {
			add_action($hook, array($this, 'render_tab_content'), 10);
			error_log('ATTORNEY HUB: Added action for hook: ' . $hook);
		}
		
		// Enqueue styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_dashboard_styles'));
		
		// Test hook to see if we're on the right page
		add_action('wp', array($this, 'detect_dashboard_page'));
		
		error_log('ATTORNEY HUB: Dashboard init() completed');
	}
	
	public function detect_dashboard_page() {
		global $post;
		
		if (!$post) {
			return;
		}
		
		error_log('ATTORNEY HUB: Current page ID: ' . $post->ID);
		error_log('ATTORNEY HUB: Current page title: ' . $post->post_title);
		error_log('ATTORNEY HUB: Has directorist shortcode: ' . (has_shortcode($post->post_content, 'directorist_user_dashboard') ? 'YES' : 'NO'));
		
		// Check URL parameters
		if (isset($_GET['tab'])) {
			error_log('ATTORNEY HUB: Tab parameter detected: ' . $_GET['tab']);
		}
	}

	private function get_dashboard_page_id() {
		error_log('ATTORNEY HUB: Getting dashboard page ID...');
		
		// Method 1: Directorist settings
		$directorist_pages = get_option('atbdp_option');
		if (!empty($directorist_pages['user_dashboard'])) {
			error_log('ATTORNEY HUB: Found dashboard page ID in Directorist settings: ' . $directorist_pages['user_dashboard']);
			return $directorist_pages['user_dashboard'];
		}
		
		// Method 2: Search for shortcode
		$pages = get_posts(array(
			'post_type' => 'page',
			'posts_per_page' => -1,
			'post_status' => 'publish',
		));
		
		foreach ($pages as $page) {
			if (has_shortcode($page->post_content, 'directorist_user_dashboard')) {
				error_log('ATTORNEY HUB: Found dashboard page by shortcode: ' . $page->ID . ' - ' . $page->post_title);
				return $page->ID;
			}
		}
		
		error_log('ATTORNEY HUB: Dashboard page NOT found!');
		return 0;
	}

	public function add_custom_dashboard_tabs($nav_items) {
		error_log('ATTORNEY HUB: add_custom_dashboard_tabs() called!');
		error_log('ATTORNEY HUB: Existing nav items: ' . print_r($nav_items, true));
		
		$user_id = get_current_user_id();
		error_log('ATTORNEY HUB: Current user ID: ' . $user_id);

		if (!$user_id) {
			error_log('ATTORNEY HUB: User not logged in, returning');
			return $nav_items;
		}

		$dashboard_page_id = $this->get_dashboard_page_id();
		$dashboard_url = get_permalink($dashboard_page_id);

		error_log('ATTORNEY HUB: Dashboard page ID: ' . $dashboard_page_id);
		error_log('ATTORNEY HUB: Dashboard URL: ' . $dashboard_url);

		if (!$dashboard_url) {
			error_log('ATTORNEY HUB: Dashboard URL is empty, returning');
			return $nav_items;
		}

		// Add test tab
		$nav_items['ah_test'] = array(
			'label' => '★ TEST TAB ★',
			'target' => 'ah_test', // Use target instead of URL for proper tab functionality
		);
		
		// Add Membership tab
		$nav_items['ah_membership'] = array(
			'label' => __('Membership', 'attorney-hub'),
			'target' => 'ah_membership', // Use target instead of URL for proper tab functionality
		);

		// Add Billing History tab
		$nav_items['ah_billing'] = array(
			'label' => __('Billing History', 'attorney-hub'),
			'target' => 'ah_billing', // Use target instead of URL for proper tab functionality
		);

		// Add My Complaints tab
		if (class_exists('Attorney_Hub_Capability_Manager') && 
		    Attorney_Hub_Capability_Manager::user_can($user_id, 'file_attorney_complaint')) {
			$nav_items['ah_my_complaints'] = array(
				'label' => __('My Complaints', 'attorney-hub'),
				'target' => 'ah_my_complaints', // Use target instead of URL for proper tab functionality
			);
			error_log('ATTORNEY HUB: Added My Complaints tab');
		}

		// Add Complaints Against Me tab
		if (class_exists('Attorney_Hub_Capability_Manager') && 
		    Attorney_Hub_Capability_Manager::user_can($user_id, 'manage_attorney_profile')) {
			$nav_items['ah_complaints_against_me'] = array(
				'label' => __('Complaints Against Me', 'attorney-hub'),
				'target' => 'ah_complaints_against_me', // Use target instead of URL for proper tab functionality
			);
			error_log('ATTORNEY HUB: Added Complaints Against Me tab');
		}
		
		error_log('ATTORNEY HUB: Final nav items count: ' . count($nav_items));
		error_log('ATTORNEY HUB: Final nav items: ' . print_r($nav_items, true));

		return $nav_items;
	}

	public function render_tab_content($active_tab) {
		error_log('ATTORNEY HUB: render_tab_content() called for tab: ' . $active_tab);
		
		if ($active_tab === 'ah_test') {
			echo '<div style="padding: 20px; background: #d4edda; border: 2px solid #28a745; margin: 20px 0;">';
			echo '<h2 style="color: #155724;">✓ TEST TAB WORKING!</h2>';
			echo '<p>If you see this, the Attorney Hub Dashboard module is working correctly!</p>';
			echo '<p><strong>User ID:</strong> ' . get_current_user_id() . '</p>';
			echo '<p><strong>Dashboard Page ID:</strong> ' . $this->get_dashboard_page_id() . '</p>';
			echo '</div>';
			return;
		}
		
		switch ($active_tab) {
			case 'ah_membership':
				$this->render_membership_tab();
				break;

			case 'ah_billing':
				$this->render_billing_tab();
				break;

			case 'ah_my_complaints':
				$this->render_my_complaints_tab();
				break;

			case 'ah_complaints_against_me':
				$this->render_complaints_against_me_tab();
				break;
		}
	}

	private function render_membership_tab() {
		$user_id = get_current_user_id();
		?>
		<div class="aah-membership-tab">
			<h3><?php esc_html_e('Membership Information', 'attorney-hub'); ?></h3>
			<?php
			if (class_exists('Attorney_Hub_Capability_Manager')) {
				$membership_slug = Attorney_Hub_Capability_Manager::get_user_membership_slug($user_id);
				$membership_name = Attorney_Hub_Capability_Manager::get_membership_name($membership_slug);

				echo '<div class="aah-membership-info">';
				echo '<p><strong>' . esc_html__('Current Tier:', 'attorney-hub') . '</strong> ' . esc_html($membership_name) . '</p>';

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
				echo '<p>Capability Manager not loaded</p>';
			}
			?>
		</div>
		<?php
	}

	private function render_billing_tab() {
		?>
		<div class="aah-billing-tab">
			<h3><?php esc_html_e('Billing History', 'attorney-hub'); ?></h3>
			<p>Billing tab content will appear here.</p>
		</div>
		<?php
	}

	private function render_my_complaints_tab() {
		?>
		<div class="aah-complaints-tab">
			<h3><?php esc_html_e('Complaints I Filed', 'attorney-hub'); ?></h3>
			<p>Your complaints will appear here.</p>
		</div>
		<?php
	}

	private function render_complaints_against_me_tab() {
		?>
		<div class="aah-attorney-complaints-tab">
			<h3><?php esc_html_e('Complaints Against My Profile', 'attorney-hub'); ?></h3>
			<p>Complaints filed against you will appear here.</p>
		</div>
		<?php
	}

	public function enqueue_dashboard_styles() {
		global $post;
		
		if (!$post) {
			return;
		}
		
		if (has_shortcode($post->post_content, 'directorist_user_dashboard')) {
			wp_enqueue_style(
				'attorney-hub-dashboard',
				ATTORNEY_HUB_ASSETS_URL . 'css/dashboard.css',
				array(),
				ATTORNEY_HUB_VERSION
			);
			error_log('ATTORNEY HUB: Dashboard CSS enqueued');
		}
	}
}