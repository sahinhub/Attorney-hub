<?php
/**
 * User Capability Checks
 */

if (!defined('ABSPATH')) exit;

/**
 * Check if user can submit reviews
 * Integrates with Directorist review system
 */
add_filter('atbdp_can_user_review', 'aah_can_user_review', 10, 3);
function aah_can_user_review($can_review, $listing_id, $user_id) {
    // Must be Verified Reviewer or Attorney Pro
    return aah_can_file_complaints($user_id);
}

/**
 * Check if user can claim listings
 * Integrates with Directorist claim system
 */
add_filter('atbdp_can_user_claim_listing', 'aah_can_user_claim', 10, 2);
function aah_can_user_claim($can_claim, $listing_id) {
    // Only Attorney Pro can claim
    return aah_is_attorney_pro(get_current_user_id());
}

/**
 * Restrict editing of specific fields after claim
 */
add_filter('atbdp_user_can_edit_field', 'aah_restrict_field_editing', 10, 3);
function aah_restrict_field_editing($can_edit, $field_name, $listing_id) {
    // Admin-only fields
    $admin_only_fields = [
        'bar_number',
        'disciplinary_history'
    ];
    
    if (in_array($field_name, $admin_only_fields)) {
        // Only admin can edit these fields
        return current_user_can('manage_options');
    }
    
    return $can_edit;
}
