import { GeolocationShapePolygon } from "../../../js/Base/GeolocationShapePolygon.js";
import { GoogleShapeTrait } from "./GoogleShapeTrait.js";

/**
 * @prop {GoogleMaps} map
 */
export class GoogleShapePolygon extends GeolocationShapePolygon {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.googleShapeTrait = new GoogleShapeTrait();

    this.googleShapes = [];

    const polygon = new google.maps.Polygon({
      paths: geometry.points,
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
  }

  remove() {
    this.googleShapes.forEach((googleShape) => {
      googleShape.remove();
    });

    super.remove();
  }
}
