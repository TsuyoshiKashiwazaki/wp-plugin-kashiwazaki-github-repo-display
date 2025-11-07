<?php
/**
 * Repository display class.
 *
 * @package Kashiwazaki_GitHub_Repo_Display
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KGRD_Repo_Display
 *
 * Handles the display of GitHub repository information.
 */
class KGRD_Repo_Display {

	/**
	 * Single instance of the class.
	 *
	 * @var KGRD_Repo_Display
	 */
	private static $instance = null;

	/**
	 * Flag to track if assets have been enqueued.
	 *
	 * @var bool
	 */
	private $assets_enqueued = false;

	/**
	 * Get the single instance of the class.
	 *
	 * @return KGRD_Repo_Display
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets_early' ), 1 );
	}

	/**
	 * Enqueue CSS and JS (always load).
	 */
	public function enqueue_assets_early() {
		wp_enqueue_style(
			'kgrd-repo-card',
			KGRD_PLUGIN_URL . 'assets/css/repo-card.css',
			array(),
			KGRD_VERSION
		);

		wp_enqueue_script(
			'kgrd-repo-card',
			KGRD_PLUGIN_URL . 'assets/js/repo-card.js',
			array( 'jquery' ),
			KGRD_VERSION,
			true
		);
	}

	/**
	 * Display a single repository.
	 *
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @param string $style Display style (card, minimal, badges-only).
	 * @param string $custom_license Optional custom license to override GitHub API detection.
	 * @return string HTML output.
	 */
	public function display_repository( $username, $repo, $style = 'card', $custom_license = '' ) {
		$this->assets_enqueued = true;

		// Get repository data.
		$api  = KGRD_GitHub_API::get_instance();
		$data = $api->get_repository( $username, $repo );

		// Handle errors.
		if ( is_wp_error( $data ) ) {
			return $this->display_error( $data );
		}

		// Override license if custom license is provided.
		if ( ! empty( $custom_license ) ) {
			$data['custom_license'] = sanitize_text_field( $custom_license );
		}

		// Choose display method based on style.
		switch ( $style ) {
			case 'minimal':
				return $this->display_minimal( $data );
			case 'badges-only':
				return $this->display_badges_only( $data );
			case 'card':
			default:
				return $this->display_card( $data );
		}
	}

	/**
	 * Get human-readable time difference in English.
	 *
	 * @param string $datetime Datetime string.
	 * @return string Time difference in English (e.g., "2 hours", "3 days").
	 */
	private function get_time_diff_english( $datetime ) {
		if ( empty( $datetime ) ) {
			return '';
		}

		$timestamp = strtotime( $datetime );
		$current = current_time( 'timestamp' );
		$diff = $current - $timestamp;

		if ( $diff < 0 ) {
			return 'just now';
		}

		$intervals = array(
			31536000 => array( 'year', 'years' ),
			2592000  => array( 'month', 'months' ),
			604800   => array( 'week', 'weeks' ),
			86400    => array( 'day', 'days' ),
			3600     => array( 'hour', 'hours' ),
			60       => array( 'minute', 'minutes' ),
			1        => array( 'second', 'seconds' ),
		);

		foreach ( $intervals as $seconds => $labels ) {
			$count = floor( $diff / $seconds );
			if ( $count > 0 ) {
				$label = $count === 1 ? $labels[0] : $labels[1];
				return sprintf( '%d %s', $count, $label );
			}
		}

		return 'just now';
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
	 * Remove emojis and special characters from text.
	 *
	 * @param string $text Input text.
	 * @return string Sanitized text.
	 */
	private function sanitize_title_text( $text ) {
		if ( empty( $text ) ) {
			return $text;
		}

		// Remove emojis (Unicode ranges for common emojis).
		$text = preg_replace( '/[\x{1F600}-\x{1F64F}]/u', '', $text ); // Emoticons
		$text = preg_replace( '/[\x{1F300}-\x{1F5FF}]/u', '', $text ); // Miscellaneous Symbols and Pictographs
		$text = preg_replace( '/[\x{1F680}-\x{1F6FF}]/u', '', $text ); // Transport and Map Symbols
		$text = preg_replace( '/[\x{1F900}-\x{1F9FF}]/u', '', $text ); // Supplemental Symbols and Pictographs
		$text = preg_replace( '/[\x{2600}-\x{26FF}]/u', '', $text );   // Miscellaneous Symbols
		$text = preg_replace( '/[\x{2700}-\x{27BF}]/u', '', $text );   // Dingbats
		$text = preg_replace( '/[\x{1F1E0}-\x{1F1FF}]/u', '', $text ); // Flags
		$text = preg_replace( '/[\x{1FA70}-\x{1FAFF}]/u', '', $text ); // Symbols and Pictographs Extended-A

		// Remove zero-width characters and other invisible Unicode characters.
		$text = preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text );

		// Trim whitespace and return.
		return trim( $text );
	}

	/**
	 * Display repository as a card.
	 *
	 * @param array $data Repository data from GitHub API.
	 * @return string HTML output.
	 */
	private function display_card( $data ) {
		// Determine title: prefer readme_title, fetch if not available, fallback to name.
		if ( ! empty( $data['readme_title'] ) ) {
			$raw_title = $data['readme_title'];
		} else {
			// Try to fetch README title if we have owner and name info.
			$fetched_title = '';
			if ( ! empty( $data['owner']['login'] ) && ! empty( $data['name'] ) ) {
				$api = KGRD_GitHub_API::get_instance();
				$readme = $api->get_readme( $data['owner']['login'], $data['name'] );

				if ( ! is_wp_error( $readme ) ) {
					// Extract title from README.
					$fetched_title = $this->extract_readme_title( $readme );
				}
			}

			// Use fetched title, or fallback to repository name.
			if ( ! empty( $fetched_title ) ) {
				$raw_title = $fetched_title;
			} elseif ( ! empty( $data['name'] ) ) {
				$raw_title = $data['name'];
			} else {
				$raw_title = __( 'Untitled Repository', 'kashiwazaki-github-repo-display' );
			}
		}
		$title = $this->sanitize_title_text( $raw_title );
		$github_description        = ! empty( $data['description'] ) ? $data['description'] : '';
		$readme_txt_content        = ! empty( $data['readme_txt_content'] ) ? $data['readme_txt_content'] : '';

		$language     = ! empty( $data['language'] ) ? $data['language'] : __( 'Unknown', 'kashiwazaki-github-repo-display' );
		$stars        = isset( $data['stargazers_count'] ) ? number_format_i18n( $data['stargazers_count'] ) : '0';
		$forks        = isset( $data['forks_count'] ) ? number_format_i18n( $data['forks_count'] ) : '0';
		$updated      = ! empty( $data['updated_at'] ) ? $this->get_time_diff_english( $data['updated_at'] ) : '';
		$html_url     = esc_url( $data['html_url'] );
		$download_url = ! empty( $data['download_url'] ) ? esc_url( $data['download_url'] ) : esc_url( $data['clone_url'] );
		$license      = ! empty( $data['license']['spdx_id'] ) ? $data['license']['spdx_id'] : 'Unknown';

		// Generate badges.
		$badges = $this->get_shields_badges( $data );

		// Build descriptions HTML.
		$descriptions_html = '';
		if ( ! empty( $github_description ) ) {
			$descriptions_html .= sprintf( '<p class="kgrd-card__description kgrd-card__description--github">%s</p>', esc_html( $github_description ) );
		}
		if ( ! empty( $readme_txt_content ) ) {
			// Split content: first 500 chars visible, rest collapsible
			$char_limit = 500;
			$content_length = mb_strlen( $readme_txt_content );

			if ( $content_length > $char_limit ) {
				$visible_content = mb_substr( $readme_txt_content, 0, $char_limit );
				$hidden_content = mb_substr( $readme_txt_content, $char_limit );

				$descriptions_html .= sprintf(
					'<div class="kgrd-card__description kgrd-card__description--readme-txt">
						<div class="kgrd-collapsible">
							<pre class="kgrd-readme-txt">%s<span class="kgrd-readme-more">...</span><span class="kgrd-collapsible__content" hidden>%s</span></pre>
							<button class="kgrd-collapsible__toggle" type="button" aria-expanded="false">
								<span class="kgrd-collapsible__toggle-text">続きを表示する</span>
								<svg class="kgrd-collapsible__icon" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M2 4L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</button>
						</div>
					</div>',
					esc_html( $visible_content ),
					esc_html( $hidden_content )
				);
			} else {
				// If content is short, show all without collapsible
				$descriptions_html .= sprintf(
					'<div class="kgrd-card__description kgrd-card__description--readme-txt">
						<pre class="kgrd-readme-txt">%s</pre>
					</div>',
					esc_html( $readme_txt_content )
				);
			}
		}

		// Allow filtering of the HTML output.
		$html = apply_filters(
			'kgrd_repo_card_html',
			sprintf(
				'<div class="kgrd-card" data-repo="%s">
					<div class="kgrd-card__header">
						<h3 class="kgrd-card__title">%s</h3>
						<a href="%s" class="kgrd-card__repo-link" target="_blank" rel="noopener noreferrer">
							<svg class="kgrd-card__github-icon" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true">
								<path fill="currentColor" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path>
							</svg>
						</a>
					</div>
					%s
					<div class="kgrd-card__stats">
						<div class="kgrd-card__stat">
							<svg class="kgrd-card__stat-icon" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true">
								<path fill="currentColor" d="M8 .25a.75.75 0 01.673.418l1.882 3.815 4.21.612a.75.75 0 01.416 1.279l-3.046 2.97.719 4.192a.75.75 0 01-1.088.791L8 12.347l-3.766 1.98a.75.75 0 01-1.088-.79l.72-4.194L.818 6.374a.75.75 0 01.416-1.28l4.21-.611L7.327.668A.75.75 0 018 .25z"></path>
							</svg>
							<span>%s</span>
						</div>
						<div class="kgrd-card__stat">
							<svg class="kgrd-card__stat-icon" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true">
								<path fill="currentColor" d="M5 3.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm0 2.122a2.25 2.25 0 10-1.5 0v.878A2.25 2.25 0 005.75 8.5h1.5v2.128a2.251 2.251 0 101.5 0V8.5h1.5a2.25 2.25 0 002.25-2.25v-.878a2.25 2.25 0 10-1.5 0v.878a.75.75 0 01-.75.75h-4.5A.75.75 0 015 6.25v-.878zm3.75 7.378a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm3-8.75a.75.75 0 100-1.5.75.75 0 000 1.5z"></path>
							</svg>
							<span>%s</span>
						</div>
						<div class="kgrd-card__stat">
							<svg class="kgrd-card__stat-icon" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true">
								<path fill="currentColor" d="M1.5 8a6.5 6.5 0 1113 0 6.5 6.5 0 01-13 0zM8 0a8 8 0 100 16A8 8 0 008 0zm.5 4.75a.75.75 0 00-1.5 0v3.5a.75.75 0 00.471.696l2.5 1a.75.75 0 00.557-1.392L8.5 7.742V4.75z"></path>
							</svg>
							<span>%s</span>
						</div>
						<div class="kgrd-card__stat">
							<span class="kgrd-card__language">%s</span>
						</div>
					</div>
					<div class="kgrd-card__badges">
						%s
					</div>
					<div class="kgrd-card__actions">
						<a href="%s" class="kgrd-card__button kgrd-card__button--primary" target="_blank" rel="noopener noreferrer">
							%s
						</a>
						<a href="%s" class="kgrd-card__button kgrd-card__button--secondary" target="_blank" rel="noopener noreferrer">
							%s
						</a>
					</div>
				</div>',
				esc_attr( $data['full_name'] ),
				esc_html( $title ),
				$html_url,
				$descriptions_html,
				$stars,
				$forks,
				sprintf(
					'Updated %s ago',
					esc_html( $updated )
				),
				esc_html( $language ),
				$badges,
				$html_url,
				esc_html__( 'View on GitHub', 'kashiwazaki-github-repo-display' ),
				$download_url,
				esc_html__( 'Download', 'kashiwazaki-github-repo-display' )
			),
			$data
		);

		return $html;
	}

	/**
	 * Display repository in minimal style.
	 *
	 * @param array $data Repository data from GitHub API.
	 * @return string HTML output.
	 */
	private function display_minimal( $data ) {
		$raw_title   = ! empty( $data['readme_title'] ) ? $data['readme_title'] : $data['name'];
		$title       = $this->sanitize_title_text( $raw_title );
		$description = ! empty( $data['description'] ) ? $data['description'] : '';
		$html_url    = esc_url( $data['html_url'] );

		$html = sprintf(
			'<div class="kgrd-minimal">
				<h4 class="kgrd-minimal__title">
					<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>
				</h4>
				%s
			</div>',
			$html_url,
			esc_html( $title ),
			! empty( $description ) ? sprintf( '<p class="kgrd-minimal__description">%s</p>', esc_html( $description ) ) : ''
		);

		return $html;
	}

	/**
	 * Display only badges.
	 *
	 * @param array $data Repository data from GitHub API.
	 * @return string HTML output.
	 */
	private function display_badges_only( $data ) {
		$badges = $this->get_shields_badges( $data );

		$html = sprintf(
			'<div class="kgrd-badges">%s</div>',
			$badges
		);

		return $html;
	}

	/**
	 * Extract license information from README content.
	 *
	 * @param string $readme_content README content.
	 * @return string License identifier or empty string.
	 */
	private function extract_license_from_readme( $readme_content ) {
		if ( empty( $readme_content ) ) {
			return '';
		}

		// Specific license patterns with SPDX-style output.
		$specific_patterns = array(
			// GPL variants - match "GPL v2 or later", "GPL-2.0", etc.
			'/\bGPL[\s\-]?v?([0-9])(?:\.0)?[\s\-]?(?:or[\s\-]?later)?/i' => function( $matches ) {
				$version = $matches[1];
				// Check if "or later" is present.
				if ( preg_match( '/or[\s\-]?later/i', $matches[0] ) ) {
					return "GPL-{$version}.0-or-later";
				}
				return "GPL-{$version}.0";
			},
			// MIT License.
			'/\bMIT\s+License/i' => 'MIT',
			// Apache variants.
			'/\bApache[\s\-]?License[\s\-]?([0-9\.]+)/i' => function( $matches ) {
				return 'Apache-' . $matches[1];
			},
			'/\bApache[\s\-]?([0-9\.]+)/i' => function( $matches ) {
				return 'Apache-' . $matches[1];
			},
			// BSD variants.
			'/\bBSD[\s\-]?([0-9])\-Clause/i' => function( $matches ) {
				return 'BSD-' . $matches[1] . '-Clause';
			},
		);

		// Try specific patterns first.
		foreach ( $specific_patterns as $pattern => $handler ) {
			if ( preg_match( $pattern, $readme_content, $matches ) ) {
				if ( is_callable( $handler ) ) {
					return $handler( $matches );
				} else {
					return $handler;
				}
			}
		}

		// Fallback: generic license extraction.
		$generic_patterns = array(
			'/license[:\s]+([A-Z0-9\-\.\s]+)/i',
			'/licensed under ([A-Z0-9\-\.\s]+)/i',
		);

		foreach ( $generic_patterns as $pattern ) {
			if ( preg_match( $pattern, $readme_content, $matches ) ) {
				$license = isset( $matches[1] ) ? trim( $matches[1] ) : '';
				// Clean up common suffixes and newlines.
				$license = preg_replace( '/\s+(or later|license|\n|\r).*$/i', '', $license );
				return $license;
			}
		}

		return '';
	}

	/**
	 * Generate Shields.io badges for a repository.
	 *
	 * @param array $data Repository data from GitHub API.
	 * @return string HTML badges.
	 */
	private function get_shields_badges( $data ) {
		$username = $data['owner']['login'];
		$repo     = $data['name'];

		// Determine the correct license to display.
		$license = '';
		if ( ! empty( $data['custom_license'] ) ) {
			// Use custom license provided via shortcode.
			$license = $data['custom_license'];
		} elseif ( ! empty( $data['license']['spdx_id'] ) && 'NOASSERTION' !== $data['license']['spdx_id'] ) {
			// Use GitHub API license if valid.
			$license = $data['license']['spdx_id'];
		} else {
			// Try to extract from README.
			$api = KGRD_GitHub_API::get_instance();
			$readme = $api->get_readme( $username, $repo );
			if ( ! is_wp_error( $readme ) ) {
				$license = $this->extract_license_from_readme( $readme );
			}
		}

		// Generate license badge URL.
		if ( ! empty( $license ) && 'NOASSERTION' !== $license ) {
			// Use custom badge with specified license.
			// Shields.io uses hyphens as separators, so we need to escape them with double hyphens.
			$license_display = str_replace( '-', '--', $license );
			// Replace spaces with underscores for better display.
			$license_display = str_replace( ' ', '_', $license_display );
			$license_badge_url = sprintf( 'https://img.shields.io/badge/license-%s-blue', rawurlencode( $license_display ) );
		} else {
			// Fall back to GitHub's automatic license detection.
			$license_badge_url = sprintf( 'https://img.shields.io/github/license/%s/%s', $username, $repo );
		}

		// Generate release_date badge: use latest release date or repository created date.
		$release_date_badge = '';
		$date = '';
		$label = '';

		// First try to get the latest release
		$api = KGRD_GitHub_API::get_instance();
		$latest_release = $api->get_latest_release( $username, $repo );

		if ( ! is_wp_error( $latest_release ) && ! empty( $latest_release['published_at'] ) ) {
			// Use latest release date.
			$date = gmdate( 'Y-m-d', strtotime( $latest_release['published_at'] ) );
			$label = 'latest_release';
		} elseif ( ! empty( $data['created_at'] ) ) {
			// Use repository created date.
			$date = gmdate( 'Y-m-d', strtotime( $data['created_at'] ) );
			$label = 'created';
		}

		if ( ! empty( $date ) && ! empty( $label ) ) {
			// Shields.io requires hyphens to be escaped as double hyphens.
			$escaped_date = str_replace( '-', '--', $date );

			$release_date_badge = sprintf(
				'https://img.shields.io/badge/%s-%s-blue',
				rawurlencode( $label ),
				rawurlencode( $escaped_date )
			);
		}

		// すべての利用可能なバッジURL
		// メジャーなバッジ（デフォルトON）
		$all_badge_urls = array(
			'version'          => sprintf( 'https://img.shields.io/github/v/tag/%s/%s', $username, $repo ),
			'last_commit'      => sprintf( 'https://img.shields.io/github/last-commit/%s/%s', $username, $repo ),
			'license'          => $license_badge_url,
			'stars'            => sprintf( 'https://img.shields.io/github/stars/%s/%s', $username, $repo ),
			'forks'            => sprintf( 'https://img.shields.io/github/forks/%s/%s', $username, $repo ),
			'issues'           => sprintf( 'https://img.shields.io/github/issues/%s/%s', $username, $repo ),
			'language'         => sprintf( 'https://img.shields.io/github/languages/top/%s/%s', $username, $repo ),
			'contributors'     => sprintf( 'https://img.shields.io/github/contributors/%s/%s', $username, $repo ),
			// マイナーなバッジ（デフォルトOFF）
			'watchers'         => sprintf( 'https://img.shields.io/github/watchers/%s/%s', $username, $repo ),
			'open_prs'         => sprintf( 'https://img.shields.io/github/issues-pr/%s/%s', $username, $repo ),
			'closed_issues'    => sprintf( 'https://img.shields.io/github/issues-closed/%s/%s', $username, $repo ),
			'downloads'        => sprintf( 'https://img.shields.io/github/downloads/%s/%s/total', $username, $repo ),
			'code_size'        => sprintf( 'https://img.shields.io/github/languages/code-size/%s/%s', $username, $repo ),
			'repo_size'        => sprintf( 'https://img.shields.io/github/repo-size/%s/%s', $username, $repo ),
			'commit_activity'  => sprintf( 'https://img.shields.io/github/commit-activity/m/%s/%s', $username, $repo ),
			'release_date'     => $release_date_badge,
		);

		// 設定で有効化されているバッジのみをフィルタリング
		$major_badges = array( 'version', 'last_commit', 'license', 'stars', 'forks', 'issues', 'language', 'contributors' );
		$badge_urls = array();
		foreach ( $all_badge_urls as $key => $url ) {
			// Skip empty URLs.
			if ( empty( $url ) ) {
				continue;
			}

			$is_enabled = get_option( 'kgrd_badge_' . $key, in_array( $key, $major_badges ) ? 1 : 0 );
			if ( $is_enabled ) {
				$badge_urls[ $key ] = $url;
			}
		}

		// Allow filtering of badge URLs.
		$badge_urls = apply_filters( 'kgrd_badge_urls', $badge_urls, $data );

		$badges_html = '';
		foreach ( $badge_urls as $key => $url ) {
			$badges_html .= sprintf(
				'<img src="%s" alt="%s" class="kgrd-card__badge" loading="lazy">',
				esc_url( $url ),
				esc_attr( ucfirst( str_replace( '_', ' ', $key ) ) . ' badge' )
			);
		}

		return $badges_html;
	}

	/**
	 * Display multiple repositories in a grid.
	 *
	 * @param array  $repos Array of repository names (string) or repository data (array).
	 * @param string $username GitHub username.
	 * @param int    $columns Number of columns (1-4).
	 * @return string HTML output.
	 */
	public function display_repositories_grid( $repos, $username, $columns = 2 ) {
		$this->assets_enqueued = true;

		$columns = absint( $columns );
		$columns = max( 1, min( 4, $columns ) ); // Clamp between 1 and 4.

		$html = sprintf( '<div class="kgrd-grid kgrd-grid--columns-%d">', $columns );

		foreach ( $repos as $repo ) {
			// Check if $repo is already data (array) or just a name (string).
			if ( is_array( $repo ) ) {
				// Already have the data, display directly without additional API call.
				$html .= $this->display_card( $repo );
			} else {
				// Traditional behavior: repo is a string (repository name).
				$html .= $this->display_repository( $username, $repo, 'card' );
			}
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Display an error message.
	 *
	 * @param WP_Error $error WordPress error object.
	 * @return string HTML error message.
	 */
	private function display_error( $error ) {
		return sprintf(
			'<div class="kgrd-error">
				<p><strong>%s:</strong> %s</p>
			</div>',
			esc_html__( 'Error', 'kashiwazaki-github-repo-display' ),
			esc_html( $error->get_error_message() )
		);
	}
}
