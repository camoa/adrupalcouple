import { GeolocationCoordinates } from "./GeolocationCoordinates.js";
import { GeolocationShape } from "./GeolocationShape.js";

/**
 * @prop {Object} geometry
 * @prop {{points: GeolocationCoordinates[]}} geometry.polygons
 */
export class GeolocationShapeMultiPolygon extends GeolocationShape {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.type = "multipolygon";
  }
}
