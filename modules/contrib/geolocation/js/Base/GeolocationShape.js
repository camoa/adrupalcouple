/**
 * @typedef {Object} GeolocationShapeSettings
 *
 * @prop {String} [id]
 * @prop {String} [title]
 * @prop {Element} [wrapper]
 * @prop {String} [content]
 * @prop {String} [strokeColor]
 * @prop {Number} [strokeOpacity]
 * @prop {Number} [strokeWidth]
 * @prop {String} [fillColor]
 * @prop {Number} [fillOpacity]
 */

/**
 * @typedef {Object} GeolocationGeometry
 *
 * @prop {GeolocationCoordinates[]} [points]
 * @prop {Array.<GeolocationCoordinates[]>} [lines]
 * @prop {Array.<GeolocationCoordinates[]>} [polygons]
 */

import { GeolocationCoordinates } from "./GeolocationCoordinates.js";
import { GeolocationBoundaries } from "./GeolocationBoundaries.js";

/**
 * @prop {String} [id]
 * @prop {String} title
 * @prop {Element} [wrapper]
 * @prop {GeolocationMapBase} map
 * @prop {String} content
 * @prop {GeolocationShapeSettings} settings
 * @prop {String} strokeColor
 * @prop {float} strokeOpacity
 * @prop {int} strokeWidth
 * @prop {String} [fillColor]
 * @prop {float} [fillOpacity]
 */
export class GeolocationShape {
  /**
   * @param {GeolocationGeometry} geometry
   *   Geometry.
   * @param {GeolocationShapeSettings} settings
   *   Settings.
   * @param {GeolocationMapBase} map
   *   Map.
   */
  constructor(geometry, settings = {}, map) {
    this.geometry = geometry;
    this.settings = settings;
    this.type = "";
    this.id = settings.id?.toString() ?? null;
    this.title = settings.title ?? undefined;
    this.wrapper = settings.wrapper ?? document.createElement("div");
    this.map = map;
    this.content = settings.content ?? this.getContent();

    if (typeof settings.strokeColor !== "undefined") {
      this.strokeColor = settings.strokeColor;
    }
    if (typeof settings.strokeOpacity !== "undefined") {
      this.strokeOpacity = settings.strokeOpacity;
    }
    if (typeof settings.strokeWidth !== "undefined") {
      this.strokeWidth = settings.strokeWidth;
    }
    if (typeof settings.fillColor !== "undefined") {
      this.fillColor = settings.fillColor;
    }
    if (typeof settings.fillOpacity !== "undefined") {
      this.fillOpacity = settings.fillOpacity;
    }
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

      if (typeof settings.strokeColor !== "undefined") {
        this.strokeColor = settings.strokeColor;
      }
      if (typeof settings.strokeOpacity !== "undefined") {
        this.strokeOpacity = settings.strokeOpacity;
      }
      if (typeof settings.strokeWidth !== "undefined") {
        this.strokeWidth = settings.strokeWidth;
      }
      if (typeof settings.fillColor !== "undefined") {
        this.fillColor = settings.fillColor;
      }
      if (typeof settings.fillOpacity !== "undefined") {
        this.fillOpacity = settings.fillOpacity;
      }
    }
  }

  getBounds() {
    const bounds = {
      north: null,
      south: null,
      east: null,
      west: null,
    };
    switch (this.type) {
      case "line":
      case "polygon":
        this.geometry.points.forEach((value) => {
          bounds.north = bounds.north === null || value.lat > bounds.north ? value.lat : bounds.north;
          bounds.south = bounds.south === null || value.lat < bounds.south ? value.lat : bounds.south;
          bounds.east = bounds.east === null || value.lat > bounds.east ? value.lat : bounds.east;
          bounds.west = bounds.west === null || value.lat < bounds.west ? value.lat : bounds.west;
        });
        break;
      case "multiline":
        this.geometry.lines.forEach((line) => {
          line.points.forEach((value) => {
            bounds.north = bounds.north === null || value.lat > bounds.north ? value.lat : bounds.north;
            bounds.south = bounds.south === null || value.lat < bounds.south ? value.lat : bounds.south;
            bounds.east = bounds.east === null || value.lat > bounds.east ? value.lat : bounds.east;
            bounds.west = bounds.west === null || value.lat < bounds.west ? value.lat : bounds.west;
          });
        });
        break;
      case "multipolygon":
        this.geometry.polygons.forEach((polygon) => {
          polygon.points.forEach((value) => {
            bounds.north = bounds.north === null || value.lat > bounds.north ? value.lat : bounds.north;
            bounds.south = bounds.south === null || value.lat < bounds.south ? value.lat : bounds.south;
            bounds.east = bounds.east === null || value.lat > bounds.east ? value.lat : bounds.east;
            bounds.west = bounds.west === null || value.lat < bounds.west ? value.lat : bounds.west;
          });
        });
        break;
    }

    if (bounds.east === null || bounds.west === null || bounds.north === null || bounds.south === null) {
      return null;
    }

    bounds.north = bounds.north < 90 ? bounds.north : 90;
    bounds.south = bounds.south > -90 ? bounds.south : -90;
    bounds.east = bounds.east < 180 ? bounds.east : 180;
    bounds.west = bounds.west > -180 ? bounds.west : -180;

    return new GeolocationBoundaries(bounds);
  }

  remove() {}

  click() {
    this.map.dataLayers.forEach((layer) => {
      layer.shapeClicked(this);
    });
  }
}
