import { GeolocationShapeLine } from "../../../js/Base/GeolocationShapeLine.js";
import { GoogleShapeTrait } from "./GoogleShapeTrait.js";

/**
 * @prop {GoogleMaps} map
 */
export class GoogleShapeLine extends GeolocationShapeLine {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.googleShapeTrait = new GoogleShapeTrait();

    this.googleShapes = [];

    const line = new google.maps.Polyline({
      path: geometry.points,
      strokeColor: this.strokeColor,
      strokeOpacity: this.strokeOpacity,
      strokeWeight: this.strokeWidth,
    });

    if (this.title) {
      this.googleShapeTrait.setTitle(this, this.title);
    }

    line.setMap(this.map.googleMap);

    this.googleShapes.push(line);
  }

  remove() {
    this.googleShapes.forEach((googleShape) => {
      googleShape.remove();
    });

    super.remove();
  }
}
