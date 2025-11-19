/**
 * Map Block Interactions
 *
 * Handles:
 * - Filter panel toggle (open/close)
 * - Collapsible filter sections
 * - Focus management and keyboard navigation
 * - Search functionality
 */

(function () {
  'use strict';

  // Prevent duplicate initialization
  if (window.wpPlacesMapInitialized) {
    return;
  }
  window.wpPlacesMapInitialized = true;

  /**
   * Initialize all map blocks on the page
   */
  function initMapBlocks() {
    const mapBlocks = document.querySelectorAll('.block-map');

    mapBlocks.forEach(function (mapBlock) {
      // Check if this specific block has already been initialized
      if (mapBlock.dataset.mapInitialized === 'true') {
        return;
      }
      mapBlock.dataset.mapInitialized = 'true';
      initMapBlock(mapBlock);
    });
  }

  /**
   * Initialize a single map block
   */
  function initMapBlock(mapBlock) {
    const filterPanel = mapBlock.querySelector('.filter-panel');
    const filterToggleBtn = mapBlock.querySelector('.filter-toggle-btn');
    const filterCloseBtn = mapBlock.querySelector('.filter-panel-close');
    const filterApplyBtn = mapBlock.querySelector('.filter-apply-btn');
    const searchBtn = mapBlock.querySelector('.search-btn');
    const searchForm = mapBlock.querySelector('.search-bar-content');

    if (!filterPanel) {
      console.error('Filter panel not found!');
      return;
    }

    // Store the element that opened the panel for focus restoration
    let panelOpener = null;

    /**
     * Disable tab navigation in filter panel (when closed)
     */
    function disableFilterPanelTabbing() {
      const focusableElements = filterPanel.querySelectorAll(
        'button, [href], input, select, textarea, [role="checkbox"], [tabindex]:not([tabindex="-1"])'
      );
      focusableElements.forEach(function(el) {
        el.setAttribute('data-original-tabindex', el.getAttribute('tabindex') || 'none');
        el.setAttribute('tabindex', '-1');
      });
    }

    /**
     * Enable tab navigation in filter panel (when opened)
     */
    function enableFilterPanelTabbing() {
      const focusableElements = filterPanel.querySelectorAll('[data-original-tabindex]');
      focusableElements.forEach(function(el) {
        const originalTabindex = el.getAttribute('data-original-tabindex');
        if (originalTabindex === 'none') {
          el.removeAttribute('tabindex');
        } else {
          el.setAttribute('tabindex', originalTabindex);
        }
        el.removeAttribute('data-original-tabindex');
      });
    }

    /**
     * Open the filter panel
     */
    function openPanel(opener) {
      panelOpener = opener;
      filterPanel.classList.add('is-open');
      filterPanel.removeAttribute('hidden');
      filterPanel.setAttribute('aria-hidden', 'false');

      // Enable tab navigation in the panel
      enableFilterPanelTabbing();

      // Focus the close button when panel opens
      setTimeout(function () {
        if (filterCloseBtn) {
          filterCloseBtn.focus();
        }
      }, 100);

      // Trap focus within panel
      trapFocus(filterPanel);
    }

    /**
     * Close the filter panel
     */
    function closePanel() {
      filterPanel.classList.remove('is-open');
      filterPanel.setAttribute('hidden', '');
      filterPanel.setAttribute('aria-hidden', 'true');

      // Disable tab navigation in the panel
      disableFilterPanelTabbing();

      // Restore focus to the element that opened the panel
      if (panelOpener) {
        panelOpener.focus();
        panelOpener = null;
      }

      // Remove focus trap
      removeFocusTrap(filterPanel);
    }

    /**
     * Toggle filter panel
     */
    function togglePanel(opener) {
      if (filterPanel.classList.contains('is-open')) {
        closePanel();
      } else {
        openPanel(opener);
      }
    }

    // Filter toggle button click
    if (filterToggleBtn) {
      filterToggleBtn.addEventListener('click', function (e) {
        e.preventDefault();
        togglePanel(filterToggleBtn);
      });
    } else {
      console.warn('Filter toggle button not found!');
    }

    // Close button click
    if (filterCloseBtn) {
      filterCloseBtn.addEventListener('click', function (e) {
        e.preventDefault();
        closePanel();
      });
    }

    // Apply filter button click (closes panel and triggers FacetWP refresh if needed)
    if (filterApplyBtn) {
      filterApplyBtn.addEventListener('click', function (e) {
        e.preventDefault();
        closePanel();
        // FacetWP will auto-refresh on facet changes
      });
    }

    // Escape key to close panel
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && filterPanel.classList.contains('is-open')) {
        closePanel();
      }
    });

    // Initialize collapsible sections
    initCollapsibleSections(mapBlock);

    // Enhance FacetWP checkboxes for keyboard accessibility
    enhanceFacetCheckboxes();

    // Remove map from tab order so users tab to search/filter controls first
    removeMapFromTabOrder();

    // Disable tab navigation in closed filter panel
    if (filterPanel && filterPanel.hasAttribute('hidden')) {
      disableFilterPanelTabbing();
    }

    // Handle search form submission
    if (searchForm) {
      searchForm.addEventListener('submit', function (e) {
        e.preventDefault();
        // FacetWP handles the search automatically, just trigger refresh
        if (typeof FWP !== 'undefined') {
          FWP.refresh();
        }
        return false;
      });
    }

    // Handle search button click
    if (searchBtn) {
      searchBtn.addEventListener('click', function (e) {
        e.preventDefault();
        // FacetWP handles the search automatically, just trigger refresh
        if (typeof FWP !== 'undefined') {
          FWP.refresh();
        }
      });
    }
  }

  /**
   * Initialize collapsible filter sections
   */
  function initCollapsibleSections(mapBlock) {
    const sectionToggles = mapBlock.querySelectorAll('.filter-section-toggle');

    sectionToggles.forEach(function (toggle) {
      toggle.addEventListener('click', function () {
        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        const targetId = toggle.getAttribute('aria-controls');
        const targetContent = document.getElementById(targetId);
        const icon = toggle.querySelector('.filter-section-icon');

        if (targetContent) {
          if (isExpanded) {
            // Collapse
            toggle.setAttribute('aria-expanded', 'false');
            targetContent.hidden = true;
            if (icon) icon.textContent = '+';
          } else {
            // Expand
            toggle.setAttribute('aria-expanded', 'true');
            targetContent.hidden = false;
            if (icon) icon.textContent = '−';
          }
        }
      });

      // Keyboard support for section toggles
      toggle.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggle.click();
        }
      });
    });
  }

  /**
   * Enhance FacetWP checkboxes with keyboard accessibility
   * Adds proper ARIA attributes, tabindex, and keyboard handlers
   */
  function enhanceFacetCheckboxes() {
    const checkboxes = document.querySelectorAll('.facetwp-checkbox');

    checkboxes.forEach(function (checkbox) {
      // Skip if already enhanced (prevent duplicate handlers)
      if (checkbox.dataset.a11yEnhanced === 'true') {
        return;
      }
      checkbox.dataset.a11yEnhanced = 'true';

      // Add ARIA attributes and tabindex
      checkbox.setAttribute('role', 'checkbox');
      checkbox.setAttribute('tabindex', '0');

      // Set initial aria-checked state based on whether it's selected
      const isChecked = checkbox.classList.contains('checked');
      checkbox.setAttribute('aria-checked', isChecked ? 'true' : 'false');

      // Keyboard event handler for Space and Enter keys
      const keyboardHandler = function (e) {
        if (e.key === ' ' || e.key === 'Enter') {
          e.preventDefault();
          checkbox.click();
        }
      };

      // Click handler to update aria-checked
      const clickHandler = function () {
        // Small delay to ensure FacetWP has updated the checked class
        setTimeout(function () {
          const isNowChecked = checkbox.classList.contains('checked');
          checkbox.setAttribute('aria-checked', isNowChecked ? 'true' : 'false');
        }, 50);
      };

      checkbox.addEventListener('keydown', keyboardHandler);
      checkbox.addEventListener('click', clickHandler);

      // Store handlers for potential cleanup
      checkbox._a11yKeyboardHandler = keyboardHandler;
      checkbox._a11yClickHandler = clickHandler;
    });
  }

  // Note: Search functionality removed - handled natively by FacetWP search facet

  /**
   * Trap focus within an element
   */
  function trapFocus(element) {
    const focusableElements = element.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"]), [role="checkbox"]'
    );
    const firstFocusable = focusableElements[0];
    const lastFocusable = focusableElements[focusableElements.length - 1];

    function handleTabKey(e) {
      if (e.key !== 'Tab') return;

      if (e.shiftKey) {
        // Shift + Tab
        if (document.activeElement === firstFocusable) {
          e.preventDefault();
          lastFocusable.focus();
        }
      } else {
        // Tab
        if (document.activeElement === lastFocusable) {
          e.preventDefault();
          firstFocusable.focus();
        }
      }
    }

    element.addEventListener('keydown', handleTabKey);
    element._focusTrapHandler = handleTabKey; // Store for cleanup
  }

  /**
   * Remove focus trap from an element
   */
  function removeFocusTrap(element) {
    if (element._focusTrapHandler) {
      element.removeEventListener('keydown', element._focusTrapHandler);
      delete element._focusTrapHandler;
    }
  }

  /**
   * Initialize on DOM ready
   */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMapBlocks);
  } else {
    initMapBlocks();
  }

  /**
   * Re-initialize after FacetWP refresh (if needed)
   * Note: We don't need to re-initialize since our event listeners
   * are attached to elements that don't get replaced by FacetWP
   */
  document.addEventListener('facetwp-loaded', function () {
    // Don't re-initialize - our elements are persistent
    // Only the facet content inside gets replaced by FacetWP

    // Re-enhance checkboxes after FacetWP refresh (they get re-rendered)
    enhanceFacetCheckboxes();

    // Re-apply map accessibility fixes (Google Maps might re-render UI elements)
    removeMapFromTabOrder();

    // Re-disable filter panel tabbing if it's still closed
    const filterPanel = document.querySelector('.filter-panel');
    if (filterPanel && filterPanel.hasAttribute('hidden')) {
      // Need to access the function from the block scope
      const focusableElements = filterPanel.querySelectorAll(
        'button, [href], input, select, textarea, [role="checkbox"], [tabindex]:not([tabindex="-1"])'
      );
      focusableElements.forEach(function(el) {
        if (!el.hasAttribute('data-original-tabindex')) {
          el.setAttribute('data-original-tabindex', el.getAttribute('tabindex') || 'none');
          el.setAttribute('tabindex', '-1');
        }
      });
    }
  });

  /**
   * Remove Google Maps UI elements from tab order
   * This ensures search/filter controls are tabbed to first
   * But keeps location markers focusable for accessibility
   */
  function removeMapFromTabOrder() {
    const mapContainer = document.querySelector('.facet.custom-map');
    if (!mapContainer) return;

    // Function to remove Google Maps UI controls from tab order
    // but preserve location markers
    const removeInteractiveElements = function() {
      mapContainer.setAttribute('tabindex', '-1');

      // Remove all buttons EXCEPT those that might be location markers
      // Location markers typically have aria-label with location info
      const mapButtons = mapContainer.querySelectorAll('button');
      const mapLinks = mapContainer.querySelectorAll('a');
      const mapIframes = mapContainer.querySelectorAll('iframe');
      const mapDivsWithTabindex = mapContainer.querySelectorAll('div[tabindex="0"]');

      let removedButtons = 0;
      mapButtons.forEach(btn => {
        // Check if this is a Google Maps control (not a location marker)
        const ariaLabel = btn.getAttribute('aria-label') || '';
        const title = btn.getAttribute('title') || '';
        const isGoogleMapsControl =
          // Zoom controls
          btn.classList.contains('gm-control-active') ||
          // Keyboard shortcuts
          btn.textContent.includes('Keyboard shortcuts') ||
          ariaLabel.includes('Keyboard shortcuts') ||
          title.includes('Keyboard shortcuts') ||
          // Camera controls
          ariaLabel.includes('camera') ||
          ariaLabel.includes('Camera') ||
          title.includes('camera') ||
          title.includes('Camera') ||
          // Rotate/tilt controls
          ariaLabel.includes('Rotate') ||
          ariaLabel.includes('Tilt') ||
          // Other Google Maps UI
          btn.closest('.gm-bundled-control') !== null ||
          btn.closest('.gm-svpc') !== null;

        // Only remove from tab order if it IS a Google Maps control
        if (isGoogleMapsControl && btn.getAttribute('tabindex') !== '-1') {
          btn.setAttribute('tabindex', '-1');
          removedButtons++;
        }
      });

      // Remove all links (Terms, Report error, etc.)
      mapLinks.forEach(link => {
        if (link.getAttribute('tabindex') !== '-1') {
          link.setAttribute('tabindex', '-1');
        }
      });

      // Remove iframes
      mapIframes.forEach(iframe => {
        if (iframe.getAttribute('tabindex') !== '-1') {
          iframe.setAttribute('tabindex', '-1');
        }
      });

      // Remove keyboard shortcut overlays, but preserve location marker divs
      let removedDivs = 0;
      mapDivsWithTabindex.forEach(div => {
        // Check if this div is a location marker (has an image or role="button" with image)
        const hasImage = div.querySelector('img') !== null;
        const isMarker = hasImage || (div.getAttribute('role') === 'button' && div.querySelector('img'));

        // Only remove keyboard overlays, not location markers
        if (!isMarker) {
          div.setAttribute('tabindex', '-1');
          removedDivs++;
        }
      });

      return {
        buttons: removedButtons,
        links: mapLinks.length,
        divs: removedDivs
      };
    };

    // Remove current elements
    removeInteractiveElements();

    // Set up MutationObserver to catch Google Maps adding elements dynamically
    if (!mapContainer._tabIndexObserver) {
      const observer = new MutationObserver(function(mutations) {
        removeInteractiveElements();
      });

      observer.observe(mapContainer, {
        childList: true,
        subtree: true
      });

      mapContainer._tabIndexObserver = observer;
    }
  }

  /**
   * Handle focus management for Google Maps info windows (location popups)
   * Traps focus inside the info window so users can tab between close button and view details
   */
  function handleInfoWindowFocus() {
    const mapContainer = document.querySelector('.facet.custom-map');
    if (!mapContainer) return;

    // Set up observer to detect when info windows open
    const infoWindowObserver = new MutationObserver(function(mutations) {
      // Look for info window elements
      const infoWindow = document.querySelector('.gm-style-iw.gm-style-iw-c');

      if (infoWindow && !infoWindow.dataset.a11yEnhanced) {
        // Mark as enhanced to prevent duplicate processing
        infoWindow.dataset.a11yEnhanced = 'true';

        // Ensure content links (like "View Details") are focusable
        const viewDetailsLink = infoWindow.querySelector('.marker-view-details');

        if (viewDetailsLink) {
          // Always set tabindex to 0, even if it's currently -1
          viewDetailsLink.setAttribute('tabindex', '0');
        }

        // Set up focus trapping - be more inclusive with selector
        const focusableElements = infoWindow.querySelectorAll('a, button, [tabindex="0"]');
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        // Focus trap handler - only allow tabbing between close button and view details
        function handleInfoWindowTabKey(e) {
          if (e.key !== 'Tab') return;

          // Check if focus is currently on one of our info window elements
          const isOnFirstElement = document.activeElement === firstFocusable;
          const isOnLastElement = document.activeElement === lastFocusable;

          // If focus is on one of our elements, trap it
          if (isOnFirstElement || isOnLastElement) {
            e.preventDefault();

            if (e.shiftKey) {
              // Shift + Tab (backward) - toggle to the other element
              if (isOnFirstElement) {
                lastFocusable.focus();
              } else {
                firstFocusable.focus();
              }
            } else {
              // Tab (forward) - toggle to the other element
              if (isOnFirstElement) {
                lastFocusable.focus();
              } else {
                firstFocusable.focus();
              }
            }
          }
        }

        // Add focus trap - listen on document to catch all tab events
        document.addEventListener('keydown', handleInfoWindowTabKey);
        infoWindow._focusTrapHandler = handleInfoWindowTabKey;
      }

      // Remove focus trap from closed info windows
      const allInfoWindows = document.querySelectorAll('.gm-style-iw.gm-style-iw-c');
      allInfoWindows.forEach(function(win) {
        if (!document.contains(win) && win._focusTrapHandler) {
          document.removeEventListener('keydown', win._focusTrapHandler);
          delete win._focusTrapHandler;
          delete win.dataset.a11yEnhanced;
        }
      });
    });

    infoWindowObserver.observe(mapContainer, {
      childList: true,
      subtree: true
    });
  }

  // Initialize info window focus handling
  handleInfoWindowFocus();

})();
