import { GoogleMapFeature } from "./GoogleMapFeature.js";

/**
 * @prop {GeolocationMapFeatureSettings} settings
 * @prop {GoogleMaps} map
 */
export default class GoogleMapDisablePOI extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setOptions({
      gestureHandling: "none",
      zoomControl: false,
    });
  }
}
