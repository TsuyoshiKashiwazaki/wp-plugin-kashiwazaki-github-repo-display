# Kashiwazaki GitHub Repository Display

A powerful WordPress plugin that displays GitHub repository information dynamically on your WordPress site. Simply specify a repository name to fetch and display the latest information from the GitHub API.

![WordPress Plugin Version](https://img.shields.io/badge/version-1.0.0--dev-blue.svg)
![WordPress Compatibility](https://img.shields.io/badge/wordpress-5.0%2B-blue.svg)
![PHP Version](https://img.shields.io/badge/php-7.2%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)

## Features

- **Easy Integration**: Display any GitHub repository with a simple shortcode
- **Multiple Display Styles**: Choose from card, minimal, or badges-only styles
- **Automatic Data Fetching**: Retrieves repository information directly from GitHub API
- **Smart Caching**: Caches API responses for 6 hours to improve performance
- **README Title Extraction**: Automatically extracts and displays the repository title from README.md
- **Responsive Design**: Looks great on all devices and screen sizes
- **Shields.io Badges**: Automatically displays repository badges (stars, license, last commit)
- **Grid Layout Support**: Display multiple repositories in a responsive grid
- **Customizable**: Multiple filter hooks for customization
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

- **Default GitHub Username**: Set your default GitHub username
- **Cache Expiration**: Adjust cache time (1-24 hours, default: 6)
- **Cache Management**: Clear all cached repository data

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
│   └── class-shortcodes.php (Shortcode registration)
├── assets/
│   ├── css/
│   │   └── repo-card.css (Styles)
│   └── js/
│       └── repo-card.js (JavaScript)
├── admin/
│   ├── class-admin-settings.php (Admin settings)
│   └── views/
│       └── settings-page.php (Settings page template)
├── readme.txt (WordPress.org readme)
├── README.md (GitHub readme)
└── CHANGELOG.md (Version history)
```

### Security Features

- All outputs escaped with `esc_html()`, `esc_url()`, `esc_attr()`
- Nonce verification for admin actions
- CSRF protection
- Sanitized user inputs
- No direct file access

### Performance Optimization

- Transient API caching (6 hours default)
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

## Advanced Examples

### Basic Usage Examples

**Display with different styles:**

```
[kashiwazaki_github_repo repo="wp-theme-backbone-seo-llmo" style="card"]
[kashiwazaki_github_repo repo="wp-theme-backbone-seo-llmo" style="minimal"]
[kashiwazaki_github_repo repo="wp-theme-backbone-seo-llmo" style="badges-only"]
```

**Auto-fetch all user repositories:**

```
[kashiwazaki_github_user_repos]
```

**Display with custom filters:**

```
[kashiwazaki_github_user_repos username="octocat" columns="3" limit="20" exclude_forks="true"]
```

**Sort by creation date:**

```
[kashiwazaki_github_user_repos sort="created" direction="asc" limit="10"]
```

**Portfolio display without forks:**

```
[kashiwazaki_github_user_repos exclude_forks="true" columns="3" limit="30"]
```

**Multiple repositories in a grid:**

```
[kashiwazaki_github_repos repos="project1,project2,project3" columns="2"]
```

### CSS Customization Examples

**Custom colors:**

```css
:root {
    --kgrd-primary-color: #e74c3c;
    --kgrd-secondary-color: #95a5a6;
    --kgrd-border-color: #dfe6e9;
    --kgrd-background-color: #ffffff;
    --kgrd-border-radius: 8px;
}
```

**Dark theme override:**

```css
.kgrd-card {
    --kgrd-primary-color: #58a6ff;
    --kgrd-background-color: #0d1117;
    --kgrd-text-color: #c9d1d9;
}
```

**Custom card styling:**

```css
.kgrd-card {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.kgrd-card__button--primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

### Advanced Filter Examples

**Add custom badges:**

```php
add_filter('kgrd_badge_urls', function($badge_urls, $data) {
    $username = $data['owner']['login'];
    $repo = $data['name'];

    $badge_urls['issues'] = sprintf(
        'https://img.shields.io/github/issues/%s/%s',
        $username,
        $repo
    );

    return $badge_urls;
}, 10, 2);
```

**Filter repositories by language:**

```php
add_filter('kgrd_repo_card_html', function($html, $data) {
    $allowed_languages = array('PHP', 'JavaScript');

    if (!in_array($data['language'], $allowed_languages)) {
        return '';
    }

    return $html;
}, 10, 2);
```

### JavaScript Events

**Track external link clicks:**

```javascript
jQuery(document).on('kgrd-external-link-click', function(event, data) {
    console.log('Repository link clicked:', data);

    if (typeof gtag !== 'undefined') {
        gtag('event', 'click', {
            'event_category': 'GitHub Repository',
            'event_label': data.repo
        });
    }
});
```

### Use Cases

**Portfolio Page:**

```html
<h1>My GitHub Portfolio</h1>
<p>Explore all my open source projects:</p>

[kashiwazaki_github_user_repos columns="3" exclude_forks="true" limit="50"]
```

**Organized by Category:**

```html
<h2>Themes</h2>
[kashiwazaki_github_repos repos="theme1,theme2,theme3" columns="3"]

<h2>Plugins</h2>
[kashiwazaki_github_repos repos="plugin1,plugin2,plugin3" columns="2"]
```

**Blog Post Integration:**

```html
<p>I recently released a new WordPress plugin that...</p>

[kashiwazaki_github_repo repo="new-plugin" style="card"]
```

## Troubleshooting

### Repository Not Displaying

1. Check repository name and username:
   ```
   [kashiwazaki_github_repo repo="exact-repo-name" username="exact-username"]
   ```

2. Verify the repository is public

3. Clear the cache:
   - Go to Settings > GitHub Repo Display
   - Click "Clear All Cache"

### Rate Limit Errors

If you're hitting GitHub's rate limit:

1. Increase cache duration:
   ```php
   add_filter('kgrd_api_cache_expiration', function() { return 24; });
   ```

2. Reduce number of repositories displayed

### Styling Issues

1. Check for CSS conflicts:
   ```css
   .kgrd-card {
       all: initial;
   }
   ```

2. Increase CSS specificity:
   ```css
   body .kgrd-card .kgrd-card__title {
       /* Your styles */
   }
   ```

3. Clear browser cache

## Best Practices

1. **Use caching wisely**: Don't set cache expiration too low (minimum 1 hour recommended)

2. **Choose the right shortcode**:
   - `[kashiwazaki_github_user_repos]` - Auto-display all repos
   - `[kashiwazaki_github_repos]` - Specific repos in specific order
   - `[kashiwazaki_github_repo]` - Single repository feature

3. **Limit repositories per page**: Recommended 12-30 repositories

4. **Choose appropriate styles**:
   - `card` - Portfolio and feature pages
   - `minimal` - Sidebars and compact displays
   - `badges-only` - Quick stats display

5. **Test responsive design**: Use `columns="2"` or `columns="3"` for better mobile experience

6. **Monitor API usage**: GitHub API limit is 60 requests/hour (unauthenticated)

---

Made by [Tsuyoshi Kashiwazaki](https://www.tsuyoshikashiwazaki.jp/)
