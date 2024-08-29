import { GeolocationMapFeature } from "../../../../js/MapFeature/GeolocationMapFeature.js";

/**
 * @prop {Bing} map
 */
export class BingMapFeature extends GeolocationMapFeature {
  /**
   * @param {BingMapMarker} marker
   *   Bing marker.
   */
  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);
  }

  /**
   * @param {BingMapMarker} marker
   *   Bing marker.
   */
  onMarkerUpdated(marker) {
    super.onMarkerUpdated(marker);
  }

  /**
   * @param {BingMapMarker} marker
   *   Bing marker.
   */
  onMarkerRemove(marker) {
    super.onMarkerRemove(marker);
  }

  /**
   * @param {BingMapMarker} marker
   *   Bing marker.
   */
  onMarkerClicked(marker) {
    super.onMarkerClicked(marker);
  }
}
