import { GoogleLayerFeature } from "./GoogleLayerFeature.js";

/**
 * @typedef {Object} OverlappingMarkerSpiderfierInterface
 *
 * @prop {function} addMarker
 * @prop {string} markerStatus.SPIDERFIED
 * @prop {string} markerStatus.UNSPIDERFIED
 * @prop {string} markerStatus.SPIDERFIABLE
 * @prop {string} markerStatus.UNSPIDERFIABLE
 */

/**
 * @typedef {Object} SpiderfyingSettings
 *
 * @extends {GeolocationMapFeatureSettings}
 *
 * @prop {String} spiderfiable_marker_path
 * @prop {String} markersWontMove
 * @prop {String} markersWontHide
 * @prop {String} keepSpiderfied
 * @prop {String} ignoreMapClick
 * @prop {String} nearbyDistance
 * @prop {String} circleSpiralSwitchover
 * @prop {String} circleFootSeparation
 * @prop {String} spiralFootSeparation
 * @prop {String} spiralLengthStart
 * @prop {String} spiralLengthFactor
 * @prop {String} legWeight
 * @prop {String} spiralIconWidth
 * @prop {String} spiralIconHeight
 */

/* global OverlappingMarkerSpiderfier */

/**
 * @prop {SpiderfyingSettings} settings
 */
export default class GoogleSpiderfying extends GoogleLayerFeature {
  constructor(settings, layer) {
    super(settings, layer);

    if (typeof OverlappingMarkerSpiderfier === "undefined") {
      throw new Error("Spiderfier not found");
    }

    this.oms = new OverlappingMarkerSpiderfier(this.layer.map.googleMap, {
      markersWontMove: this.settings.markersWontMove,
      markersWontHide: this.settings.markersWontHide,
      keepSpiderfied: this.settings.keepSpiderfied,
      ignoreMapClick: this.settings.ignoreMapClick,
      circleSpiralSwitchover: this.settings.circleSpiralSwitchover ?? undefined,
      nearbyDistance: this.settings.nearbyDistance ?? undefined,
      circleFootSeparation: this.settings.circleFootSeparation ?? undefined,
      spiralFootSeparation: this.settings.spiralFootSeparation ?? undefined,
      spiralLengthStart: this.settings.spiralLengthStart ?? undefined,
      spiralLengthFactor: this.settings.spiralLengthFactor ?? undefined,
      legWeight: this.settings.legWeight ?? undefined,
      spiralIconWidth: this.settings.spiralIconWidth ?? undefined,
      spiralIconHeight: this.settings.spiralIconHeight ?? undefined,
    });

    if (!this.oms) {
      throw new Error("Spiderfier could not initialize");
    }

    // Remove if https://github.com/jawj/OverlappingMarkerSpiderfier/issues/103
    // is ever corrected.
    this.layer.map.googleMap.addListener("idle", () => {
      Object.getPrototypeOf(this.oms).h.call(this.oms);
    });
  }

  onMarkerAdded(marker) {
    super.onMarkerAdded(marker);

    this.layer.map.googleMap.addListener("spider_format", (status) => {
      /**
       * @param {Object} marker.originalIcon
       */
      if (typeof marker.googleMarker.originalIcon === "undefined") {
        const originalIcon = marker.googleMarker.getIcon();

        if (typeof originalIcon === "undefined") {
          marker.googleMarker.orginalIcon = "";
        } else if (typeof originalIcon !== "undefined" && originalIcon !== null && typeof originalIcon.url !== "undefined" && originalIcon.url === this.settings.spiderfiable_marker_path) {
          // Do nothing.
        } else {
          marker.googleMarker.orginalIcon = originalIcon;
        }
      }

      let icon = null;
      const iconSize = new google.maps.Size(23, 32);
      switch (status) {
        case OverlappingMarkerSpiderfier.markerStatus.SPIDERFIABLE:
          icon = {
            url: this.settings.spiderfiable_marker_path,
            size: iconSize,
            scaledSize: iconSize,
          };
          break;

        case OverlappingMarkerSpiderfier.markerStatus.SPIDERFIED:
          icon = marker.googleMarker.orginalIcon;
          break;

        case OverlappingMarkerSpiderfier.markerStatus.UNSPIDERFIABLE:
          icon = marker.googleMarker.orginalIcon;
          break;

        case OverlappingMarkerSpiderfier.markerStatus.UNSPIDERFIED:
          icon = marker.googleMarker.orginalIcon;
          break;

        default:
          throw new Error("Spidierfier unknown status.");
      }
      marker.googleMarker.setIcon(icon);
    });

    Object.values(marker.googleMarker.listeners ?? {}).forEach((listener) => {
      if (listener.e === "click") {
        google.maps.event.removeListener(listener.listener);
        marker.googleMarker.addListener("spider_click", listener.f);
      }
    });

    this.oms.addMarker(marker.googleMarker);
  }
}
