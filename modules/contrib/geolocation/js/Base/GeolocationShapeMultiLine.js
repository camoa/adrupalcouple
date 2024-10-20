import { GeolocationCoordinates } from "./GeolocationCoordinates.js";
import { GeolocationShape } from "./GeolocationShape.js";

/**
 * @prop {Object} geometry
 * @prop {{points: GeolocationCoordinates[]}} geometry.lines
 */
export class GeolocationShapeMultiLine extends GeolocationShape {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.type = "multiline";
  }
}
