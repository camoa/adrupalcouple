import { GeolocationLayerFeature } from "../../../../js/LayerFeature/GeolocationLayerFeature.js";

/**
 * @prop {Bing} layer.map
 */
export class BingLayerFeature extends GeolocationLayerFeature {
  /**
   * @param {BingMapMarker} marker
   *   Bing marker.
   */
  onMarkerAdded(marker) {}

  /**
   * @param {BingMapMarker} marker
   *   Bing marker.
   */
  onMarkerRemove(marker) {}

  /**
   * @param {BingMapMarker} marker
   *   Bing marker.
   */
  onMarkerUpdated(marker) {}

  /**
   * @param {BingMapMarker} marker
   *   Bing marker.
   */
  onMarkerClicked(marker) {}
}
