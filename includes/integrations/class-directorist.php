<?php
/**
 * Directorist Integration Module
 *
 * Handles all integration between Attorney Hub and Directorist plugin
 * Includes custom fields, search filters, and listing management
 *
 * @package    AttorneyHub
 * @subpackage Integrations
 * @since      1.0.0
 * @author     Attorney Accountability Hub Team
 */

/**
 * Class Attorney_Hub_Integration_Directorist
 *
 * Manages Directorist integration and customization
 *
 * @package    AttorneyHub
 * @subpackage Integrations
 * @since      1.0.0
 */
class Attorney_Hub_Integration_Directorist extends Attorney_Hub_Module {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->module_name = 'Directorist Integration';
		parent::__construct();
	}

	/**
	 * Check Directorist dependency
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	protected function check_dependencies() {
		return class_exists('Directorist\Directorist');
	}

	/**
	 * Initialize Directorist integration
	 *
	 * Hooks into Directorist to add custom functionality
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function init() {
		// Add custom attorney fields
		add_action('init', array($this, 'setup_custom_fields'), 20);

		// Modify search filters
		add_filter('atbdp_search_form_fields', array($this, 'add_search_filters'), 20);

		// Display attorney credentials on listings
		add_action('atbdp_single_listing_before_description', array($this, 'display_attorney_credentials'));

		// Restrict reviews based on membership
		add_filter('atbdp_can_user_review', array($this, 'restrict_reviews_by_membership'), 10, 3);

		// Restrict claiming based on membership
		add_filter('atbdp_can_user_claim_listing', array($this, 'restrict_claiming_by_membership'), 10, 2);

		// Restrict field editing after claiming
		add_filter('atbdp_user_can_edit_field', array($this, 'restrict_field_editing'), 10, 3);

		// Add verified reviewer badge to reviews
		add_filter('atbdp_review_display_data', array($this, 'add_reviewer_badge'), 10, 2);
	}

	/**
	 * Setup custom attorney fields
	 *
	 * Registers custom fields for attorney listings in Directorist
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function setup_custom_fields() {
		// This is handled through Directorist's admin interface
		// This method is here for future programmatic field registration
		do_action('attorney_hub_setup_custom_fields');
	}

	/**
	 * Add custom search filters
	 *
	 * Adds attorney-specific filters to the directory search form
	 *
	 * @since 1.0.0
	 * 
	 * @param array $fields The search form fields
	 * @return array
	 */
	public function add_search_filters($fields) {
		// Add practice areas filter
		$fields['practice_areas'] = array(
			'label' => __('Practice Areas', 'attorney-hub'),
			'type' => 'select',
			'options' => $this->get_practice_area_options(),
			'placeholder' => __('All Practice Areas', 'attorney-hub'),
		);

		// Add license status filter
		$fields['license_status'] = array(
			'label' => __('License Status', 'attorney-hub'),
			'type' => 'select',
			'options' => array(
				'' => __('All Statuses', 'attorney-hub'),
				'active' => __('Active', 'attorney-hub'),
				'inactive' => __('Inactive', 'attorney-hub'),
				'suspended' => __('Suspended', 'attorney-hub'),
			),
		);

		// Add minimum rating filter
		$fields['min_rating'] = array(
			'label' => __('Minimum Rating', 'attorney-hub'),
			'type' => 'select',
			'options' => array(
				'' => __('All Ratings', 'attorney-hub'),
				'1' => __('1 Star & Up', 'attorney-hub'),
				'2' => __('2 Stars & Up', 'attorney-hub'),
				'3' => __('3 Stars & Up', 'attorney-hub'),
				'4' => __('4 Stars & Up', 'attorney-hub'),
				'5' => __('5 Stars', 'attorney-hub'),
			),
		);

		return $fields;
	}

	/**
	 * Display attorney credentials on listing page
	 *
	 * Shows bar number, practice areas, and other credentials
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function display_attorney_credentials() {
		global $post;

		if ($post->post_type !== 'at_biz_dir') {
			return;
		}

		$credentials = $this->get_attorney_credentials($post->ID);

		if (empty($credentials)) {
			return;
		}

		echo '<div class="attorney-hub-credentials-section">';
		echo '<h3>' . esc_html__('Attorney Credentials', 'attorney-hub') . '</h3>';
		echo '<div class="attorney-hub-credentials">';

		if (!empty($credentials['bar_number'])) {
			echo '<div class="credential-item">';
			echo '<span class="label">' . esc_html__('Bar Number:', 'attorney-hub') . '</span> ';
			echo '<span class="value">' . esc_html($credentials['bar_number']) . '</span>';
			echo '</div>';
		}

		if (!empty($credentials['state_admission'])) {
			echo '<div class="credential-item">';
			echo '<span class="label">' . esc_html__('State of Admission:', 'attorney-hub') . '</span> ';
			echo '<span class="value">' . esc_html($credentials['state_admission']) . '</span>';
			echo '</div>';
		}

		if (!empty($credentials['practice_areas'])) {
			echo '<div class="credential-item">';
			echo '<span class="label">' . esc_html__('Practice Areas:', 'attorney-hub') . '</span> ';
			echo '<span class="value">' . esc_html(implode(', ', $credentials['practice_areas'])) . '</span>';
			echo '</div>';
		}

		if (!empty($credentials['years_experience'])) {
			echo '<div class="credential-item">';
			echo '<span class="label">' . esc_html__('Years of Experience:', 'attorney-hub') . '</span> ';
			echo '<span class="value">' . esc_html($credentials['years_experience']) . '</span>';
			echo '</div>';
		}

		if (!empty($credentials['law_firm'])) {
			echo '<div class="credential-item">';
			echo '<span class="label">' . esc_html__('Law Firm:', 'attorney-hub') . '</span> ';
			echo '<span class="value">' . esc_html($credentials['law_firm']) . '</span>';
			echo '</div>';
		}

		if (!empty($credentials['license_status'])) {
			echo '<div class="credential-item">';
			echo '<span class="label">' . esc_html__('License Status:', 'attorney-hub') . '</span> ';
			echo '<span class="value">' . esc_html($this->get_status_label($credentials['license_status'])) . '</span>';
			echo '</div>';
		}

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Get attorney credentials for a listing
	 *
	 * @since 1.0.0
	 * 
	 * @param int $listing_id The listing post ID
	 * @return array Array of credentials
	 */
	private function get_attorney_credentials($listing_id) {
		return array(
			'bar_number' => get_post_meta($listing_id, '_bar_number', true),
			'state_admission' => get_post_meta($listing_id, '_state_admission', true),
			'practice_areas' => array_filter((array) get_post_meta($listing_id, '_practice_areas', true)),
			'years_experience' => get_post_meta($listing_id, '_years_experience', true),
			'law_firm' => get_post_meta($listing_id, '_law_firm', true),
			'license_status' => get_post_meta($listing_id, '_license_status', true),
		);
	}

	/**
	 * Get practice area options
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_practice_area_options() {
		return array(
			'' => __('All Practice Areas', 'attorney-hub'),
			'criminal-law' => __('Criminal Law', 'attorney-hub'),
			'family-law' => __('Family Law', 'attorney-hub'),
			'personal-injury' => __('Personal Injury', 'attorney-hub'),
			'business-law' => __('Business Law', 'attorney-hub'),
			'estate-planning' => __('Estate Planning', 'attorney-hub'),
			'immigration-law' => __('Immigration Law', 'attorney-hub'),
			'real-estate' => __('Real Estate', 'attorney-hub'),
			'employment-law' => __('Employment Law', 'attorney-hub'),
			'tax-law' => __('Tax Law', 'attorney-hub'),
			'bankruptcy' => __('Bankruptcy', 'attorney-hub'),
		);
	}

	/**
	 * Get status label for a license status code
	 *
	 * @since 1.0.0
	 * 
	 * @param string $status The status code
	 * @return string The status label
	 */
	private function get_status_label($status) {
		$labels = array(
			'active' => __('Active', 'attorney-hub'),
			'inactive' => __('Inactive', 'attorney-hub'),
			'suspended' => __('Suspended', 'attorney-hub'),
		);

		return isset($labels[$status]) ? $labels[$status] : $status;
	}

	/**
	 * Restrict reviews by membership level
	 *
	 * Users must have 'verified-reviewer' or higher membership to review
	 *
	 * @since 1.0.0
	 * 
	 * @param bool $can_review Whether user can review
	 * @param int  $listing_id The listing ID
	 * @param int  $user_id The user ID
	 * @return bool
	 */
	public function restrict_reviews_by_membership($can_review, $listing_id, $user_id) {
		if (!$user_id) {
			return false;
		}

		return Attorney_Hub_Capability_Manager::user_can($user_id, 'submit_attorney_review');
	}

	/**
	 * Restrict claiming by membership level
	 *
	 * Only 'attorney-pro' members can claim listings
	 *
	 * @since 1.0.0
	 * 
	 * @param bool $can_claim Whether user can claim
	 * @param int  $listing_id The listing ID
	 * @return bool
	 */
	public function restrict_claiming_by_membership($can_claim, $listing_id) {
		$user_id = get_current_user_id();

		if (!$user_id) {
			return false;
		}

		return Attorney_Hub_Capability_Manager::user_can($user_id, 'claim_attorney_listing');
	}

	/**
	 * Restrict field editing after claiming
	 *
	 * Certain fields (bar number, disciplinary history) can only be edited by admins
	 *
	 * @since 1.0.0
	 * 
	 * @param bool   $can_edit Whether user can edit field
	 * @param string $field_name The field name/slug
	 * @param int    $listing_id The listing ID
	 * @return bool
	 */
	public function restrict_field_editing($can_edit, $field_name, $listing_id) {
		// Fields that should only be editable by admins
		$admin_only_fields = array(
			'_bar_number',
			'_disciplinary_history',
		);

		if (in_array($field_name, $admin_only_fields, true)) {
			return current_user_can('manage_options');
		}

		return $can_edit;
	}

	/**
	 * Add verified reviewer badge to reviews
	 *
	 * Adds a badge to reviews from verified reviewers
	 *
	 * @since 1.0.0
	 * 
	 * @param array  $review_data The review display data
	 * @param object $review The review/comment object
	 * @return array
	 */
	public function add_reviewer_badge($review_data, $review) {
		$user_id = $review->user_id;

		if (!$user_id) {
			return $review_data;
		}

		$membership_slug = Attorney_Hub_Capability_Manager::get_user_membership_slug($user_id);

		if (in_array($membership_slug, array('verified-reviewer', 'attorney-pro'), true)) {
			$review_data['reviewer_badge'] = '<span class="attorney-hub-verified-badge">' . 
				esc_html__('✓ Verified Reviewer', 'attorney-hub') . 
				'</span>';
		}

		return $review_data;
	}
}
