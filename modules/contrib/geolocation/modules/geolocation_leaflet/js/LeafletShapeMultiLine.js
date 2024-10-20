import { GeolocationShapeMultiLine } from "../../../js/Base/GeolocationShapeMultiLine.js";

/**
 * @prop {Leaflet} map
 */
export class LeafletShapeMultiLine extends GeolocationShapeMultiLine {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.leafletShapes = [];
    this.geometry.lines.forEach((lineGeometry) => {
      const line = L.polyline(lineGeometry.points, {
        color: this.strokeColor,
        opacity: this.strokeOpacity,
        weight: this.strokeWidth,
      });
      if (this.title) {
        line.bindTooltip(this.title);
      }
      line.addTo(this.map.leafletMap);

      this.leafletShapes.push(line);
    });
  }

  remove() {
    this.leafletShapes.forEach((leafletShape) => {
      leafletShape.remove();
    });

    super.remove();
  }
}
