<?php
/**
 * Admin settings class.
 *
 * @package Kashiwazaki_GitHub_Repo_Display
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KGRD_Admin_Settings
 *
 * Handles all admin settings and configuration.
 */
class KGRD_Admin_Settings {

	/**
	 * Single instance of the class.
	 *
	 * @var KGRD_Admin_Settings
	 */
	private static $instance = null;

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	private $page_slug = 'kgrd-settings';

	/**
	 * Get the single instance of the class.
	 *
	 * @return KGRD_Admin_Settings
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
		// Always output custom styles on frontend.
		add_action( 'wp_head', array( $this, 'output_custom_styles' ), 999 );

		// Admin-only hooks.
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'admin_post_kgrd_clear_cache', array( $this, 'handle_clear_cache' ) );
			add_filter( 'plugin_action_links_' . KGRD_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
		}
	}

	/**
	 * Add admin menu page.
	 */
	public function add_admin_menu() {
		add_menu_page(
			'Kashiwazaki GitHub Repository Display Settings',
			'Kashiwazaki GitHub Repository Display',
			'manage_options',
			$this->page_slug,
			array( $this, 'render_settings_page' ),
			'dashicons-github',
			82
		);
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing links.
	 * @return array Modified links.
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=' . $this->page_slug ),
			'設定'
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		// 基本設定
		register_setting(
			'kgrd_general_group',
			'kgrd_default_username',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'TsuyoshiKashiwazaki',
			)
		);

		register_setting(
			'kgrd_general_group',
			'kgrd_github_token',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'kgrd_general_group',
			'kgrd_cache_expiration',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_cache_expiration' ),
				'default'           => 6,
			)
		);

		register_setting(
			'kgrd_general_group',
			'kgrd_enable_cache_jitter',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => 1,
			)
		);

		register_setting(
			'kgrd_general_group',
			'kgrd_cache_jitter_percent',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_jitter_percent' ),
				'default'           => 20,
			)
		);

		// バッジ設定
		$badge_types = array(
			// メジャー（デフォルトON）
			'version', 'last_commit', 'license', 'stars', 'forks', 'issues', 'language', 'contributors',
			// マイナー（デフォルトOFF）
			'watchers', 'open_prs', 'closed_issues', 'downloads', 'code_size', 'repo_size', 'commit_activity', 'release_date'
		);
		$major_badges = array( 'version', 'last_commit', 'license', 'stars', 'forks', 'issues', 'language', 'contributors' );

		foreach ( $badge_types as $badge ) {
			register_setting(
				'kgrd_badges_group',
				'kgrd_badge_' . $badge,
				array(
					'type'              => 'boolean',
					'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
					'default'           => in_array( $badge, $major_badges ) ? 1 : 0,
				)
			);
		}

		// デザイン設定 - カラー（シンプル）
		$color_options = array(
			'button_text'       => '#ffffff',
			'button_bg'         => '#333333',
			'button_border'     => '#333333',
			'card_border'       => '#dddddd',
			'card_background'   => '#ffffff',
			'text_color'        => '#333333',
		);

		foreach ( $color_options as $option => $default ) {
			register_setting(
				'kgrd_design_group',
				'kgrd_' . $option,
				array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_hex_color',
					'default'           => $default,
				)
			);
		}

		// デザイン設定 - レイアウト
		$layout_options = array(
			'border_radius' => 0,
			'spacing'       => 16,
		);

		foreach ( $layout_options as $option => $default ) {
			register_setting(
				'kgrd_design_group',
				'kgrd_' . $option,
				array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'default'           => $default,
				)
			);
		}

		// デザイン設定 - 装飾オプション
		$decoration_options = array(
			'enable_shadow'     => 0,
			'enable_animation'  => 0,
			'enable_hover_lift' => 0,
		);

		foreach ( $decoration_options as $option => $default ) {
			register_setting(
				'kgrd_design_group',
				'kgrd_' . $option,
				array(
					'type'              => 'boolean',
					'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
					'default'           => $default,
				)
			);
		}

		// 基本設定セクション
		add_settings_section(
			'kgrd_general_section',
			'基本設定',
			array( $this, 'render_general_section' ),
			'kgrd-general'
		);

		add_settings_field(
			'kgrd_default_username',
			'デフォルトGitHubユーザー名',
			array( $this, 'render_username_field' ),
			'kgrd-general',
			'kgrd_general_section'
		);

		add_settings_field(
			'kgrd_github_token',
			'GitHubパーソナルアクセストークン',
			array( $this, 'render_token_field' ),
			'kgrd-general',
			'kgrd_general_section'
		);

		add_settings_field(
			'kgrd_cache_expiration',
			'キャッシュ有効期限（時間）',
			array( $this, 'render_cache_field' ),
			'kgrd-general',
			'kgrd_general_section'
		);

		add_settings_field(
			'kgrd_cache_jitter',
			'キャッシュ更新タイミングの分散（Jitter）',
			array( $this, 'render_jitter_field' ),
			'kgrd-general',
			'kgrd_general_section'
		);

		// バッジ設定セクション
		add_settings_section(
			'kgrd_badges_section',
			'バッジ設定',
			array( $this, 'render_badges_section' ),
			'kgrd-badges'
		);

		add_settings_field(
			'kgrd_badge_selection',
			'表示するバッジ',
			array( $this, 'render_badge_fields' ),
			'kgrd-badges',
			'kgrd_badges_section'
		);

		// デザイン設定セクション
		add_settings_section(
			'kgrd_design_section',
			'デザイン設定',
			array( $this, 'render_design_section' ),
			'kgrd-design'
		);

		add_settings_field(
			'kgrd_colors',
			'カラー設定',
			array( $this, 'render_color_fields' ),
			'kgrd-design',
			'kgrd_design_section'
		);

		add_settings_field(
			'kgrd_layout',
			'レイアウト設定',
			array( $this, 'render_layout_fields' ),
			'kgrd-design',
			'kgrd_design_section'
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our settings page.
		// For top-level menu pages, the hook is 'toplevel_page_' + slug
		if ( 'toplevel_page_' . $this->page_slug !== $hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style(
			'kgrd-admin',
			KGRD_PLUGIN_URL . 'assets/css/repo-card.css',
			array(),
			KGRD_VERSION
		);

		wp_add_inline_script(
			'wp-color-picker',
			'jQuery(document).ready(function($) { $(".kgrd-color-picker").wpColorPicker(); });'
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if cache was cleared.
		if ( isset( $_GET['cache-cleared'] ) && '1' === $_GET['cache-cleared'] ) {
			add_settings_error(
				'kgrd_messages',
				'kgrd_cache_cleared',
				'キャッシュをクリアしました。',
				'success'
			);
		}

		// Include the settings page template.
		require_once KGRD_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Render general section description.
	 */
	public function render_general_section() {
		echo '<p>GitHubリポジトリ表示の基本設定を行います。</p>';
	}

	/**
	 * Render badges section description.
	 */
	public function render_badges_section() {
		echo '<p>リポジトリカードに表示するバッジを選択してください。</p>';
	}

	/**
	 * Render design section description.
	 */
	public function render_design_section() {
		echo '<p>リポジトリカードのデザインをカスタマイズできます。</p>';
	}

	/**
	 * Render username field.
	 */
	public function render_username_field() {
		$value = get_option( 'kgrd_default_username', 'TsuyoshiKashiwazaki' );
		?>
		<input
			type="text"
			id="kgrd_default_username"
			name="kgrd_default_username"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<p class="description">
			ショートコードでユーザー名が指定されていない場合に使用されます。
		</p>
		<?php
	}

	/**
	 * Render GitHub token field.
	 */
	public function render_token_field() {
		$value = get_option( 'kgrd_github_token', '' );
		?>
		<input
			type="password"
			id="kgrd_github_token"
			name="kgrd_github_token"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			autocomplete="off"
		/>
		<p class="description">
			APIレート制限を回避するためのトークン（オプション）。<a href="https://github.com/settings/tokens" target="_blank">GitHubで作成</a>してください。
		</p>
		<?php
	}

	/**
	 * Render cache expiration field.
	 */
	public function render_cache_field() {
		$value = get_option( 'kgrd_cache_expiration', 6 );
		?>
		<input
			type="number"
			id="kgrd_cache_expiration"
			name="kgrd_cache_expiration"
			value="<?php echo esc_attr( $value ); ?>"
			min="1"
			max="24"
			class="small-text"
		/> 時間
		<p class="description">
			GitHub APIのレスポンスをキャッシュする時間を設定します（1〜24時間）。
		</p>
		<?php
	}

	/**
	 * Render cache jitter field.
	 */
	public function render_jitter_field() {
		$enable_jitter   = get_option( 'kgrd_enable_cache_jitter', 1 );
		$jitter_percent  = get_option( 'kgrd_cache_jitter_percent', 20 );
		$cache_hours     = get_option( 'kgrd_cache_expiration', 6 );

		// Calculate example range
		$min_hours = $cache_hours * ( 1 - $jitter_percent / 100 );
		$max_hours = $cache_hours * ( 1 + $jitter_percent / 100 );
		?>
		<fieldset>
			<label style="display: block; margin-bottom: 12px;">
				<input
					type="checkbox"
					name="kgrd_enable_cache_jitter"
					value="1"
					<?php checked( 1, $enable_jitter ); ?>
				/>
				キャッシュ更新タイミングの分散を有効化
			</label>

			<div style="margin-left: 24px; margin-top: 12px;">
				<label style="display: block; margin-bottom: 8px;">
					<strong>分散率：</strong>
				</label>
				<label style="display: inline-block; margin-right: 16px;">
					<input
						type="radio"
						name="kgrd_cache_jitter_percent"
						value="10"
						<?php checked( 10, $jitter_percent ); ?>
					/>
					10%
				</label>
				<label style="display: inline-block; margin-right: 16px;">
					<input
						type="radio"
						name="kgrd_cache_jitter_percent"
						value="20"
						<?php checked( 20, $jitter_percent ); ?>
					/>
					20%（推奨）
				</label>
				<label style="display: inline-block;">
					<input
						type="radio"
						name="kgrd_cache_jitter_percent"
						value="30"
						<?php checked( 30, $jitter_percent ); ?>
					/>
					30%
				</label>
			</div>
		</fieldset>
		<p class="description">
			複数のリポジトリのキャッシュ更新タイミングをずらすことで、ページロード時の負荷を軽減します。<br>
			<strong>現在の設定：</strong> <?php echo esc_html( $cache_hours ); ?>時間 + <?php echo esc_html( $jitter_percent ); ?>%分散
			→ <strong><?php echo esc_html( number_format( $min_hours, 1 ) ); ?>〜<?php echo esc_html( number_format( $max_hours, 1 ) ); ?>時間</strong>の範囲でランダムに期限切れ
		</p>
		<?php
	}

	/**
	 * Render badge selection fields.
	 */
	public function render_badge_fields() {
		$major_badges = array(
			'version'      => 'Version（バージョン）',
			'last_commit'  => 'Last Commit（最終コミット）',
			'license'      => 'License（ライセンス）',
			'stars'        => 'Stars（スター数）',
			'forks'        => 'Forks（フォーク数）',
			'issues'       => 'Issues（イシュー数）',
			'language'     => 'Language（主要言語）',
			'contributors' => 'Contributors（コントリビューター数）',
		);

		$minor_badges = array(
			'watchers'        => 'Watchers（ウォッチャー数）',
			'open_prs'        => 'Open PRs（オープンPR数）',
			'closed_issues'   => 'Closed Issues（クローズ済みイシュー数）',
			'downloads'       => 'Downloads（ダウンロード数）',
			'code_size'       => 'Code Size（コードサイズ）',
			'repo_size'       => 'Repository Size（リポジトリサイズ）',
			'commit_activity' => 'Commit Activity（月間コミット数）',
			'release_date'    => 'Release Date（リリース日）',
		);

		$major_badge_keys = array_keys( $major_badges );

		echo '<fieldset>';
		echo '<p><strong>メジャーなバッジ（推奨）</strong></p>';
		foreach ( $major_badges as $key => $label ) {
			$option_name = 'kgrd_badge_' . $key;
			$checked     = get_option( $option_name, in_array( $key, $major_badge_keys ) ? 1 : 0 );
			?>
			<label style="display: inline-block; min-width: 300px; margin-bottom: 10px;">
				<input
					type="checkbox"
					name="<?php echo esc_attr( $option_name ); ?>"
					value="1"
					<?php checked( 1, $checked ); ?>
				/>
				<?php echo esc_html( $label ); ?>
			</label>
			<?php
		}

		echo '<p style="margin-top: 20px;"><strong>マイナーなバッジ（オプション）</strong></p>';
		foreach ( $minor_badges as $key => $label ) {
			$option_name = 'kgrd_badge_' . $key;
			$checked     = get_option( $option_name, 0 );
			?>
			<label style="display: inline-block; min-width: 300px; margin-bottom: 10px;">
				<input
					type="checkbox"
					name="<?php echo esc_attr( $option_name ); ?>"
					value="1"
					<?php checked( 1, $checked ); ?>
				/>
				<?php echo esc_html( $label ); ?>
			</label>
			<?php
		}
		echo '</fieldset>';
		echo '<p class="description">表示したいバッジにチェックを入れてください。メジャーなバッジは一般的に使われるもので、デフォルトで有効です。</p>';
	}

	/**
	 * Render color fields.
	 */
	public function render_color_fields() {
		$colors = array(
			'button_text'     => array(
				'label'   => 'ボタンテキスト色',
				'default' => '#ffffff',
				'desc'    => '全てのボタンのテキスト色',
			),
			'button_bg'       => array(
				'label'   => 'ボタン背景色',
				'default' => '#333333',
				'desc'    => '全てのボタンの背景色',
			),
			'button_border'   => array(
				'label'   => 'ボタンボーダー色',
				'default' => '#333333',
				'desc'    => '全てのボタンのボーダー色',
			),
			'card_border'     => array(
				'label'   => 'カードボーダー色',
				'default' => '#dddddd',
				'desc'    => 'カード全体の境界線の色',
			),
			'card_background' => array(
				'label'   => 'カード背景色',
				'default' => '#ffffff',
				'desc'    => 'カード全体の背景色',
			),
			'text_color'      => array(
				'label'   => 'テキスト色',
				'default' => '#333333',
				'desc'    => '全体のテキスト色',
			),
		);

		echo '<table class="form-table"><tbody>';
		foreach ( $colors as $key => $color_data ) {
			$option_name = 'kgrd_' . $key;
			$value       = get_option( $option_name, $color_data['default'] );
			?>
			<tr>
				<th scope="row"><?php echo esc_html( $color_data['label'] ); ?></th>
				<td>
					<input
						type="text"
						name="<?php echo esc_attr( $option_name ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						class="kgrd-color-picker"
						data-default-color="<?php echo esc_attr( $color_data['default'] ); ?>"
					/>
					<p class="description"><?php echo esc_html( $color_data['desc'] ); ?></p>
				</td>
			</tr>
			<?php
		}
		echo '</tbody></table>';
	}

	/**
	 * Render layout fields.
	 */
	public function render_layout_fields() {
		$border_radius     = get_option( 'kgrd_border_radius', 0 );
		$spacing           = get_option( 'kgrd_spacing', 16 );
		$enable_shadow     = get_option( 'kgrd_enable_shadow', 0 );
		$enable_animation  = get_option( 'kgrd_enable_animation', 0 );
		$enable_hover_lift = get_option( 'kgrd_enable_hover_lift', 0 );
		?>
		<table class="form-table"><tbody>
			<tr>
				<th scope="row">角の丸み</th>
				<td>
					<input
						type="number"
						name="kgrd_border_radius"
						value="<?php echo esc_attr( $border_radius ); ?>"
						min="0"
						max="50"
						class="small-text"
					/> px
					<p class="description">カードの角の丸みを設定します（0〜50px）。</p>
				</td>
			</tr>
			<tr>
				<th scope="row">余白</th>
				<td>
					<input
						type="number"
						name="kgrd_spacing"
						value="<?php echo esc_attr( $spacing ); ?>"
						min="8"
						max="32"
						class="small-text"
					/> px
					<p class="description">カード内の余白を設定します（8〜32px）。</p>
				</td>
			</tr>
			<tr>
				<th scope="row">装飾オプション</th>
				<td>
					<fieldset>
						<label style="display: block; margin-bottom: 8px;">
							<input
								type="checkbox"
								name="kgrd_enable_shadow"
								value="1"
								<?php checked( 1, $enable_shadow ); ?>
							/>
							シャドウ（影）を表示
						</label>
						<label style="display: block; margin-bottom: 8px;">
							<input
								type="checkbox"
								name="kgrd_enable_animation"
								value="1"
								<?php checked( 1, $enable_animation ); ?>
							/>
							アニメーション効果を有効化
						</label>
						<label style="display: block; margin-bottom: 8px;">
							<input
								type="checkbox"
								name="kgrd_enable_hover_lift"
								value="1"
								<?php checked( 1, $enable_hover_lift ); ?>
							/>
							ホバー時の浮き上がり効果を有効化
						</label>
					</fieldset>
					<p class="description">各装飾効果を有効にするかどうかを選択できます。</p>
				</td>
			</tr>
		</tbody></table>
		<?php
	}

	/**
	 * Sanitize checkbox value.
	 *
	 * @param mixed $value Input value.
	 * @return int Sanitized value.
	 */
	public function sanitize_checkbox( $value ) {
		return ! empty( $value ) ? 1 : 0;
	}

	/**
	 * Sanitize cache expiration value.
	 *
	 * @param mixed $value Input value.
	 * @return int Sanitized value.
	 */
	public function sanitize_cache_expiration( $value ) {
		$value = absint( $value );
		return max( 1, min( 24, $value ) );
	}

	/**
	 * Sanitize jitter percent value.
	 *
	 * @param mixed $value Input value.
	 * @return int Sanitized value (10, 20, or 30).
	 */
	public function sanitize_jitter_percent( $value ) {
		$value = absint( $value );
		$allowed = array( 10, 20, 30 );

		if ( ! in_array( $value, $allowed ) ) {
			return 20; // Default to 20%
		}

		return $value;
	}

	/**
	 * Handle cache clearing request.
	 */
	public function handle_clear_cache() {
		// Verify user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'この操作を実行する権限がありません。' );
		}

		// Verify nonce.
		if ( ! isset( $_POST['kgrd_clear_cache_nonce'] ) || ! wp_verify_nonce( $_POST['kgrd_clear_cache_nonce'], 'kgrd_clear_cache' ) ) {
			wp_die( 'セキュリティチェックに失敗しました。' );
		}

		// Clear cache.
		$api   = KGRD_GitHub_API::get_instance();
		$count = $api->clear_all_cache();

		// Redirect back to settings page with success message.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => $this->page_slug,
					'cache-cleared' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Get cache statistics.
	 *
	 * @return array Cache statistics.
	 */
	public function get_cache_stats() {
		global $wpdb;

		// Count shortcode output cache (repository data cache is disabled)
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_kgrd_output_%'
			)
		);

		return array(
			'count' => absint( $count ),
		);
	}

	/**
	 * Output custom CSS styles based on settings.
	 */
	public function output_custom_styles() {
		// シンプルな色設定
		$button_text     = get_option( 'kgrd_button_text', '#ffffff' );
		$button_bg       = get_option( 'kgrd_button_bg', '#333333' );
		$button_border   = get_option( 'kgrd_button_border', '#333333' );
		$card_border     = get_option( 'kgrd_card_border', '#dddddd' );
		$card_background = get_option( 'kgrd_card_background', '#ffffff' );
		$text_color      = get_option( 'kgrd_text_color', '#333333' );

		// レイアウト設定
		$border_radius = get_option( 'kgrd_border_radius', 0 );
		$spacing       = get_option( 'kgrd_spacing', 16 );

		// 装飾設定
		$enable_shadow     = get_option( 'kgrd_enable_shadow', 0 );
		$enable_animation  = get_option( 'kgrd_enable_animation', 0 );
		$enable_hover_lift = get_option( 'kgrd_enable_hover_lift', 0 );

		?>
		<style type="text/css" id="kgrd-custom-styles">
			.kgrd-card__button,
			.kgrd-card__button--primary,
			.kgrd-card__button--secondary {
				background-color: <?php echo esc_attr( $button_bg ); ?> !important;
				color: <?php echo esc_attr( $button_text ); ?> !important;
				border-color: <?php echo esc_attr( $button_border ); ?> !important;
			}

			.kgrd-card {
				border-color: <?php echo esc_attr( $card_border ); ?> !important;
				background-color: <?php echo esc_attr( $card_background ); ?> !important;
				border-radius: <?php echo esc_attr( $border_radius ); ?>px !important;
				padding: <?php echo esc_attr( $spacing ); ?>px !important;
				box-shadow: <?php echo $enable_shadow ? '0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24)' : 'none'; ?> !important;
				animation: <?php echo $enable_animation ? 'kgrd-fade-in 0.3s ease-out' : 'none'; ?> !important;
			}

			.kgrd-card:hover {
				box-shadow: <?php echo $enable_shadow ? '0 3px 6px rgba(0, 0, 0, 0.15), 0 2px 4px rgba(0, 0, 0, 0.12)' : 'none'; ?> !important;
				transform: <?php echo $enable_hover_lift ? 'translateY(-2px)' : 'none'; ?> !important;
			}

			.kgrd-card,
			.kgrd-card *:not(.kgrd-card__button) {
				color: <?php echo esc_attr( $text_color ); ?> !important;
			}

			.kgrd-card__header,
			.kgrd-card__stats {
				border-color: <?php echo esc_attr( $card_border ); ?> !important;
			}
		</style>
		<?php
	}
}
