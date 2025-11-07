=== Kashiwazaki GitHub Repository Display ===
Contributors: tsuyoshikashiwazaki
Tags: github, repository, api, shortcode, developer
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.0.0-dev
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display GitHub repository information dynamically on your WordPress site. Simply specify a repository name to fetch and display the latest information from the GitHub API.

== Description ==

**Kashiwazaki GitHub Repository Display** is a powerful and easy-to-use WordPress plugin that allows you to showcase your GitHub repositories on your WordPress website with beautiful, responsive cards.

### Features

* **Easy Integration**: Display any GitHub repository with a simple shortcode
* **Multiple Display Styles**: Choose from card, minimal, or badges-only styles
* **Automatic Data Fetching**: Retrieves repository information directly from GitHub API
* **Smart Caching**: Caches API responses to improve performance and reduce API calls
* **README Title Extraction**: Automatically extracts and displays the repository title from README.md
* **Responsive Design**: Looks great on all devices and screen sizes
* **Shields.io Badges**: Automatically displays repository badges (stars, license, last commit)
* **Grid Layout Support**: Display multiple repositories in a responsive grid
* **Customizable**: Multiple filter hooks for customization
* **Dark Mode Support**: Automatically adapts to dark mode preferences
* **Accessibility**: Keyboard navigation and screen reader friendly

### Available Shortcodes

**Single Repository:**
`[kashiwazaki_github_repo repo="repository-name"]`

**With Custom Username and Style:**
`[kashiwazaki_github_repo repo="repository-name" username="github-username" style="card"]`

**Multiple Repositories:**
`[kashiwazaki_github_repos repos="repo1,repo2,repo3" columns="2"]`

**All User Repositories (Auto-Fetch):**
`[kashiwazaki_github_user_repos]`

**All User Repositories with Custom Parameters:**
`[kashiwazaki_github_user_repos username="octocat" columns="3" limit="20" exclude_forks="true"]`

### Display Styles

* **card** - Full card with title, description, stats, badges, and action buttons
* **minimal** - Simple display with title and description only
* **badges-only** - Display only Shields.io badges

### What Information is Displayed

* Repository title (from README.md H1 or repository name)
* Description
* Stars count
* Forks count
* Primary language
* Last updated time
* License information
* Shields.io badges (Last Commit, License, Stars)
* Links to repository, download, and documentation

### Filter Hooks

The plugin provides several filter hooks for customization:

* `kgrd_api_cache_expiration` - Modify cache expiration time
* `kgrd_repo_card_html` - Customize card HTML output
* `kgrd_default_username` - Change default GitHub username
* `kgrd_badge_urls` - Customize badge URLs

### Requirements

* WordPress 5.0 or higher
* PHP 7.2 or higher
* Internet connection for GitHub API access

== Installation ==

### Automatic Installation

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "Kashiwazaki GitHub Repository Display"
4. Click "Install Now" and then "Activate"

### Manual Installation

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Click "Activate Plugin"

### Configuration

1. Navigate to Settings > GitHub Repo Display
2. Set your default GitHub username
3. Adjust cache expiration time if needed (default: 6 hours)
4. Use the shortcodes in your posts or pages

== Frequently Asked Questions ==

= Do I need a GitHub API token? =

No, the plugin uses the public GitHub API which doesn't require authentication for public repositories. However, unauthenticated requests are limited to 60 per hour per IP address.

= How often is the repository data updated? =

The plugin uses a 2-layer caching system:
1. GitHub API data cache (repository lists and details) - 6 hours by default
2. Rendered HTML output cache - 6 hours with random jitter by default

You can adjust cache expiration in Settings > GitHub Repo Display or manually clear the cache if you need immediate updates.

= What's the difference between the three shortcodes? =

* `[kashiwazaki_github_repo]` - Display a single repository (requires repo name)
* `[kashiwazaki_github_repos]` - Display multiple repositories (requires comma-separated repo names)
* `[kashiwazaki_github_user_repos]` - Automatically fetch and display all repositories for a user (no repo names needed)

= Can I display private repositories? =

No, this plugin only works with public GitHub repositories as it uses unauthenticated API requests.

= What happens if a repository doesn't exist? =

The plugin will display a user-friendly error message indicating that the repository was not found.

= Can I customize the appearance? =

Yes! The plugin uses CSS custom properties (variables) for easy color customization. You can override these in your theme's CSS or use the provided filter hooks for more advanced customization.

= Does it work with dark mode? =

Yes, the plugin automatically detects and adapts to dark mode preferences using CSS media queries.

= Will it slow down my website? =

No, the plugin includes smart caching and only loads assets when shortcodes are actually used on a page.

= Can I use it with Gutenberg? =

Yes, you can add the shortcodes in a Shortcode block or Classic block in the Gutenberg editor.

== Screenshots ==

1. Card style display showing repository information
2. Settings page with configuration options
3. Minimal style display
4. Grid layout with multiple repositories
5. Badges-only display style
6. Mobile responsive design

== Changelog ==

= 1.0.0 - 2025-10-15 =
* Initial release
* Single repository display shortcode
* Multiple repositories grid display shortcode
* Three display styles (card, minimal, badges-only)
* Admin settings page
* Cache management system
* Responsive design with mobile support
* Dark mode support
* Accessibility features
* README title extraction
* Shields.io badges integration

== Upgrade Notice ==

= 1.0.0 =
Initial release of Kashiwazaki GitHub Repository Display.

== Privacy Policy ==

This plugin makes external HTTP requests to:
* GitHub API (api.github.com) - To fetch repository information
* Shields.io (img.shields.io) - To display repository badges

No personal data is collected or transmitted. All API requests are for publicly available repository information.

== Credits ==

* Developed by Tsuyoshi Kashiwazaki
* Uses GitHub REST API v3
* Badges provided by Shields.io
* Icons from GitHub Octicons

== Support ==

For support, bug reports, or feature requests, please visit:
https://www.tsuyoshikashiwazaki.jp/

== Additional Information ==

* GitHub Repository: Coming soon
* Documentation: See examples.md in plugin directory
* Filters and Hooks: See examples.md for advanced customization
