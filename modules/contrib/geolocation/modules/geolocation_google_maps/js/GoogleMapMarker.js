import { GeolocationMapMarker } from "../../../js/Base/GeolocationMapMarker.js";
import { GeolocationCoordinates } from "../../../js/Base/GeolocationCoordinates.js";

/**
 * @prop {google.maps.Marker} googleMarker
 * @prop {GoogleMaps} map
 */
export class GoogleMapMarker extends GeolocationMapMarker {
  constructor(coordinates, settings = {}, map = null) {
    super(coordinates, settings, map);

    this.googleMarker = new google.maps.Marker({
      position: this.coordinates,
      icon: this.icon ?? this.map.settings.google_map_settings.marker_icon_path ?? null,
      map: this.map.googleMap,
      title: this.title,
      label: this.label,
    });

    this.googleMarker.addListener("click", () => {
      this.click();
    });

    if (this.settings.draggable) {
      this.googleMarker.setDraggable(true);
      this.googleMarker.addListener("dragend", (e) => {
        this.update(new GeolocationCoordinates(Number(e.latLng.lat()), Number(e.latLng.lng())));
      });
    }
  }

  update(newCoordinates, settings) {
    super.update(newCoordinates, settings);

    if (newCoordinates) {
      if (!newCoordinates.equals(this.googleMarker.getPosition().lat(), this.googleMarker.getPosition().lng())) {
        this.googleMarker.setPosition(newCoordinates);
      }
    }

    if (this.title) {
      this.googleMarker.setTitle(this.title);
    }
    if (this.label) {
      this.googleMarker.setLabel(this.label);
    }
    if (this.icon) {
      this.googleMarker.setIcon(this.icon);
    }
  }

  remove() {
    super.remove();

    this.googleMarker.setMap(null);
  }
}
