# Kashiwazaki GitHub Repository Display

A powerful WordPress plugin that displays GitHub repository information dynamically on your WordPress site. Simply specify a repository name to fetch and display the latest information from the GitHub API.

![WordPress Plugin Version](https://img.shields.io/badge/version-1.0.1-blue.svg)
![WordPress Compatibility](https://img.shields.io/badge/wordpress-5.0%2B-blue.svg)
![PHP Version](https://img.shields.io/badge/php-7.2%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)

## Features

- **Easy Integration**: Display any GitHub repository with a simple shortcode
- **Multiple Display Styles**: Choose from card, minimal, or badges-only styles
- **Repository Detail Page**: Individual detail pages with full README content display
- **Automatic Data Fetching**: Retrieves repository information directly from GitHub API
- **Smart Caching**: Caches API responses with configurable expiration and jitter
- **Cache Jitter**: Random variation in cache expiration to prevent cache stampede
- **WP-Cron Support**: Optional background cache refresh for tracked repositories
- **README Title Extraction**: Automatically extracts and displays the repository title from README.md
- **Markdown Rendering**: Full GitHub Flavored Markdown support on detail pages
- **Responsive Design**: Looks great on all devices and screen sizes
- **Shields.io Badges**: Customizable selection of repository badges (16+ types available)
- **Grid Layout Support**: Display multiple repositories in a responsive grid
- **Customizable Base Path**: Multi-level URL path support for detail pages (e.g., `tools/software`)
- **GitHub Token Support**: Optional authentication for higher API rate limits
- **Customizable**: Multiple filter hooks and admin settings for customization
- **Dark Mode Support**: Automatically adapts to dark mode preferences
- **Accessibility**: Keyboard navigation and screen reader friendly

## Installation

### Automatic Installation

1. Log in to your WordPress admin panel
2. Navigate to **Plugins > Add New**
3. Search for "Kashiwazaki GitHub Repository Display"
4. Click "Install Now" and then "Activate"

### Manual Installation

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure settings at **Settings > GitHub Repo Display**

## Usage

### Single Repository

Display a single repository with default settings:

```
[kashiwazaki_github_repo repo="wp-theme-backbone-seo-llmo"]
```

With custom username and style:

```
[kashiwazaki_github_repo repo="repository-name" username="github-username" style="card"]
```

### Multiple Repositories

Display multiple repositories in a grid:

```
[kashiwazaki_github_repos repos="repo1,repo2,repo3" columns="2"]
```

### All User Repositories (Auto-Fetch)

Display all repositories for a GitHub user automatically:

```
[kashiwazaki_github_user_repos]
```

With custom parameters:

```
[kashiwazaki_github_user_repos username="octocat" columns="3" limit="20" exclude_forks="true"]
```

### Shortcode Parameters

#### `[kashiwazaki_github_repo]`

| Parameter | Required | Default | Description |
|-----------|----------|---------|-------------|
| `repo` | Yes | - | Repository name |
| `username` | No | TsuyoshiKashiwazaki | GitHub username |
| `style` | No | card | Display style (card, minimal, badges-only) |

#### `[kashiwazaki_github_repos]`

| Parameter | Required | Default | Description |
|-----------|----------|---------|-------------|
| `repos` | Yes | - | Comma-separated repository names |
| `username` | No | TsuyoshiKashiwazaki | GitHub username |
| `columns` | No | 2 | Number of columns (1-4) |

#### `[kashiwazaki_github_user_repos]`

| Parameter | Required | Default | Description |
|-----------|----------|---------|-------------|
| `username` | No | TsuyoshiKashiwazaki | GitHub username |
| `columns` | No | 2 | Number of columns (1-4) |
| `limit` | No | 30 | Maximum number of repositories to display (1-100) |
| `sort` | No | updated | Sort order (created, updated, pushed, full_name) |
| `direction` | No | desc | Sort direction (asc, desc) |
| `type` | No | owner | Repository type (all, owner, public, private, member) |
| `exclude_forks` | No | false | Exclude forked repositories (true, false) |

## Display Styles

### Card (Default)

Full card display with:
- Repository title (from README.md H1 or repository name)
- Description
- Statistics (stars, forks, language, last update)
- Shields.io badges
- Action buttons (View on GitHub, Download, Documentation)

### Minimal

Simple display with:
- Repository title
- Description
- GitHub link

### Badges Only

Displays only Shields.io badges:
- Last Commit
- License
- Stars

## Configuration

Navigate to **Settings > GitHub Repo Display** to configure:

### Basic Settings
- **Default GitHub Username**: Set your default GitHub username
- **GitHub Personal Access Token**: Optional token for higher API rate limits (60 → 5000 requests/hour)
- **Cache Expiration**: Adjust cache time (1-24 hours, default: 6)
- **Cache Jitter**: Random variation in cache expiration (10%, 20%, 30%) to prevent cache stampede
- **WP-Cron Refresh**: Enable automatic background cache refresh for tracked repositories
- **Detail Page Base Path**: Set custom URL path for detail pages (supports multi-level, e.g., `tools/software`)

### Badge Settings
Choose which badges to display (16+ types available):
- **Major badges**: Version, Last Commit, License, Stars, Forks, Issues, Language, Contributors
- **Minor badges**: Watchers, Open PRs, Closed Issues, Downloads, Code Size, Repo Size, Commit Activity, Release Date

### Design Settings
- Color customization (buttons, card border, background, text)
- Layout settings (border radius, spacing)
- Decoration options (shadow, animation, hover effects)

### Cache Management
- View cache statistics
- Clear all cached repository data

## Customization

### CSS Custom Properties

The plugin uses CSS custom properties for easy color customization:

```css
:root {
    --kgrd-primary-color: #0366d6;
    --kgrd-secondary-color: #586069;
    --kgrd-border-color: #e1e4e8;
    --kgrd-background-color: #ffffff;
    --kgrd-border-radius: 6px;
    --kgrd-spacing: 16px;
}
```

### Filter Hooks

#### Modify Cache Expiration

```php
add_filter('kgrd_api_cache_expiration', function($hours) {
    return 12; // 12 hours
});
```

#### Customize Card HTML

```php
add_filter('kgrd_repo_card_html', function($html, $data) {
    // Modify $html
    return $html;
}, 10, 2);
```

#### Change Default Username

```php
add_filter('kgrd_default_username', function($username) {
    return 'your-github-username';
});
```

#### Customize Badge URLs

```php
add_filter('kgrd_badge_urls', function($badge_urls, $data) {
    $badge_urls['issues'] = sprintf(
        'https://img.shields.io/github/issues/%s/%s',
        $data['owner']['login'],
        $data['name']
    );
    return $badge_urls;
}, 10, 2);
```

## Technical Details

### File Structure

```
kashiwazaki-github-repo-display/
├── kashiwazaki-github-repo-display.php (Main plugin file)
├── includes/
│   ├── class-github-api.php (GitHub API communication)
│   ├── class-repo-display.php (Display rendering)
│   ├── class-shortcodes.php (Shortcode registration)
│   └── class-repo-detail-page.php (Detail page with README display)
├── assets/
│   ├── css/
│   │   ├── repo-card.css (Card styles)
│   │   └── detail-page.css (Detail page styles)
│   └── js/
│       └── repo-card.js (JavaScript)
├── admin/
│   ├── class-admin-settings.php (Admin settings)
│   └── views/
│       └── settings-page.php (Settings page template)
├── readme.txt (WordPress.org readme)
├── README.md (GitHub readme)
├── CHANGELOG.md (Version history)
└── examples.md (Usage examples)
```

### Security Features

- All outputs escaped with `esc_html()`, `esc_url()`, `esc_attr()`
- Nonce verification for admin actions
- CSRF protection
- Sanitized user inputs
- No direct file access

### Performance Optimization

- 2-layer caching system (API data + HTML output)
- Configurable cache expiration (1-24 hours)
- Cache jitter to prevent cache stampede
- Optional WP-Cron background cache refresh
- Conditional asset loading (only when shortcodes are used)
- Lazy loading for badge images
- Debounced resize handlers

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Internet connection for GitHub API access

## GitHub API Limits

The plugin uses unauthenticated GitHub API requests:
- **Rate Limit**: 60 requests per hour per IP address
- **Caching**: Smart caching reduces API calls significantly
- **Error Handling**: Graceful handling of rate limit errors

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers

## Accessibility

- WCAG 2.1 Level AA compliant
- Keyboard navigation support
- Screen reader friendly
- Proper ARIA attributes
- Focus indicators

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For support, bug reports, or feature requests:
- Visit: https://www.tsuyoshikashiwazaki.jp/
- Email: Contact through website

## Author

**Tsuyoshi Kashiwazaki**
- Website: https://www.tsuyoshikashiwazaki.jp/

## License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2025 Tsuyoshi Kashiwazaki

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Credits

- GitHub REST API v3
- Shields.io badge service
- GitHub Octicons

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## Examples

See [examples.md](examples.md) for detailed usage examples and customization guides.

---

Made with ❤️ by [Tsuyoshi Kashiwazaki](https://www.tsuyoshikashiwazaki.jp/)
