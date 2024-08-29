import { GoogleMapFeature } from "./GoogleMapFeature.js";

export default class GoogleClientLocationIndicator extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    if (!navigator.geolocation) {
      return;
    }

    const clientLocationMarker = new google.maps.Marker({
      clickable: false,
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        fillColor: "#039be5",
        fillOpacity: 1.0,
        scale: 8,
        strokeColor: "white",
        strokeWeight: 2,
      },
      shadow: null,
      zIndex: 999,
      map: map.googleMap,
      position: { lat: 0, lng: 0 },
    });

    let indicatorCircle;

    setInterval(() => {
      navigator.geolocation.getCurrentPosition((currentPosition) => {
        const currentLocation = new google.maps.LatLng(currentPosition.coords.latitude, currentPosition.coords.longitude);
        clientLocationMarker.setPosition(currentLocation);

        if (indicatorCircle) {
          indicatorCircle.setCenter(currentLocation);
          indicatorCircle.setRadius(parseInt(currentPosition.coords.accuracy.toString()));
        } else {
          indicatorCircle = this.map.addAccuracyIndicatorCircle(currentLocation, parseInt(currentPosition.coords.accuracy.toString()));
        }
      });
    }, 5000);
  }
}
