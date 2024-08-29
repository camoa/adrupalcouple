import { LeafletMapFeature } from "./LeafletMapFeature.js";

export default class LeafletClientLocationIndicator extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);

    if (!navigator.geolocation) {
      return;
    }

    const clientLocationMarker = L.circleMarker([0, 0], {
      interactive: false,
      radius: 8,
      fillColor: "#039be5",
      fillOpacity: 1.0,
      color: "white",
      weight: 2,
    }).addTo(map.leafletMap);

    let indicatorCircle = null;

    setInterval(() => {
      navigator.geolocation.getCurrentPosition((currentPosition) => {
        const currentLocation = L.latLng(currentPosition.coords.latitude, currentPosition.coords.longitude);
        clientLocationMarker.setLatLng(currentLocation);

        if (!indicatorCircle) {
          indicatorCircle = map.addAccuracyIndicatorCircle(currentLocation, parseInt(currentPosition.coords.accuracy.toString()));
        } else {
          indicatorCircle.setLatLng(currentLocation);
          indicatorCircle.setRadius(parseInt(currentPosition.coords.accuracy.toString()));
        }
      });
    }, 5000);
  }
}
