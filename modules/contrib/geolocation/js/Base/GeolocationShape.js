/**
 * @typedef {Object} GeolocationShapeSettings
 *
 * @prop {String} [id]
 * @prop {String} [title]
 * @prop {Element} [wrapper]
 * @prop {String} [content]
 * @prop {String} [strokeColor]
 * @prop {String} [strokeOpacity]
 * @prop {String} [strokeWidth]
 * @prop {String} [fillColor]
 * @prop {String} [fillOpacity]
 */

/**
 * @typedef {Object} GeolocationGeometry
 *
 * @prop {GeolocationCoordinates[]} [points]
 * @prop {Array.<GeolocationCoordinates[]>} [lines]
 * @prop {Array.<GeolocationCoordinates[]>} [polygons]
 */

import { GeolocationCoordinates } from "./GeolocationCoordinates.js";

/**
 * @prop {String} [id]
 * @prop {String} title
 * @prop {Element} [wrapper]
 * @prop {GeolocationMapBase} map
 * @prop {String} content
 * @prop {GeolocationShapeSettings} settings
 */
export class GeolocationShape {
  /**
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   * @param {GeolocationShapeSettings} settings
   *   Settings.
   * @param {GeolocationMapBase} map
   *   Map.
   * @param {String} layerId
   *   Layer ID.
   */
  constructor(geometry, settings = {}, map = null, layerId = "default") {
    this.geometry = geometry;
    this.settings = settings;
    this.id = settings.id?.toString() ?? null;
    this.title = settings.title ?? undefined;
    this.wrapper = settings.wrapper ?? document.createElement("div");
    this.map = map;
    this.layerId = layerId;
    this.content = settings.content ?? this.getContent();
  }

  /**
   * @param {Element} metaWrapper
   *   Element.
   * @return {GeolocationCoordinates[]}
   *   Points.
   */
  static getPointsByGeoShapeMeta(metaWrapper) {
    const points = [];

    if (!metaWrapper) {
      return points;
    }

    metaWrapper
      .getAttribute("content")
      ?.split(" ")
      .forEach((value) => {
        const coordinates = value.split(",");
        if (coordinates.length !== 2) {
          return;
        }

        const lat = parseFloat(coordinates[0]);
        const lon = parseFloat(coordinates[1]);

        points.push(new GeolocationCoordinates(lat, lon));
      });

    return points;
  }

  getContent() {
    if (!this.content) {
      this.content = this.wrapper?.querySelector(".location-content")?.innerHTML ?? "";
    }

    return this.content;
  }

  /**
   * @param {Object} [geometry]
   *   Geometry.
   * @param {GeolocationShapeSettings} [settings]
   *   Settings.
   */
  update(geometry, settings) {
    if (geometry) {
      this.geometry = geometry;
    }

    if (settings) {
      this.settings = {
        ...this.settings,
        ...settings,
      };

      if (settings.id) {
        this.id = settings.id.toString();
      }
      if (settings.title) {
        this.title = settings.title.toString();
      }
      if (settings.wrapper) {
        this.wrapper = settings.wrapper;
      }
      if (settings.content) {
        this.content = settings.content;
      }
    }

    this.map.dataLayers.get(this.layerId).updateShape(this);
  }

  remove() {
    this.map.dataLayers.get(this.layerId).removeShape(this);
  }
}
