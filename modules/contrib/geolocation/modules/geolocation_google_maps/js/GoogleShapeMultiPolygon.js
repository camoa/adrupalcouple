import { GeolocationShapeMultiPolygon } from "../../../js/Base/GeolocationShapeMultiPolygon.js";
import { GoogleShapeTrait } from "./GoogleShapeTrait.js";

/**
 * @prop {GoogleMaps} map
 */
export class GoogleShapeMultiPolygon extends GeolocationShapeMultiPolygon {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.googleShapeTrait = new GoogleShapeTrait();

    this.googleShapes = [];
    this.geometry.polygons.forEach((polygonGeometry) => {
      const polygon = new google.maps.Polygon({
        paths: polygonGeometry.points,
        strokeColor: this.strokeColor,
        strokeOpacity: this.strokeOpacity,
        strokeWeight: this.strokeWidth,
        fillColor: this.fillColor,
        fillOpacity: this.fillOpacity,
      });
      if (this.title) {
        this.googleShapeTrait.setTitle(this, this.title);
      }

      polygon.setMap(this.map.googleMap);

      this.googleShapes.push(polygon);
    });
  }

  remove() {
    this.googleShapes.forEach((googleShape) => {
      googleShape.remove();
    });

    super.remove();
  }
}
