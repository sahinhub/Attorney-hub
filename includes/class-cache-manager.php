<?php
/**
 * Cache Manager Class
 *
 * Provides caching utilities for performance optimization
 * Wraps WordPress object and transient caching systems
 *
 * @package    AttorneyHub
 * @subpackage Core
 * @since      1.0.0
 * @author     Attorney Accountability Hub Team
 */

/**
 * Class Attorney_Hub_Cache_Manager
 *
 * Manages caching for expensive operations
 *
 * @package    AttorneyHub
 * @subpackage Core
 * @since      1.0.0
 */
class Attorney_Hub_Cache_Manager extends Attorney_Hub_Module {

	/**
	 * Cache prefix for all Attorney Hub cache keys
	 *
	 * @var string
	 */
	const CACHE_PREFIX = 'attorney_hub_';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->module_name = 'Cache Manager';
		parent::__construct();
	}

	/**
	 * Initialize cache manager
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function init() {
		// Add action to clear cache on relevant events
		add_action('wp_insert_post', array($this, 'clear_related_caches'), 10, 2);
		add_action('wp_update_post', array($this, 'clear_related_caches'), 10, 2);
	}

	/**
	 * Get or set cached value
	 *
	 * If cached value exists, returns it. Otherwise calls the callback,
	 * caches the result, and returns it.
	 *
	 * @since 1.0.0
	 * 
	 * @param string   $key The cache key
	 * @param callable $callback The callback to generate the value
	 * @param int      $expiration Cache expiration time in seconds (default 30 minutes)
	 * @return mixed The cached or generated value
	 * 
	 * @example
	 * ```php
	 * $memberships = Attorney_Hub_Cache_Manager::remember(
	 *     "user_memberships_{$user_id}",
	 *     function() use ($user_id) {
	 *         return get_user_memberships($user_id);
	 *     },
	 *     1800
	 * );
	 * ```
	 */
	public static function remember($key, $callback, $expiration = 1800) {
		$cache_key = self::CACHE_PREFIX . $key;
		$cached_value = wp_cache_get($cache_key);

		if ($cached_value !== false) {
			return $cached_value;
		}

		$value = call_user_func($callback);
		wp_cache_set($cache_key, $value, '', $expiration);

		return $value;
	}

	/**
	 * Get cached value
	 *
	 * Returns cached value if it exists, null otherwise
	 *
	 * @since 1.0.0
	 * 
	 * @param string $key The cache key
	 * @return mixed The cached value or false if not found
	 */
	public static function get($key) {
		$cache_key = self::CACHE_PREFIX . $key;
		return wp_cache_get($cache_key);
	}

	/**
	 * Set cached value
	 *
	 * Sets a value in the cache
	 *
	 * @since 1.0.0
	 * 
	 * @param string $key The cache key
	 * @param mixed  $value The value to cache
	 * @param int    $expiration Cache expiration time in seconds (default 30 minutes)
	 * @return bool True on success, false on failure
	 */
	public static function set($key, $value, $expiration = 1800) {
		$cache_key = self::CACHE_PREFIX . $key;
		return wp_cache_set($cache_key, $value, '', $expiration);
	}

	/**
	 * Delete cached value
	 *
	 * Removes a value from the cache
	 *
	 * @since 1.0.0
	 * 
	 * @param string $key The cache key to delete
	 * @return bool True on success, false on failure
	 */
	public static function forget($key) {
		$cache_key = self::CACHE_PREFIX . $key;
		return wp_cache_delete($cache_key);
	}

	/**
	 * Clear all Attorney Hub cache
	 *
	 * Flushes all cached values related to Attorney Hub
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function clear_all() {
		global $wpdb;

		// Clear object cache
		wp_cache_flush();

		// Clear transients
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'%' . self::CACHE_PREFIX . '%'
			)
		);
	}

	/**
	 * Clear cache for a specific user
	 *
	 * Removes all cached data related to a user
	 *
	 * @since 1.0.0
	 * 
	 * @param int $user_id The user ID
	 * @return void
	 */
	public static function clear_user_cache($user_id) {
		self::forget("user_memberships_{$user_id}");
		self::forget("user_complaints_{$user_id}");
		self::forget("user_listings_{$user_id}");
	}

	/**
	 * Clear cache for a specific listing
	 *
	 * Removes all cached data related to a listing
	 *
	 * @since 1.0.0
	 * 
	 * @param int $listing_id The listing ID
	 * @return void
	 */
	public static function clear_listing_cache($listing_id) {
		self::forget("listing_{$listing_id}");
		self::forget("listing_complaints_{$listing_id}");
		self::forget("listing_reviews_{$listing_id}");
	}

	/**
	 * Clear related caches when a post is updated
	 *
	 * @since 1.0.0
	 * 
	 * @param int      $post_id The post ID
	 * @param WP_Post  $post The post object
	 * @return void
	 */
	public function clear_related_caches($post_id, $post) {
		if ($post->post_type === 'attorney_complaint') {
			// Clear caches related to complaints
			self::forget("listing_complaints_{$post->post_parent}");
			self::clear_user_cache($post->post_author);
		} elseif ($post->post_type === 'at_biz_dir') {
			// Clear caches related to attorney listings
			self::clear_listing_cache($post_id);
		}
	}
}
