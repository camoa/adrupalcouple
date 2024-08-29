import { GoogleMapFeature } from "./GoogleMapFeature.js";

export default class GoogleMapTypeStyle extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    this.map.googleMap.setOptions({
      styles: JSON.parse(this.settings.style),
    });
  }
}
