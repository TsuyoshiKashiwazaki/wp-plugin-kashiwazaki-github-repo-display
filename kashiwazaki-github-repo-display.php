<?php
/**
 * Plugin Name: Kashiwazaki GitHub Repository Display
 * Plugin URI: https://www.tsuyoshikashiwazaki.jp/
 * Description: Display GitHub repository information dynamically on your WordPress site. Simply specify a repository name to fetch and display the latest information from the GitHub API.
 * Version: 1.0.1
 * Author: Tsuyoshi Kashiwazaki
 * Author URI: https://www.tsuyoshikashiwazaki.jp/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kashiwazaki-github-repo-display
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 *
 * @package Kashiwazaki_GitHub_Repo_Display
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'KGRD_VERSION', '1.0.1' );
define( 'KGRD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KGRD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'KGRD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class.
 */
class Kashiwazaki_GitHub_Repo_Display {

	/**
	 * Single instance of the class.
	 *
	 * @var Kashiwazaki_GitHub_Repo_Display
	 */
	private static $instance = null;

	/**
	 * Get the single instance of the class.
	 *
	 * @return Kashiwazaki_GitHub_Repo_Display
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required dependencies.
	 */
	private function load_dependencies() {
		require_once KGRD_PLUGIN_DIR . 'includes/class-github-api.php';
		require_once KGRD_PLUGIN_DIR . 'includes/class-repo-display.php';
		require_once KGRD_PLUGIN_DIR . 'includes/class-shortcodes.php';
		require_once KGRD_PLUGIN_DIR . 'includes/class-repo-detail-page.php';
		require_once KGRD_PLUGIN_DIR . 'admin/class-admin-settings.php';
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init' ) );

		// Register cron hook.
		add_action( 'kgrd_cache_refresh_event', array( $this, 'run_cache_refresh' ) );

		// Add custom cron schedule.
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );

		// Register activation and deactivation hooks.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Load plugin textdomain for translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'kashiwazaki-github-repo-display',
			false,
			dirname( KGRD_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Initialize plugin components.
	 */
	public function init() {
		// Initialize shortcodes.
		KGRD_Shortcodes::get_instance();

		// Initialize repo display (needed for CSS enqueue).
		KGRD_Repo_Display::get_instance();

		// Initialize repository detail page.
		KGRD_Repo_Detail_Page::get_instance();

		// Initialize admin settings (needed for both frontend and admin).
		KGRD_Admin_Settings::get_instance();
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		// Set default options.
		if ( ! get_option( 'kgrd_default_username' ) ) {
			update_option( 'kgrd_default_username', 'TsuyoshiKashiwazaki' );
		}
		if ( ! get_option( 'kgrd_cache_expiration' ) ) {
			update_option( 'kgrd_cache_expiration', 6 );
		}

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		// Clear scheduled cron event.
		$this->clear_cron_schedule();

		// Clear all cached data.
		$this->clear_all_cache();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Add custom cron schedules.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array Modified schedules.
	 */
	public function add_cron_schedules( $schedules ) {
		$interval = get_option( 'kgrd_cron_interval', 6 );
		$interval = absint( $interval );
		$interval = max( 1, min( 24, $interval ) );

		$schedules['kgrd_custom_interval'] = array(
			'interval' => $interval * HOUR_IN_SECONDS,
			'display'  => sprintf( __( 'Every %d hours (KGRD)', 'kashiwazaki-github-repo-display' ), $interval ),
		);

		return $schedules;
	}

	/**
	 * Schedule the cron event.
	 */
	public static function schedule_cron_event() {
		if ( ! wp_next_scheduled( 'kgrd_cache_refresh_event' ) ) {
			wp_schedule_event( time(), 'kgrd_custom_interval', 'kgrd_cache_refresh_event' );
		}
	}

	/**
	 * Clear the cron schedule.
	 */
	public static function clear_cron_schedule() {
		$timestamp = wp_next_scheduled( 'kgrd_cache_refresh_event' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'kgrd_cache_refresh_event' );
		}
	}

	/**
	 * Reschedule the cron event (called when settings change).
	 */
	public static function reschedule_cron_event() {
		self::clear_cron_schedule();

		$enabled = get_option( 'kgrd_enable_cron_refresh', 0 );
		if ( $enabled ) {
			self::schedule_cron_event();
		}
	}

	/**
	 * Run the cache refresh process.
	 */
	public function run_cache_refresh() {
		// Check if cron refresh is enabled.
		$enabled = get_option( 'kgrd_enable_cron_refresh', 0 );
		if ( ! $enabled ) {
			return;
		}

		// Get tracked repositories.
		$tracked_repos = get_option( 'kgrd_tracked_repositories', array() );
		if ( empty( $tracked_repos ) ) {
			return;
		}

		$api = KGRD_GitHub_API::get_instance();

		// Clear output cache for tracked repositories.
		$this->clear_output_cache_for_repos( $tracked_repos );

		foreach ( $tracked_repos as $repo_key => $repo_info ) {
			// Fetch fresh data from GitHub API.
			$api->get_repository( $repo_info['username'], $repo_info['repo'] );

			// Small delay to avoid rate limiting.
			usleep( 500000 ); // 0.5 seconds.
		}

		// Update last refresh time.
		update_option( 'kgrd_last_cron_refresh', time() );
	}

	/**
	 * Clear output cache for specific repositories.
	 *
	 * @param array $repos Array of repository info.
	 */
	private function clear_output_cache_for_repos( $repos ) {
		global $wpdb;

		// Delete output transients that contain these repositories.
		// Since we can't easily match specific repos, clear all output cache.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_kgrd_output_%',
				'_transient_timeout_kgrd_output_%'
			)
		);
	}

	/**
	 * Clear all cached repository data.
	 */
	private function clear_all_cache() {
		global $wpdb;

		// Delete all transients with our prefix.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_kgrd_repo_%',
				'_transient_timeout_kgrd_repo_%'
			)
		);
	}
}

/**
 * Get the main instance of the plugin.
 *
 * @return Kashiwazaki_GitHub_Repo_Display
 */
function kgrd() {
	return Kashiwazaki_GitHub_Repo_Display::get_instance();
}

// Initialize the plugin.
kgrd();
