<?php
if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();

// Get attorney's claimed listings
$claimed_listings = get_posts([
    'post_type' => 'at_biz_dir',
    'author' => $user_id,
    'posts_per_page' => -1,
    'fields' => 'ids'
]);

if (empty($claimed_listings)) {
    ?>
    <div class="aah-empty-state">
        <i class="la la-shield-alt"></i>
        <h3><?php _e('No Claimed Listings', 'attorney-hub'); ?></h3>
        <p><?php _e('You need to claim an attorney profile first to see complaints against you.', 'attorney-hub'); ?></p>
        <a href="<?php echo esc_url(home_url('/all-attorneys/')); ?>" class="aah-btn aah-btn-primary">
            <?php _e('Browse Attorneys', 'attorney-hub'); ?>
        </a>
    </div>
    <?php
    return;
}

// Get complaints against these listings
$complaints = get_posts([
    'post_type' => 'attorney_complaint',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'meta_query' => [
        [
            'key' => '_attorney_id',
            'value' => $claimed_listings,
            'compare' => 'IN'
        ]
    ]
]);
?>
<div class="aah-attorney-complaints-tab">
    <h2><?php _e('Complaints Against Me', 'attorney-hub'); ?></h2>
    
    <?php if (!empty($complaints)): ?>
        <?php
        // Count by status
        $pending = 0;
        $under_review = 0;
        $resolved = 0;
        foreach ($complaints as $c) {
            $status = get_post_status($c);
            if ($status == 'pending') $pending++;
            if ($status == 'under_review') $under_review++;
            if ($status == 'resolved') $resolved++;
        }
        ?>
        
        <div class="aah-stats-cards">
            <div class="aah-stat-card">
                <div class="aah-stat-number"><?php echo count($complaints); ?></div>
                <div class="aah-stat-label"><?php _e('Total Complaints', 'attorney-hub'); ?></div>
            </div>
            <div class="aah-stat-card">
                <div class="aah-stat-number"><?php echo $pending + $under_review; ?></div>
                <div class="aah-stat-label"><?php _e('Pending', 'attorney-hub'); ?></div>
            </div>
            <div class="aah-stat-card">
                <div class="aah-stat-number"><?php echo $resolved; ?></div>
                <div class="aah-stat-label"><?php _e('Resolved', 'attorney-hub'); ?></div>
            </div>
        </div>
        
        <div class="aah-complaints-list">
            <?php foreach ($complaints as $complaint): 
                $complainant_id = $complaint->post_author;
                $complainant = get_userdata($complainant_id);
                $status = get_post_status($complaint);
                $evidence_file = get_post_meta($complaint->ID, '_evidence_file', true);
            ?>
            <div class="aah-complaint-item">
                <div class="aah-complaint-header">
                    <div>
                        <h4><?php _e('Complaint from:', 'attorney-hub'); ?> 
                            <?php echo esc_html($complainant ? $complainant->display_name : 'Anonymous'); ?>
                        </h4>
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
                    <?php echo wpautop($complaint->post_content); ?>
                </div>
                
                <?php if ($evidence_file): ?>
                    <div class="aah-complaint-evidence">
                        <strong><?php _e('Evidence:', 'attorney-hub'); ?></strong>
                        <a href="<?php echo wp_get_attachment_url($evidence_file); ?>" target="_blank">
                            <i class="la la-paperclip"></i> <?php _e('View Attached File', 'attorney-hub'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
    <?php else: ?>
        <div class="aah-empty-state success">
            <i class="la la-check-circle"></i>
            <h3><?php _e('No Complaints', 'attorney-hub'); ?></h3>
            <p><?php _e('Great news! You don\'t have any complaints filed against you.', 'attorney-hub'); ?></p>
        </div>
    <?php endif; ?>
</div>
