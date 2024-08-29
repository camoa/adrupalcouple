import { GeolocationGeocoder } from "../../../../js/Geocoder/GeolocationGeocoder.js";
import { GeolocationGeocodedResult } from "../../../../js/Base/GeolocationGeocodedResult.js";
import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";
import { GeolocationBoundaries } from "../../../../js/Base/GeolocationBoundaries.js";

/**
 * @prop {String} settings.google_api_url
 */
export default class GoogleGeocodingAPI extends GeolocationGeocoder {
  constructor(settings) {
    super(settings);

    Drupal.geolocation.addScript(this.settings.google_api_url).then(() => {
      this.geocoder = new google.maps.Geocoder();
    });
  }

  geocode(address) {
    return new Promise((resolve) => {
      const results = [];

      const parameters = {
        address,
      };

      if (typeof this.settings.componentRestrictions !== "undefined") {
        if (this.settings.componentRestrictions) {
          parameters.componentRestrictions = this.settings.componentRestrictions;
        }
      }
      if (typeof this.settings.bounds !== "undefined") {
        if (this.settings.bounds) {
          parameters.bounds = this.settings.bounds;
        }
      }

      this.geocoder
        .geocode(parameters)
        .then((googleGeocoderResponse) => {
          googleGeocoderResponse.results.forEach((result) => {
            let bounds = null;
            if (result.geometry.bounds) {
              bounds = new GeolocationBoundaries({
                north: result.geometry.bounds.getNorthEast().lat(),
                east: result.geometry.bounds.getNorthEast().lng(),
                south: result.geometry.bounds.getSouthWest().lat(),
                west: result.geometry.bounds.getSouthWest().lng(),
              });
            } else if (result.geometry.viewport) {
              bounds = new GeolocationBoundaries({
                north: result.geometry.viewport.getNorthEast().lat(),
                east: result.geometry.viewport.getNorthEast().lng(),
                south: result.geometry.viewport.getSouthWest().lat(),
                west: result.geometry.viewport.getSouthWest().lng(),
              });
            }

            const coordinates = new GeolocationCoordinates(result.geometry.location.lat(), result.geometry.location.lng());
            const accuracy = result.geometry.accuracy ?? null;

            results.push({
              label: result.formatted_address,
              geocodedResult: new GeolocationGeocodedResult(coordinates, bounds, accuracy),
            });
          });
          resolve(results);
        })
        .catch((reason) => {
          console.error(`Geolocation - GoogleGeocodingAPI: ${reason}`);
        });
    });
  }
}
