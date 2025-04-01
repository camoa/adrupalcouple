/**
 * @file
 * Javascript for the geolocation geometry Google Maps widget.
 */

/**
 * @typedef {Object} GoogleGeojsonData
 *
 * @property {Object[]} features
 */

(function (Drupal) {
  /**
   * Google maps GeoJSON widget.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Widget.
   */
  Drupal.behaviors.geolocationGeometryWidgetGoogleMaps = {
    attach: (context) => {
      context.querySelectorAll(".geolocation-geometry-widget-google-maps-geojson").forEach((item) => {
        if (item.classList.contains("processed")) {
          return;
        }
        item.classList.add("processed");

        const mapWrapper = item.querySelector(".geolocation-geometry-widget-google-maps-geojson-map");
        const inputWrapper = item.querySelector(".geolocation-geometry-widget-google-maps-geojson-input");
        const geometryType = item.getAttribute("data-geometry-type");

        Drupal.geolocation.maps.getMap(mapWrapper.getAttribute("id")).then(
          /** @param {GoogleMaps} map */ (map) => {
            let availableControls = [];
            switch (geometryType) {
              case "polygon":
              case "multipolygon":
                availableControls = ["Polygon"];
                break;

              case "polyline":
              case "multipolyline":
                availableControls = ["LineString"];
                break;

              case "point":
              case "multipoint":
                availableControls = ["Point"];
                break;

              default:
                availableControls = ["Point", "LineString", "Polygon"];
                break;
            }

            map.googleMap.data.setControls(availableControls);
            map.googleMap.data.setControlPosition(google.maps.ControlPosition.TOP_CENTER);
            map.googleMap.data.setStyle({
              editable: true,
              draggable: true,
            });

            if (inputWrapper.value) {
              try {
                const geometry = JSON.parse(inputWrapper.value);
                map.googleMap.data.addGeoJson({
                  type: "FeatureCollection",
                  features: [
                    {
                      type: "Feature",
                      id: "value",
                      geometry,
                    },
                  ],
                });
              } catch (error) {
                console.error(error.message);
                return;
              }

              const bounds = new google.maps.LatLngBounds();
              map.googleMap.data.forEach(function (feature) {
                feature.getGeometry().forEachLatLng(function (latlng) {
                  bounds.extend(latlng);
                });
              });
              map.setBoundaries(map.normalizeBoundaries(bounds));
            }

            function refreshGeoJsonFromData() {
              map.googleMap.data.toGeoJson(
                /** @param {GoogleGeojsonData} geoJson */ (geoJson) => {
                  if (typeof geoJson.features === "undefined") {
                    inputWrapper.value = "";
                  }

                  switch (geoJson.features.length) {
                    case 0:
                      inputWrapper.value = "";
                      break;

                    case 1:
                      inputWrapper.value = JSON.stringify(geoJson.features[0].geometry);
                      break;

                    default: {
                      const types = {
                        multi_polygon: "MultiPolygon",
                        multi_polyline: "MultiPolyline",
                        multi_point: "MultiPoint",
                        default: "GeometryCollection",
                      };

                      const geometry = {
                        type: types[geometryType] || types.default,
                        geometries: [],
                      };

                      geoJson.features.forEach(function (feature) {
                        geometry.geometries.push(feature.geometry);
                      });
                      inputWrapper.value = JSON.stringify(geometry);
                      break;
                    }
                  }
                }
              );
            }

            function bindDataLayerListeners(dataLayer) {
              dataLayer.addListener("addfeature", refreshGeoJsonFromData);
              dataLayer.addListener("removefeature", refreshGeoJsonFromData);
              dataLayer.addListener("setgeometry", refreshGeoJsonFromData);

              map.googleMap.data.addListener("click", function (event) {
                const newPolyPoints = [];

                event.feature.getGeometry().forEachLatLng(function (latlng) {
                  if (!(latlng.lat() === event.latLng.lat() && latlng.lng() === event.latLng.lng())) {
                    newPolyPoints.push(latlng);
                  }
                });

                if (newPolyPoints.length < 2) {
                  dataLayer.remove(event.feature);
                } else {
                  event.feature.setGeometry(new google.maps.Data.Polygon([new google.maps.Data.LinearRing(newPolyPoints)]));
                }
              });
            }

            bindDataLayerListeners(map.googleMap.data);

            inputWrapper.addEventListener("change", () => {
              const newData = new google.maps.Data({
                map: map.googleMap,
                style: map.googleMap.data.getStyle(),
                controls: availableControls,
              });
              try {
                newData.addGeoJson(JSON.parse(inputWrapper.value));
              } catch (error) {
                newData.setMap(null);
                return;
              }
              // No error means GeoJSON was valid!
              map.googleMap.data.setMap(null);
              map.googleMap.data = newData;
              bindDataLayerListeners(newData);
            });
          }
        );
      });
    },
    detach: () => {},
  };
})(Drupal);
