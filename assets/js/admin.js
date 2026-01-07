/**
 * Attorney Hub Admin JavaScript
 * 
 * Admin-side functionality for the Attorney Hub plugin
 */

(function($) {
	'use strict';

	/**
	 * Attorney Hub Admin Object
	 */
	var AttorneyHubAdmin = {

		/**
		 * Initialize the admin module
		 */
		init: function() {
			this.setupEventListeners();
			this.setupAjax();
		},

		/**
		 * Setup event listeners
		 */
		setupEventListeners: function() {
			// Add admin event listeners
		},

		/**
		 * Setup AJAX defaults
		 */
		setupAjax: function() {
			if (typeof attorneyHubAdmin !== 'undefined') {
				$.ajaxSetup({
					headers: {
						'X-WP-Nonce': attorneyHubAdmin.nonce
					}
				});
			}
		},

		/**
		 * Show admin notice
		 * 
		 * @param {string} message The message to display
		 * @param {string} type The notice type (success, error, warning, info)
		 */
		showNotice: function(message, type) {
			type = type || 'info';

			var noticeClass = 'notice notice-' + type + ' is-dismissible';
			var $notice = $('<div class="' + noticeClass + '"><p>' + message + '</p></div>');

			$('.wrap').prepend($notice);

			// Make it dismissible
			$notice.find('.notice-dismiss').on('click', function() {
				$notice.fadeOut(function() {
					$notice.remove();
				});
			});

			setTimeout(function() {
				if ($notice.is(':visible')) {
					$notice.fadeOut(function() {
						$notice.remove();
					});
				}
			}, 5000);
		}
	};

	// Initialize when document is ready
	$(document).ready(function() {
		AttorneyHubAdmin.init();
	});

	// Export for external use
	window.AttorneyHubAdmin = AttorneyHubAdmin;

})(jQuery);
