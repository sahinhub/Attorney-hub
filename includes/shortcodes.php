<?php
/**
 * Unified User Avatar Header Shortcode
 * 
 * @package Attorney_Hub_Dashboard
 * @since 1.0.1
 */

if (!defined('ABSPATH')) exit;

/**
 * Unified User Avatar Header Shortcode
 */
function aah_directorist_author_shortcode() {
    // Optional: Get theme options if available
    $header_account = true;
    
    if (class_exists('\wpWax\OneListing\Theme')) {
        $header_account = isset(\wpWax\OneListing\Theme::$options['header_account']) 
            ? \wpWax\OneListing\Theme::$options['header_account'] 
            : true;
    }

    // Hide on specific pages
    $is_special_page = false;
    if (function_exists('atbdp_is_page')) {
        $is_special_page = (
            atbdp_is_page('sign-in')
            || atbdp_is_page('registration')
            || atbdp_is_page('add_listing')
            || atbdp_is_page('user-dashboard')
        );
    }
    
    if (!$header_account || $is_special_page) {
        return '';
    }

    static $script_added = false;

    ob_start();
    ?>
    <li class="theme-header-action__authentication aah-avatar-wrapper">
        <?php if (!is_user_logged_in()) : ?>
            <?php echo aah_render_logged_out_avatar(); ?>
            <?php if (!$script_added) : 
                $script_added = true;
                aah_output_logged_out_avatar_script();
            endif; ?>
        <?php else : ?>
            <?php echo aah_render_logged_in_avatar(); ?>
            <?php if (!$script_added) : 
                $script_added = true;
                aah_output_avatar_menu_assets();
            endif; ?>
        <?php endif; ?>
    </li>
    <?php
    return ob_get_clean();
}

/**
 * Render avatar for logged-out users
 */
function aah_render_logged_out_avatar() {
    $login_url = aah_get_login_url();
    
    ob_start();
    ?>
    <a href="<?php echo esc_url($login_url); ?>" class="theme-header-action__author-info theme-header-action__author-info--logged-out theme-header-action__login-link" data-login-url="<?php echo esc_url($login_url); ?>">
        <img 
            alt="<?php esc_attr_e('Login to your account', 'attorney-hub'); ?>" 
            src="https://secure.gravatar.com/avatar/?s=96&d=mm&r=g"
            class="photo rounded-circle theme-header-login-avatar" 
            height="60" 
            width="60"
            loading="lazy"
            title="<?php esc_attr_e('Click to login', 'attorney-hub'); ?>">
    </a>
    <?php
    return ob_get_clean();
}

/**
 * Render avatar and dropdown menu for logged-in users
 */
function aah_render_logged_in_avatar() {
    $current_user = wp_get_current_user();
    $author_id = $current_user->ID;
    $author_img = aah_get_user_avatar($author_id);
    $display_name = !empty($current_user->display_name) ? $current_user->display_name : $current_user->user_login;
    
    $dashboard_url = aah_get_dashboard_url();
    $add_listing_url = aah_get_add_listing_url();
    
    $menu_items = aah_get_avatar_menu_items($dashboard_url, $add_listing_url, $author_id);
    
    ob_start();
    ?>
    <div class="theme-header-action__author-info directorist-user-menu-<?php echo esc_attr($author_id); ?>"
         data-user-id="<?php echo esc_attr($author_id); ?>"
         role="button"
         tabindex="0"
         aria-haspopup="true"
         aria-expanded="false"
         aria-label="<?php echo esc_attr(sprintf(__('User menu for %s', 'attorney-hub'), $display_name)); ?>">
        
        <img 
            alt="<?php echo esc_attr($display_name); ?>" 
            src="<?php echo esc_url($author_img); ?>"
            class="avatar avatar-40 photo rounded-circle directorist-author-avatar"
            height="60" 
            width="60"
            loading="lazy">
        
        <nav class="theme-header-author-navigation directorist-user-dashboard__nav" 
             role="menu" 
             aria-label="<?php esc_attr_e('User navigation', 'attorney-hub'); ?>">
            <ul class="directorist-tab__nav">
                <?php foreach ($menu_items as $item) : 
                    if (!$item['visible']) continue; 
                ?>
                    <li role="none">
                        <a href="<?php echo esc_url($item['url']); ?>"
                           class="directorist-booking-nav-link directorist-tab__nav__link <?php echo esc_attr($item['class']); ?>"
                           role="menuitem">
                            <span class="directorist_menuItem-text">
                                <span class="directorist_menuItem-icon" aria-hidden="true">
                                    <i class="fas <?php echo esc_attr($item['icon']); ?>"></i>
                                </span>
                                <?php echo esc_html($item['label']); ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Get user avatar URL
 */
function aah_get_user_avatar($author_id) {
    $u_pro_pic = get_user_meta($author_id, 'pro_pic', true);
    $u_pro_pic_src = !empty($u_pro_pic) ? wp_get_attachment_image_src($u_pro_pic, array(60, 60)) : '';
    
    return !empty($u_pro_pic_src) 
        ? $u_pro_pic_src[0] 
        : get_avatar_url($author_id, array('size' => 60));
}

/**
 * Get avatar dropdown menu items
 */
function aah_get_avatar_menu_items($dashboard_url, $add_listing_url, $user_id) {
    $menu_items = array(
        array(
            'url' => $dashboard_url . '#dashboard_my_listings',
            'label' => __('My Listings', 'attorney-hub'),
            'icon' => 'fa-list',
            'class' => '',
            'visible' => true
        ),
        array(
            'url' => $dashboard_url . '#dashboard_profile',
            'label' => __('My Profile', 'attorney-hub'),
            'icon' => 'fa-user',
            'class' => '',
            'visible' => true
        ),
        array(
            'url' => $dashboard_url . '#dashboard_fav_listings',
            'label' => __('Saved Attorneys', 'attorney-hub'),
            'icon' => 'fa-heart',
            'class' => '',
            'visible' => true
        ),
        array(
            'url' => $dashboard_url . '#dashboard_membership_account',
            'label' => __('Membership', 'attorney-hub'),
            'icon' => 'fa-crown',
            'class' => '',
            'visible' => true
        ),
        array(
            'url' => $dashboard_url . '#dashboard_complaints',
            'label' => __('My Complaints', 'attorney-hub'),
            'icon' => 'fa-exclamation-triangle',
            'class' => '',
            'visible' => function_exists('aah_can_file_complaints') ? aah_can_file_complaints($user_id) : true
        ),
        array(
            'url' => $dashboard_url . '#dashboard_complaints_received',
            'label' => __('Complaints Against Me', 'attorney-hub'),
            'icon' => 'fa-shield-alt',
            'class' => '',
            'visible' => function_exists('aah_is_attorney_pro') ? aah_is_attorney_pro($user_id) : false
        ),
        array(
            'url' => $add_listing_url,
            'label' => __('Add Attorney', 'attorney-hub'),
            'icon' => 'fa-plus',
            'class' => '',
            'visible' => true
        ),
        array(
            'url' => wp_logout_url(home_url()),
            'label' => __('Log Out', 'attorney-hub'),
            'icon' => 'fa-sign-out-alt',
            'class' => 'aah-logout-link',
            'visible' => true
        )
    );
    
    return apply_filters('aah_avatar_menu_items', $menu_items, $user_id);
}

/**
 * Get login URL - PRIORITY ORDER
 */
function aah_get_login_url() {
    // Priority 1: MemberPress login page (If available)
    if (class_exists('MeprOptions')) {
        $mepr_options = MeprOptions::fetch();
        if ($mepr_options) {
            $mepr_login = $mepr_options->login_page_url();
            if ($mepr_login) {
                return $mepr_login;
            }
        }
    }

    // Priority 2: Directorist login page
    if (class_exists('\wpWax\OneListing\ATBDP_Permalink')) {
        $directorist_login = \wpWax\OneListing\ATBDP_Permalink::get_login_page_link();
        if ($directorist_login) {
            return $directorist_login;
        }
    }
    
    // Priority 3: Directorist settings
    if (function_exists('atbdp_get_option')) {
        $login_page_id = atbdp_get_option('login_page', 'atbdp_general');
        if ($login_page_id) {
            return get_permalink($login_page_id);
        }
    }
    
    // Priority 4: Default WordPress login
    return wp_login_url();
}

/**
 * Get dashboard URL
 */
function aah_get_dashboard_url() {
    if (class_exists('\wpWax\OneListing\ATBDP_Permalink')) {
        return \wpWax\OneListing\ATBDP_Permalink::get_dashboard_page_link();
    }
    return home_url('/user-dashboard/');
}

/**
 * Get add listing URL
 */
function aah_get_add_listing_url() {
    if (class_exists('\wpWax\OneListing\ATBDP_Permalink')) {
        return \wpWax\OneListing\ATBDP_Permalink::get_add_listing_page_link();
    }
    return home_url('/add-listing/');
}

/**
 * Output script for logged-out avatar
 */
function aah_output_logged_out_avatar_script() {
    ?>
    <script>
    (function() {
        'use strict';
        console.log('AAH: Logged-out avatar is a direct link to login page');
    })();
    </script>
    <?php
}

/**
 * Output styles and scripts for avatar dropdown menu
 */
function aah_output_avatar_menu_assets() {
    ?>
    <style>
        /* Avatar Container */
        li.theme-header-action__authentication {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .theme-header-action__author-info {
            position: relative;
            cursor: pointer;
            display: inline-block;
            line-height: 0;
        }

        /* Logged-out link styling */
        .theme-header-action__author-info--logged-out,
        .theme-header-action__login-link {
            display: inline-block;
            line-height: 0;
            transition: opacity 0.2s ease, transform 0.2s ease;
            text-decoration: none;
        }

        .theme-header-action__author-info--logged-out:hover,
        .theme-header-action__login-link:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }

        .theme-header-action__author-info--logged-out:active,
        .theme-header-action__login-link:active {
            transform: scale(0.98);
        }

        /* Avatar Image */
        .theme-header-action__author-info .photo,
        .theme-header-action__author-info .avatar {
            border-radius: 50%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 2px solid transparent;
        }

        .theme-header-action__author-info:hover .photo,
        .theme-header-action__author-info:hover .avatar {
            transform: scale(1.05);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
            border-color: rgba(200, 167, 104, 0.3);
        }

        .theme-header-action__author-info.active .avatar {
            border-color: #C8A768;
            box-shadow: 0 0 0 3px rgba(200, 167, 104, 0.15);
        }

        /* Dropdown Navigation */
        .theme-header-author-navigation {
            position: absolute;
            top: 100%;
            right: 0;
            background: #ffffff;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.12);
            border-radius: 8px;
            min-width: 240px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9999;
            margin-top: 15px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .theme-header-action__author-info.active .theme-header-author-navigation,
        .theme-header-action__author-info[aria-expanded="true"] .theme-header-author-navigation {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Dropdown Arrow */
        .theme-header-author-navigation::before {
            content: '';
            position: absolute;
            top: -8px;
            right: 20px;
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 8px solid #ffffff;
            filter: drop-shadow(0 -2px 2px rgba(0, 0, 0, 0.05));
        }

        /* Menu List */
        .theme-header-author-navigation ul {
            list-style: none;
            margin: 0;
            padding: 8px 0;
        }

        .theme-header-author-navigation ul li {
            margin: 0;
        }

        .theme-header-author-navigation ul li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #000000;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .theme-header-author-navigation ul li a:hover,
        .theme-header-author-navigation ul li a:focus {
            background-color: rgba(200, 167, 104, 0.08);
            color: #C8A768;
            outline: none;
            padding-left: 24px;
        }

        /* Menu Item Content */
        .directorist_menuItem-text {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }

        .directorist_menuItem-icon {
            display: inline-flex;
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            justify-content: center;
            align-items: center;
            color: inherit;
        }

        .directorist_menuItem-icon .fas {
            font-size: 16px;
            color: inherit;
        }

        /* Logout Link Special Styling */
        .theme-header-author-navigation ul li:has(.aah-logout-link) {
            border-top: 1px solid #e9ecef;
            margin-top: 4px;
            padding-top: 4px;
        }

        .theme-header-author-navigation ul li .aah-logout-link {
            color: #dc3545;
        }

        .theme-header-author-navigation ul li .aah-logout-link:hover,
        .theme-header-author-navigation ul li .aah-logout-link:focus {
            background-color: #fff5f5;
            color: #c82333;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .theme-header-author-navigation {
                min-width: 220px;
                right: -10px;
            }
            
            .theme-header-author-navigation ul li a {
                padding: 10px 16px;
                font-size: 13px;
            }

            .theme-header-author-navigation ul li a:hover,
            .theme-header-author-navigation ul li a:focus {
                padding-left: 20px;
            }

            .directorist_menuItem-icon {
                width: 16px;
                height: 16px;
            }
        }

        /* Accessibility */
        .theme-header-action__author-info:focus {
            outline: 2px solid #C8A768;
            outline-offset: 2px;
            border-radius: 50%;
        }

        .theme-header-action__author-info:focus:not(:focus-visible) {
            outline: none;
        }
    </style>
    
    <script>
    (function() {
        'use strict';
        
        if (window.aahAvatarMenuInit) return;
        window.aahAvatarMenuInit = true;

        function initAvatarMenu() {
            const authorInfos = document.querySelectorAll('.theme-header-action__author-info[data-user-id]');

            if (!authorInfos.length) return;

            authorInfos.forEach(function(authorInfo) {
                const avatar = authorInfo.querySelector('.directorist-author-avatar');
                if (!avatar) return;

                avatar.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleMenu(authorInfo);
                });

                authorInfo.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        toggleMenu(authorInfo);
                    }
                    if (e.key === 'Escape') {
                        closeMenu(authorInfo);
                    }
                });

                const menu = authorInfo.querySelector('.theme-header-author-navigation');
                if (menu) {
                    menu.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                }
            });

            document.addEventListener('click', function(e) {
                const clickedOutside = !e.target.closest('.theme-header-action__author-info[data-user-id]');
                if (clickedOutside) {
                    closeAllMenus();
                }
            });

            let scrollTimeout;
            window.addEventListener('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(closeAllMenus, 100);
            }, { passive: true });
        }

        function toggleMenu(authorInfo) {
            const isActive = authorInfo.classList.contains('active');
            closeAllMenus();
            if (!isActive) {
                openMenu(authorInfo);
            }
        }

        function openMenu(authorInfo) {
            authorInfo.classList.add('active');
            authorInfo.setAttribute('aria-expanded', 'true');
        }

        function closeMenu(authorInfo) {
            authorInfo.classList.remove('active');
            authorInfo.setAttribute('aria-expanded', 'false');
        }

        function closeAllMenus() {
            document.querySelectorAll('.theme-header-action__author-info[data-user-id]').forEach(function(menu) {
                closeMenu(menu);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAvatarMenu);
        } else {
            initAvatarMenu();
        }

        if (window.elementorFrontend && window.elementorFrontend.hooks) {
            window.elementorFrontend.hooks.addAction('frontend/element_ready/widget', initAvatarMenu);
        }

        window.aahReinitAvatarMenu = initAvatarMenu;
    })();
    </script>
    <?php
}

// Register shortcode
add_shortcode('directorist_author', 'aah_directorist_author_shortcode');

/**
 * Enqueue avatar styles
 */
function aah_enqueue_avatar_styles() {
    if (is_user_logged_in() || is_front_page() || is_home()) {
        wp_enqueue_style(
            'aah-dashboard',
            ATTORNEY_HUB_ASSETS_URL . 'css/dashboard.css',
            array(),
            ATTORNEY_HUB_VERSION,
            'all'
        );
    }
}
add_action('wp_enqueue_scripts', 'aah_enqueue_avatar_styles', 20);
