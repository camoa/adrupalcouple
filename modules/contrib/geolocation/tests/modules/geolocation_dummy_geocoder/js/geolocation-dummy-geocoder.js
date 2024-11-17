/**
 * @file
 * Javascript for the Dummy geocoder.
 */

(function ($, Drupal) {
  if (typeof Drupal.geolocation.geocoder === "undefined") {
    return false;
  }

  /**
   * Attach geocoder input for Dummy.
   */
  Drupal.behaviors.geolocationGeocoderDummy = {
    attach: (context) => {
      $("input.geolocation-geocoder-dummy", context)
        .once()
        .on("input", () => {
          const that = $(this);
          Drupal.geolocation.geocoder.clearCallback(that.data("source-identifier"));

          if (!that.val().length) {
            return;
          }

          $.ajax(Drupal.url(`geolocation_dummy_geocoder/geocode/${that.val()}`)).done((data) => {
            if (data.length < 3) {
              return;
            }
            /**
             * @type {GeolocationGeocodedResult}
             */
            const address = {
              geometry: {
                location: {
                  lat: () => {
                    return data.location.lat;
                  },
                  lng: () => {
                    return data.location.lng;
                  },
                },
              },
            };
            Drupal.geolocation.geocoder.resultCallback(address);
          });
        });
    },
  };
})(jQuery, Drupal);
