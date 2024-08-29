import { GeolocationMapMarker } from "../../../js/Base/GeolocationMapMarker.js";
import { GeolocationCoordinates } from "../../../js/Base/GeolocationCoordinates.js";

/* global Microsoft */

/**
 * @prop {Microsoft.Maps.Pushpin} bingMarker
 * @prop {Bing} map
 */
export class BingMapMarker extends GeolocationMapMarker {
  constructor(coordinates, settings = {}, map = null) {
    super(coordinates, settings, map);

    /** @type {Microsoft.Maps.IPushpinOptions} */
    const bingMarkerSettings = {
      title: this.settings.title,
    };

    if (this.settings.icon) {
      bingMarkerSettings.icon = this.settings.icon;
    }

    if (this.settings.label) {
      bingMarkerSettings.text = this.settings.label;
    }

    this.bingMarker = new Microsoft.Maps.Pushpin(new Microsoft.Maps.Location(coordinates.lat, coordinates.lng), bingMarkerSettings);

    Microsoft.Maps.Events.addHandler(this.bingMarker, "click", () => {
      this.click();
    });

    if (this.settings.draggable) {
      this.bingMarker.setOptions({ draggable: true });
      Microsoft.Maps.Events.addHandler(this.bingMarker, "dragend", (e) => {
        this.update(new GeolocationCoordinates(Number(e.location.longitude), Number(e.location.latitude)));
      });
    }
  }

  update(newCoordinates, settings) {
    super.update(newCoordinates, settings);

    if (newCoordinates) {
      if (!newCoordinates.equals(this.bingMarker.getLocation().latitude, this.bingMarker.getLocation().longitude)) {
        this.bingMarker.setLocation(new Microsoft.Maps.Location(newCoordinates.lat, newCoordinates.lng));
      }
    }

    const options = {};

    if (this.settings.title) {
      options.title = this.settings.title;
    }
    if (this.settings.label) {
      options.text = this.settings.label;
    }
    if (this.settings.icon) {
      options.icon = this.settings.icon;
    }

    this.bingMarker.setOptions(options);
  }

  remove() {
    super.remove();

    this.map.bingMap.entities.remove(this.bingMarker);
  }
}
