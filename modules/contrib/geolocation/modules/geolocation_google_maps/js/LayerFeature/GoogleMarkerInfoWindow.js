import { GoogleLayerFeature } from "./GoogleLayerFeature.js";

/**
 * @typedef {Object} MarkerInfoWindowSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {Boolean} info_auto_display
 * @prop {Boolean} disable_auto_pan
 * @prop {Boolean} info_window_solitary
 * @prop {int} max_width
 */

/**
 * @typedef {Object} GoogleInfoWindow
 * @prop {Function} open
 * @prop {Function} close
 */

/**
 * @prop {MarkerInfoWindowSettings} settings
 * @prop {GoogleInfoWindow} GeolocationGoogleMap.infoWindow
 * @prop {function({}):GoogleInfoWindow} GeolocationGoogleMap.InfoWindow
 */
export default class GoogleMarkerInfoWindow extends GoogleLayerFeature {
  onMarkerClicked(marker) {
    super.onMarkerClicked(marker);

    if (this.settings.info_window_solitary) {
      this.layer.map.dataLayers.get("default").markers.forEach((currentMarker) => {
        if (currentMarker.infoWindow) {
          currentMarker.infoWindow.close();
        }
      });
    }

    if (marker.infoWindow) {
      marker.infoWindow.open({
        anchor: marker.googleMarker,
        map: this.layer.map.googleMap,
        shouldFocus: true,
      });
    }
  }

  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    // Set the info popup text.
    marker.infoWindow = new google.maps.InfoWindow({
      content: marker.getContent(),
      disableAutoPan: this.settings.disable_auto_pan,
      maxWidth: this.settings.max_width ?? undefined,
    });

    if (this.settings.info_auto_display) {
      this.layer.map.dataLayers.get("default").markers.forEach((currentMarker) => {
        if (currentMarker.infoWindow) {
          currentMarker.infoWindow.open({
            anchor: currentMarker.googleMarker,
            map: this.layer.map.googleMap,
            shouldFocus: false,
          });
        }
      });
    }
  }
}
