import { GeolocationShapeMultiPolygon } from "../../../js/Base/GeolocationShapeMultiPolygon.js";

/**
 * @prop {Leaflet} map
 */
export class LeafletShapeMultiPolygon extends GeolocationShapeMultiPolygon {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.leafletShapes = [];
    this.geometry.polygons.forEach((polygonGeometry) => {
      const polygon = L.polygon(polygonGeometry.points, {
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
    });
  }

  remove() {
    this.leafletShapes.forEach((leafletShape) => {
      leafletShape.remove();
    });

    super.remove();
  }
}
