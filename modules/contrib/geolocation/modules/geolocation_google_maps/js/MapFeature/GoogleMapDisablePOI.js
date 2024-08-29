import { GoogleMapFeature } from "./GoogleMapFeature.js";

export default class GoogleMapDisablePOI extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    let styles = [];
    if (this.map.googleMap.styles) {
      styles = styles.concat(this.map.googleMap.styles);
    }
    styles = styles.concat([
      {
        featureType: "poi",
        stylers: [{ visibility: "off" }],
      },
    ]);

    this.map.googleMap.setOptions({ styles });
  }
}
