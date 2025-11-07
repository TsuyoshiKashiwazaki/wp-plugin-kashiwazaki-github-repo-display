# Changelog

All notable changes to the Kashiwazaki GitHub Repository Display plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-10-15

### Added

#### Core Features
- Initial release of Kashiwazaki GitHub Repository Display plugin
- GitHub REST API v3 integration for fetching repository data
- Smart caching system using WordPress Transient API (6-hour default)
- Automatic README.md title extraction (H1 heading)
- Error handling for API failures and rate limiting

#### Shortcodes
- `[kashiwazaki_github_repo]` - Display a single repository
  - Parameters: `repo` (required), `username` (optional), `style` (optional)
- `[kashiwazaki_github_repos]` - Display multiple repositories
  - Parameters: `repos` (required), `username` (optional), `columns` (optional)

#### Display Styles
- **Card Style** - Full repository card with all information
  - Repository title (from README or repo name)
  - Description
  - Statistics (stars, forks, language, last update)
  - Shields.io badges (last commit, license, stars)
  - Action buttons (GitHub, Download, Documentation)
- **Minimal Style** - Simple title and description display
- **Badges Only Style** - Display only Shields.io badges

#### Admin Features
- Settings page at Settings > GitHub Repo Display
- Default GitHub username configuration
- Cache expiration time setting (1-24 hours)
- Cache statistics display
- Manual cache clearing functionality
- Quick start guide and documentation in admin
- Live preview of card style

#### Design & UI
- Responsive design with mobile-first approach
- BEM naming convention for CSS classes
- CSS custom properties for easy customization
- Dark mode support via prefers-color-scheme
- Grid layout support (1-4 columns)
- Smooth animations and transitions
- GitHub-inspired design aesthetic

#### JavaScript Features
- Hover effects on repository cards
- External link tracking (custom event trigger)
- Double-click to copy clone URL functionality
- Lazy loading for badge images (Intersection Observer)
- Keyboard navigation support (Enter key on cards)
- Responsive grid adjustments
- Debounced window resize handling

#### Developer Features
- Filter hook: `kgrd_api_cache_expiration` - Modify cache duration
- Filter hook: `kgrd_repo_card_html` - Customize card HTML
- Filter hook: `kgrd_default_username` - Change default username
- Filter hook: `kgrd_badge_urls` - Customize badge URLs
- Custom event: `kgrd-external-link-click` - Track link clicks
- Custom event: `kgrd-initialized` - Plugin initialization complete

#### Security
- All output properly escaped (esc_html, esc_url, esc_attr)
- Nonce verification for admin actions
- CSRF protection on forms
- Input sanitization
- No direct file access
- WordPress Coding Standards compliance

#### Performance
- Conditional asset loading (only when shortcodes are used)
- Transient API caching reduces API calls
- Lazy loading for images
- Debounced event handlers
- Optimized database queries

#### Accessibility
- WCAG 2.1 Level AA compliant
- Keyboard navigation support
- Screen reader friendly markup
- Proper ARIA attributes
- Focus indicators for interactive elements
- Semantic HTML structure

#### Documentation
- Comprehensive README.md
- WordPress.org compatible readme.txt
- Usage examples and code snippets
- Filter hook documentation
- Installation instructions
- FAQ section

#### Internationalization
- All strings wrapped in translation functions
- Text domain: kashiwazaki-github-repo-display
- POT file ready for translation
- Supports WordPress multilingual setup

### Changed
- N/A (Initial release)

### Deprecated
- N/A (Initial release)

### Removed
- N/A (Initial release)

### Fixed
- N/A (Initial release)

### Security
- Implemented comprehensive security measures from the start
- All user inputs sanitized
- All outputs escaped
- Nonce verification on all forms
- CSRF protection enabled

## [Unreleased]

### Planned Features for Future Releases

#### Version 1.1.0 (Planned)
- Gutenberg block for visual shortcode creation
- GitHub authentication option for higher API limits
- Additional badge providers (Travis CI, CircleCI, etc.)
- Repository comparison view
- WP-CLI commands for cache management
- Custom badge color schemes
- Export repository data as JSON

#### Version 1.2.0 (Planned)
- Display repository contributors
- Show recent commits
- Display open issues and pull requests
- Release information display
- Topics/tags display
- Repository activity graph

#### Version 2.0.0 (Planned)
- GitLab support
- Bitbucket support
- Custom API endpoints
- Advanced filtering options
- Repository search functionality
- Multi-user support with different API tokens

### Ideas Under Consideration
- Repository comparison feature
- Analytics dashboard
- Widget support
- Page builder integration (Elementor, Divi, etc.)
- Repository health score display
- Automated repository documentation
- Code quality metrics display

---

## Version History

- **1.0.0** - 2025-10-15 - Initial release

---

## Support

For support, feature requests, or bug reports:
- Visit: https://www.tsuyoshikashiwazaki.jp/

## Credits

Developed by [Tsuyoshi Kashiwazaki](https://www.tsuyoshikashiwazaki.jp/)

---

[1.0.0]: https://github.com/TsuyoshiKashiwazaki/kashiwazaki-github-repo-display/releases/tag/1.0.0
[Unreleased]: https://github.com/TsuyoshiKashiwazaki/kashiwazaki-github-repo-display/compare/1.0.0...HEAD
