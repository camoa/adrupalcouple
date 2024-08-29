import { LeafletMapFeature } from "./LeafletMapFeature.js";

export default class LeafletControlRecenter extends LeafletMapFeature {
  constructor(settings, map) {
    super(settings, map);
    map.leafletMap.controls.forEach((control) => {
      const currentControlContainer = control.getContainer();
      if (!currentControlContainer.classList.contains("leaflet_control_recenter")) {
        return;
      }
      map.leafletMap.removeControl(control);
      map.leafletMap.addControl(control);
    });

    const recenterButton = map.wrapper.querySelector(".geolocation-map-control .recenter");
    recenterButton.on("click", (e) => {
      map.setCenter();
      e.preventDefault();
    });
  }
}
