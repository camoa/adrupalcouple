import { GoogleLayerFeature } from "./GoogleLayerFeature.js";

/**
 * @typedef {Object} MarkerOpacitySettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} opacity
 */

export default class GoogleMarkerOpacity extends GoogleLayerFeature {
  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    marker.googleMarker.setOpacity(parseFloat(this.settings.opacity));
  }
}
