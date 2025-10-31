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
    console.log('Map block JavaScript already initialized, skipping...');
    return;
  }
  window.wpPlacesMapInitialized = true;

  console.log('Map block JavaScript loaded!');

  /**
   * Initialize all map blocks on the page
   */
  function initMapBlocks() {
    console.log('initMapBlocks called');
    const mapBlocks = document.querySelectorAll('.block-map');
    console.log('Found', mapBlocks.length, 'map blocks');

    mapBlocks.forEach(function (mapBlock) {
      // Check if this specific block has already been initialized
      if (mapBlock.dataset.mapInitialized === 'true') {
        console.log('Map block already initialized, skipping...');
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

    // Debug logging
    console.log('Initializing map block...');
    console.log('Filter panel:', filterPanel);
    console.log('Filter toggle button:', filterToggleBtn);
    console.log('Search button:', searchBtn);

    if (!filterPanel) {
      console.error('Filter panel not found!');
      return;
    }

    // Store the element that opened the panel for focus restoration
    let panelOpener = null;

    /**
     * Open the filter panel
     */
    function openPanel(opener) {
      panelOpener = opener;
      filterPanel.classList.add('is-open');
      filterPanel.removeAttribute('hidden');
      filterPanel.setAttribute('aria-hidden', 'false');

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
      console.log('Attaching click event to filter toggle button');
      filterToggleBtn.addEventListener('click', function (e) {
        e.preventDefault();
        console.log('Filter toggle button clicked!');
        togglePanel(filterToggleBtn);
      });
    } else {
      console.warn('Filter toggle button not found!');
    }

    // Close button click
    if (filterCloseBtn) {
      filterCloseBtn.addEventListener('click', function (e) {
        e.preventDefault();
        console.log('Close button clicked!');
        closePanel();
      });
    }

    // Apply filter button click (closes panel and triggers FacetWP refresh if needed)
    if (filterApplyBtn) {
      filterApplyBtn.addEventListener('click', function (e) {
        e.preventDefault();
        console.log('Apply filter button clicked!');
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

    // Handle search form submission
    if (searchForm) {
      searchForm.addEventListener('submit', function (e) {
        e.preventDefault();
        console.log('Search form submitted (prevented default)');
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
        console.log('Search button clicked');
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

  // Note: Search functionality removed - handled natively by FacetWP search facet

  /**
   * Trap focus within an element
   */
  function trapFocus(element) {
    const focusableElements = element.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
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
    console.log('FacetWP loaded event fired');
    console.log('Current facet values after refresh:', FWP.facets);
    console.log('Number of results:', FWP.settings.num_results);
    // Don't re-initialize - our elements are persistent
    // Only the facet content inside gets replaced by FacetWP
  });

})();
