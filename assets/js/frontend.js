/**
 * Attorney Hub Frontend JavaScript
 * 
 * Main frontend functionality for the Attorney Hub plugin
 */

(function($) {
	'use strict';

	/**
	 * Attorney Hub Frontend Object
	 */
	var AttorneyHubFrontend = {

		/**
		 * Initialize the frontend module
		 */
		init: function() {
			this.setupEventListeners();
			this.setupAjax();
		},

		/**
		 * Setup event listeners
		 */
		setupEventListeners: function() {
			// Add event listeners as needed
		},

		/**
		 * Setup AJAX defaults
		 */
		setupAjax: function() {
			if (typeof attorneyHubFrontend !== 'undefined') {
				$.ajaxSetup({
					headers: {
						'X-WP-Nonce': attorneyHubFrontend.nonce
					}
				});
			}
		},

		/**
		 * Show notification to user
		 * 
		 * @param {string} message The message to display
		 * @param {string} type The notification type (success, error, warning, info)
		 */
		showNotification: function(message, type) {
			type = type || 'info';

			var alertClass = 'attorney-hub-alert attorney-hub-alert-' + type;
			var $alert = $('<div class="' + alertClass + '">' + message + '</div>');

			$('body').prepend($alert);

			setTimeout(function() {
				$alert.fadeOut(function() {
					$alert.remove();
				});
			}, 5000);
		},

		/**
		 * Show loading indicator
		 */
		showLoading: function() {
			$('body').append(
				'<div class="attorney-hub-loading"><div class="attorney-hub-spinner"></div></div>'
			);
		},

		/**
		 * Hide loading indicator
		 */
		hideLoading: function() {
			$('.attorney-hub-loading').remove();
		}
	};

	// Initialize when document is ready
	$(document).ready(function() {
		AttorneyHubFrontend.init();
	});

	// Export for external use
	window.AttorneyHubFrontend = AttorneyHubFrontend;

})(jQuery);
