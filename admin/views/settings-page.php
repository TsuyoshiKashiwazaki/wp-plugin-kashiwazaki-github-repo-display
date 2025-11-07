<?php
/**
 * Settings page template.
 *
 * @package Kashiwazaki_GitHub_Repo_Display
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get cache stats.
$admin       = KGRD_Admin_Settings::get_instance();
$cache_stats = $admin->get_cache_stats();

// Get current tab.
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
?>

<div class="wrap">
	<h1>GitHub リポジトリ表示設定</h1>

	<?php settings_errors( 'kgrd_messages' ); ?>

	<!-- タブナビゲーション -->
	<h2 class="nav-tab-wrapper">
		<a href="?page=<?php echo esc_attr( $_GET['page'] ); ?>&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
			基本設定
		</a>
		<a href="?page=<?php echo esc_attr( $_GET['page'] ); ?>&tab=badges" class="nav-tab <?php echo $current_tab === 'badges' ? 'nav-tab-active' : ''; ?>">
			バッジ設定
		</a>
		<a href="?page=<?php echo esc_attr( $_GET['page'] ); ?>&tab=design" class="nav-tab <?php echo $current_tab === 'design' ? 'nav-tab-active' : ''; ?>">
			デザイン設定
		</a>
		<a href="?page=<?php echo esc_attr( $_GET['page'] ); ?>&tab=cache" class="nav-tab <?php echo $current_tab === 'cache' ? 'nav-tab-active' : ''; ?>">
			キャッシュ管理
		</a>
	</h2>

	<div class="kgrd-settings-content" style="margin-top: 20px;">
		<?php if ( 'general' === $current_tab ) : ?>
			<!-- 基本設定タブ -->
			<div class="kgrd-tab-content">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'kgrd_general_group' );
					do_settings_sections( 'kgrd-general' );
					submit_button( '設定を保存' );
					?>
				</form>

				<!-- クイックスタートガイド -->
				<div class="card" style="max-width: 800px; margin-top: 20px;">
					<h2>クイックスタート</h2>

					<h3 style="margin-top: 20px;">📦 単一リポジトリの表示</h3>
					<p>投稿やページに以下のショートコードを追加してください：</p>
					<code style="display: block; background: #f6f8fa; padding: 10px; border-radius: 4px; margin: 10px 0;">
						[kashiwazaki_github_repo repo="リポジトリ名"]
					</code>

					<h3 style="margin-top: 20px;">📋 複数リポジトリの表示</h3>
					<p>複数のリポジトリをグリッド表示する場合：</p>
					<code style="display: block; background: #f6f8fa; padding: 10px; border-radius: 4px; margin: 10px 0;">
						[kashiwazaki_github_repos repos="repo1,repo2,repo3" columns="2"]
					</code>

					<h3 style="margin-top: 20px;">🚀 全リポジトリの自動取得</h3>
					<p>GitHubユーザーの全リポジトリを自動的に取得して表示：</p>
					<code style="display: block; background: #f6f8fa; padding: 10px; border-radius: 4px; margin: 10px 0;">
						[kashiwazaki_github_user_repos]
					</code>
					<p style="margin-top: 10px;"><strong>パラメータ例：</strong></p>
					<ul style="margin-left: 20px;">
						<li><code>username="ユーザー名"</code> - 対象のGitHubユーザー（デフォルト: 上記設定値）</li>
						<li><code>columns="3"</code> - グリッドの列数（1-4、デフォルト: 2）</li>
						<li><code>limit="20"</code> - 表示する最大数（1-100、デフォルト: 30）</li>
						<li><code>exclude_forks="true"</code> - フォークを除外（デフォルト: false）</li>
						<li><code>sort="updated"</code> - ソート順（created/updated/pushed/full_name）</li>
						<li><code>direction="desc"</code> - ソート方向（asc/desc）</li>
					</ul>
					<p style="margin-top: 10px;"><strong>使用例：</strong></p>
					<code style="display: block; background: #f6f8fa; padding: 10px; border-radius: 4px; margin: 10px 0;">
						[kashiwazaki_github_user_repos columns="3" limit="20" exclude_forks="true"]
					</code>

					<h3 style="margin-top: 20px;">🎨 表示スタイル</h3>
					<p>単一リポジトリ表示で使用可能なスタイル：</p>
					<ul>
						<li><strong>card</strong>（デフォルト）: 詳細情報を含む完全なカード</li>
						<li><strong>minimal</strong>: タイトルと説明のみのシンプル表示</li>
						<li><strong>badges-only</strong>: バッジのみの表示</li>
					</ul>
					<code style="display: block; background: #f6f8fa; padding: 10px; border-radius: 4px; margin: 10px 0;">
						[kashiwazaki_github_repo repo="リポジトリ名" style="minimal"]
					</code>

					<h3 style="margin-top: 20px;">💡 ショートコードの使い分け</h3>
					<ul>
						<li><strong>kashiwazaki_github_repo</strong> - 特定の1つのリポジトリを詳しく紹介したい場合</li>
						<li><strong>kashiwazaki_github_repos</strong> - 特定の複数リポジトリを指定した順序で表示したい場合</li>
						<li><strong>kashiwazaki_github_user_repos</strong> - ユーザーの全リポジトリを自動で取得・表示したい場合（ポートフォリオに最適）</li>
					</ul>
				</div>
			</div>

		<?php elseif ( 'badges' === $current_tab ) : ?>
			<!-- バッジ設定タブ -->
			<div class="kgrd-tab-content">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'kgrd_badges_group' );
					do_settings_sections( 'kgrd-badges' );
					submit_button( 'バッジ設定を保存' );
					?>
				</form>

				<!-- バッジプレビュー -->
				<div class="card" style="max-width: 800px; margin-top: 20px;">
					<h2>バッジプレビュー</h2>
					<p>選択したバッジは以下のように表示されます：</p>
					<div style="margin-top: 15px; display: flex; flex-wrap: wrap; gap: 8px;">
						<?php
						$username = get_option( 'kgrd_default_username', 'TsuyoshiKashiwazaki' );
						$repo     = 'example-repo';

						$badge_configs = array(
							'last_commit' => "https://img.shields.io/github/last-commit/{$username}/{$repo}",
							'license'     => "https://img.shields.io/github/license/{$username}/{$repo}",
							'stars'       => "https://img.shields.io/github/stars/{$username}/{$repo}",
							'issues'      => "https://img.shields.io/github/issues/{$username}/{$repo}",
							'forks'       => "https://img.shields.io/github/forks/{$username}/{$repo}",
							'downloads'   => "https://img.shields.io/github/downloads/{$username}/{$repo}/total",
							'language'    => "https://img.shields.io/github/languages/top/{$username}/{$repo}",
							'code_size'   => "https://img.shields.io/github/languages/code-size/{$username}/{$repo}",
						);

						foreach ( $badge_configs as $key => $url ) {
							$enabled = get_option( 'kgrd_badge_' . $key, in_array( $key, array( 'last_commit', 'license', 'stars' ) ) ? 1 : 0 );
							if ( $enabled ) {
								echo '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $key ) . '" style="height: 20px;">';
							}
						}
						?>
					</div>
				</div>
			</div>

		<?php elseif ( 'design' === $current_tab ) : ?>
			<!-- デザイン設定タブ -->
			<div class="kgrd-tab-content">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'kgrd_design_group' );
					do_settings_sections( 'kgrd-design' );
					submit_button( 'デザイン設定を保存' );
					?>
				</form>

				<!-- プレビュー -->
				<div class="card" style="max-width: 800px; margin-top: 20px;">
					<h2>プレビュー</h2>
					<p>現在の設定でのカード表示イメージ：</p>
					<div style="margin-top: 15px;">
						<?php
						// プレビュー用のスタイルを生成
						$preview_primary    = get_option( 'kgrd_primary_color', '#0366d6' );
						$preview_secondary  = get_option( 'kgrd_secondary_color', '#586069' );
						$preview_border     = get_option( 'kgrd_border_color', '#e1e4e8' );
						$preview_background = get_option( 'kgrd_background_color', '#ffffff' );
						$preview_text       = get_option( 'kgrd_text_color', '#24292e' );
						$preview_radius     = get_option( 'kgrd_border_radius', 6 );
						$preview_spacing    = get_option( 'kgrd_spacing', 16 );
						?>
						<div style="
							background: <?php echo esc_attr( $preview_background ); ?>;
							border: 1px solid <?php echo esc_attr( $preview_border ); ?>;
							border-radius: <?php echo esc_attr( $preview_radius ); ?>px;
							padding: <?php echo esc_attr( $preview_spacing ); ?>px;
							max-width: 500px;
						">
							<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: <?php echo esc_attr( $preview_spacing * 0.75 ); ?>px; padding-bottom: <?php echo esc_attr( $preview_spacing * 0.75 ); ?>px; border-bottom: 1px solid <?php echo esc_attr( $preview_border ); ?>;">
								<h3 style="margin: 0; color: <?php echo esc_attr( $preview_text ); ?>; font-size: 18px;">サンプルリポジトリ</h3>
								<svg style="width: 20px; height: 20px; color: <?php echo esc_attr( $preview_secondary ); ?>;" viewBox="0 0 16 16">
									<path fill="currentColor" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path>
								</svg>
							</div>
							<p style="margin: 0 0 <?php echo esc_attr( $preview_spacing ); ?>px 0; color: <?php echo esc_attr( $preview_secondary ); ?>; font-size: 14px;">
								リポジトリの説明文がここに表示されます。設定したデザインがプレビューに反映されます。
							</p>
							<div style="display: flex; gap: 12px; font-size: 12px; color: <?php echo esc_attr( $preview_secondary ); ?>; margin-bottom: <?php echo esc_attr( $preview_spacing ); ?>px;">
								<span>★ 123</span>
								<span>⑂ 45</span>
								<span>JavaScript</span>
							</div>
							<a href="#" style="
								display: inline-block;
								padding: 8px 16px;
								background: <?php echo esc_attr( $preview_primary ); ?>;
								color: #ffffff;
								text-decoration: none;
								border-radius: <?php echo esc_attr( $preview_radius ); ?>px;
								font-size: 14px;
							">GitHubで見る</a>
						</div>
					</div>
					<p style="margin-top: 15px;">
						<small>※ 実際の表示は、選択したバッジや設定によって異なります。</small>
					</p>
				</div>
			</div>

		<?php elseif ( 'cache' === $current_tab ) : ?>
			<!-- キャッシュ管理タブ -->
			<div class="kgrd-tab-content">
				<div class="card" style="max-width: 800px;">
					<h2>キャッシュの仕組み</h2>
					<p>このプラグインは<strong>2層のキャッシュシステム</strong>を使用してパフォーマンスを最適化しています：</p>

					<h3 style="margin-top: 20px;">第1層: APIデータキャッシュ</h3>
					<ul style="margin-left: 20px;">
						<li><strong>対象:</strong> GitHub APIから取得した生データ（リポジトリ情報、ユーザーリポジトリリスト）</li>
						<li><strong>期間:</strong> <?php echo esc_html( get_option( 'kgrd_cache_expiration', 6 ) ); ?>時間（基本設定で変更可能）</li>
						<li><strong>目的:</strong> GitHub APIへのリクエスト数を削減し、レート制限を回避</li>
					</ul>

					<h3 style="margin-top: 20px;">第2層: HTML出力キャッシュ</h3>
					<ul style="margin-left: 20px;">
						<li><strong>対象:</strong> レンダリング済みのHTML</li>
						<li><strong>期間:</strong> <?php echo esc_html( get_option( 'kgrd_cache_expiration', 6 ) ); ?>時間 ± ランダムジッター（20%）</li>
						<li><strong>目的:</strong> ページ表示速度の向上、複数キャッシュの同時期限切れ防止</li>
					</ul>

					<p style="margin-top: 15px; padding: 12px; background: #f0f6fc; border-left: 4px solid #0969da; border-radius: 4px;">
						<strong>💡 ジッターとは？</strong><br>
						キャッシュの有効期限にランダムな時間差（±20%）を追加する仕組みです。これにより、大量のキャッシュが同時に期限切れになることを防ぎ、サーバーへの負荷を分散させます。
					</p>
				</div>

				<div class="card" style="max-width: 800px; margin-top: 20px;">
					<h2>キャッシュ統計</h2>
					<p style="font-size: 16px;">
						現在 <strong><?php echo esc_html( $cache_stats['count'] ); ?></strong> 件のリポジトリ情報をキャッシュしています。
					</p>

					<h3 style="margin-top: 30px;">キャッシュのクリア</h3>
					<p>
						キャッシュをクリアすると、次回アクセス時にGitHub APIから最新の情報を取得します。<br>
						※ APIのレート制限に注意してください（認証なしの場合、1時間あたり60リクエストまで）。
					</p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 20px;">
						<input type="hidden" name="action" value="kgrd_clear_cache">
						<?php wp_nonce_field( 'kgrd_clear_cache', 'kgrd_clear_cache_nonce' ); ?>
						<p>
							<button type="submit" class="button button-secondary" onclick="return confirm('本当にキャッシュをクリアしますか？');">
								すべてのキャッシュをクリア
							</button>
						</p>
					</form>
				</div>

				<div class="card" style="max-width: 800px; margin-top: 20px;">
					<h2>GitHub APIについて</h2>
					<table class="widefat" style="margin-top: 15px;">
						<thead>
							<tr>
								<th>項目</th>
								<th>制限</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>認証なしのレート制限</td>
								<td>60リクエスト/時間（IPアドレス毎）</td>
							</tr>
							<tr>
								<td>認証ありのレート制限</td>
								<td>5,000リクエスト/時間（ユーザー毎）</td>
							</tr>
							<tr>
								<td>デフォルトキャッシュ時間</td>
								<td><?php echo esc_html( get_option( 'kgrd_cache_expiration', 6 ) ); ?>時間</td>
							</tr>
						</tbody>
					</table>
					<p style="margin-top: 15px;">
						<small>
							※ 認証機能は今後のバージョンで実装予定です。<br>
							※ レート制限の詳細は<a href="https://docs.github.com/ja/rest/overview/resources-in-the-rest-api#rate-limiting" target="_blank" rel="noopener noreferrer">GitHub公式ドキュメント</a>をご確認ください。
						</small>
					</p>
				</div>
			</div>

		<?php endif; ?>
	</div>
</div>

<style>
.kgrd-tab-content {
	background: #fff;
	border: 1px solid #ccd0d4;
	border-top: none;
	padding: 20px;
}

.card {
	background: #fff;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
	padding: 20px;
}

.card h2 {
	margin-top: 0;
	font-size: 16px;
	font-weight: 600;
	border-bottom: 1px solid #e1e4e8;
	padding-bottom: 10px;
}

.card h3 {
	font-size: 14px;
	font-weight: 600;
	margin-top: 15px;
	margin-bottom: 8px;
}

.widefat {
	width: 100%;
	border-collapse: collapse;
}

.widefat th,
.widefat td {
	padding: 10px;
	text-align: left;
	border: 1px solid #ccd0d4;
}

.widefat thead th {
	background: #f6f7f7;
	font-weight: 600;
}

.widefat tbody tr:nth-child(even) {
	background: #f9f9f9;
}

code {
	font-family: Consolas, Monaco, monospace;
	font-size: 13px;
}

.wp-color-result {
	height: 30px !important;
}
</style>
