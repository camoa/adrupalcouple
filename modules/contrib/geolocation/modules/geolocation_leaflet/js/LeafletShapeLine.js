import { GeolocationShapeLine } from "../../../js/Base/GeolocationShapeLine.js";

/**
 * @prop {Leaflet} map
 */
export class LeafletShapeLine extends GeolocationShapeLine {
  constructor(geometry, settings = {}, map) {
    super(geometry, settings, map);

    this.leafletShapes = [];

    const line = L.polyline(geometry.points, {
      color: settings.strokeColor,
      opacity: this.strokeOpacity,
      weight: this.strokeWidth,
    });
    if (this.title) {
      line.bindTooltip(this.title);
    }

    line.addTo(this.map.leafletMap);

    this.leafletShapes.push(line);
  }

  remove() {
    this.leafletShapes.forEach((leafletShape) => {
      leafletShape.remove();
    });

    super.remove();
  }
}
