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
      if (marker.infoWindowOpened) {
        marker.infoWindow.close();
        marker.infoWindowOpened = false;
      } else {
        marker.infoWindow.open({
          anchor: marker.googleMarker,
          map: this.layer.map.googleMap,
          shouldFocus: true,
        });
        marker.infoWindowOpened = true;
      }
    }
  }

  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    marker.infoWindowOpened = false;

    // Set the info popup text.
    marker.infoWindow = new google.maps.InfoWindow({
      content: marker.getContent(),
      disableAutoPan: this.settings.disable_auto_pan,
      maxWidth: this.settings.max_width ?? undefined,
    });

    if (marker.title) {
      marker.infoWindow.setHeaderContent(marker.title);
    }

    if (this.settings.info_auto_display) {
      marker.infoWindow.open({
        anchor: marker.googleMarker,
        map: this.layer.map.googleMap,
        shouldFocus: false,
      });
      marker.infoWindowOpened = true;
    }
  }
}
