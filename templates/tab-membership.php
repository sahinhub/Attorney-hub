<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('mepr_get_current_user')) {
    echo '<p>' . __('MemberPress is not active.', 'attorney-hub') . '</p>';
    return;
}

$user = mepr_get_current_user();
$memberships = $user->active_product_subscriptions('ids');
?>
<div class="aah-membership-tab">
    <h2><?php _e('My Membership', 'attorney-hub'); ?></h2>
    
    <?php if (!empty($memberships)): ?>
        <div class="aah-memberships-list">
            <?php foreach ($memberships as $sub_id): 
                $sub = new MeprSubscription($sub_id);
                $product = $sub->product();
            ?>
            <div class="aah-membership-card active">
                <div class="aah-membership-header">
                    <h3><?php echo esc_html($product->post_title); ?></h3>
                    <span class="aah-status-badge status-<?php echo esc_attr(strtolower($sub->status)); ?>">
                        <?php echo esc_html(ucfirst($sub->status)); ?>
                    </span>
                </div>
                
                <div class="aah-membership-details">
                    <div class="aah-detail-row">
                        <span class="label"><?php _e('Member Since:', 'attorney-hub'); ?></span>
                        <span class="value"><?php echo date_i18n('F j, Y', strtotime($sub->created_at)); ?></span>
                    </div>
                    
                    <?php if ($sub->expires_at && $sub->expires_at != '0000-00-00 00:00:00'): ?>
                    <div class="aah-detail-row">
                        <span class="label"><?php _e('Expires:', 'attorney-hub'); ?></span>
                        <span class="value"><?php echo date_i18n('F j, Y', strtotime($sub->expires_at)); ?></span>
                    </div>
                    <?php else: ?>
                    <div class="aah-detail-row">
                        <span class="label"><?php _e('Subscription:', 'attorney-hub'); ?></span>
                        <span class="value"><?php _e('Lifetime', 'attorney-hub'); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($product->price > 0): ?>
                    <div class="aah-detail-row">
                        <span class="label"><?php _e('Price:', 'attorney-hub'); ?></span>
                        <span class="value"><?php echo MeprAppHelper::format_currency($product->price); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php
                // Show membership benefits
                $benefits = aah_get_membership_benefits($product->post_name);
                if (!empty($benefits)):
                ?>
                <div class="aah-membership-benefits">
                    <h4><?php _e('Your Benefits:', 'attorney-hub'); ?></h4>
                    <ul>
                        <?php foreach ($benefits as $benefit): ?>
                        <li><i class="la la-check-circle"></i> <?php echo esc_html($benefit); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="aah-no-membership">
            <p><?php _e('You don\'t have an active membership.', 'attorney-hub'); ?></p>
            <a href="<?php echo esc_url(home_url('/plans/pricing/')); ?>" class="aah-btn aah-btn-primary">
                <?php _e('View Membership Plans', 'attorney-hub'); ?>
            </a>
        </div>
    <?php endif; ?>
    
    <div class="aah-membership-actions">
        <a href="<?php echo esc_url(home_url('/plans/pricing/')); ?>" class="aah-btn aah-btn-secondary">
            <?php _e('Upgrade / Change Plan', 'attorney-hub'); ?>
        </a>
    </div>
</div>

<?php
/**
 * Get membership benefits by slug
 */
function aah_get_membership_benefits($membership_slug) {
    $benefits = [
        'free-member' => [
            __('Browse attorney directory', 'attorney-hub'),
            __('View attorney profiles', 'attorney-hub'),
            __('Search by location and practice area', 'attorney-hub')
        ],
        'verified-reviewer' => [
            __('Submit reviews for attorneys', 'attorney-hub'),
            __('File complaints with evidence', 'attorney-hub'),
            __('Save favorite attorneys', 'attorney-hub'),
            __('All Free Member benefits', 'attorney-hub')
        ],
        'attorney-pro' => [
            __('Claim and edit your attorney profile', 'attorney-hub'),
            __('Respond to complaints (view only)', 'attorney-hub'),
            __('Upload professional documents', 'attorney-hub'),
            __('Enhanced profile visibility', 'attorney-hub'),
            __('All Verified Reviewer benefits', 'attorney-hub')
        ]
    ];
    
    return isset($benefits[$membership_slug]) ? $benefits[$membership_slug] : [];
}
