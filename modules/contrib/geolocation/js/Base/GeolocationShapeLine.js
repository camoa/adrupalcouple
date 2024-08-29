import { GeolocationCoordinates } from "./GeolocationCoordinates.js";
import { GeolocationShape } from "./GeolocationShape.js";

/**
 * @prop {Object} geometry
 * @prop {GeolocationCoordinates[]} geometry.points
 * @prop {String} strokeColor
 * @prop {String} strokeOpacity
 * @prop {String} strokeWidth
 */
export class GeolocationShapeLine extends GeolocationShape {
  constructor(geometry, settings = {}, map = null, layerId = "default") {
    super(geometry, settings, map, layerId);

    this.type = "line";

    this.strokeColor = settings.strokeColor;
    this.strokeOpacity = settings.strokeOpacity;
    this.strokeWidth = settings.strokeWidth;
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
