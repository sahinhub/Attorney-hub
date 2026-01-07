<?php
/**
 * Complaints System
 */

if (!defined('ABSPATH')) exit;

/**
 * Register custom post type for complaints
 */
add_action('init', 'aah_register_complaint_cpt');
function aah_register_complaint_cpt() {
    register_post_type('attorney_complaint', [
        'labels' => [
            'name' => __('Complaints', 'attorney-hub'),
            'singular_name' => __('Complaint', 'attorney-hub'),
            'add_new' => __('Add New Complaint', 'attorney-hub'),
            'add_new_item' => __('Add New Complaint', 'attorney-hub'),
            'edit_item' => __('Edit Complaint', 'attorney-hub'),
            'view_item' => __('View Complaint', 'attorney-hub'),
            'search_items' => __('Search Complaints', 'attorney-hub')
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        'capabilities' => [
            'create_posts' => false // Prevent manual creation via admin
        ],
        'map_meta_cap' => true,
        'supports' => ['title', 'editor', 'author'],
        'menu_icon' => 'dashicons-warning',
        'menu_position' => 26
    ]);
    
    // Register custom post statuses
    register_post_status('under_review', [
        'label' => __('Under Review', 'attorney-hub'),
        'public' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Under Review <span class="count">(%s)</span>', 'Under Review <span class="count">(%s)</span>', 'attorney-hub')
    ]);
    
    register_post_status('resolved', [
        'label' => __('Resolved', 'attorney-hub'),
        'public' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Resolved <span class="count">(%s)</span>', 'Resolved <span class="count">(%s)</span>', 'attorney-hub')
    ]);
    
    register_post_status('dismissed', [
        'label' => __('Dismissed', 'attorney-hub'),
        'public' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Dismissed <span class="count">(%s)</span>', 'Dismissed <span class="count">(%s)</span>', 'attorney-hub')
    ]);
}

/**
 * Add custom columns to complaints admin list
 */
add_filter('manage_attorney_complaint_posts_columns', 'aah_complaint_columns');
function aah_complaint_columns($columns) {
    $new_columns = [
        'cb' => $columns['cb'],
        'title' => __('Complaint', 'attorney-hub'),
        'attorney' => __('Attorney', 'attorney-hub'),
        'complainant' => __('Filed By', 'attorney-hub'),
        'date' => __('Date Filed', 'attorney-hub'),
        'status' => __('Status', 'attorney-hub')
    ];
    return $new_columns;
}

add_action('manage_attorney_complaint_posts_custom_column', 'aah_complaint_column_content', 10, 2);
function aah_complaint_column_content($column, $post_id) {
    switch($column) {
        case 'attorney':
            $attorney_id = get_post_meta($post_id, '_attorney_id', true);
            if ($attorney_id) {
                $attorney = get_post($attorney_id);
                echo '<a href="' . get_edit_post_link($attorney_id) . '">' . esc_html($attorney->post_title) . '</a>';
            }
            break;
            
        case 'complainant':
            $author_id = get_post_field('post_author', $post_id);
            $author = get_userdata($author_id);
            echo esc_html($author->display_name);
            break;
            
        case 'status':
            $status = get_post_status($post_id);
            $status_labels = [
                'pending' => __('Pending', 'attorney-hub'),
                'under_review' => __('Under Review', 'attorney-hub'),
                'resolved' => __('Resolved', 'attorney-hub'),
                'dismissed' => __('Dismissed', 'attorney-hub')
            ];
            echo isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status);
            break;
    }
}

/**
 * Handle complaint form submission
 */
add_action('admin_post_submit_complaint', 'aah_handle_complaint_submission');
add_action('admin_post_nopriv_submit_complaint', 'aah_handle_complaint_submission');
function aah_handle_complaint_submission() {
    // Verify nonce
    if (!isset($_POST['complaint_nonce']) || !wp_verify_nonce($_POST['complaint_nonce'], 'submit_complaint')) {
        wp_die(__('Security check failed', 'attorney-hub'), 403);
    }
    
    // Check user is logged in
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(home_url('/file-a-complaint/')));
        exit;
    }
    
    // Check capability
    $user_id = get_current_user_id();
    if (!aah_can_file_complaints($user_id)) {
        wp_die(__('You do not have permission to file complaints. Please upgrade your membership.', 'attorney-hub'), 403);
    }
    
    // Sanitize input
    $attorney_id = intval($_POST['attorney_id']);
    $complaint_text = sanitize_textarea_field($_POST['complaint_text']);
    
    // Validate
    $errors = [];
    
    if (empty($attorney_id)) {
        $errors[] = __('Please select an attorney', 'attorney-hub');
    }
    
    if (empty($complaint_text) || strlen($complaint_text) < 50) {
        $errors[] = __('Complaint must be at least 50 characters', 'attorney-hub');
    }
    
    // Check if attorney exists
    $attorney = get_post($attorney_id);
    if (!$attorney || $attorney->post_type !== 'at_biz_dir') {
        $errors[] = __('Invalid attorney selected', 'attorney-hub');
    }
    
    // If there are errors, redirect back
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
        wp_redirect(add_query_arg('error', urlencode($error_message), wp_get_referer()));
        exit;
    }
    
    // Create complaint
    $complaint_id = wp_insert_post([
        'post_type' => 'attorney_complaint',
        'post_title' => sprintf(__('Complaint against %s', 'attorney-hub'), $attorney->post_title),
        'post_content' => $complaint_text,
        'post_status' => 'pending',
        'post_author' => $user_id
    ]);
    
    if (is_wp_error($complaint_id)) {
        wp_redirect(add_query_arg('error', urlencode($complaint_id->get_error_message()), wp_get_referer()));
        exit;
    }
    
    // Save metadata
    update_post_meta($complaint_id, '_attorney_id', $attorney_id);
    update_post_meta($complaint_id, '_filed_date', current_time('mysql'));
    
    // Handle file upload
    if (!empty($_FILES['evidence']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Validate file type
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $file_type = $_FILES['evidence']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $attachment_id = media_handle_upload('evidence', $complaint_id);
            
            if (!is_wp_error($attachment_id)) {
                update_post_meta($complaint_id, '_evidence_file', $attachment_id);
            }
        }
    }
    
    // Send email notification to admin
    $admin_email = get_option('admin_email');
    $subject = __('New Complaint Filed - Requires Review', 'attorney-hub');
    $message = sprintf(
        __('A new complaint has been filed and requires your review.

Complaint Details:
- Attorney: %s
- Filed by: %s
- Date: %s

View complaint: %s', 'attorney-hub'),
        $attorney->post_title,
        wp_get_current_user()->display_name,
        current_time('F j, Y g:i a'),
        admin_url('post.php?post=' . $complaint_id . '&action=edit')
    );
    
    wp_mail($admin_email, $subject, $message);
    
    // Redirect to dashboard with success message
    $redirect_url = add_query_arg([
        'tab' => 'complaints',
        'success' => '1'
    ], get_permalink(ATTORNEY_HUB_DASHBOARD_ID));
    
    wp_redirect($redirect_url);
    exit;
}

/**
 * Shortcode for complaint form
 */
add_shortcode('complaint_form', 'aah_complaint_form_shortcode');
function aah_complaint_form_shortcode($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        $login_url = wp_login_url(get_permalink());
        return '<p>' . sprintf(__('Please <a href="%s">login</a> to file a complaint.', 'attorney-hub'), esc_url($login_url)) . '</p>';
    }
    
    // Check capability
    if (!aah_can_file_complaints(get_current_user_id())) {
        $pricing_url = home_url('/plans/pricing/');
        return '<div class="aah-upgrade-notice">
            <p>' . __('You need to be a <strong>Verified Reviewer</strong> or <strong>Attorney Pro</strong> member to file complaints.', 'attorney-hub') . '</p>
            <a href="' . esc_url($pricing_url) . '" class="button">' . __('Upgrade Membership', 'attorney-hub') . '</a>
        </div>';
    }
    
    ob_start();
    aah_get_template('complaint-form.php');
    return ob_get_clean();
}
