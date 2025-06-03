import { GeolocationShapeMultiLine } from "../../../js/Base/GeolocationShapeMultiLine.js";
import { GoogleShapeTrait } from "./GoogleShapeTrait.js";
import { GeolocationCoordinates } from "../../../js/Base/GeolocationCoordinates.js";

/**
 * @prop {GoogleMaps} map
 */
export class GoogleShapeMultiLine extends GeolocationShapeMultiLine {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.googleShapeTrait = new GoogleShapeTrait();

    this.googleShapes = [];
    this.geometry.lines.forEach((lineGeometry) => {
      const line = new google.maps.Polyline({
        path: lineGeometry.points,
        strokeColor: this.strokeColor,
        strokeOpacity: this.strokeOpacity,
        strokeWeight: this.strokeWidth,
      });

      if (this.title) {
        this.googleShapeTrait.setTitle(line, this.title, this.map);
      }

      line.addListener("click", (event) => {
        this.click(new GeolocationCoordinates(event.latLng.lat(), event.latLng.lng()));
      });

      line.setMap(this.map.googleMap);

      this.googleShapes.push(line);
    });
  }

  remove() {
    this.googleShapes.forEach((googleShape) => {
      googleShape.remove();
    });

    super.remove();
  }
}
