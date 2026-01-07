<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('mepr_get_current_user')) {
    echo '<p>' . __('MemberPress is not active.', 'attorney-hub') . '</p>';
    return;
}

$user = mepr_get_current_user();
$transactions = $user->transactions();
?>
<div class="aah-billing-tab">
    <h2><?php _e('Billing History', 'attorney-hub'); ?></h2>
    
    <?php if (!empty($transactions)): ?>
        <div class="aah-table-responsive">
            <table class="aah-billing-table">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'attorney-hub'); ?></th>
                        <th><?php _e('Description', 'attorney-hub'); ?></th>
                        <th><?php _e('Amount', 'attorney-hub'); ?></th>
                        <th><?php _e('Status', 'attorney-hub'); ?></th>
                        <th><?php _e('Invoice', 'attorney-hub'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): 
                        $product = $txn->product();
                    ?>
                    <tr>
                        <td data-label="<?php _e('Date', 'attorney-hub'); ?>">
                            <?php echo date_i18n('M j, Y', strtotime($txn->created_at)); ?>
                        </td>
                        <td data-label="<?php _e('Description', 'attorney-hub'); ?>">
                            <strong><?php echo esc_html($product->post_title); ?></strong>
                            <?php if ($txn->subscription_id): ?>
                                <br><small><?php _e('Subscription Payment', 'attorney-hub'); ?></small>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?php _e('Amount', 'attorney-hub'); ?>">
                            <strong><?php echo MeprAppHelper::format_currency($txn->total); ?></strong>
                        </td>
                        <td data-label="<?php _e('Status', 'attorney-hub'); ?>">
                            <span class="aah-status-badge status-<?php echo esc_attr(strtolower($txn->status)); ?>">
                                <?php echo esc_html(ucfirst($txn->status)); ?>
                            </span>
                        </td>
                        <td data-label="<?php _e('Invoice', 'attorney-hub'); ?>">
                            <?php if ($txn->status == MeprTransaction::$complete_str): ?>
                                <a href="<?php echo esc_url($txn->invoice_url()); ?>" target="_blank" class="aah-invoice-link">
                                    <i class="la la-file-pdf"></i> <?php _e('View', 'attorney-hub'); ?>
                                </a>
                            <?php else: ?>
                                <span class="aah-text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="aah-empty-state">
            <i class="la la-file-invoice"></i>
            <p><?php _e('No billing history yet.', 'attorney-hub'); ?></p>
        </div>
    <?php endif; ?>
</div>
