<?php
/**
 * Helper Functions
 */

if (!defined('ABSPATH')) exit;

/**
 * Check if user has specific membership
 */
function aah_user_has_membership($user_id, $slug) {
    if (!function_exists('mepr_get_user')) {
        return false;
    }
    
    try {
        $user = new MeprUser($user_id);
        $memberships = $user->active_product_subscriptions('ids');
        
        foreach ($memberships as $membership_id) {
            $product = new MeprProduct($membership_id);
            if ($product->post_name === $slug) {
                return true;
            }
        }
    } catch (Exception $e) {
        error_log('Attorney Hub Helper Error: ' . $e->getMessage());
    }
    
    return false;
}

/**
 * Check if user can file complaints
 */
function aah_can_file_complaints($user_id) {
    return aah_user_has_membership($user_id, 'verified-reviewer') || 
           aah_user_has_membership($user_id, 'attorney-pro');
}

/**
 * Check if user is attorney pro
 */
function aah_is_attorney_pro($user_id) {
    return aah_user_has_membership($user_id, 'attorney-pro');
}

/**
 * Get user's membership level
 */
function aah_get_membership_level($user_id) {
    if (aah_is_attorney_pro($user_id)) {
        return 'attorney-pro';
    }
    if (aah_can_file_complaints($user_id)) {
        return 'verified-reviewer';
    }
    return 'free-member';
}

/**
 * Get template file
 */
function aah_get_template($template_name, $args = []) {
    if (!empty($args) && is_array($args)) {
        extract($args);
    }
    
    $template_path = ATTORNEY_HUB_PATH . 'templates/' . $template_name;
    
    if (file_exists($template_path)) {
        include $template_path;
        return true;
    }
    
    return false;
}

/**
 * Get all attorneys for dropdown
 */
function aah_get_attorneys_list() {
    return get_posts([
        'post_type' => 'at_biz_dir',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
}
