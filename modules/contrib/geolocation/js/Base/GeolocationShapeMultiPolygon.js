import { GeolocationCoordinates } from "./GeolocationCoordinates.js";
import { GeolocationShape } from "./GeolocationShape.js";

/**
 * @prop {Object} geometry
 * @prop {{points: GeolocationCoordinates[]}} geometry.polygons
 * @prop {String} strokeColor
 * @prop {String} strokeOpacity
 * @prop {String} strokeWidth
 * @prop {String} fillColor
 * @prop {String} fillOpacity
 */
export class GeolocationShapeMultiPolygon extends GeolocationShape {
  constructor(geometry, settings = {}, map = null, layerId = "default") {
    super(geometry, settings, map, layerId);

    this.type = "multipolygon";

    this.strokeColor = settings.strokeColor;
    this.strokeOpacity = settings.strokeOpacity;
    this.strokeWidth = settings.strokeWidth;
    this.fillColor = settings.fillColor;
    this.fillOpacity = settings.fillOpacity;
  }
}
