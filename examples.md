# Kashiwazaki GitHub Repository Display - Examples

This document provides comprehensive examples and use cases for the Kashiwazaki GitHub Repository Display plugin.

## Table of Contents

1. [Basic Usage](#basic-usage)
2. [Advanced Shortcode Examples](#advanced-shortcode-examples)
3. [Repository Detail Pages](#repository-detail-pages)
4. [CSS Customization](#css-customization)
5. [Filter Hook Examples](#filter-hook-examples)
6. [JavaScript Events](#javascript-events)
7. [Use Cases](#use-cases)
8. [Troubleshooting](#troubleshooting)

---

## Basic Usage

### Display a Single Repository

The simplest way to display a repository:

```
[kashiwazaki_github_repo repo="wp-theme-backbone-seo-llmo"]
```

This will use the default username set in the plugin settings and display the repository in card style.

### Specify Username

If you want to display a repository from a different user:

```
[kashiwazaki_github_repo repo="wordpress-develop" username="wordpress"]
```

### Change Display Style

Choose from three different display styles:

**Card Style (Default):**
```
[kashiwazaki_github_repo repo="wp-theme-backbone-seo-llmo" style="card"]
```

**Minimal Style:**
```
[kashiwazaki_github_repo repo="wp-theme-backbone-seo-llmo" style="minimal"]
```

**Badges Only:**
```
[kashiwazaki_github_repo repo="wp-theme-backbone-seo-llmo" style="badges-only"]
```

---

## Advanced Shortcode Examples

### Auto-Fetch All User Repositories

Display all repositories for a GitHub user automatically (no need to specify repository names):

```
[kashiwazaki_github_user_repos]
```

This will display all repositories for the default username (set in Settings > GitHub Repo Display).

### Auto-Fetch with Custom Parameters

Display repositories with specific filters and sorting:

```
[kashiwazaki_github_user_repos username="octocat" columns="3" limit="20" exclude_forks="true"]
```

### Sort by Creation Date

Show the oldest repositories first:

```
[kashiwazaki_github_user_repos sort="created" direction="asc" limit="10"]
```

### Show Only Recent Updates

Display the most recently updated repositories:

```
[kashiwazaki_github_user_repos sort="updated" direction="desc" limit="15"]
```

### Portfolio Display Without Forks

Perfect for showcasing only your original work:

```
[kashiwazaki_github_user_repos exclude_forks="true" columns="3" limit="30"]
```

### Multiple Repositories in a Grid

Display multiple repositories in a 2-column grid:

```
[kashiwazaki_github_repos repos="wp-theme-backbone-seo-llmo,wp-plugin-kashiwazaki-shortcode-collector" columns="2"]
```

### 3-Column Portfolio Display

Perfect for a portfolio page:

```
[kashiwazaki_github_repos repos="project1,project2,project3,project4,project5,project6" columns="3"]
```

### Single Column List

Display repositories in a vertical list:

```
[kashiwazaki_github_repos repos="repo1,repo2,repo3" columns="1"]
```

### Mixed Repositories from Different Users

While the shortcode doesn't directly support this, you can use multiple shortcodes:

```
[kashiwazaki_github_repo repo="wordpress-develop" username="wordpress" style="card"]
[kashiwazaki_github_repo repo="gutenberg" username="wordpress" style="card"]
[kashiwazaki_github_repo repo="wp-theme-backbone-seo-llmo" username="TsuyoshiKashiwazaki" style="card"]
```

### In a WordPress Post

```html
<h2>My Projects</h2>
<p>Here are some of the WordPress projects I've been working on:</p>

[kashiwazaki_github_repos repos="wp-theme-backbone-seo-llmo,wp-plugin-kashiwazaki-shortcode-collector" columns="2"]

<h2>Contributing To</h2>
<p>I also contribute to these open source projects:</p>

[kashiwazaki_github_repo repo="wordpress-develop" username="wordpress" style="minimal"]
```

---

## Repository Detail Pages

Version 1.0.1 introduces individual detail pages for each repository, displaying the full README content with GitHub Flavored Markdown rendering.

### How Detail Pages Work

1. When a repository card is displayed via shortcode, it automatically includes a "Details" button
2. Clicking the button navigates to `/{base-path}/{repository-slug}/`
3. The detail page displays:
   - Repository name and description
   - Metadata (language, stars, forks, license)
   - View on GitHub and Download buttons
   - Full README content rendered as HTML

### Configuring the Base Path

Set the base path in **Settings > GitHub Repo Display > Basic Settings**:

- **Default**: `software` → URLs like `/software/my-repo/`
- **Custom**: `tools/github` → URLs like `/tools/github/my-repo/`

After changing the base path, go to **Settings > Permalinks** and click "Save Changes" to refresh rewrite rules.

### Repository Tracking

Repositories displayed via shortcodes are automatically tracked. This enables:
- Detail page URL generation
- WP-Cron background cache refresh

View tracked repositories in the admin settings under "Cron Refresh" status section.

### Detail Page Caching

Detail pages are cached separately from list pages. The cache includes:
- Repository metadata from GitHub API
- Rendered README HTML

Cache duration follows the global cache expiration setting with optional jitter.

---

## CSS Customization

### Custom Colors

Add this to your theme's CSS file or use the Customizer:

```css
:root {
    /* Primary color (buttons, links) */
    --kgrd-primary-color: #e74c3c;

    /* Secondary color (icons, metadata) */
    --kgrd-secondary-color: #95a5a6;

    /* Border color */
    --kgrd-border-color: #dfe6e9;

    /* Background color */
    --kgrd-background-color: #ffffff;

    /* Hover background */
    --kgrd-hover-background: #f8f9fa;

    /* Text colors */
    --kgrd-text-color: #2d3436;
    --kgrd-text-secondary: #636e72;

    /* Border radius */
    --kgrd-border-radius: 8px;

    /* Spacing */
    --kgrd-spacing: 20px;
}
```

### Dark Theme Override

Force dark theme regardless of system preferences:

```css
.kgrd-card {
    --kgrd-primary-color: #58a6ff;
    --kgrd-secondary-color: #8b949e;
    --kgrd-border-color: #30363d;
    --kgrd-background-color: #0d1117;
    --kgrd-hover-background: #161b22;
    --kgrd-text-color: #c9d1d9;
    --kgrd-text-secondary: #8b949e;
}
```

### Custom Card Styling

Add a custom style to specific cards:

```css
/* Add shadow effect */
.kgrd-card {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Customize title */
.kgrd-card__title {
    font-family: 'Montserrat', sans-serif;
    color: #2c3e50;
}

/* Customize buttons */
.kgrd-card__button--primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.kgrd-card__button--primary:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}
```

### Responsive Adjustments

Override responsive breakpoints:

```css
@media (max-width: 1024px) {
    .kgrd-grid--columns-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .kgrd-card {
        padding: 12px;
    }

    .kgrd-card__title {
        font-size: 1rem;
    }
}
```

### Hide Specific Elements

```css
/* Hide badges */
.kgrd-card__badges {
    display: none;
}

/* Hide download button */
.kgrd-card__button--secondary {
    display: none;
}

/* Hide language tag */
.kgrd-card__language {
    display: none;
}
```

---

## Filter Hook Examples

### Change Cache Expiration Time

Set cache to expire after 12 hours:

```php
add_filter('kgrd_api_cache_expiration', function($hours) {
    return 12; // 12 hours
});
```

### Change Default Username Dynamically

```php
add_filter('kgrd_default_username', function($username) {
    // Use different username based on current user
    if (is_user_logged_in()) {
        $user_meta = get_user_meta(get_current_user_id(), 'github_username', true);
        if (!empty($user_meta)) {
            return $user_meta;
        }
    }
    return $username;
});
```

### Customize Repository Card HTML

Add custom content to cards:

```php
add_filter('kgrd_repo_card_html', function($html, $data) {
    // Add a "Sponsor" button if the repo has sponsors enabled
    if (!empty($data['has_sponsors'])) {
        $sponsor_button = sprintf(
            '<a href="%s" class="kgrd-card__button kgrd-card__button--sponsor" target="_blank" rel="noopener noreferrer">Sponsor ❤️</a>',
            'https://github.com/sponsors/' . $data['owner']['login']
        );

        // Insert before closing div
        $html = str_replace('</div>', $sponsor_button . '</div>', $html);
    }

    return $html;
}, 10, 2);
```

### Add Custom Badges

```php
add_filter('kgrd_badge_urls', function($badge_urls, $data) {
    $username = $data['owner']['login'];
    $repo = $data['name'];

    // Add issues badge
    $badge_urls['issues'] = sprintf(
        'https://img.shields.io/github/issues/%s/%s',
        $username,
        $repo
    );

    // Add downloads badge
    $badge_urls['downloads'] = sprintf(
        'https://img.shields.io/github/downloads/%s/%s/total',
        $username,
        $repo
    );

    // Add code size badge
    $badge_urls['code-size'] = sprintf(
        'https://img.shields.io/github/languages/code-size/%s/%s',
        $username,
        $repo
    );

    return $badge_urls;
}, 10, 2);
```

### Filter Repositories by Language

Only show repositories of a specific language:

```php
add_filter('kgrd_repo_card_html', function($html, $data) {
    // Only show PHP repositories
    $allowed_languages = array('PHP', 'JavaScript');

    if (!in_array($data['language'], $allowed_languages)) {
        return ''; // Return empty string to hide
    }

    return $html;
}, 10, 2);
```

---

## JavaScript Events

### Track External Link Clicks

Add analytics tracking:

```javascript
jQuery(document).on('kgrd-external-link-click', function(event, data) {
    console.log('Repository link clicked:', data);

    // Send to Google Analytics
    if (typeof gtag !== 'undefined') {
        gtag('event', 'click', {
            'event_category': 'GitHub Repository',
            'event_label': data.repo,
            'value': data.linkType
        });
    }

    // Send to custom analytics
    // yourAnalytics.track('repo_click', data);
});
```

### Custom Behavior After Plugin Initialization

```javascript
jQuery(document).on('kgrd-initialized', function() {
    console.log('GitHub Repo Display plugin initialized');

    // Add custom tooltips
    jQuery('.kgrd-card').each(function() {
        // Your custom initialization code
    });
});
```

### Add Custom Hover Effects

```javascript
jQuery(document).ready(function($) {
    $('.kgrd-card').hover(
        function() {
            $(this).find('.kgrd-card__badges').fadeIn(200);
        },
        function() {
            $(this).find('.kgrd-card__badges').fadeOut(200);
        }
    );
});
```

---

## Use Cases

### Portfolio Page

Create a comprehensive portfolio showcasing all your projects:

**Option 1: Auto-fetch all repositories**
```html
<h1>My GitHub Portfolio</h1>
<p>Explore all my open source projects:</p>

[kashiwazaki_github_user_repos columns="3" exclude_forks="true" limit="50"]
```

**Option 2: Organized by category**
```html
<h1>My WordPress Projects</h1>

<h2>Themes</h2>
[kashiwazaki_github_repos repos="theme1,theme2,theme3" columns="3"]

<h2>Plugins</h2>
[kashiwazaki_github_repos repos="plugin1,plugin2,plugin3,plugin4" columns="2"]

<h2>Utilities</h2>
[kashiwazaki_github_repos repos="util1,util2" columns="2"]
```

### Developer Profile Page

Show all your contributions automatically:

```html
<h1>Open Source Contributions</h1>
<p>Here are my latest projects on GitHub:</p>

[kashiwazaki_github_user_repos sort="updated" direction="desc" limit="12" columns="3"]
```

### Plugin/Theme Documentation Page

Show repository info on your plugin's landing page:

```html
<h1>Download Plugin Name</h1>
[kashiwazaki_github_repo repo="plugin-name" style="card"]

<h2>Installation</h2>
<p>Installation instructions here...</p>

<h2>Related Projects</h2>
[kashiwazaki_github_repos repos="addon1,addon2,addon3" columns="3" style="minimal"]
```

### Team Page

Show contributions from team members:

```html
<h2>Team Projects</h2>
[kashiwazaki_github_repo repo="main-project" username="company" style="card"]
[kashiwazaki_github_repo repo="side-project" username="company" style="card"]
```

### Blog Post Integration

```html
<p>I recently released a new WordPress plugin that...</p>

[kashiwazaki_github_repo repo="new-plugin" style="card"]

<p>You can install it directly from GitHub or...</p>
```

### Sidebar Widget

Add using the Text widget:

```
[kashiwazaki_github_repo repo="featured-repo" style="minimal"]
```

---

## Troubleshooting

### Repository Not Displaying

1. **Check repository name and username:**
   ```
   [kashiwazaki_github_repo repo="exact-repo-name" username="exact-username"]
   ```

2. **Verify the repository is public**

3. **Clear the cache:**
   - Go to Settings > GitHub Repo Display
   - Click "Clear All Cache"

### Rate Limit Errors

If you're hitting GitHub's rate limit:

1. **Increase cache duration:**
   ```php
   add_filter('kgrd_api_cache_expiration', function() { return 24; });
   ```

2. **Reduce number of repositories displayed**

3. **Consider using GitHub authentication (future feature)**

### Styling Issues

If styles aren't applying:

1. **Check for CSS conflicts:**
   ```css
   .kgrd-card {
       all: initial; /* Reset all styles */
       /* Then add your custom styles */
   }
   ```

2. **Increase CSS specificity:**
   ```css
   body .kgrd-card .kgrd-card__title {
       /* Your styles */
   }
   ```

3. **Clear browser cache**

### Shortcode Not Working

1. **Check for typos:**
   - Correct: `kashiwazaki_github_repo`
   - Wrong: `kashiwazaki-github-repo`

2. **Verify shortcodes are enabled in your theme**

3. **Check if content is being filtered:**
   ```php
   echo do_shortcode('[kashiwazaki_github_repo repo="test"]');
   ```

---

## Advanced Integration Examples

### Custom Page Template

Create a custom page template showing all your repositories:

```php
<?php
/**
 * Template Name: GitHub Repositories
 */

get_header();

// Get all repositories from a user (custom implementation)
$repos = array('repo1', 'repo2', 'repo3', 'repo4');
?>

<div class="github-portfolio">
    <h1><?php the_title(); ?></h1>

    <?php echo do_shortcode('[kashiwazaki_github_repos repos="' . implode(',', $repos) . '" columns="2"]'); ?>
</div>

<?php get_footer(); ?>
```

### WP REST API Integration

Expose repository data via REST API:

```php
add_action('rest_api_init', function() {
    register_rest_route('kgrd/v1', '/repositories', array(
        'methods' => 'GET',
        'callback' => 'kgrd_get_repositories_rest',
    ));
});

function kgrd_get_repositories_rest($request) {
    $api = KGRD_GitHub_API::get_instance();
    $repos = $request->get_param('repos');

    $data = array();
    foreach (explode(',', $repos) as $repo) {
        $data[] = $api->get_repository('TsuyoshiKashiwazaki', trim($repo));
    }

    return rest_ensure_response($data);
}
```

---

## Best Practices

1. **Use caching wisely:** Don't set cache expiration too low
   - The plugin uses 2-layer caching (API data + HTML output)
   - Default cache: 6 hours with jitter to prevent cache stampede
   - Recommended minimum: 1 hour to avoid hitting GitHub rate limits
   - Enable cache jitter (20% recommended) to prevent simultaneous cache refreshes

2. **Leverage WP-Cron for high-traffic sites:**
   - Enable WP-Cron cache refresh to update cache in the background
   - This prevents users from experiencing slow page loads during cache refresh
   - Set cron interval slightly shorter than cache expiration for best results

3. **Use GitHub Personal Access Token for heavy usage:**
   - Unauthenticated: 60 requests/hour
   - Authenticated: 5,000 requests/hour
   - Get a token at https://github.com/settings/tokens (no special scopes needed for public repos)

4. **Choose the right shortcode:**
   - `[kashiwazaki_github_user_repos]` - Best for automatically displaying all repos
   - `[kashiwazaki_github_repos]` - Best when you want specific repos in a specific order
   - `[kashiwazaki_github_repo]` - Best for featuring a single repository

5. **Limit repositories per page:** Avoid displaying too many at once
   - Recommended: 12-30 repositories per page
   - Use `limit` parameter to control the number displayed

6. **Choose appropriate styles:** Match the style to your use case
   - `card` - Best for portfolio and feature pages (includes Details button)
   - `minimal` - Best for sidebars and compact displays
   - `badges-only` - Best for quick stats display

7. **Configure detail page base path properly:**
   - Choose a path that doesn't conflict with existing pages
   - Multi-level paths are supported (e.g., `tools/software`)
   - Remember to flush permalinks after changing the base path

8. **Test responsive design:** Check on mobile devices
   - Use `columns="2"` or `columns="3"` for better mobile experience
   - Avoid `columns="4"` unless you have wide content areas

9. **Monitor API usage:** Be aware of GitHub's rate limits
   - GitHub API limit: 60 requests/hour (unauthenticated), 5000/hour (authenticated)
   - Cache helps reduce API calls significantly
   - Consider using `exclude_forks="true"` to reduce unnecessary data

10. **Keep plugin updated:** Check for updates regularly

---

## Support

For more examples and support:
- Visit: https://www.tsuyoshikashiwazaki.jp/
- Check the FAQ in readme.txt
- Review the plugin documentation

---

**Last Updated:** 2026-01-05
**Version:** 1.0.1
