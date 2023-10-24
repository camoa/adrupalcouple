/* eslint-disable max-len, prefer-destructuring */

/**
 * @file
 * Schema.org JSON-LD details behaviors.
 */

((Drupal, once) => {
  // Determine if local storage exists and is enabled.
  // This approach is copied from Modernizr.
  // @see https://github.com/Modernizr/Modernizr/blob/c56fb8b09515f629806ca44742932902ac145302/modernizr.js#L696-731
  let hasLocalStorage;
  try {
    localStorage.setItem('schemadotorg_details', 'schemadotorg_details');
    localStorage.removeItem('schemadotorg_details');
    hasLocalStorage = true;
  } catch (e) {
    hasLocalStorage = false;
  }

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

      once(
        'schemadotorg-details-state',
        'details[data-schemadotorg-details-key]',
        context,
      ).forEach((element) => {
        const key = element.getAttribute('data-schemadotorg-details-key');
        element.querySelector('summary').addEventListener('click', () => {
          const open = element.getAttribute('open') !== 'open' ? '1' : '0';
          localStorage.setItem(key, open);
        });

        const open = localStorage.getItem(key);
        if (open === '1') {
          element.setAttribute('open', 'open');
        } else if (open === '0') {
          element.removeAttribute('open');
        }
      });
    },
  };

  Drupal.behaviors.schemaDotOrgDetailsToggle = {
    attach: function attach(context) {
      if (!hasLocalStorage) {
        return;
      }

      // eslint-disable-next-line
      once('schemadotorg-details-toggle', 'body form', context).forEach(() => {
        // Region can either be the Mercury Editor node form (.me-node-form)
        // or the help region (.region-help).
        const region = document.querySelector('.me-node-form, .region-help');
        // eslint-disable-next-line
        if (!region || region.querySelector('.schemadotorg-details-toggle')) {
          return;
        }

        // Build the toggle details button.
        const button = document.createElement('button');
        button.setAttribute('type', 'button');
        // eslint-disable-next-line
        button.setAttribute('class', 'schemadotorg-details-toggle button button-small button--extrasmall');
        // eslint-disable-next-line
        button.setAttribute('style', 'float: right; margin: 0; min-width: 7rem');
        // eslint-disable-next-line
        button.setAttribute('title', Drupal.t('Toggle details widget state.'));

        /**
         * Set the toggle details button's label.
         */
        function setButtonLabel() {
          const isClosed = document.querySelector('details:not([open])');
          // eslint-disable-next-line
          button.innerText = isClosed ? Drupal.t('+ Expand all') : Drupal.t('− Collapse all');
        }

        // Init button label.
        setButtonLabel();

        // Add button click event handler.
        button.addEventListener('click', () => {
          const isClosed = document.querySelector('details:not([open])');
          // Toggle all details.
          document.querySelectorAll('details').forEach((details) => {
            const key = details.getAttribute('data-schemadotorg-details-key');
            if (isClosed) {
              details.setAttribute('open', 'open');
              if (key) {
                localStorage.setItem(key, '1');
              }
            } else {
              details.removeAttribute('open');
              if (key) {
                localStorage.setItem(key, '0');
              }
            }
          });
          // Announce toggling of details state.
          const text = isClosed
            ? Drupal.t('All details have been expanded.')
            : Drupal.t('All details have been collapsed.');
          Drupal.announce(text);
          // Set toggle button label.
          setButtonLabel();
        });

        // Prepend the toggle details button to the help region.
        region.prepend(button);
      });
    },
  };
})(Drupal, once);
