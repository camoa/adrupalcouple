(function ($, Drupal) {

  "use strict";

  /**
   * Add the selected proportion class when one is selected on the widget
   * settings form.
   */
  Drupal.behaviors.customFieldInlineWidgetSettings = {
    attach: function (context) {
      $('.customfield-inline--widget-settings select').change(function(event) {
        var value = $(this).val();
        var $parent = $(this).parents('.customfield-inline__item');
        $parent.removeClass (function (index, className) {
          return (className.match (/(^|\s)customfield-inline__item--.*?\S+/g) || []).join(' ');
        });
        $parent.addClass('customfield-inline__item--' + value)
      });
    }
  };

}(jQuery, Drupal));
