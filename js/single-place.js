/**
 * Single Place Tab and FAQ Interactions
 * Vanilla JavaScript - No dependencies
 * Implements WAI-ARIA tab pattern with keyboard navigation and deep linking
 */

(function() {
  'use strict';

  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    initTabs();
    initFAQs();
  }

  /**
   * Tab Management
   * Implements WAI-ARIA tab pattern with keyboard navigation and URL hash deep linking
   */
  function initTabs() {
    const tabList = document.querySelector('.place-tabs');
    if (!tabList) return;

    const tabs = Array.from(tabList.querySelectorAll('.tab-button'));
    const panels = Array.from(document.querySelectorAll('.tab-content'));

    if (tabs.length === 0 || panels.length === 0) return;

    // Check for hash in URL and activate that tab
    const hash = window.location.hash.slice(1);
    if (hash) {
      const targetTab = tabs.find(tab => tab.dataset.tab === hash);
      if (targetTab) {
        activateTab(targetTab, tabs, panels);
      }
    }

    // Tab click handlers
    tabs.forEach(tab => {
      tab.addEventListener('click', (e) => {
        e.preventDefault();
        activateTab(tab, tabs, panels);
        updateURL(tab.dataset.tab);
      });

      // Keyboard navigation
      tab.addEventListener('keydown', (e) => {
        handleTabKeydown(e, tab, tabs, panels);
      });
    });

    // Handle browser back/forward buttons
    window.addEventListener('hashchange', () => {
      const newHash = window.location.hash.slice(1);
      if (newHash) {
        const targetTab = tabs.find(tab => tab.dataset.tab === newHash);
        if (targetTab) {
          activateTab(targetTab, tabs, panels);
        }
      }
    });
  }

  /**
   * Activate a specific tab
   */
  function activateTab(targetTab, allTabs, allPanels) {
    const targetId = targetTab.dataset.tab;
    const targetPanel = document.getElementById(`tab-${targetId}`);

    if (!targetPanel) return;

    // Deactivate all tabs
    allTabs.forEach(tab => {
      tab.classList.remove('active');
      tab.setAttribute('aria-selected', 'false');
      tab.setAttribute('tabindex', '-1');
    });

    // Hide all panels
    allPanels.forEach(panel => {
      panel.classList.remove('active');
      panel.setAttribute('hidden', '');
    });

    // Activate target tab
    targetTab.classList.add('active');
    targetTab.setAttribute('aria-selected', 'true');
    targetTab.setAttribute('tabindex', '0');

    // Show target panel
    targetPanel.classList.add('active');
    targetPanel.removeAttribute('hidden');

    // Focus management: move focus to panel
    targetPanel.focus();
  }

  /**
   * Handle keyboard navigation for tabs
   * Arrow keys, Home, End
   */
  function handleTabKeydown(event, currentTab, allTabs, allPanels) {
    const currentIndex = allTabs.indexOf(currentTab);
    let targetTab = null;

    switch (event.key) {
      case 'ArrowRight':
      case 'ArrowDown':
        event.preventDefault();
        // Move to next tab (wrap around)
        const nextIndex = (currentIndex + 1) % allTabs.length;
        targetTab = allTabs[nextIndex];
        break;

      case 'ArrowLeft':
      case 'ArrowUp':
        event.preventDefault();
        // Move to previous tab (wrap around)
        const prevIndex = currentIndex === 0 ? allTabs.length - 1 : currentIndex - 1;
        targetTab = allTabs[prevIndex];
        break;

      case 'Home':
        event.preventDefault();
        targetTab = allTabs[0];
        break;

      case 'End':
        event.preventDefault();
        targetTab = allTabs[allTabs.length - 1];
        break;

      default:
        return;
    }

    if (targetTab) {
      activateTab(targetTab, allTabs, allPanels);
      targetTab.focus();
      updateURL(targetTab.dataset.tab);
    }
  }

  /**
   * Update URL hash without page jump
   */
  function updateURL(tabId) {
    if (history.pushState) {
      history.pushState(null, null, `#${tabId}`);
    } else {
      // Fallback for older browsers
      window.location.hash = tabId;
    }
  }

  /**
   * FAQ Accordion Management
   * Implements WAI-ARIA disclosure pattern
   */
  function initFAQs() {
    const faqButtons = document.querySelectorAll('.faq-question');

    if (faqButtons.length === 0) return;

    faqButtons.forEach(button => {
      button.addEventListener('click', () => {
        toggleFAQ(button);
      });

      // Keyboard support (Enter and Space are handled by default button behavior)
      button.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggleFAQ(button);
        }
      });
    });
  }

  /**
   * Toggle FAQ answer visibility
   */
  function toggleFAQ(button) {
    const answerId = button.getAttribute('aria-controls');
    const answer = document.getElementById(answerId);

    if (!answer) return;

    const isExpanded = button.getAttribute('aria-expanded') === 'true';

    if (isExpanded) {
      // Collapse
      button.setAttribute('aria-expanded', 'false');
      answer.setAttribute('hidden', '');
    } else {
      // Expand
      button.setAttribute('aria-expanded', 'true');
      answer.removeAttribute('hidden');
    }
  }

  /**
   * Print support
   * Expand all FAQs and show all tab content when printing
   */
  window.addEventListener('beforeprint', () => {
    // Expand all FAQs
    const faqButtons = document.querySelectorAll('.faq-question');
    faqButtons.forEach(button => {
      button.setAttribute('aria-expanded', 'true');
      const answerId = button.getAttribute('aria-controls');
      const answer = document.getElementById(answerId);
      if (answer) {
        answer.removeAttribute('hidden');
      }
    });

    // Show all tab panels
    const allPanels = document.querySelectorAll('.tab-content');
    allPanels.forEach(panel => {
      panel.classList.add('active');
      panel.removeAttribute('hidden');
    });
  });

  /**
   * Restore state after print
   */
  window.addEventListener('afterprint', () => {
    // Re-initialize to restore proper state
    initTabs();
    initFAQs();
  });

})();
