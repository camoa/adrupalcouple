import { YandexLayerFeature } from "./YandexLayerFeature.js";

/**
 * @typedef {Object} YandexBalloonSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {Boolean} infoAutoDisplay
 * @prop {Boolean} disableAutoPan
 * @prop {int} maxWidth
 * @prop {String} panelMaxMapArea
 */

/**
 * @prop {YandexBalloonSettings} settings
 */
export default class YandexBalloon extends YandexLayerFeature {
  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    marker.yandexMarker.properties.set("balloonContent", marker.getContent());

    if (this.settings.disableAutoPan) {
      marker.yandexMarker.options.set("balloonAutoPan", false);
    }

    if (this.settings.maxWidth > 0) {
      marker.yandexMarker.options.set("balloonMaxWidth", this.settings.maxWidth);
    }

    if (this.settings.panelMaxMapArea !== "") {
      marker.yandexMarker.options.set("balloonPanelMaxMapArea", this.settings.panelMaxMapArea);
    }

    if (this.settings.infoAutoDisplay) {
      marker.yandexMarker.balloon.open();
    }
  }
}
