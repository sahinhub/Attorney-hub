<?php
if (!defined('ABSPATH')) exit;

$attorneys = aah_get_attorneys_list();
$error = isset($_GET['error']) ? urldecode($_GET['error']) : '';
?>
<div class="aah-complaint-form-wrapper">
    <h2><?php _e('File a Complaint', 'attorney-hub'); ?></h2>
    <p><?php _e('Please provide detailed information about your complaint. All submissions are reviewed by our team before being published.', 'attorney-hub'); ?></p>
    
    <?php if ($error): ?>
        <div class="aah-notice aah-notice-error">
            <?php echo esc_html($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="aah-complaint-form">
        <input type="hidden" name="action" value="submit_complaint">
        <?php wp_nonce_field('submit_complaint', 'complaint_nonce'); ?>
        
        <div class="aah-form-group">
            <label for="attorney_id"><?php _e('Select Attorney', 'attorney-hub'); ?> <span class="required">*</span></label>
            <select name="attorney_id" id="attorney_id" required class="aah-form-control">
                <option value=""><?php _e('-- Select Attorney --', 'attorney-hub'); ?></option>
                <?php foreach ($attorneys as $attorney): ?>
                    <option value="<?php echo esc_attr($attorney->ID); ?>">
                        <?php echo esc_html($attorney->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="aah-form-group">
            <label for="complaint_text"><?php _e('Describe Your Complaint', 'attorney-hub'); ?> <span class="required">*</span></label>
            <textarea 
                name="complaint_text" 
                id="complaint_text" 
                rows="8" 
                required 
                minlength="50"
                class="aah-form-control"
                placeholder="<?php _e('Please provide detailed information about your complaint...', 'attorney-hub'); ?>"
            ></textarea>
            <small class="aah-form-help"><?php _e('Minimum 50 characters required', 'attorney-hub'); ?></small>
        </div>
        
        <div class="aah-form-group">
            <label for="evidence"><?php _e('Upload Evidence (Optional)', 'attorney-hub'); ?></label>
            <input 
                type="file" 
                name="evidence" 
                id="evidence" 
                accept=".pdf,.jpg,.jpeg,.png"
                class="aah-form-control"
            >
            <small class="aah-form-help"><?php _e('Accepted formats: PDF, JPG, PNG (Max 5MB)', 'attorney-hub'); ?></small>
        </div>
        
        <div class="aah-form-actions">
            <button type="submit" class="aah-btn aah-btn-primary aah-btn-lg">
                <i class="la la-paper-plane"></i> <?php _e('Submit Complaint', 'attorney-hub'); ?>
            </button>
            <a href="<?php echo esc_url(get_permalink(ATTORNEY_HUB_DASHBOARD_ID)); ?>" class="aah-btn aah-btn-secondary">
                <?php _e('Cancel', 'attorney-hub'); ?>
            </a>
        </div>
    </form>
</div>
