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
	 * Request-scope memoization for API responses (including errors).
	 *
	 * Prevents duplicate HTTP requests for the same resource within a single
	 * page load, and avoids hammering the API repeatedly when a request fails.
	 *
	 * @var array
	 */
	private static $memo = array();

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
	public function get_repository( $username, $repo, $force_refresh = false ) {
		// Sanitize inputs.
		$username = sanitize_text_field( $username );
		$repo     = sanitize_text_field( $repo );

		// Check cache first (request memo + transient). When $force_refresh is
		// set (cron refresh), the cache is bypassed and only overwritten on a
		// successful fetch, so a failed refresh never destroys warm data.
		$cache_key = $this->get_cache_key( $username, $repo );
		if ( ! $force_refresh ) {
			$cached = $this->api_cache_get( $cache_key );
			if ( null !== $cached ) {
				return $cached;
			}
		}

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
			$this->api_cache_set( $cache_key, $response );
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
				$error = new WP_Error(
					'rate_limit_exceeded',
					sprintf(
						/* translators: %s: Time until rate limit reset */
						__( 'GitHub API rate limit exceeded. Resets at %s.', 'kashiwazaki-github-repo-display' ),
						date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $rate_limit_reset )
					)
				);
				$this->api_cache_set( $cache_key, $error );
				return $error;
			}
		}

		// Handle not found.
		if ( 404 === $response_code ) {
			$error = new WP_Error(
				'repo_not_found',
				sprintf(
					/* translators: 1: username, 2: repository name */
					__( 'Repository "%1$s/%2$s" not found.', 'kashiwazaki-github-repo-display' ),
					$username,
					$repo
				)
			);
			$this->api_cache_set( $cache_key, $error );
			return $error;
		}

		// Handle other errors.
		if ( $response_code >= 400 ) {
			$error = new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'GitHub API error: HTTP %d', 'kashiwazaki-github-repo-display' ),
					$response_code
				)
			);
			$this->api_cache_set( $cache_key, $error );
			return $error;
		}

		$data = json_decode( $body, true );

		if ( null === $data ) {
			$error = new WP_Error(
				'invalid_response',
				__( 'Invalid response from GitHub API.', 'kashiwazaki-github-repo-display' )
			);
			$this->api_cache_set( $cache_key, $error );
			return $error;
		}

		// During a force refresh, keep the previous enrichment values when a
		// sub-fetch fails, so a transient sub-endpoint error never degrades the
		// warm aggregate cache (e.g. download_url falling back to the default
		// branch just because the tag lookup failed once).
		$previous = $force_refresh ? get_transient( $cache_key ) : false;
		if ( ! is_array( $previous ) ) {
			$previous = array();
		}

		// Get README content.
		$readme = $this->get_readme( $username, $repo, $force_refresh );
		if ( ! is_wp_error( $readme ) ) {
			$data['readme_title'] = $this->extract_readme_title( $readme );
		} elseif ( 'readme_not_found' !== $readme->get_error_code() && isset( $previous['readme_title'] ) ) {
			$data['readme_title'] = $previous['readme_title'];
		}

		// Get readme.txt content if it exists (for WordPress plugins/themes).
		$readme_txt = $this->get_file_contents( $username, $repo, 'readme.txt', $force_refresh );
		if ( ! is_wp_error( $readme_txt ) && ! empty( $readme_txt ) ) {
			// Store the entire readme.txt content
			$data['readme_txt_content'] = $readme_txt;
		} elseif ( is_wp_error( $readme_txt ) && 'file_not_found' !== $readme_txt->get_error_code() && isset( $previous['readme_txt_content'] ) ) {
			$data['readme_txt_content'] = $previous['readme_txt_content'];
		}

		// Get latest tag/release for download URL.
		$latest_tag = $this->get_latest_tag( $username, $repo, $force_refresh );
		if ( ! is_wp_error( $latest_tag ) && ! empty( $latest_tag ) ) {
			$data['download_url'] = sprintf( 'https://github.com/%s/%s/archive/refs/tags/%s.zip', $username, $repo, $latest_tag );
		} elseif ( is_wp_error( $latest_tag ) && 'tags_not_found' !== $latest_tag->get_error_code() && ! empty( $previous['download_url'] ) ) {
			// Transient lookup failure: keep the previously known download URL.
			$data['download_url'] = $previous['download_url'];
		} else {
			// Fallback to default branch zip.
			$default_branch = ! empty( $data['default_branch'] ) ? $data['default_branch'] : 'main';
			$data['download_url'] = sprintf( 'https://github.com/%s/%s/archive/refs/heads/%s.zip', $username, $repo, $default_branch );
		}

		// Cache the enriched repository data (readme_title / readme_txt_content / download_url included).
		$this->api_cache_set( $cache_key, $data );

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
	public function get_file_contents( $username, $repo, $path, $force_refresh = false ) {
		$username = sanitize_text_field( $username );
		$repo     = sanitize_text_field( $repo );
		$path     = sanitize_text_field( $path );

		// Check cache first (request memo + transient).
		$cache_key = $this->build_api_cache_key( 'kgrd_file_', array( $username, $repo, $path ) );
		if ( ! $force_refresh ) {
			$cached = $this->api_cache_get( $cache_key );
			if ( null !== $cached ) {
				return $cached;
			}
		}

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
			$this->api_cache_set( $cache_key, $response );
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 404 === $response_code ) {
			$error = new WP_Error( 'file_not_found', __( 'File not found.', 'kashiwazaki-github-repo-display' ) );
			$this->api_cache_set( $cache_key, $error );
			return $error;
		}

		if ( $response_code >= 400 ) {
			$error = new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'GitHub API error: HTTP %d', 'kashiwazaki-github-repo-display' ),
					$response_code
				)
			);
			$this->api_cache_set( $cache_key, $error );
			return $error;
		}

		$body = wp_remote_retrieve_body( $response );
		$this->api_cache_set( $cache_key, $body );

		return $body;
	}

	/**
	 * Get repository README content.
	 *
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @return string|WP_Error README content or error.
	 */
	public function get_readme( $username, $repo, $force_refresh = false ) {
		$username = sanitize_text_field( $username );
		$repo     = sanitize_text_field( $repo );

		// Check cache first (request memo + transient).
		$cache_key = $this->build_api_cache_key( 'kgrd_readme_', array( $username, $repo ) );
		if ( ! $force_refresh ) {
			$cached = $this->api_cache_get( $cache_key );
			if ( null !== $cached ) {
				return $cached;
			}
		}

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
			$this->api_cache_set( $cache_key, $response );
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 404 === $response_code ) {
			$error = new WP_Error( 'readme_not_found', __( 'README not found.', 'kashiwazaki-github-repo-display' ) );
			$this->api_cache_set( $cache_key, $error );
			return $error;
		}

		if ( $response_code >= 400 ) {
			$error = new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'GitHub API error: HTTP %d', 'kashiwazaki-github-repo-display' ),
					$response_code
				)
			);
			$this->api_cache_set( $cache_key, $error );
			return $error;
		}

		$body = wp_remote_retrieve_body( $response );
		$this->api_cache_set( $cache_key, $body );

		return $body;
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
	public function get_latest_release( $username, $repo, $force_refresh = false ) {
		$username = sanitize_text_field( $username );
		$repo     = sanitize_text_field( $repo );

		// Check cache first (request memo + transient).
		$cache_key = $this->build_api_cache_key( 'kgrd_release_', array( $username, $repo ) );
		if ( ! $force_refresh ) {
			$cached = $this->api_cache_get( $cache_key );
			if ( null !== $cached ) {
				return $cached;
			}
		}

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
			$this->api_cache_set( $cache_key, $response );
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 404 === $response_code ) {
			// No releases found - this is not an error, just return empty.
			$this->api_cache_set( $cache_key, array() );
			return array();
		}

		if ( $response_code >= 400 ) {
			$error = new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'GitHub API error: HTTP %d', 'kashiwazaki-github-repo-display' ),
					$response_code
				)
			);
			$this->api_cache_set( $cache_key, $error );
			return $error;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			$data = array();
		}

		$this->api_cache_set( $cache_key, $data );

		return $data;
	}

	/**
	 * Get latest tag for a repository.
	 *
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @return string|WP_Error Latest tag name or error.
	 */
	public function get_latest_tag( $username, $repo, $force_refresh = false ) {
		$username = sanitize_text_field( $username );
		$repo     = sanitize_text_field( $repo );

		// Check cache first (request memo + transient).
		$cache_key = $this->build_api_cache_key( 'kgrd_tag_', array( $username, $repo ) );
		if ( ! $force_refresh ) {
			$cached = $this->api_cache_get( $cache_key );
			if ( null !== $cached ) {
				return $cached;
			}
		}

		// First, try the latest release (most reliable for versioned releases).
		// Reuses get_latest_release() so the same endpoint is never fetched
		// twice within a request (memo + transient cache).
		$release = $this->get_latest_release( $username, $repo, $force_refresh );
		if ( ! is_wp_error( $release ) && ! empty( $release['tag_name'] ) ) {
			$this->api_cache_set( $cache_key, $release['tag_name'] );
			return $release['tag_name'];
		}

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

		// Fallback to tags API if no releases exist.
		$tags_url = sprintf( '%s/repos/%s/%s/tags', $this->api_base, $username, $repo );
		$response = wp_remote_get(
			$tags_url,
			array(
				'timeout' => 15,
				'headers' => $headers,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->api_cache_set( $cache_key, $response );
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 404 === $response_code ) {
			$error = new WP_Error( 'tags_not_found', __( 'No tags found.', 'kashiwazaki-github-repo-display' ) );
			$this->api_cache_set( $cache_key, $error );
			return $error;
		}

		if ( $response_code >= 400 ) {
			$error = new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'GitHub API error: HTTP %d', 'kashiwazaki-github-repo-display' ),
					$response_code
				)
			);
			$this->api_cache_set( $cache_key, $error );
			return $error;
		}

		$tags = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $tags ) || ! is_array( $tags ) ) {
			$this->api_cache_set( $cache_key, '' );
			return '';
		}

		// Return the first tag (note: tags API order is not guaranteed to be latest first).
		$this->api_cache_set( $cache_key, $tags[0]['name'] );

		return $tags[0]['name'];
	}

	/**
	 * Build the GitHub Pages URL for a repository if Pages is enabled.
	 *
	 * Uses the `has_pages` flag returned by the GitHub REST API. Custom domains
	 * still work because GitHub redirects the canonical `*.github.io` URL.
	 *
	 * @param array $data Repository data from GitHub API.
	 * @return string Pages URL or empty string if not available.
	 */
	public static function get_pages_url( $data ) {
		if ( empty( $data['has_pages'] ) ) {
			return '';
		}

		$owner = ! empty( $data['owner']['login'] ) ? $data['owner']['login'] : '';
		$name  = ! empty( $data['name'] ) ? $data['name'] : '';

		if ( empty( $owner ) || empty( $name ) ) {
			return '';
		}

		$owner_lc = strtolower( $owner );

		// User/Organization site: repo named "<owner>.github.io".
		if ( strcasecmp( $name, $owner . '.github.io' ) === 0 ) {
			return sprintf( 'https://%s.github.io/', $owner_lc );
		}

		// Project site.
		return sprintf( 'https://%s.github.io/%s/', $owner_lc, $name );
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
		return 'kgrd_repo_' . md5( strtolower( $username ) . '|' . strtolower( $repo ) . $this->get_token_fingerprint() );
	}

	/**
	 * Build a namespaced cache key for an API resource.
	 *
	 * Includes a token fingerprint so cached data is invalidated when the
	 * authentication token changes (e.g. private repos becoming inaccessible).
	 *
	 * @param string $prefix Key prefix (e.g. 'kgrd_readme_').
	 * @param array  $parts Key parts (username, repo, path, ...).
	 * @return string Cache key.
	 */
	private function build_api_cache_key( $prefix, $parts ) {
		return $prefix . md5( strtolower( implode( '|', $parts ) ) . $this->get_token_fingerprint() );
	}

	/**
	 * Get a short fingerprint of the configured token for cache keying.
	 *
	 * @return string Fingerprint or empty string when no token is set.
	 */
	private function get_token_fingerprint() {
		$token = get_option( 'kgrd_github_token', '' );
		return empty( $token ) ? '' : '|' . substr( md5( $token ), 0, 8 );
	}

	/**
	 * Get a cached API response (request memo first, then transient).
	 *
	 * @param string $cache_key Cache key.
	 * @return mixed Cached value, or null when not cached.
	 */
	private function api_cache_get( $cache_key ) {
		if ( array_key_exists( $cache_key, self::$memo ) ) {
			return self::$memo[ $cache_key ];
		}

		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			self::$memo[ $cache_key ] = $cached;
			return $cached;
		}

		return null;
	}

	/**
	 * Store an API response in the request memo and, for successes, in a transient.
	 *
	 * WP_Error values are memoized for the current request only so a failing
	 * endpoint is not re-fetched dozens of times in one page load, but errors
	 * are never persisted.
	 *
	 * @param string $cache_key Cache key.
	 * @param mixed  $value Value to cache.
	 */
	private function api_cache_set( $cache_key, $value ) {
		self::$memo[ $cache_key ] = $value;

		if ( ! is_wp_error( $value ) ) {
			set_transient( $cache_key, $value, $this->get_cache_expiration() );
		}
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
		$keys = array(
			$this->get_cache_key( $username, $repo ),
			$this->build_api_cache_key( 'kgrd_readme_', array( $username, $repo ) ),
			$this->build_api_cache_key( 'kgrd_release_', array( $username, $repo ) ),
			$this->build_api_cache_key( 'kgrd_tag_', array( $username, $repo ) ),
			$this->build_api_cache_key( 'kgrd_file_', array( $username, $repo, 'readme.txt' ) ),
		);

		$deleted = false;
		foreach ( $keys as $key ) {
			if ( delete_transient( $key ) ) {
				$deleted = true;
			}
			unset( self::$memo[ $key ] );
		}

		return $deleted;
	}

	/**
	 * Clear all cached repositories.
	 *
	 * @return int Number of cache entries cleared.
	 */
	public function clear_all_cache() {
		global $wpdb;

		// Clear all plugin caches: shortcode output, API layer (repo/readme/file/release/tag),
		// user repos list and detail page caches.
		$output_count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_kgrd_%',
				'_transient_timeout_kgrd_%'
			)
		);

		// Reset the request-scope memo as well.
		self::$memo = array();

		return absint( $output_count / 2 ); // Divide by 2 because each transient has a timeout entry.
	}
}
