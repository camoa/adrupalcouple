import { GoogleMapFeature } from "./GoogleMapFeature.js";

export default class GoogleControlCustomRecenter extends GoogleMapFeature {
  /**
   * @inheritDoc
   */
  constructor(settings, map) {
    super(settings, map);

    const recenterButton = this.map.wrapper.querySelector(".geolocation-map-control .recenter");

    recenterButton.addEventListener("click", (e) => {
      e.preventDefault();

      this.map.setCenterByOptions();
    });
  }
}
