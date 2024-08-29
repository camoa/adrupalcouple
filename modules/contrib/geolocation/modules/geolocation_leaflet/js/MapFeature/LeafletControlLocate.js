import { LeafletMapFeature } from "./LeafletMapFeature.js";

export default class LeafletControlLocate extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);

    const locateButton = map.wrapper.querySelector(".geolocation-map-control .locate");
    if (navigator.geolocation && window.location.protocol === "https:") {
      locateButton.on("click", (e) => {
        e.preventDefault();
        navigator.geolocation.getCurrentPosition((currentPosition) => {
          const currentLocation = new L.LatLng(currentPosition.coords.latitude, currentPosition.coords.longitude);
          map.setCenterByCoordinates(currentLocation, currentPosition.coords.accuracy);
        });
      });
    } else {
      locateButton.remove();
    }
  }
}
