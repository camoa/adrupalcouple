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
