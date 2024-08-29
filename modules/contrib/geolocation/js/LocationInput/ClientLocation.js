import { GeolocationLocationInputBase } from "./GeolocationLocationInputBase.js";
import { GeolocationCoordinates } from "../Base/GeolocationCoordinates.js";

export default class ClientLocation extends GeolocationLocationInputBase {
  constructor(form, settings = {}) {
    super(form, settings);

    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition((position) => {
        this.setCoordinates(new GeolocationCoordinates(position.coords.latitude, position.coords.longitude));
      });
    }
  }
}
