/**
 * @file custom-field-flex.widget-settings.js
 */

(function (Drupal, drupalSettings) {

  "use strict";

  /**
   * Add the selected column class when one is selected on the widget
   * settings form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *  Attaches the behavior for column classes.
   */
  Drupal.behaviors.customFieldFlexWidgetSettings = {
    attach: function (context) {
      var selects = context.querySelectorAll('.custom-field-flex--widget-settings select');
      Array.prototype.forEach.call(selects, function (select) {
        select.addEventListener('change', function (event) {
          var value = this.value;
          var parent = this.closest('.custom-field-col');
          parent.className = parent.className.replace(/(^|\s)custom-field-col-.*?\S+/g, '');
          parent.classList.add('custom-field-col-' + value);
        });
      });
    }
  };

})(Drupal, drupalSettings);
