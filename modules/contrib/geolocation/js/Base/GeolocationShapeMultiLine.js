import { GeolocationCoordinates } from "./GeolocationCoordinates.js";
import { GeolocationShape } from "./GeolocationShape.js";

/**
 * @prop {Object} geometry
 * @prop {{points: GeolocationCoordinates[]}} geometry.lines
 * @prop {String} strokeColor
 * @prop {String} strokeOpacity
 * @prop {String} strokeWidth
 * @prop {String} fillColor
 * @prop {String} fillOpacity
 */
export class GeolocationShapeMultiLine extends GeolocationShape {
  constructor(geometry, settings = {}, map = null, layerId = "default") {
    super(geometry, settings, map, layerId);

    this.type = "multipolygon";

    this.strokeColor = settings.strokeColor;
    this.strokeOpacity = settings.strokeOpacity;
    this.strokeWidth = settings.strokeWidth;
  }
}
