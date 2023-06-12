/* eslint-disable strict, no-use-before-define */

/**
 * @file
 * Schema.org UI behaviors.
 */

"use strict";

(($, Drupal, debounce, once) => {
  /**
   * Schema.org UI field prefix.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.schemaDotOrgUiFieldPrefix = {
    attach: function attach(context) {
      once('schemadotorg-ui-field-prefix', 'input[name="label"]', context)
        .forEach((labelInput) => {
          const schemaDotOrgLabelInput = document.querySelector('input[name="schemadotorg_label"]');
          const formUpdatedEvent = new Event('formUpdated');
          schemaDotOrgLabelInput.addEventListener('change', (event) => {
            labelInput.value = schemaDotOrgLabelInput.value;
            labelInput.dispatchEvent(formUpdatedEvent);
          });
          labelInput.addEventListener('change', (event) => {
            schemaDotOrgLabelInput.value = labelInput.value;
            schemaDotOrgLabelInput.dispatchEvent(formUpdatedEvent);
          });
        });
    }
  };
})(jQuery, Drupal, Drupal.debounce, once);
