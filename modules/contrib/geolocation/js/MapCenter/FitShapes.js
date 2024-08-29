import { GeolocationMapCenterBase } from "./GeolocationMapCenterBase.js";

/**
 * @prop {boolean} settings.reset_zoom
 */
export default class FitShapes extends GeolocationMapCenterBase {
  setCenter() {
    super.setCenter();

    if (this.map.dataLayers.get("default").shapes.length === 0) {
      return false;
    }

    this.map.fitMapToShapes();

    if (this.settings.min_zoom) {
      this.map.getZoom().then((zoom) => {
        if (this.settings.min_zoom < zoom) {
          this.map.setZoom(this.settings.min_zoom);
        }
      });
    }

    return true;
  }
}
