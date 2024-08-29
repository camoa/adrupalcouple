import { LeafletMapFeature } from "./LeafletMapFeature.js";

/**
 * @typedef {Object} ControlZoomSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} position
 */

/**
 * @prop {ControlZoomSettings} settings
 */
export default class LeafletControlZoom extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);
    L.control
      .zoom({
        position: this.settings.position,
      })
      .addTo(map.leafletMap);
  }
}
