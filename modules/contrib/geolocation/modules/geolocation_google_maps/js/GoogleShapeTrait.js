export class GoogleShapeTrait {
  /**
   * @param {google.maps.MVCObject} shape
   * @param {String} title
   * @param {GoogleMaps} map
   */
  setTitle(shape, title, map) {
    const infoWindow = new google.maps.InfoWindow({
      disableAutoPan: true,
      headerDisabled: true,
    });
    google.maps.event.addListener(shape, "mouseover", (e) => {
      infoWindow.setPosition(e.latLng);
      infoWindow.setContent(title);
      infoWindow.open({
        map: map.googleMap,
      });
    });
    google.maps.event.addListener(shape, "mouseout", () => {
      infoWindow.close();
    });
  }
}
