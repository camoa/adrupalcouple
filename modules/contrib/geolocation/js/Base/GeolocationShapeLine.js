import { GeolocationCoordinates } from "./GeolocationCoordinates.js";
import { GeolocationShape } from "./GeolocationShape.js";

/**
 * @prop {Object} geometry
 * @prop {GeolocationCoordinates[]} geometry.points
 */
export class GeolocationShapeLine extends GeolocationShape {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.type = "line";
  }
}
