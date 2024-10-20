export class GoogleShapeTrait {
  /**
   * @param {GeolocationShape} shape
   * @param {GoogleMaps} shape.map
   *
   * @param {String} title
   */
  setTitle(shape, title) {
    const infoWindow = new google.maps.InfoWindow();
    google.maps.event.addListener(shape, "mouseover", (e) => {
      infoWindow.setPosition(e.latLng);
      infoWindow.setContent(title);
      infoWindow.open(shape.map.googleMap);
    });
    google.maps.event.addListener(this, "mouseout", () => {
      infoWindow.close();
    });
  }
}
