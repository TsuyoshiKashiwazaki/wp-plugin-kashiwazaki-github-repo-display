<?php
/**
 * Shortcodes class.
 *
 * @package Kashiwazaki_GitHub_Repo_Display
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KGRD_Shortcodes
 *
 * Registers and handles all plugin shortcodes.
 */
class KGRD_Shortcodes {

	/**
	 * Single instance of the class.
	 *
	 * @var KGRD_Shortcodes
	 */
	private static $instance = null;

	/**
	 * Get the single instance of the class.
	 *
	 * @return KGRD_Shortcodes
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
		$this->register_shortcodes();
	}

	/**
	 * Register all shortcodes.
	 */
	private function register_shortcodes() {
		add_shortcode( 'kashiwazaki_github_repo', array( $this, 'single_repo_shortcode' ) );
		add_shortcode( 'kashiwazaki_github_repos', array( $this, 'multiple_repos_shortcode' ) );
		add_shortcode( 'kashiwazaki_github_user_repos', array( $this, 'user_repos_shortcode' ) );
	}

	/**
	 * Single repository shortcode handler.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function single_repo_shortcode( $atts ) {
		// Parse shortcode attributes.
		$atts = shortcode_atts(
			array(
				'repo'     => '',
				'username' => $this->get_default_username(),
				'style'    => 'card',
				'license'  => '',
			),
			$atts,
			'kashiwazaki_github_repo'
		);

		// Validate required attributes.
		if ( empty( $atts['repo'] ) ) {
			return $this->error_message( __( 'Repository name is required.', 'kashiwazaki-github-repo-display' ) );
		}

		// Validate style.
		$valid_styles = array( 'card', 'minimal', 'badges-only' );
		if ( ! in_array( $atts['style'], $valid_styles, true ) ) {
			$atts['style'] = 'card';
		}

		// Check output cache.
		$cache_key = 'kgrd_output_' . md5( $atts['username'] . '|' . $atts['repo'] . '|' . $atts['style'] . '|' . $atts['license'] );
		$cached_output = get_transient( $cache_key );

		if ( false !== $cached_output ) {
			// Decode base64 encoded content
			return base64_decode( $cached_output );
		}

		// Display the repository.
		$display = KGRD_Repo_Display::get_instance();
		$output = $display->display_repository( $atts['username'], $atts['repo'], $atts['style'], $atts['license'] );

		// Sanitize output to remove invalid characters that can't be stored in database
		$output = $this->sanitize_for_cache( $output );

		// Cache the output with the same expiration as repository data.
		$cache_duration = $this->get_cache_duration();
		set_transient( $cache_key, $output, $cache_duration );

		// Decode before returning for display
		return base64_decode( $output );
	}

	/**
	 * Multiple repositories shortcode handler.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function multiple_repos_shortcode( $atts ) {
		// Parse shortcode attributes.
		$atts = shortcode_atts(
			array(
				'repos'    => '',
				'username' => $this->get_default_username(),
				'columns'  => 2,
			),
			$atts,
			'kashiwazaki_github_repos'
		);

		// Validate required attributes.
		if ( empty( $atts['repos'] ) ) {
			return $this->error_message( __( 'Repository names are required (comma-separated).', 'kashiwazaki-github-repo-display' ) );
		}

		// Parse repository names.
		$repos = array_map( 'trim', explode( ',', $atts['repos'] ) );
		$repos = array_filter( $repos ); // Remove empty values.

		if ( empty( $repos ) ) {
			return $this->error_message( __( 'No valid repository names provided.', 'kashiwazaki-github-repo-display' ) );
		}

		// Validate columns.
		$columns = absint( $atts['columns'] );
		$columns = max( 1, min( 4, $columns ) ); // Clamp between 1 and 4.

		// Check output cache.
		$cache_key = 'kgrd_output_' . md5( $atts['username'] . '|' . implode( ',', $repos ) . '|' . $columns );
		$cached_output = get_transient( $cache_key );

		if ( false !== $cached_output ) {
			// Decode base64 encoded content
			return base64_decode( $cached_output );
		}

		// Display the repositories.
		$display = KGRD_Repo_Display::get_instance();
		$output = $display->display_repositories_grid( $repos, $atts['username'], $columns );

		// Sanitize output to remove invalid characters that can't be stored in database
		$output = $this->sanitize_for_cache( $output );

		// Cache the output with the same expiration as repository data.
		$cache_duration = $this->get_cache_duration();
		set_transient( $cache_key, $output, $cache_duration );

		// Decode before returning for display
		return base64_decode( $output );
	}

	/**
	 * User repositories shortcode handler.
	 * Fetches all repositories for a GitHub user automatically.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function user_repos_shortcode( $atts ) {
		// Parse shortcode attributes.
		$atts = shortcode_atts(
			array(
				'username'      => $this->get_default_username(),
				'columns'       => 2,
				'limit'         => 30,
				'sort'          => 'updated',
				'direction'     => 'desc',
				'type'          => 'owner',
				'exclude_forks' => false,
			),
			$atts,
			'kashiwazaki_github_user_repos'
		);

		// Sanitize attributes.
		$username      = sanitize_text_field( $atts['username'] );
		$columns       = absint( $atts['columns'] );
		$columns       = max( 1, min( 4, $columns ) ); // Clamp between 1 and 4.
		$limit         = absint( $atts['limit'] );
		$limit         = max( 1, min( 100, $limit ) ); // Clamp between 1 and 100.
		$sort          = sanitize_text_field( $atts['sort'] );
		$direction     = sanitize_text_field( $atts['direction'] );
		$type          = sanitize_text_field( $atts['type'] );
		$exclude_forks = filter_var( $atts['exclude_forks'], FILTER_VALIDATE_BOOLEAN );

		// Validate sort.
		$valid_sorts = array( 'created', 'updated', 'pushed', 'full_name' );
		if ( ! in_array( $sort, $valid_sorts, true ) ) {
			$sort = 'updated';
		}

		// Validate direction.
		$valid_directions = array( 'asc', 'desc' );
		if ( ! in_array( $direction, $valid_directions, true ) ) {
			$direction = 'desc';
		}

		// Validate type.
		$valid_types = array( 'all', 'owner', 'public', 'private', 'member' );
		if ( ! in_array( $type, $valid_types, true ) ) {
			$type = 'owner';
		}

		// Check output cache.
		$cache_key = 'kgrd_output_' . md5(
			$username . '|user_repos|' . $columns . '|' . $limit . '|' .
			$sort . '|' . $direction . '|' . $type . '|' . ( $exclude_forks ? '1' : '0' )
		);
		$cached_output = get_transient( $cache_key );

		if ( false !== $cached_output ) {
			// Decode base64 encoded content
			return base64_decode( $cached_output );
		}

		// Fetch repositories from GitHub API.
		$api = KGRD_GitHub_API::get_instance();
		$repos_data = $api->get_user_repositories(
			$username,
			array(
				'type'      => $type,
				'sort'      => $sort,
				'direction' => $direction,
				'per_page'  => $limit,
			)
		);

		// Handle errors.
		if ( is_wp_error( $repos_data ) ) {
			return $this->error_message( $repos_data->get_error_message() );
		}

		// Check if we got any repositories.
		if ( empty( $repos_data ) ) {
			return $this->error_message(
				sprintf(
					/* translators: %s: GitHub username */
					__( 'No repositories found for user "%s".', 'kashiwazaki-github-repo-display' ),
					$username
				)
			);
		}

		// Filter out forks if requested.
		if ( $exclude_forks ) {
			$repos_data = array_filter( $repos_data, function( $repo ) {
				return empty( $repo['fork'] );
			} );
		}

		// Apply limit.
		$repos_data = array_slice( $repos_data, 0, $limit );

		// Re-index array after filtering.
		$repos_data = array_values( $repos_data );

		// If no repositories after filtering.
		if ( empty( $repos_data ) ) {
			return $this->error_message(
				sprintf(
					/* translators: %s: GitHub username */
					__( 'No repositories found for user "%s" with the specified filters.', 'kashiwazaki-github-repo-display' ),
					$username
				)
			);
		}

		// Display the repositories using existing grid display.
		// Pass repository data directly to avoid redundant API calls.
		$display = KGRD_Repo_Display::get_instance();
		$output = $display->display_repositories_grid( $repos_data, $username, $columns );

		// Sanitize output to remove invalid characters that can't be stored in database
		$output = $this->sanitize_for_cache( $output );

		// Cache the output with the same expiration as repository data.
		$cache_duration = $this->get_cache_duration();
		set_transient( $cache_key, $output, $cache_duration );

		// Decode before returning for display
		return base64_decode( $output );
	}

	/**
	 * Encode content for safe cache storage.
	 * Use base64 encoding to ensure all content can be stored regardless of special characters.
	 *
	 * @param string $content Content to encode.
	 * @return string Encoded content.
	 */
	private function sanitize_for_cache( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}

		// Base64 encode to ensure safe storage of any content
		return base64_encode( $content );
	}

	/**
	 * Get cache duration with jitter.
	 *
	 * @return int Cache duration in seconds.
	 */
	private function get_cache_duration() {
		$hours = get_option( 'kgrd_cache_expiration', 6 );
		$hours = absint( $hours );
		$hours = apply_filters( 'kgrd_api_cache_expiration', $hours );

		$base_seconds = $hours * HOUR_IN_SECONDS;

		// Check if jitter is enabled.
		$enable_jitter = get_option( 'kgrd_enable_cache_jitter', 1 );

		if ( $enable_jitter ) {
			// Get jitter percentage from settings (10%, 20%, or 30%).
			$jitter_percent = get_option( 'kgrd_cache_jitter_percent', 20 );
			$jitter_percent = $jitter_percent / 100; // Convert to decimal.

			// Calculate jitter range.
			$jitter_range = (int) ( $base_seconds * $jitter_percent );

			// Add random time within the jitter range.
			$jitter = wp_rand( -$jitter_range, $jitter_range );

			return $base_seconds + $jitter;
		}

		return $base_seconds;
	}

	/**
	 * Get the default GitHub username.
	 *
	 * @return string Default username.
	 */
	private function get_default_username() {
		$username = get_option( 'kgrd_default_username', 'TsuyoshiKashiwazaki' );

		// Apply filter to allow customization.
		return apply_filters( 'kgrd_default_username', $username );
	}

	/**
	 * Display an error message.
	 *
	 * @param string $message Error message.
	 * @return string HTML error message.
	 */
	private function error_message( $message ) {
		return sprintf(
			'<div class="kgrd-error"><p><strong>%s:</strong> %s</p></div>',
			esc_html__( 'Shortcode Error', 'kashiwazaki-github-repo-display' ),
			esc_html( $message )
		);
	}
}
