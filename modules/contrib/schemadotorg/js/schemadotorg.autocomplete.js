/* eslint-disable strict, func-names */

/**
 * @file
 * Schema.org autocomplete behaviors.
 */

"use strict";

(($, Drupal, once) => {
  /**
   * Schema.org filter autocomplete action handler.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgAutocompleteAction = {
    attach: function attach(context) {
      once('schemadotorg-autocomplete-action', 'input[data-schemadotorg-autocomplete-action]', context)
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
            const id = ui.item.value;
            const url = action + id;

            const isDialog = (Drupal.schemaDotOrgOpenDialog &&
              element.closest('.ui-dialog'));
            if (isDialog) {
              Drupal.schemaDotOrgOpenDialog(url);
            } else {
              window.top.location = url;
            }
          });
        });
    }
  };
})(jQuery, Drupal, once);
