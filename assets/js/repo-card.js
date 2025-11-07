/**
 * Kashiwazaki GitHub Repository Display - JavaScript
 *
 * @package Kashiwazaki_GitHub_Repo_Display
 */

(function($) {
	'use strict';

	/**
	 * Initialize repository cards.
	 */
	function initRepoCards() {
		$('.kgrd-card').each(function() {
			var $card = $(this);

			// Add hover effects
			$card.on('mouseenter', function() {
				$(this).addClass('kgrd-card--hover');
			}).on('mouseleave', function() {
				$(this).removeClass('kgrd-card--hover');
			});

			// Track external link clicks (for analytics, if needed)
			$card.find('a[target="_blank"]').on('click', function() {
				var repoName = $card.data('repo');
				var linkType = $(this).text().trim();

				// Trigger custom event that can be hooked by analytics scripts
				$(document).trigger('kgrd-external-link-click', {
					repo: repoName,
					linkType: linkType,
					url: $(this).attr('href')
				});
			});
		});
	}

	/**
	 * Add copy functionality to clone URLs.
	 */
	function initCopyButtons() {
		// Create copy buttons for download links
		$('.kgrd-card__button--secondary').each(function() {
			var $button = $(this);
			var buttonText = $button.text().trim();

			// Only add copy functionality to download buttons
			if (buttonText === 'Download' || buttonText === 'ダウンロード') {
				var cloneUrl = $button.attr('href');

				// Add data attribute for easy access
				$button.attr('data-clone-url', cloneUrl);

				// Add double-click to copy functionality
				$button.on('dblclick', function(e) {
					e.preventDefault();
					copyToClipboard(cloneUrl);

					// Visual feedback
					var originalText = $button.text();
					$button.text('Copied!').addClass('kgrd-copied');

					setTimeout(function() {
						$button.text(originalText).removeClass('kgrd-copied');
					}, 2000);
				});
			}
		});
	}

	/**
	 * Copy text to clipboard.
	 *
	 * @param {string} text - Text to copy.
	 */
	function copyToClipboard(text) {
		// Modern clipboard API
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).catch(function(err) {
				// Silently fail
			});
		} else {
			// Fallback for older browsers
			var $temp = $('<textarea>');
			$('body').append($temp);
			$temp.val(text).select();

			try {
				document.execCommand('copy');
			} catch (err) {
				// Silently fail
			}

			$temp.remove();
		}
	}

	/**
	 * Lazy load badge images.
	 */
	function initLazyLoadBadges() {
		if ('IntersectionObserver' in window) {
			var badgeObserver = new IntersectionObserver(function(entries) {
				entries.forEach(function(entry) {
					if (entry.isIntersecting) {
						var $img = $(entry.target);
						var src = $img.attr('data-src');

						if (src) {
							$img.attr('src', src).removeAttr('data-src');
							badgeObserver.unobserve(entry.target);
						}
					}
				});
			}, {
				rootMargin: '50px'
			});

			$('.kgrd-card__badge[data-src]').each(function() {
				badgeObserver.observe(this);
			});
		}
	}

	/**
	 * Add keyboard navigation support.
	 */
	function initKeyboardNavigation() {
		$('.kgrd-card').attr('tabindex', '0');

		$('.kgrd-card').on('keydown', function(e) {
			// Enter key opens the main GitHub link
			if (e.key === 'Enter' || e.keyCode === 13) {
				var $link = $(this).find('.kgrd-card__button--primary').first();
				if ($link.length) {
					window.open($link.attr('href'), '_blank');
				}
			}
		});
	}

	/**
	 * Initialize tooltips for stats.
	 */
	function initTooltips() {
		$('.kgrd-card__stat').each(function() {
			var $stat = $(this);
			var text = $stat.text().trim();

			// Add title attribute for native tooltip
			if (text) {
				$stat.attr('title', text);
			}
		});
	}

	/**
	 * Initialize collapsible sections.
	 */
	function initCollapsibles() {
		$('.kgrd-collapsible__toggle').on('click', function() {
			var $toggle = $(this);
			var $collapsible = $toggle.closest('.kgrd-collapsible');
			var $content = $collapsible.find('.kgrd-collapsible__content');
			var $moreIndicator = $collapsible.find('.kgrd-readme-more');
			var isExpanded = $toggle.attr('aria-expanded') === 'true';
			var $toggleText = $toggle.find('.kgrd-collapsible__toggle-text');
			var currentText = $toggleText.text();

			if (isExpanded) {
				// Collapse
				$toggle.attr('aria-expanded', 'false');
				// Restore original text
				if (currentText.indexOf('非表示') !== -1) {
					$toggleText.text(currentText.replace('非表示にする', 'する').replace('非表示', '表示'));
				} else if (currentText.indexOf('隠す') !== -1) {
					$toggleText.text(currentText.replace('隠す', '表示する'));
				}
				$content.attr('hidden', '');
				$moreIndicator.show();
			} else {
				// Expand
				$toggle.attr('aria-expanded', 'true');
				// Change to hide text
				if (currentText === '続きを表示する' || currentText === '続きを表示') {
					$toggleText.text('続きを非表示にする');
				} else if (currentText.indexOf('表示') !== -1) {
					$toggleText.text(currentText.replace('表示する', '非表示にする').replace('表示', '非表示'));
				}
				$content.removeAttr('hidden');
				$moreIndicator.hide();
			}
		});
	}

	/**
	 * Handle responsive grid adjustments.
	 */
	function handleResponsiveGrid() {
		function adjustGrid() {
			var windowWidth = $(window).width();

			$('.kgrd-grid').each(function() {
				var $grid = $(this);
				var columns = $grid.attr('class').match(/kgrd-grid--columns-(\d)/);

				if (columns && columns[1]) {
					var columnCount = parseInt(columns[1], 10);

					// Add responsive class based on screen size
					if (windowWidth < 480) {
						$grid.addClass('kgrd-grid--mobile');
					} else if (windowWidth < 768) {
						$grid.addClass('kgrd-grid--tablet');
					} else {
						$grid.removeClass('kgrd-grid--mobile kgrd-grid--tablet');
					}
				}
			});
		}

		// Run on load
		adjustGrid();

		// Run on resize (debounced)
		var resizeTimer;
		$(window).on('resize', function() {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(adjustGrid, 250);
		});
	}

	/**
	 * Initialize all functionality when DOM is ready.
	 */
	$(document).ready(function() {
		initRepoCards();
		initCopyButtons();
		initLazyLoadBadges();
		initKeyboardNavigation();
		initTooltips();
		initCollapsibles();
		handleResponsiveGrid();

		// Trigger custom event to allow external scripts to hook in
		$(document).trigger('kgrd-initialized');
	});

})(jQuery);
