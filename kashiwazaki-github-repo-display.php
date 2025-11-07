<?php
/**
 * Plugin Name: Kashiwazaki GitHub Repository Display
 * Plugin URI: https://www.tsuyoshikashiwazaki.jp/
 * Description: Display GitHub repository information dynamically on your WordPress site. Simply specify a repository name to fetch and display the latest information from the GitHub API.
 * Version: 1.0.0-dev
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
define( 'KGRD_VERSION', '1.0.0-dev' );
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
		require_once KGRD_PLUGIN_DIR . 'admin/class-admin-settings.php';
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init' ) );

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
		// Clear all cached data.
		$this->clear_all_cache();

		// Flush rewrite rules.
		flush_rewrite_rules();
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
