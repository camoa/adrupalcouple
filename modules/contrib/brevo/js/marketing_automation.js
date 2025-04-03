(function (Drupal, drupalSettings) {
  Drupal.behaviors.brevoMarketingAutomation = {
    attach: function (context, settings) {
      window.Brevo = window.Brevo || [];
      Brevo.push(['init', {
        client_key: drupalSettings.brevo.clientKey,
      }]);
    }
  };
})(Drupal, drupalSettings);
