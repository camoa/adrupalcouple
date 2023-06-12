/* eslint-disable strict, prefer-destructuring */

/**
 * @file
 * Schema.org JSON-LD details behaviors.
 */

"use strict";

((Drupal, once) => {
  // Determine if local storage exists and is enabled.
  // This approach is copied from Modernizr.
  // @see https://github.com/Modernizr/Modernizr/blob/c56fb8b09515f629806ca44742932902ac145302/modernizr.js#L696-731
  const hasLocalStorage = (function hasLocalStorage() {
    try {
      localStorage.setItem('schemadotorg_details', 'schemadotorg_details');
      localStorage.removeItem('schemadotorg_details');
      return true;
    }
    catch (e) {
      return false;
    }
  }());

  /**
   * Tracks Schema.org details open/close state.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgDetailsState = {
    attach: function attach(context) {
      if (!hasLocalStorage) {
        return;
      }

      once('schemadotorg-details-state', 'details[data-schemadotorg-details-key]', context)
        .forEach((element) => {
          const key = element.getAttribute('data-schemadotorg-details-key');
          element
            .querySelector('summary')
            .addEventListener('click', () => {
              const open = (element.getAttribute('open') !== 'open') ? '1' : '0';
              localStorage.setItem(key, open);
            });

          const open = localStorage.getItem(key);
          if (open === '1') {
            element.setAttribute('open', 'open');
          }
          else if (open === '0') {
            element.removeAttribute('open');
          }
        });
    }
  };
})(Drupal, once);
