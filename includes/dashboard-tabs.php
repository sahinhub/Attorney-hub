<?php
/**
 * Dashboard Tab System
 */

if (!defined('ABSPATH')) exit;

/**
 * Redirect MemberPress account page to Directorist dashboard
 */
add_action('template_redirect', 'aah_redirect_memberpress_to_directorist', 1);
function aah_redirect_memberpress_to_directorist() {
    // Check if on MemberPress account page
    if (function_exists('MeprUtils') && MeprUtils::is_account_page()) {
        $dashboard_url = get_permalink(ATTORNEY_HUB_DASHBOARD_ID);
        if ($dashboard_url) {
            wp_redirect($dashboard_url);
            exit;
        }
    }
    
    // Alternative check using the option
    $mepr_account_page_id = get_option('mepr_account_page_id');
    if ($mepr_account_page_id && is_page($mepr_account_page_id)) {
        $dashboard_url = get_permalink(ATTORNEY_HUB_DASHBOARD_ID);
        if ($dashboard_url) {
            wp_redirect($dashboard_url);
            exit;
        }
    }
}

/**
 * Disable theme authentication modal on MemberPress related pages
 */
add_action('wp', 'aah_disable_theme_auth_modal');
function aah_disable_theme_auth_modal() {
    // Check if we're on any MemberPress related pages
    $is_memberpress_page = false;
    
    if (function_exists('MeprUtils')) {
        $is_memberpress_page = MeprUtils::is_account_page() || 
                              MeprUtils::is_login_page() || 
                              MeprUtils::is_register_page() ||
                              MeprUtils::is_unauthorized_page();
    }
    
    // Also check if it's the dashboard page
    $is_dashboard_page = is_page(ATTORNEY_HUB_DASHBOARD_ID);
    
    if ($is_memberpress_page || $is_dashboard_page) {
        // Remove theme's authentication modal hooks
        remove_action('wp_footer', 'theme_authentication_modal');
        remove_action('wp_footer', 'theme_login_modal');
        remove_action('wp_footer', 'theme_registration_modal');
        
        // Remove any theme scripts that trigger the modal
        add_action('wp_enqueue_scripts', function() {
            wp_dequeue_script('theme-authentication');
            wp_dequeue_script('theme-modal');
            
            // If the theme enqueues specific auth scripts, dequeue those too
            wp_dequeue_script('theme-login');
            wp_dequeue_script('theme-register');
        }, 100);
        
        // Also prevent the modal from being triggered via JavaScript
        add_action('wp_footer', function() {
            echo '<script type="text/javascript">
            jQuery(document).ready(function($) {
                // Remove any event listeners that trigger the theme modal
                $(document).off("click", ".show-login-modal, .show-auth-modal");
                $(".theme-authentication-modal").hide();
                
                // Remove modal backdrop if it exists
                $(".modal-backdrop").remove();
                
                // Remove body classes that might trigger modal
                $("body").removeClass("show-login-modal show-auth-modal modal-open");
                
                // Override any theme modal functions
                if (typeof show_login_modal !== "undefined") {
                    show_login_modal = function() { return false; };
                }
                if (typeof show_auth_modal !== "undefined") {
                    show_auth_modal = function() { return false; };
                }
            });
            </script>';
        }, 999);
    }
}

/**
 * Prevent theme from overriding MemberPress pages
 */
add_action('template_redirect', 'aah_prevent_theme_memberpress_conflict', 5);
function aah_prevent_theme_memberpress_conflict() {
    // Check if we're on any MemberPress related pages
    $is_memberpress_page = false;
    
    if (function_exists('MeprUtils')) {
        $is_memberpress_page = MeprUtils::is_account_page() || 
                              MeprUtils::is_login_page() || 
                              MeprUtils::is_register_page() ||
                              MeprUtils::is_unauthorized_page();
    }
    
    // Also check if it's the dashboard page
    $is_dashboard_page = is_page(ATTORNEY_HUB_DASHBOARD_ID);
    
    // Check if it's a search page
    $is_search_page = is_page(get_option('atbdp_search_result_page_id')) || 
                      (isset($_GET['directory_type']) && $_GET['directory_type'] === 'attorneys') ||
                      is_search();
    
    if ($is_memberpress_page || $is_dashboard_page || $is_search_page) {
        // Remove theme's template hooks that might interfere
        remove_action('wp_head', 'theme_force_login_modal');
        remove_action('wp_head', 'theme_check_forced_modal');
        
        // Prevent theme from adding body classes that trigger modals
        add_filter('body_class', function($classes) {
            $modal_classes = ['show-login-modal', 'show-auth-modal', 'modal-open', 'modal-active'];
            return array_diff($classes, $modal_classes);
        });
        
        // Add a body class to identify MemberPress pages
        add_filter('body_class', function($classes) {
            $classes[] = 'memberpress-page';
            $classes[] = 'attorney-hub-page';
            $classes[] = 'directorist-page'; // For Directorist pages
            return $classes;
        });
    }
}

/**
 * Prevent theme from overriding Directorist search pages
 */
add_action('template_redirect', 'aah_prevent_theme_search_conflict', 5);
function aah_prevent_theme_search_conflict() {
    // Check if it's a Directorist search page
    $is_search_page = is_page(get_option('atbdp_search_result_page_id')) || 
                      (isset($_GET['directory_type']) && $_GET['directory_type'] === 'attorneys') ||
                      is_search();
    
    // Check if it's the main directory page
    $is_directory_page = is_page(get_option('atbdp_directorist_dashboard')) ||
                        is_page(get_option('atbdp_all_listing_page')) ||
                        (function_exists('is_at_biz_dir') && is_at_biz_dir());
    
    if ($is_search_page || $is_directory_page) {
        // Remove theme's search modal hooks
        remove_action('wp_footer', 'theme_search_modal');
        remove_action('wp_footer', 'theme_directory_search_modal');
        
        // Dequeue theme search scripts that might interfere
        add_action('wp_enqueue_scripts', function() {
            wp_dequeue_script('theme-search');
            wp_dequeue_script('theme-directory-search');
            wp_dequeue_script('theme-modal-search');
        }, 100);
        
        // Add CSS to prevent theme search modal conflicts
        add_action('wp_head', function() {
            echo '<style type="text/css">
            .theme-search-modal { display: none !important; }
            .theme-directory-search-modal { display: none !important; }
            .dspb-search__popup { z-index: 9999 !important; }
            .directorist-search-modal { z-index: 9998 !important; }
            </style>';
        }, 999);
        
        // Add JavaScript to ensure Directorist search modal works properly
        add_action('wp_footer', function() {
            echo '<script type="text/javascript">
            jQuery(document).ready(function($) {
                // Ensure Directorist search functionality takes precedence
                $(document).off("click", ".theme-search-trigger");
                $(".theme-search-modal").hide();
                
                // Make sure Directorist modals work correctly
                $(".dspb-search__popup").css("z-index", "9999");
                
                // Prevent theme from overriding search functionality
                if (typeof theme_search_function !== "undefined") {
                    theme_search_function = function() { return false; };
                }
            });
            </script>';
        }, 999);
    }
}

/**
 * Ensure Directorist dashboard loads properly
 */
add_action('template_redirect', 'aah_ensure_directorist_dashboard', 20);
function aah_ensure_directorist_dashboard() {
    // Check if we're on any MemberPress related pages
    $is_memberpress_page = false;
    
    if (function_exists('MeprUtils')) {
        $is_memberpress_page = MeprUtils::is_account_page() || 
                              MeprUtils::is_login_page() || 
                              MeprUtils::is_register_page() ||
                              MeprUtils::is_unauthorized_page();
    }
    
    // Also check if it's the dashboard page
    $is_dashboard_page = is_page(ATTORNEY_HUB_DASHBOARD_ID);
    
    if ($is_memberpress_page || $is_dashboard_page) {
        // Remove theme's authentication modal body class
        add_filter('body_class', function($classes) {
            // Remove classes that trigger theme modal
            $classes = array_filter($classes, function($class) {
                return !in_array($class, ['show-login-modal', 'show-auth-modal', 'modal-open']);
            });
            return $classes;
        });
        
        // Prevent theme from overriding the page content
        add_action('wp_head', function() {
            echo '<style type="text/css">
            .theme-authentication-modal { display: none !important; }
            .modal-backdrop { display: none !important; }
            .show-login-modal .theme-authentication-modal { display: none !important; }
            .show-auth-modal .theme-authentication-modal { display: none !important; }
            body.show-login-modal, body.show-auth-modal { overflow: visible !important; }
            </style>';
        }, 999);
    }
}

/**
 * Add custom tabs to Directorist dashboard
 */
add_filter('atbdp_user_nav_items', 'aah_add_dashboard_tabs', 20);
function aah_add_dashboard_tabs($items) {
    $user_id = get_current_user_id();
    $dashboard_url = get_permalink(ATTORNEY_HUB_DASHBOARD_ID);
    
    // Membership tab (all users)
    $items['membership'] = [
        'label' => __('Membership', 'attorney-hub'),
        'icon' => 'la la-crown',
        'url' => add_query_arg('tab', 'membership', $dashboard_url)
    ];
    
    // Billing History tab (all users)
    $items['billing'] = [
        'label' => __('Billing History', 'attorney-hub'),
        'icon' => 'la la-file-invoice-dollar',
        'url' => add_query_arg('tab', 'billing', $dashboard_url)
    ];
    
    // My Complaints tab (Verified Reviewer and Attorney Pro)
    if (aah_can_file_complaints($user_id)) {
        $items['complaints'] = [
            'label' => __('My Complaints', 'attorney-hub'),
            'icon' => 'la la-exclamation-triangle',
            'url' => add_query_arg('tab', 'complaints', $dashboard_url)
        ];
    }
    
    // Complaints Against Me tab (Attorney Pro only)
    if (aah_is_attorney_pro($user_id)) {
        $items['complaints-received'] = [
            'label' => __('Complaints Against Me', 'attorney-hub'),
            'icon' => 'la la-shield-alt',
            'url' => add_query_arg('tab', 'complaints-received', $dashboard_url)
        ];
    }
    
    return $items;
}

/**
 * Display custom tab content
 */
add_action('atbdp_dashboard_tab_content', 'aah_display_tab_content');
function aah_display_tab_content() {
    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
    
    switch($tab) {
        case 'membership':
            aah_get_template('tab-membership.php');
            break;
            
        case 'billing':
            aah_get_template('tab-billing.php');
            break;
            
        case 'complaints':
            aah_get_template('tab-complaints.php');
            break;
            
        case 'complaints-received':
            aah_get_template('tab-complaints-received.php');
            break;
    }
}
