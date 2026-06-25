/**
 * @file
 * Theme toggle behavior — switches data-theme on <html> between adc-warm / adc-dark,
 * persists the choice in localStorage, and on load applies the stored preference
 * (or honours prefers-color-scheme when unset).
 *
 * Mirrors the logic in ThemeSwitcher.tsx from the ADrupalCouple React template.
 * Storage key: 'adc-theme' (matches STORAGE_KEY in ThemeSwitcher.tsx).
 */

/**
 * Early apply — runs synchronously at parse time when the library is in <head>
 * (header: true in adrupalcouple.libraries.yml). Prevents Flash Of Unstyled
 * Content (FOUC) by setting data-theme before the browser lays out any content.
 */
(function earlyApply() {
  try {
    var stored = localStorage.getItem('adc-theme');
    var theme =
      stored === 'adc-warm' || stored === 'adc-dark'
        ? stored
        : typeof window.matchMedia === 'function' &&
          window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'adc-dark'
        : 'adc-warm';
    document.documentElement.setAttribute('data-theme', theme);
  } catch (e) {
    /* Private-browsing or storage errors — CSS default (adc-warm) will render. */
  }
})();

(function (Drupal, once) {
  'use strict';

  var STORAGE_KEY = 'adc-theme';

  /**
   * Applies a theme to <html> and persists the choice in localStorage.
   *
   * @param {string} theme - 'adc-warm' or 'adc-dark'.
   */
  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch (e) {
      /* Storage unavailable — theme applies for the session only. */
    }
  }

  /**
   * Returns the theme that should be active: stored preference, then
   * prefers-color-scheme, then adc-warm. Mirrors getInitialTheme() in
   * ThemeSwitcher.tsx.
   *
   * @return {string} 'adc-warm' or 'adc-dark'.
   */
  function resolveTheme() {
    try {
      var stored = localStorage.getItem(STORAGE_KEY);
      if (stored === 'adc-warm' || stored === 'adc-dark') return stored;
    } catch (e) { /* ignore */ }
    if (
      typeof window.matchMedia === 'function' &&
      window.matchMedia('(prefers-color-scheme: dark)').matches
    ) {
      return 'adc-dark';
    }
    return 'adc-warm';
  }

  /**
   * Syncs the visible glyph and ARIA state of a toggle button to the active
   * theme. Mirrors the aria-label / glyph logic in ThemeSwitcher.tsx.
   *
   * ☾ = currently light (will go dark on click).
   * ☀ = currently dark  (will go light on click).
   *
   * @param {Element} button - The .theme-toggle button element.
   * @param {string}  theme  - 'adc-warm' or 'adc-dark'.
   */
  function syncButton(button, theme) {
    var isDark = theme === 'adc-dark';
    button.setAttribute('aria-pressed', String(isDark));
    button.setAttribute(
      'aria-label',
      isDark
        ? Drupal.t('Switch to light theme')
        : Drupal.t('Switch to dark theme')
    );
    var glyph = button.querySelector('[aria-hidden]');
    if (glyph) {
      glyph.textContent = isDark ? '☀' : '☾'; /* ☀ / ☾ */
    }
  }

  /**
   * Behavior: adrupalcoupleThemeToggle.
   *
   * - On attach, ensures the correct theme is applied to <html>.
   * - Wires up each .theme-toggle button (once per element, idempotent).
   * - On click, swaps the theme and re-syncs ALL buttons on the page (there may
   *   be one in the desktop navbar and one in the mobile drawer simultaneously).
   */
  Drupal.behaviors.adrupalcoupleThemeToggle = {
    attach: function (context) {
      // Ensure <html data-theme> is correct. The earlyApply IIFE already ran
      // (assuming the script was in <head>); this is the fallback for any case
      // where <html data-theme> is still unset when the behavior fires.
      var initial = resolveTheme();
      if (!document.documentElement.hasAttribute('data-theme')) {
        applyTheme(initial);
      }

      var current =
        document.documentElement.getAttribute('data-theme') || initial;

      // Wire each unprocessed .theme-toggle exactly once per DOM lifecycle.
      once('adc-theme-toggle', '.theme-toggle', context).forEach(
        function (button) {
          // Sync button glyph / ARIA to the theme that is currently applied.
          syncButton(button, current);

          button.addEventListener('click', function () {
            var active =
              document.documentElement.getAttribute('data-theme') || 'adc-warm';
            var next = active === 'adc-dark' ? 'adc-warm' : 'adc-dark';
            applyTheme(next);
            // Re-sync every toggle button visible on the page.
            document
              .querySelectorAll('.theme-toggle')
              .forEach(function (btn) { syncButton(btn, next); });
          });
        }
      );
    },
  };
})(Drupal, once);
