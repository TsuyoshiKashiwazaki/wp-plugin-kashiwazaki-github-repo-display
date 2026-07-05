<?php
/**
 * Repository detail page class.
 *
 * @package Kashiwazaki_GitHub_Repo_Display
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KGRD_Repo_Detail_Page
 *
 * Handles the repository detail page with README display.
 */
class KGRD_Repo_Detail_Page {

	/**
	 * Single instance of the class.
	 *
	 * @var KGRD_Repo_Detail_Page
	 */
	private static $instance = null;

	/**
	 * Get the single instance of the class.
	 *
	 * @return KGRD_Repo_Detail_Page
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
		// Register rewrite rules directly since we're called during init.
		$this->register_rewrite_rules();
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_detail_page' ) );
	}

	/**
	 * Register rewrite rules for repository detail pages.
	 */
	public function register_rewrite_rules() {
		// Get the base path from settings (default: software).
		$base_path = get_option( 'kgrd_detail_base_path', 'software' );
		$base_path = self::sanitize_base_path( $base_path );

		// Get tracked repositories and create rules for each.
		$tracked_repos = get_option( 'kgrd_tracked_repositories', array() );

		foreach ( $tracked_repos as $key => $repo_info ) {
			$slug = sanitize_title( $repo_info['repo'] );
			// Create a specific rule for each tracked repository.
			add_rewrite_rule(
				'^' . preg_quote( $base_path, '/' ) . '/' . preg_quote( $slug, '/' ) . '/?$',
				'index.php?kgrd_repo_slug=' . $slug,
				'top'
			);
		}
	}

	/**
	 * Sanitize base path (supports multiple directory levels).
	 *
	 * @param string $path Base path.
	 * @return string Sanitized path.
	 */
	private static function sanitize_base_path( $path ) {
		$path = trim( $path, '/' );
		$segments = explode( '/', $path );
		$sanitized = array();

		foreach ( $segments as $segment ) {
			$segment = sanitize_title( $segment );
			if ( ! empty( $segment ) ) {
				$sanitized[] = $segment;
			}
		}

		return ! empty( $sanitized ) ? implode( '/', $sanitized ) : 'software';
	}

	/**
	 * Register custom query variables.
	 *
	 * @param array $vars Existing query vars.
	 * @return array Modified query vars.
	 */
	public function register_query_vars( $vars ) {
		$vars[] = 'kgrd_repo_slug';
		return $vars;
	}

	/**
	 * Handle the detail page request.
	 */
	public function handle_detail_page() {
		$slug = get_query_var( 'kgrd_repo_slug' );

		if ( empty( $slug ) ) {
			return;
		}

		$slug = sanitize_title( $slug );

		// Find repository in tracked list by slug.
		$tracked_repos = get_option( 'kgrd_tracked_repositories', array() );
		$username      = '';
		$repo          = '';

		foreach ( $tracked_repos as $key => $repo_info ) {
			$repo_slug = sanitize_title( $repo_info['repo'] );
			if ( $repo_slug === $slug ) {
				$username = $repo_info['username'];
				$repo     = $repo_info['repo'];
				break;
			}
		}

		// If not found in tracked list, try with default username.
		if ( empty( $username ) ) {
			$username = get_option( 'kgrd_default_username', '' );
			$repo     = $slug;
		}

		if ( empty( $username ) ) {
			return;
		}

		// Sanitize inputs.
		$username = sanitize_text_field( $username );
		$repo     = sanitize_text_field( $repo );

		// Check cache first.
		$cache_key   = 'kgrd_detail_' . md5( $username . '/' . $repo );
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data && is_array( $cached_data ) ) {
			$data        = $cached_data['data'];
			$readme_html = $cached_data['readme_html'];
		} else {
			// Build (and cache) the detail page data.
			$result = $this->build_detail_cache( $username, $repo );

			if ( is_wp_error( $result ) ) {
				$this->render_error_page( $result->get_error_message() );
				exit;
			}

			$data        = $result['data'];
			$readme_html = $result['readme_html'];
		}

		// Render the page.
		$this->render_detail_page( $data, $readme_html, $username, $repo );
		exit;
	}

	/**
	 * Build and cache the detail page data for a repository.
	 *
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @return array|WP_Error Array with 'data' and 'readme_html', or error.
	 */
	private function build_detail_cache( $username, $repo ) {
		$api  = KGRD_GitHub_API::get_instance();
		$data = $api->get_repository( $username, $repo );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Get README content (served from the API-layer cache when warm).
		$readme_content = $api->get_readme( $username, $repo );
		if ( is_wp_error( $readme_content ) ) {
			$readme_html = '<p>' . esc_html__( 'README not found.', 'kashiwazaki-github-repo-display' ) . '</p>';
		} else {
			$readme_html = $this->parse_markdown( $readme_content, $username, $repo );
		}

		$result = array(
			'data'        => $data,
			'readme_html' => $readme_html,
		);

		set_transient(
			'kgrd_detail_' . md5( $username . '/' . $repo ),
			$result,
			$this->get_cache_duration()
		);

		return $result;
	}

	/**
	 * Pre-warm the detail page cache for a repository (used by the cron refresh).
	 *
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @return array|WP_Error Built cache payload or error.
	 */
	public static function warm_cache( $username, $repo ) {
		$username = sanitize_text_field( $username );
		$repo     = sanitize_text_field( $repo );

		return self::get_instance()->build_detail_cache( $username, $repo );
	}

	/**
	 * Parse markdown to HTML.
	 *
	 * @param string $markdown Markdown content.
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @return string HTML content.
	 */
	private function parse_markdown( $markdown, $username, $repo ) {
		// Use Parsedown if available, otherwise use GitHub API.
		if ( class_exists( 'Parsedown' ) ) {
			$parsedown = new Parsedown();
			// setSafeMode only exists in newer versions of Parsedown.
			if ( method_exists( $parsedown, 'setSafeMode' ) ) {
				$parsedown->setSafeMode( true );
			}
			$html = $parsedown->text( $markdown );
		} else {
			// Use GitHub's markdown API as fallback.
			$html = $this->render_markdown_via_github( $markdown );
		}

		// Fix relative image URLs to point to GitHub raw content.
		$html = $this->fix_relative_urls( $html, $username, $repo );

		return $html;
	}

	/**
	 * Render markdown using GitHub API.
	 *
	 * @param string $markdown Markdown content.
	 * @return string HTML content.
	 */
	private function render_markdown_via_github( $markdown ) {
		$api = KGRD_GitHub_API::get_instance();

		$response = wp_remote_post(
			'https://api.github.com/markdown',
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'       => 'application/vnd.github.v3+json',
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'text' => $markdown,
						'mode' => 'gfm',
					)
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Fallback: basic conversion.
			return $this->basic_markdown_to_html( $markdown );
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Basic markdown to HTML conversion.
	 *
	 * @param string $markdown Markdown content.
	 * @return string HTML content.
	 */
	private function basic_markdown_to_html( $markdown ) {
		$html = esc_html( $markdown );

		// Headers.
		$html = preg_replace( '/^######\s+(.+)$/m', '<h6>$1</h6>', $html );
		$html = preg_replace( '/^#####\s+(.+)$/m', '<h5>$1</h5>', $html );
		$html = preg_replace( '/^####\s+(.+)$/m', '<h4>$1</h4>', $html );
		$html = preg_replace( '/^###\s+(.+)$/m', '<h3>$1</h3>', $html );
		$html = preg_replace( '/^##\s+(.+)$/m', '<h2>$1</h2>', $html );
		$html = preg_replace( '/^#\s+(.+)$/m', '<h1>$1</h1>', $html );

		// Bold and italic.
		$html = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html );
		$html = preg_replace( '/\*(.+?)\*/', '<em>$1</em>', $html );

		// Code blocks.
		$html = preg_replace( '/```[\w]*\n([\s\S]*?)```/', '<pre><code>$1</code></pre>', $html );
		$html = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $html );

		// Links.
		$html = preg_replace( '/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $html );

		// Images.
		$html = preg_replace( '/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1" style="max-width:100%;">', $html );

		// Line breaks.
		$html = nl2br( $html );

		return $html;
	}

	/**
	 * Fix relative URLs in HTML to point to GitHub raw content.
	 *
	 * @param string $html HTML content.
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @return string Modified HTML.
	 */
	private function fix_relative_urls( $html, $username, $repo ) {
		$raw_base = sprintf( 'https://raw.githubusercontent.com/%s/%s/HEAD/', $username, $repo );
		$github_base = sprintf( 'https://github.com/%s/%s/blob/HEAD/', $username, $repo );

		// Fix image src attributes (relative paths).
		$html = preg_replace_callback(
			'/(<img[^>]+src=")(?!https?:\/\/)([^"]+)(")/i',
			function ( $matches ) use ( $raw_base ) {
				$path = ltrim( $matches[2], './' );
				return $matches[1] . $raw_base . $path . $matches[3];
			},
			$html
		);

		// Fix anchor href attributes (relative paths, but not anchors).
		$html = preg_replace_callback(
			'/(<a[^>]+href=")(?!https?:\/\/)(?!#)([^"]+)(")/i',
			function ( $matches ) use ( $github_base ) {
				$path = ltrim( $matches[2], './' );
				return $matches[1] . $github_base . $path . $matches[3];
			},
			$html
		);

		return $html;
	}

	/**
	 * Render the detail page.
	 *
	 * @param array  $data Repository data.
	 * @param string $readme_html README HTML content.
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 */
	private function render_detail_page( $data, $readme_html, $username, $repo ) {
		// Set page title via filter.
		$page_title = ! empty( $data['name'] ) ? $data['name'] : $repo;
		add_filter( 'pre_get_document_title', function() use ( $page_title ) {
			return $page_title . ' - ' . get_bloginfo( 'name' );
		} );

		// Also set for themes that use wp_title.
		add_filter( 'wp_title', function() use ( $page_title ) {
			return $page_title . ' - ' . get_bloginfo( 'name' );
		} );

		// Add body classes for single post template.
		add_filter( 'body_class', function( $classes ) {
			$classes[] = 'single';
			$classes[] = 'single-post';
			$classes[] = 'kgrd-repo-detail';
			return $classes;
		} );

		// Force two-column layout for detail page (for themes that support backbone_get_layout).
		add_filter( 'theme_mod_site_layout', function( $layout ) {
			return 'two-columns';
		} );

		// Enqueue styles.
		wp_enqueue_style(
			'kgrd-repo-card',
			KGRD_PLUGIN_URL . 'assets/css/repo-card.css',
			array(),
			KGRD_VERSION
		);

		// Enqueue detail page styles.
		wp_enqueue_style(
			'kgrd-detail-page',
			KGRD_PLUGIN_URL . 'assets/css/detail-page.css',
			array( 'kgrd-repo-card' ),
			KGRD_VERSION
		);

		// Get theme header.
		get_header();
		?>

		<nav class="kgrd-detail-nav">
			<a href="<?php echo esc_url( self::get_list_url() ); ?>" class="kgrd-back-link">
				&larr; <?php esc_html_e( 'Back to List', 'kashiwazaki-github-repo-display' ); ?>
			</a>
		</nav>

		<article class="kgrd-detail-page post type-post hentry">
			<header class="entry-header kgrd-detail-header">
				<h1 class="entry-title kgrd-detail-title"><?php echo esc_html( $data['name'] ); ?></h1>

				<?php if ( ! empty( $data['description'] ) ) : ?>
					<p class="kgrd-detail-description"><?php echo esc_html( $data['description'] ); ?></p>
				<?php endif; ?>

				<div class="kgrd-detail-meta">
					<?php if ( ! empty( $data['language'] ) ) : ?>
						<span><?php echo esc_html( $data['language'] ); ?></span>
					<?php endif; ?>
					<span>&#9733; <?php echo esc_html( number_format( $data['stargazers_count'] ?? 0 ) ); ?></span>
					<span>&#128340; <?php echo esc_html( number_format( $data['forks_count'] ?? 0 ) ); ?></span>
					<?php if ( ! empty( $data['license']['name'] ) ) : ?>
						<span><?php echo esc_html( $data['license']['name'] ); ?></span>
					<?php endif; ?>
				</div>

				<?php
				$kgrd_label_source   = get_option( 'kgrd_label_source', 'Source' );
				$kgrd_label_download = get_option( 'kgrd_label_download', 'ZIP Download' );
				$kgrd_label_pages    = get_option( 'kgrd_label_pages', 'Docs' );
				if ( '' === trim( (string) $kgrd_label_source ) )   { $kgrd_label_source   = 'Source'; }
				if ( '' === trim( (string) $kgrd_label_download ) ) { $kgrd_label_download = 'ZIP Download'; }
				if ( '' === trim( (string) $kgrd_label_pages ) )    { $kgrd_label_pages    = 'Docs'; }
				?>
				<div class="kgrd-detail-buttons">
					<a href="<?php echo esc_url( $data['html_url'] ); ?>" target="_blank" rel="noopener" class="kgrd-detail-button">
						<?php echo esc_html( $kgrd_label_source ); ?>
					</a>
					<?php if ( ! empty( $data['download_url'] ) ) : ?>
						<a href="<?php echo esc_url( $data['download_url'] ); ?>" class="kgrd-detail-button kgrd-detail-button--secondary">
							<?php echo esc_html( $kgrd_label_download ); ?>
						</a>
					<?php endif; ?>
					<?php
					$kgrd_pages_url = KGRD_GitHub_API::get_pages_url( $data );
					if ( ! empty( $kgrd_pages_url ) ) :
						?>
						<a href="<?php echo esc_url( $kgrd_pages_url ); ?>" target="_blank" rel="noopener" class="kgrd-detail-button kgrd-detail-button--pages">
							<?php echo esc_html( $kgrd_label_pages ); ?>
						</a>
					<?php endif; ?>
				</div>
			</header>

			<div class="entry-content kgrd-detail-readme">
				<?php echo wp_kses_post( $readme_html ); ?>
			</div>
		</article>

		<script>
		(function() {
			var readme = document.querySelector('.kgrd-detail-readme');
			if (!readme) return;
			var h1 = readme.querySelector('h1');
			if (!h1) return;
			var badgeP = h1.nextElementSibling;
			if (badgeP && badgeP.tagName === 'P' && (badgeP.querySelector('a > img') || badgeP.querySelector('img'))) {
				// Add class for CSS targeting
				badgeP.classList.add('kgrd-badges-container');
				// Force horizontal layout with inline styles
				badgeP.style.cssText = 'display: flex !important; flex-wrap: wrap !important; gap: 4px !important; align-items: center !important; justify-content: flex-start !important;';
				// Style each badge link
				badgeP.querySelectorAll('a').forEach(function(a) {
					a.style.cssText = 'display: inline-flex !important; flex-shrink: 0 !important; flex-grow: 0 !important;';
				});
				// Style direct img children (badges without link wrapper)
				Array.from(badgeP.children).forEach(function(child) {
					if (child.tagName === 'IMG') {
						child.style.cssText = 'display: inline-flex !important; flex-shrink: 0 !important; flex-grow: 0 !important; height: 20px !important; width: auto !important; margin: 0 !important;';
					}
				});
				// Remove br tags that might cause spacing
				badgeP.querySelectorAll('br').forEach(function(br) {
					br.remove();
				});
				// Remove text nodes (whitespace) between badges
				Array.from(badgeP.childNodes).forEach(function(node) {
					if (node.nodeType === Node.TEXT_NODE && node.textContent.trim() === '') {
						node.remove();
					}
				});
			}
		})();
		</script>

		<?php
		// Get theme footer.
		get_footer();
	}

	/**
	 * Render error page.
	 *
	 * @param string $message Error message.
	 */
	private function render_error_page( $message ) {
		status_header( 404 );

		// Set page title.
		add_filter( 'pre_get_document_title', function() {
			return __( 'Repository Not Found', 'kashiwazaki-github-repo-display' ) . ' - ' . get_bloginfo( 'name' );
		} );

		// Enqueue styles.
		wp_enqueue_style(
			'kgrd-detail-page',
			KGRD_PLUGIN_URL . 'assets/css/detail-page.css',
			array(),
			KGRD_VERSION
		);

		// Get theme header.
		get_header();
		?>

		<main id="main" class="site-main">
			<article class="kgrd-detail-page">
				<div class="kgrd-detail-container">
					<div class="kgrd-error-box">
						<h1><?php esc_html_e( 'Repository Not Found', 'kashiwazaki-github-repo-display' ); ?></h1>
						<p><?php echo esc_html( $message ); ?></p>
					</div>
				</div>
			</article>
		</main>

		<?php
		// Get theme footer.
		get_footer();
	}

	/**
	 * Get the detail page URL for a repository.
	 *
	 * @param string $username GitHub username.
	 * @param string $repo Repository name.
	 * @return string Detail page URL.
	 */
	public static function get_detail_url( $username, $repo ) {
		$base_path = get_option( 'kgrd_detail_base_path', 'software' );
		$base_path = self::sanitize_base_path( $base_path );
		$slug      = sanitize_title( $repo );
		return home_url( '/' . $base_path . '/' . $slug . '/' );
	}

	/**
	 * Get the list page URL.
	 *
	 * @return string List page URL.
	 */
	public static function get_list_url() {
		$base_path = get_option( 'kgrd_detail_base_path', 'software' );
		$base_path = self::sanitize_base_path( $base_path );
		return home_url( '/' . $base_path . '/' );
	}

	/**
	 * Get cache duration in seconds.
	 *
	 * @return int Cache duration.
	 */
	private function get_cache_duration() {
		$hours = get_option( 'kgrd_cache_expiration', 6 );
		$hours = absint( $hours );
		$hours = apply_filters( 'kgrd_api_cache_expiration', $hours );

		$base_seconds = $hours * HOUR_IN_SECONDS;

		// Check if jitter is enabled.
		$enable_jitter = get_option( 'kgrd_enable_cache_jitter', 1 );

		if ( $enable_jitter ) {
			$jitter_percent = get_option( 'kgrd_cache_jitter_percent', 20 );
			$jitter_percent = $jitter_percent / 100;
			$jitter_range   = (int) ( $base_seconds * $jitter_percent );
			$jitter         = wp_rand( -$jitter_range, $jitter_range );

			return $base_seconds + $jitter;
		}

		return $base_seconds;
	}
}
