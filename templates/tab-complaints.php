<?php
if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$complaints = get_posts([
    'post_type' => 'attorney_complaint',
    'author' => $user_id,
    'posts_per_page' => -1,
    'post_status' => 'any'
]);

$success = isset($_GET['success']) && $_GET['success'] == '1';
$error = isset($_GET['error']) ? urldecode($_GET['error']) : '';
?>
<div class="aah-complaints-tab">
    <h2><?php _e('My Complaints', 'attorney-hub'); ?></h2>
    
    <?php if ($success): ?>
        <div class="aah-notice aah-notice-success">
            <?php _e('Your complaint has been submitted successfully and is pending admin review.', 'attorney-hub'); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="aah-notice aah-notice-error">
            <?php echo esc_html($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($complaints)): ?>
        <div class="aah-complaints-list">
            <?php foreach ($complaints as $complaint): 
                $attorney_id = get_post_meta($complaint->ID, '_attorney_id', true);
                $status = get_post_status($complaint);
                $evidence_file = get_post_meta($complaint->ID, '_evidence_file', true);
            ?>
            <div class="aah-complaint-item">
                <div class="aah-complaint-header">
                    <div>
                        <h3><?php _e('Complaint against:', 'attorney-hub'); ?> 
                            <a href="<?php echo get_permalink($attorney_id); ?>">
                                <?php echo esc_html(get_the_title($attorney_id)); ?>
                            </a>
                        </h3>
                        <div class="aah-complaint-meta">
                            <span><i class="la la-calendar"></i> <?php echo get_the_date('', $complaint); ?></span>
                        </div>
                    </div>
                    <span class="aah-status-badge status-<?php echo esc_attr($status); ?>">
                        <?php 
                        $status_labels = [
                            'pending' => __('Pending Review', 'attorney-hub'),
                            'under_review' => __('Under Review', 'attorney-hub'),
                            'resolved' => __('Resolved', 'attorney-hub'),
                            'dismissed' => __('Dismissed', 'attorney-hub')
                        ];
                        echo isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status);
                        ?>
                    </span>
                </div>
                
                <div class="aah-complaint-content">
                    <?php echo wp_trim_words($complaint->post_content, 40); ?>
                </div>
                
                <?php if ($evidence_file): ?>
                    <div class="aah-complaint-evidence">
                        <i class="la la-paperclip"></i>
                        <a href="<?php echo wp_get_attachment_url($evidence_file); ?>" target="_blank">
                            <?php _e('View Evidence', 'attorney-hub'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="aah-empty-state">
            <i class="la la-exclamation-triangle"></i>
            <h3><?php _e('No Complaints Filed', 'attorney-hub'); ?></h3>
            <p><?php _e('You haven\'t filed any complaints yet.', 'attorney-hub'); ?></p>
            <a href="<?php echo esc_url(home_url('/file-a-complaint/')); ?>" class="aah-btn aah-btn-primary">
                <i class="la la-plus"></i> <?php _e('File a Complaint', 'attorney-hub'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>
