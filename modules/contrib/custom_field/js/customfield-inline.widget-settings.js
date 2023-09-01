/**
 * @file customfield-inline.widget-settings.js
 */

(function (Drupal, drupalSettings) {

  "use strict";

  /**
   * Add the selected proportion class when one is selected on the widget
   * settings form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *  Attaches the behavior for proportion classes.
   */
  Drupal.behaviors.customFieldInlineWidgetSettings = {
    attach: function (context) {
      var selects = context.querySelectorAll('.customfield-inline--widget-settings select');
      Array.prototype.forEach.call(selects, function (select) {
        select.addEventListener('change', function (event) {
          var value = this.value;
          var parent = this.closest('.customfield-inline__item');
          parent.className = parent.className.replace(/(^|\s)customfield-inline__item--.*?\S+/g, '');
          parent.classList.add('customfield-inline__item--' + value);
        });
      });
    }
  };

})(Drupal, drupalSettings);
