import GeolocationDataLayer from "./GeolocationDataLayer.js";

export default class DefaultLayer extends GeolocationDataLayer {
  async loadMarkers(selector) {
    selector = ".geolocation-location:not(.geolocation-map-layer .geolocation-location)";
    return super.loadMarkers(selector);
  }

  async loadShapes(selector) {
    selector = ".geolocation-geometry:not(.geolocation-map-layer .geolocation-geometry)";
    return super.loadShapes(selector);
  }

  addMarker(marker) {
    super.addMarker(marker);

    this.map.features.forEach((feature) => {
      try {
        feature.onMarkerAdded(marker);
      } catch (e) {
        console.error(`Feature  ${feature.constructor.name} failed onMarkerAdded: ${e.toString()}`);
      }
    });

    return marker;
  }

  updateMarker(marker) {
    super.updateMarker(marker);

    this.map.features.forEach((feature) => {
      try {
        feature.onMarkerUpdated(marker);
      } catch (e) {
        console.error(`Feature  ${feature.constructor.name} failed onMarkerUpdated: ${e.toString()}`);
      }
    });
  }

  removeMarker(marker) {
    super.removeMarker(marker);

    this.map.features.forEach((feature) => {
      try {
        feature.onMarkerRemove(marker);
      } catch (e) {
        console.error(`Feature  ${feature.constructor.name} failed onMarkerRemove: ${e.toString()}`);
      }
    });
  }

  clickMarker(marker) {
    super.clickMarker(marker);

    this.map.features.forEach((feature) => {
      try {
        feature.onMarkerClicked(marker);
      } catch (e) {
        console.error(`Feature  ${feature.constructor.name} failed onMarkerClicked: ${e.toString()}`);
      }
    });
  }
}
