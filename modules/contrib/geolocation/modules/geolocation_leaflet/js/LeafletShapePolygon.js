import { GeolocationShapePolygon } from "../../../js/Base/GeolocationShapePolygon.js";

/**
 * @prop {Leaflet} map
 */
export class LeafletShapePolygon extends GeolocationShapePolygon {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.leafletShapes = [];
    const polygon = L.polyline(geometry.points, {
      color: this.strokeColor,
      opacity: this.strokeOpacity,
      weight: this.strokeWidth,
      fillColor: this.fillColor,
      fillOpacity: this.fillOpacity,
      fill: this.fillOpacity > 0,
    });
    if (this.title) {
      polygon.bindTooltip(this.title);
    }

    polygon.addTo(this.map.leafletMap);

    this.leafletShapes.push(polygon);
  }

  remove() {
    this.leafletShapes.forEach((leafletShape) => {
      leafletShape.remove();
    });

    super.remove();
  }
}
