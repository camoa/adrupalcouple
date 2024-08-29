import { GoogleMapFeature } from "./GoogleMapFeature.js";
import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";

export default class GoogleControlCustomLocate extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    const locateButton = this.map.wrapper.querySelector(".geolocation-map-control .locate");

    if (navigator.geolocation && window.location.protocol === "https:") {
      locateButton.addEventListener(
        "click",
        (e) => {
          navigator.geolocation.getCurrentPosition((currentPosition) => {
            this.map.setCenterByCoordinates(new GeolocationCoordinates(currentPosition.coords.latitude, currentPosition.coords.longitude), currentPosition.coords.accuracy);
          });
          e.preventDefault();
        },
        false
      );
    } else {
      locateButton.parentNode.removeChild(locateButton);
    }
  }
}
