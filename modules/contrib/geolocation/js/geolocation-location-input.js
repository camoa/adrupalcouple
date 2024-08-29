/**
 * @file
 * Geolocation - Location Input Form & Plugin Management.
 */

(function (Drupal) {
  "use strict";

  Drupal.behaviors.geolocationLocationInput = {
    /**
     * @param {Element} context
     * @param {Object} drupalSettings
     * @param {Object} drupalSettings.geolocation
     * @param {Object} drupalSettings.geolocation.locationInput
     */
    attach: function (context, drupalSettings) {
      for (const identifier in drupalSettings.geolocation.locationInput) {
        let locationInputForm = context.querySelector(".geolocation-location-input[data-identifier=" + identifier + "]");

        if (!locationInputForm) {
          // Nothing left to do. Probably a different context. Not an error.
          return;
        }

        if (locationInputForm.classList.contains("geolocation-location-input-processed")) {
          return;
        }
        locationInputForm.classList.add("geolocation-location-input-processed");

        for (const pluginName in drupalSettings.geolocation.locationInput[identifier]) {
          let pluginSettings = drupalSettings.geolocation.locationInput[identifier][pluginName] ?? {};
          import(pluginSettings.import_path).then((plugin) => {
            /** @param {GeolocationLocationInputBase} locationInputPlugin */
            let locationInputPlugin = new plugin.default(locationInputForm, pluginSettings.settings);

            if (!locationInputPlugin) {
              console.error(pluginSettings, "Could not instantiate LocationInput Plugin.");
            }
          });
        }
      }
    },
  };
})(Drupal);
