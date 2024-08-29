import { GoogleLayerFeature } from "./GoogleLayerFeature.js";

/**
 * @typedef {Object} MarkerIconSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} markerIconPath
 * @prop {String} marker_icon_path
 * @prop {Array} anchor
 * @prop {Number} anchor.x
 * @prop {Number} anchor.y
 * @prop {Array} label_origin
 * @prop {Number} label_origin.x
 * @prop {Number} label_origin.y
 * @prop {Array} origin
 * @prop {Number} origin.x
 * @prop {Number} origin.y
 * @prop {Array} size
 * @prop {Number} size.width
 * @prop {Number} size.height
 * @prop {Array} scaled_size
 * @prop {Number} scaled_size.width
 * @prop {Number} scaled_size.height
 */

/**
 * @prop {MarkerIconSettings} settings
 */
export default class GoogleMarkerIcon extends GoogleLayerFeature {
  onMarkerAdded(marker) {
    const newIcon = {};

    const currentIcon = marker.googleMarker.getIcon();
    if (typeof currentIcon === "undefined") {
      if (typeof this.settings.markerIconPath === "string") {
        newIcon.url = this.settings.markerIconPath;
      } else {
        return;
      }
    } else if (typeof currentIcon === "string") {
      newIcon.url = currentIcon;
    } else if (typeof currentIcon.url === "string") {
      newIcon.url = currentIcon.url;
    }

    const anchorX = marker.wrapper.getAttribute("data-marker-icon-anchor-x") || this.settings.anchor.x;
    const anchorY = marker.wrapper.getAttribute("data-marker-icon-anchor-y") || this.settings.anchor.y;
    const labelOriginX = marker.wrapper.getAttribute("data-marker-icon-label-origin-x") || this.settings.label_origin.x;
    const labelOriginY = marker.wrapper.getAttribute("data-marker-icon-label-origin-y") || this.settings.label_origin.y;
    const originX = marker.wrapper.getAttribute("data-marker-icon-origin-x") || this.settings.origin.x;
    const originY = marker.wrapper.getAttribute("data-marker-icon-origin-y") || this.settings.origin.y;
    const sizeWidth = marker.wrapper.getAttribute("data-marker-icon-size-width") || this.settings.size.width;
    const sizeHeight = marker.wrapper.getAttribute("data-marker-icon-size-height") || this.settings.size.height;
    const scaledSizeWidth = marker.wrapper.getAttribute("data-marker-icon-scaled-size-width") || this.settings.scaled_size.width;
    const scaledSizeHeight = marker.wrapper.getAttribute("data-marker-icon-scaled-size-height") || this.settings.scaled_size.height;

    if (anchorX !== null && anchorY !== null) {
      newIcon.anchor = new google.maps.Point(anchorX, anchorY);
    }

    if (labelOriginX !== null && labelOriginY !== null) {
      newIcon.labelOrigin = new google.maps.Point(labelOriginX, labelOriginY);
    }

    if (originX !== null && originY !== null) {
      newIcon.origin = new google.maps.Point(originX, originY);
    }

    if (sizeWidth !== null && sizeHeight !== null) {
      newIcon.size = new google.maps.Size(sizeWidth, sizeHeight);
    }

    if (scaledSizeWidth !== null && scaledSizeHeight !== null) {
      newIcon.scaledSize = new google.maps.Size(scaledSizeWidth, scaledSizeHeight);
    }

    marker.googleMarker.setIcon(newIcon);
  }
}
