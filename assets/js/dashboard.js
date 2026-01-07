/**
 * Attorney Hub - Dashboard Scripts
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        initDashboard();
    });
    
    function initDashboard() {
        // Auto-dismiss success notices after 5 seconds
        setTimeout(function() {
            $('.aah-notice-success').fadeOut();
        }, 5000);
        
        // Confirm before submitting complaint
        $('.aah-complaint-form').on('submit', function(e) {
            var confirmed = confirm('Are you sure you want to submit this complaint? Please ensure all information is accurate.');
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
        });
        
        // Character counter for complaint text
        var $textarea = $('#complaint_text');
        if ($textarea.length) {
            $textarea.after('<div class="aah-char-counter"><span class="current">0</span> / <span class="min">50</span> characters</div>');
            
            $textarea.on('input', function() {
                var length = $(this).val().length;
                $('.aah-char-counter .current').text(length);
                
                if (length >= 50) {
                    $('.aah-char-counter').css('color', '#4CAF50');
                } else {
                    $('.aah-char-counter').css('color', '#666');
                }
            });
        }
        
        // File upload validation
        $('#evidence').on('change', function() {
            var file = this.files[0];
            if (file) {
                var fileSize = file.size / 1024 / 1024; // in MB
                var allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                
                if (fileSize > 5) {
                    alert('File size must not exceed 5MB');
                    $(this).val('');
                    return false;
                }
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload only PDF, JPG, or PNG files');
                    $(this).val('');
                    return false;
                }
            }
        });
    }
    
})(jQuery);
