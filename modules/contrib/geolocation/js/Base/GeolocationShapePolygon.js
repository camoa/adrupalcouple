import { GeolocationCoordinates } from "./GeolocationCoordinates.js";
import { GeolocationShape } from "./GeolocationShape.js";

/**
 * @prop {Object} geometry
 * @prop {GeolocationCoordinates[]} geometry.points
 * @prop {String} strokeColor
 * @prop {String} strokeOpacity
 * @prop {String} strokeWidth
 * @prop {String} fillColor
 * @prop {String} fillOpacity
 */
export class GeolocationShapePolygon extends GeolocationShape {
  constructor(geometry, settings = {}, map = null, layerId = "default") {
    super(geometry, settings, map, layerId);

    this.type = "polygon";

    this.strokeColor = settings.strokeColor;
    this.strokeOpacity = settings.strokeOpacity;
    this.strokeWidth = settings.strokeWidth;
    this.fillColor = settings.fillColor;
    this.fillOpacity = settings.fillOpacity;
  }

  getContent() {
    if (!this.content) {
      this.content = this.wrapper?.querySelector(".location-content")?.innerHTML ?? "";
    }

    return this.content;
  }

  update(geometry, settings) {
    super.update(geometry, settings);
    if (geometry) {
      this.geometry = geometry;
    }

    if (settings) {
      this.settings = {
        ...this.settings,
        ...settings,
      };

      if (settings.fillColor) {
        this.fillColor = settings.fillColor.toString();
      }
      if (settings.fillOpacity) {
        this.fillOpacity = settings.fillOpacity.toString();
      }
      if (settings.strokeColor) {
        this.strokeColor = settings.strokeColor.toString();
      }
      if (settings.strokeOpacity) {
        this.strokeOpacity = settings.strokeOpacity.toString();
      }
      if (settings.strokeWidth) {
        this.strokeWidth = settings.strokeWidth.toString();
      }
    }
  }
}
