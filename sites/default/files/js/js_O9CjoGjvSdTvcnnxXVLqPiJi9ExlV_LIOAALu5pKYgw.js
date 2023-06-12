/* eslint-disable strict, func-names */

/**
 * @file
 * Schema.org autocomplete behaviors.
 */

"use strict";

(($, Drupal, once) => {
  /**
   * Schema.org filter autocomplete handler.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgAutocomplete = {
    attach: function attach(context) {
      once('schemadotorg-autocomplete', 'input.schemadotorg-autocomplete', context)
        .forEach((element) => {
          // If input value is an autocomplete match, reset the input to its
          // default value.
          if (/\(([^)]+)\)$/.test(element.value)) {
            element.value = element.defaultValue;
          }

          // jQuery UI autocomplete submit onclick result.
          // Must use jQuery to bind to a custom event.
          // @see http://stackoverflow.com/questions/5366068/jquery-ui-autocomplete-submit-onclick-result
          $(element).bind('autocompleteselect', function (event, ui) {
            if (!ui.item) {
              return;
            }

            const action = element.getAttribute('data-schemadotorg-autocomplete-action');
            if (action) {
              const url = `${action}/${ui.item.value}`;
              const isDialog = (Drupal.schemaDotOrgOpenDialog &&
                element.closest('.ui-dialog'));
              if (isDialog) {
                Drupal.schemaDotOrgOpenDialog(url);
              } else {
                window.top.location = url;
              }
            } else {
              element.value = ui.item.value;
              element.form.submit();
            }
          });
        });
    }
  };
})(jQuery, Drupal, once);
;
/**
 * @file
 * Responsive navigation tabs.
 *
 * This also supports collapsible navigable is the 'is-collapsible' class is
 * added to the main element, and a target element is included.
 */
(($, Drupal) => {
  function init(tab) {
    const $tab = $(tab);
    const $target = $tab.find('[data-drupal-nav-tabs-target]');
    const $active = $target.find('.js-active-tab');

    const openMenu = () => {
      $target.toggleClass('is-open');
    };

    const toggleOrder = (reset) => {
      const current = $active.index();
      const original = $active.data('original-order');

      // Do not change order if already first or if already reset.
      if (original === 0 || reset === (current === original)) {
        return;
      }

      const siblings = {
        first: '[data-original-order="0"]',
        previous: `[data-original-order="${original - 1}"]`,
      };

      const $first = $target.find(siblings.first);
      const $previous = $target.find(siblings.previous);

      if (reset && current !== original) {
        $active.insertAfter($previous);
      } else if (!reset && current === original) {
        $active.insertBefore($first);
      }
    };

    const toggleCollapsed = () => {
      if (window.matchMedia('(min-width: 48em)').matches) {
        if ($tab.hasClass('is-horizontal') && !$tab.attr('data-width')) {
          let width = 0;

          $target.find('.js-tabs-link').each((index, value) => {
            width += $(value).outerWidth();
          });
          $tab.attr('data-width', width);
        }

        // Collapse the tabs if the combined width of the tabs is greater than
        // the width of the parent container.
        const isHorizontal = $tab.attr('data-width') <= $tab.outerWidth();
        $tab.toggleClass('is-horizontal', isHorizontal);
        toggleOrder(isHorizontal);
      } else {
        toggleOrder(false);
      }
    };

    $tab.addClass('position-container is-horizontal-enabled');

    $target.find('.js-tab').each((index, element) => {
      const $item = $(element);
      $item.attr('data-original-order', $item.index());
    });

    $tab.on('click.tabs', '[data-drupal-nav-tabs-trigger]', openMenu);
    $(window)
      // @todo use a media query event listener https://www.drupal.org/project/drupal/issues/3225621
      .on('resize.tabs', Drupal.debounce(toggleCollapsed, 150))
      .trigger('resize.tabs');
  }
  /**
   * Initialize the tabs JS.
   */
  Drupal.behaviors.navTabs = {
    attach(context) {
      once(
        'nav-tabs',
        '[data-drupal-nav-tabs].is-collapsible',
        context,
      ).forEach(init);
    },
  };
})(jQuery, Drupal);
;
