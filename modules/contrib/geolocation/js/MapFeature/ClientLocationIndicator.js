import { GeolocationMapFeature } from "./GeolocationMapFeature.js";
import { GeolocationCoordinates } from "../Base/GeolocationCoordinates.js";

/**
 * @prop {String} settings.icon_path
 */
export default class ClientLocationIndicator extends GeolocationMapFeature {
  constructor(settings, map) {
    super(settings, map);

    if (!navigator.geolocation) {
      return;
    }

    const clientLocationMarker = this.map.createMarker(new GeolocationCoordinates(0, 0), {
      id: "current-location",
      title: Drupal.t("Current location"),
      icon: drupalSettings.path.baseUrl + settings.icon_path,
    });

    /** @type {GeolocationCircle} */
    let indicatorCircle;
    /** @type {GeolocationCoordinates} */
    let currentCoordinates;

    setInterval(() => {
      navigator.geolocation.getCurrentPosition((currentPosition) => {
        currentCoordinates = new GeolocationCoordinates(currentPosition.coords.latitude, currentPosition.coords.longitude);

        clientLocationMarker.update(currentCoordinates);

        if (indicatorCircle) {
          indicatorCircle.update(currentCoordinates, parseInt(currentPosition.coords.accuracy.toString()));
        } else {
          indicatorCircle = this.map.createCircle(currentCoordinates, parseInt(currentPosition.coords.accuracy.toString()));
        }
      });
    }, 5000);
  }
}
