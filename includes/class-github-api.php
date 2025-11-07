<?php
/**
 * GitHub API communication class.
 *
 * @package Kashiwazaki_GitHub_Repo_Display
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KGRD_GitHub_API
 *
 * Handles all communication with the GitHub REST API v3.
 */
class KGRD_GitHub_API {

	/**
	 * GitHub API base URL.
	 *
	 * @var string
	 */
	private $api_base = 'https://api.github.com';

	/**
	 * Single instance of the class.
	 *
	 * @var KGRD_GitHub_API
	 */
	private static $instance = null;

	/**
	 * Get the single instance of the class.
	 *
	 * @return KGRD_GitHub_API
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
		// Private constructor to prevent direct instantiation.
	}

	/**
	 * Get all repositories for a GitHub user.
	 *
	 * @param string $username GitHub username.
	 * @param array  $args Optional. Query arguments.
	 * @return array|WP_Error Array of repository data or error.
	 */
	public function get_user_repositories( $username, $args = array() ) {
		// Sanitize inputs.
		$username = sanitize_text_field( $username );

		// Parse arguments.
		$defaults = array(
			'type'      => 'owner',
			'sort'      => 'updated',
			'direction' => 'desc',
			'per_page'  => 100,
		);
		$args = wp_parse_args( $args, $defaults );

		// Check cache first.
		$cache_key = $this->get_user_repos_cache_key( $username, $args );
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		// Build API URL with query parameters.
		$url = sprintf( '%s/users/%s/repos', $this->api_base, $username );
		$url = add_query_arg(
			array(
				'type'      => $args['type'],
				'sort'      => $args['sort'],
				'direction' => $args['direction'],
				'per_page'  => $args['per_page'],
			),
			$url
		);

		$headers = array(
			'Accept' => 'application/vnd.github.v3+json',
		);

		// Add authentication if token is available.
		$token = get_option( 'kgrd_github_token', '' );
		if ( ! empty( $token ) ) {
			// Use Bearer for new tokens (github_pat_*), token for classic tokens.
			if ( strpos( $token, 'github_pat_' ) === 0 || strpos( $token, 'ghp_' ) === 0 ) {
				$headers['Authorization'] = 'Bearer ' . $token;
			} else {
				$headers['Authorization'] = 'token ' . $token;
			}
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => $headers,
			)
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$body          = wp_remote_retrieve_body( $response );

		// Handle rate limiting.
		if ( 403 === $response_code ) {
			$headers         = wp_remote_retrieve_headers( $response );
			$remaining       = isset( $headers['x-ratelimit-remaining'] ) ? $headers['x-ratelimit-remaining'] : 0;
			$rate_limit_reset = isset( $headers['x-ratelimit-reset'] ) ? $headers['x-ratelimit-reset'] : 0;

			if ( 0 == $remaining ) {
				return new WP_Error(
					'rate_limit_exceeded',
					sprintf(
						/* translators: %s: Time until rate limit reset */
						__( 'GitHub API rate limit exceeded. Resets at %s.', 'kashiwazaki-github-repo-display' ),
						date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $rate_limit_reset )
					)
				);
			}
		}

		// Handle not found.
		if ( 404 === $response_code ) {
			return new WP_Error(
				'user_not_found',
				sprintf(
					/* translators: %s: username */
					__( 'GitHub user "%s" not found.', 'kashiwazaki-github-repo-display' ),
					$username
				)
			);
		}

		// Handle other errors.
		if ( $response_code >= 400 ) {
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'GitHub API error: HTTP %d', 'kashiwazaki-github-repo-display' ),
					$response_code
				)
			);
		}

		$data = json_decode( $body, true );

		if ( null === $data || ! is_array( $data ) ) {
			return new WP_Error(
				'invalid_response',
				__( 'Invalid response from GitHub API.', 'kashiwazaki-github-repo-display' )
			);
		}

		// Cache the result.
		$cache_duration = $this->get_cache_expiration();
		set_transient( $cache_key, $data, $cache_duration );

		return $data;
	}

	/**
	 * Get repository information.
	 *
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @return array|WP_Error Repository data or error.
	 */
	public function get_repository( $username, $repo ) {
		// Sanitize inputs.
		$username = sanitize_text_field( $username );
		$repo     = sanitize_text_field( $repo );

		// Note: Repository-level caching is disabled. Shortcode output caching provides sufficient performance.

		// Fetch from API.
		$url      = sprintf( '%s/repos/%s/%s', $this->api_base, $username, $repo );
		$headers  = array(
			'Accept' => 'application/vnd.github.v3+json',
		);

		// Add authentication if token is available.
		$token = get_option( 'kgrd_github_token', '' );
		if ( ! empty( $token ) ) {
			// Use Bearer for new tokens (github_pat_*), token for classic tokens.
			if ( strpos( $token, 'github_pat_' ) === 0 || strpos( $token, 'ghp_' ) === 0 ) {
				$headers['Authorization'] = 'Bearer ' . $token;
			} else {
				$headers['Authorization'] = 'token ' . $token;
			}
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => $headers,
			)
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$body          = wp_remote_retrieve_body( $response );

		// Handle rate limiting.
		if ( 403 === $response_code ) {
			$headers         = wp_remote_retrieve_headers( $response );
			$remaining       = isset( $headers['x-ratelimit-remaining'] ) ? $headers['x-ratelimit-remaining'] : 0;
			$rate_limit_reset = isset( $headers['x-ratelimit-reset'] ) ? $headers['x-ratelimit-reset'] : 0;

			if ( 0 == $remaining ) {
				return new WP_Error(
					'rate_limit_exceeded',
					sprintf(
						/* translators: %s: Time until rate limit reset */
						__( 'GitHub API rate limit exceeded. Resets at %s.', 'kashiwazaki-github-repo-display' ),
						date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $rate_limit_reset )
					)
				);
			}
		}

		// Handle not found.
		if ( 404 === $response_code ) {
			return new WP_Error(
				'repo_not_found',
				sprintf(
					/* translators: 1: username, 2: repository name */
					__( 'Repository "%1$s/%2$s" not found.', 'kashiwazaki-github-repo-display' ),
					$username,
					$repo
				)
			);
		}

		// Handle other errors.
		if ( $response_code >= 400 ) {
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'GitHub API error: HTTP %d', 'kashiwazaki-github-repo-display' ),
					$response_code
				)
			);
		}

		$data = json_decode( $body, true );

		if ( null === $data ) {
			return new WP_Error(
				'invalid_response',
				__( 'Invalid response from GitHub API.', 'kashiwazaki-github-repo-display' )
			);
		}

		// Get README content.
		$readme = $this->get_readme( $username, $repo );
		if ( ! is_wp_error( $readme ) ) {
			$data['readme_title'] = $this->extract_readme_title( $readme );
		}

		// Get readme.txt content if it exists (for WordPress plugins/themes).
		$readme_txt = $this->get_file_contents( $username, $repo, 'readme.txt' );
		if ( ! is_wp_error( $readme_txt ) && ! empty( $readme_txt ) ) {
			// Store the entire readme.txt content
			$data['readme_txt_content'] = $readme_txt;
		}

		// Get latest tag/release for download URL.
		$latest_tag = $this->get_latest_tag( $username, $repo );
		if ( ! is_wp_error( $latest_tag ) && ! empty( $latest_tag ) ) {
			$data['download_url'] = sprintf( 'https://github.com/%s/%s/archive/refs/tags/%s.zip', $username, $repo, $latest_tag );
		} else {
			// Fallback to default branch zip.
			$default_branch = ! empty( $data['default_branch'] ) ? $data['default_branch'] : 'main';
			$data['download_url'] = sprintf( 'https://github.com/%s/%s/archive/refs/heads/%s.zip', $username, $repo, $default_branch );
		}

		// Note: Repository data caching is disabled due to data validation issues.
		// Shortcode output caching (which works perfectly) is sufficient for performance.
		return $data;
	}

	/**
	 * Get file contents from repository.
	 *
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @param string $path File path in repository.
	 * @return string|WP_Error File content or error.
	 */
	public function get_file_contents( $username, $repo, $path ) {
		$username = sanitize_text_field( $username );
		$repo     = sanitize_text_field( $repo );
		$path     = sanitize_text_field( $path );

		$url     = sprintf( '%s/repos/%s/%s/contents/%s', $this->api_base, $username, $repo, $path );
		$headers = array(
			'Accept' => 'application/vnd.github.v3.raw',
		);

		// Add authentication if token is available.
		$token = get_option( 'kgrd_github_token', '' );
		if ( ! empty( $token ) ) {
			// Use Bearer for new tokens (github_pat_*), token for classic tokens.
			if ( strpos( $token, 'github_pat_' ) === 0 || strpos( $token, 'ghp_' ) === 0 ) {
				$headers['Authorization'] = 'Bearer ' . $token;
			} else {
				$headers['Authorization'] = 'token ' . $token;
			}
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => $headers,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 404 === $response_code ) {
			return new WP_Error( 'file_not_found', __( 'File not found.', 'kashiwazaki-github-repo-display' ) );
		}

		if ( $response_code >= 400 ) {
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'GitHub API error: HTTP %d', 'kashiwazaki-github-repo-display' ),
					$response_code
				)
			);
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Get repository README content.
	 *
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @return string|WP_Error README content or error.
	 */
	public function get_readme( $username, $repo ) {
		$username = sanitize_text_field( $username );
		$repo     = sanitize_text_field( $repo );

		$url     = sprintf( '%s/repos/%s/%s/readme', $this->api_base, $username, $repo );
		$headers = array(
			'Accept' => 'application/vnd.github.v3.raw',
		);

		// Add authentication if token is available.
		$token = get_option( 'kgrd_github_token', '' );
		if ( ! empty( $token ) ) {
			// Use Bearer for new tokens (github_pat_*), token for classic tokens.
			if ( strpos( $token, 'github_pat_' ) === 0 || strpos( $token, 'ghp_' ) === 0 ) {
				$headers['Authorization'] = 'Bearer ' . $token;
			} else {
				$headers['Authorization'] = 'token ' . $token;
			}
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => $headers,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 404 === $response_code ) {
			return new WP_Error( 'readme_not_found', __( 'README not found.', 'kashiwazaki-github-repo-display' ) );
		}

		if ( $response_code >= 400 ) {
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'GitHub API error: HTTP %d', 'kashiwazaki-github-repo-display' ),
					$response_code
				)
			);
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Extract title from README content.
	 *
	 * @param string $readme_content README markdown content.
	 * @return string Title or empty string.
	 */
	private function extract_readme_title( $readme_content ) {
		if ( empty( $readme_content ) ) {
			return '';
		}

		// Match first H1 heading (# Title or ===).
		$lines = explode( "\n", $readme_content );
		foreach ( $lines as $index => $line ) {
			$line = trim( $line );

			// Check for # Title format.
			if ( preg_match( '/^#\s+(.+)$/u', $line, $matches ) ) {
				return trim( $matches[1] );
			}

			// Check for underline format (next line is ===).
			if ( ! empty( $line ) && isset( $lines[ $index + 1 ] ) ) {
				$next_line = trim( $lines[ $index + 1 ] );
				if ( preg_match( '/^=+$/', $next_line ) ) {
					return $line;
				}
			}
		}

		return '';
	}

	/**
	 * Extract Description section from WordPress plugin/theme readme.txt file.
	 *
	 * @param string $readme_txt_content readme.txt file content.
	 * @return string Description or empty string.
	 */
	private function extract_readme_txt_description( $readme_txt_content ) {
		if ( empty( $readme_txt_content ) ) {
			return '';
		}

		// Match Description section in readme.txt - extract full Description section
		// Pattern: == Description == followed by content until next == section (at line start) or EOF
		// Support both \n and \r\n line endings
		if ( preg_match( '/==\s*Description\s*==\s*[\r\n]+(.*?)(?:[\r\n]+^==|\z)/ism', $readme_txt_content, $matches ) ) {
			$description = trim( $matches[1] );
			// Normalize line endings to \n
			$description = str_replace( "\r\n", "\n", $description );
			// Remove multiple consecutive line breaks (more than 2)
			$description = preg_replace( '/\n{3,}/', "\n\n", $description );
			return trim( $description );
		}

		return '';
	}

	/**
	 * Get latest release for a repository.
	 *
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @return array|WP_Error Latest release data or error.
	 */
	public function get_latest_release( $username, $repo ) {
		$username = sanitize_text_field( $username );
		$repo     = sanitize_text_field( $repo );

		$url     = sprintf( '%s/repos/%s/%s/releases/latest', $this->api_base, $username, $repo );
		$headers = array(
			'Accept' => 'application/vnd.github.v3+json',
		);

		// Add authentication if token is available.
		$token = get_option( 'kgrd_github_token', '' );
		if ( ! empty( $token ) ) {
			// Use Bearer for new tokens (github_pat_*), token for classic tokens.
			if ( strpos( $token, 'github_pat_' ) === 0 || strpos( $token, 'ghp_' ) === 0 ) {
				$headers['Authorization'] = 'Bearer ' . $token;
			} else {
				$headers['Authorization'] = 'token ' . $token;
			}
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => $headers,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 404 === $response_code ) {
			// No releases found - this is not an error, just return empty.
			return array();
		}

		if ( $response_code >= 400 ) {
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'GitHub API error: HTTP %d', 'kashiwazaki-github-repo-display' ),
					$response_code
				)
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return array();
		}

		return $data;
	}

	/**
	 * Get latest tag for a repository.
	 *
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @return string|WP_Error Latest tag name or error.
	 */
	public function get_latest_tag( $username, $repo ) {
		$username = sanitize_text_field( $username );
		$repo     = sanitize_text_field( $repo );

		$url     = sprintf( '%s/repos/%s/%s/tags', $this->api_base, $username, $repo );
		$headers = array(
			'Accept' => 'application/vnd.github.v3+json',
		);

		// Add authentication if token is available.
		$token = get_option( 'kgrd_github_token', '' );
		if ( ! empty( $token ) ) {
			// Use Bearer for new tokens (github_pat_*), token for classic tokens.
			if ( strpos( $token, 'github_pat_' ) === 0 || strpos( $token, 'ghp_' ) === 0 ) {
				$headers['Authorization'] = 'Bearer ' . $token;
			} else {
				$headers['Authorization'] = 'token ' . $token;
			}
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => $headers,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 404 === $response_code ) {
			return new WP_Error( 'tags_not_found', __( 'No tags found.', 'kashiwazaki-github-repo-display' ) );
		}

		if ( $response_code >= 400 ) {
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'GitHub API error: HTTP %d', 'kashiwazaki-github-repo-display' ),
					$response_code
				)
			);
		}

		$tags = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $tags ) || ! is_array( $tags ) ) {
			return '';
		}

		// Return the first tag (latest).
		return $tags[0]['name'];
	}

	/**
	 * Get cache key for a repository.
	 *
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @return string Cache key.
	 */
	private function get_cache_key( $username, $repo ) {
		// Use md5 hash to keep key length under 64 chars (WordPress option name limit).
		return 'kgrd_repo_' . md5( strtolower( $username ) . '|' . strtolower( $repo ) );
	}

	/**
	 * Get cache key for user repositories list.
	 *
	 * @param string $username GitHub username.
	 * @param array  $args Query arguments.
	 * @return string Cache key.
	 */
	private function get_user_repos_cache_key( $username, $args ) {
		// Create a unique key based on username and query parameters.
		$key_data = strtolower( $username ) . '|' . serialize( $args );
		return 'kgrd_user_repos_' . md5( $key_data );
	}

	/**
	 * Get cache expiration time in seconds with jitter.
	 *
	 * Adds random variance to prevent cache stampede when multiple
	 * repositories expire simultaneously.
	 *
	 * @return int Expiration time in seconds.
	 */
	private function get_cache_expiration() {
		$hours = get_option( 'kgrd_cache_expiration', 6 );
		$hours = absint( $hours );

		// Apply filter to allow customization.
		$hours = apply_filters( 'kgrd_api_cache_expiration', $hours );

		$base_seconds = $hours * HOUR_IN_SECONDS;

		// Check if jitter is enabled.
		$enable_jitter = get_option( 'kgrd_enable_cache_jitter', 1 );

		if ( $enable_jitter ) {
			// Get jitter percentage from settings (10%, 20%, or 30%).
			$jitter_percent = get_option( 'kgrd_cache_jitter_percent', 20 );
			$jitter_percent = $jitter_percent / 100; // Convert to decimal (e.g., 20 -> 0.2)

			// Calculate jitter range.
			$jitter_range = (int) ( $base_seconds * $jitter_percent );

			// Add random time within the jitter range.
			$jitter = wp_rand( -$jitter_range, $jitter_range );

			return $base_seconds + $jitter;
		}

		return $base_seconds;
	}

	/**
	 * Clear cache for a specific repository.
	 *
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @return bool True on success, false on failure.
	 */
	public function clear_cache( $username, $repo ) {
		$cache_key = $this->get_cache_key( $username, $repo );
		return delete_transient( $cache_key );
	}

	/**
	 * Clear all cached repositories.
	 *
	 * @return int Number of cache entries cleared.
	 */
	public function clear_all_cache() {
		global $wpdb;

		// Clear shortcode output cache (repository data cache is disabled)
		$output_count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_kgrd_output_%',
				'_transient_timeout_kgrd_output_%'
			)
		);

		return absint( $output_count / 2 ); // Divide by 2 because each transient has a timeout entry.
	}
}
