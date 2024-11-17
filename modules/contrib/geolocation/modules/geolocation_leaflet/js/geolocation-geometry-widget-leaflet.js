/**
 * @file
 * Javascript for the geolocation geometry Leaflet widget.
 */

(function (Drupal) {
  /**
   * Leaflet GeoJSON widget.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Function} layerToGeoJson
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Widget.
   */
  Drupal.behaviors.geolocationGeometryWidgetLeaflet = {
    /**
     * @param {String} geometryType
     */
    getDrawSettingsByTyp: (geometryType) => {
      switch (geometryType) {
        case "polygon":
        case "multipolygon":
          return {
            polyline: false,
            marker: false,
            circlemarker: false,
          };

        case "polyline":
        case "multipolyline":
          return {
            polygon: false,
            rectangle: false,
            circle: false,
            marker: false,
            circlemarker: false,
          };

        case "point":
        case "multipoint":
          return {
            polyline: false,
            polygon: false,
            rectangle: false,
            circle: false,
            circlemarker: false,
          };

        default:
          return {
            circlemarker: false,
          };
      }
    },
    /**
     * @param {GeoJSON} layer
     * @param {String} geometryType
     */
    layerToGeoJson: (layer, geometryType) => {
      const featureCollection = layer.toGeoJSON();

      switch (featureCollection.features.length) {
        case 0:
          return JSON.stringify("");

        case 1:
          return JSON.stringify(featureCollection.features[0].geometry);

        default: {
          const types = {
            multipolygon: "MultiPolygon",
            multipolyline: "MultiPolyline",
            multipoint: "MultiPoint",
            default: "GeometryCollection",
          };

          const geometryCollection = {
            type: types[geometryType] || types.default,
            geometries: [],
          };

          featureCollection.features.forEach((feature) => {
            geometryCollection.geometries.push(feature.geometry);
          });

          return JSON.stringify(geometryCollection);
        }
      }
    },
    attach: (context) => {
      context.querySelectorAll(".geolocation-geometry-widget-leaflet-geojson").forEach((item) => {
        if (item.classList.contains("processed")) {
          return;
        }
        item.classList.add("processed");

        const mapWrapper = item.querySelector(".geolocation-geometry-widget-leaflet-geojson-map");
        const inputWrapper = item.querySelector(".geolocation-geometry-widget-leaflet-geojson-input");
        const geometryType = item.getAttribute("data-geometry-type");

        Drupal.geolocation.maps.getMap(mapWrapper.getAttribute("id")).then(
          /** @param {Leaflet} map */ (map) => {
            Drupal.geolocation.addStylesheet("https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css");
            Drupal.geolocation.addScript("https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js").then(() => {
              const geoJsonLayer = L.geoJSON().addTo(map.leafletMap);
              const drawControl = new L.Control.Draw({
                draw: this.getDrawSettingsByTyp(geometryType),
                edit: {
                  featureGroup: geoJsonLayer,
                },
              });
              map.leafletMap.addControl(drawControl);

              map.leafletMap.on(
                L.Draw.Event.CREATED,
                /** @param {Created} event */ (event) => {
                  geoJsonLayer.addLayer(event.layer);
                  inputWrapper.value = this.layerToGeoJson(geoJsonLayer, geometryType);
                }
              );
              map.leafletMap.on(L.Draw.Event.EDITED, () => {
                inputWrapper.value = this.layerToGeoJson(geoJsonLayer, geometryType);
              });
              map.leafletMap.on(L.Draw.Event.DELETED, () => {
                inputWrapper.value = this.layerToGeoJson(geoJsonLayer, geometryType);
              });

              if (inputWrapper.value) {
                try {
                  geoJsonLayer.addData(JSON.parse(inputWrapper.value));
                } catch (error) {
                  console.error(error.message);
                  return;
                }

                map.setBoundaries(map.normalizeBoundaries(geoJsonLayer.getBounds()));
              }
            });
          }
        );
      });
    },
    detach: () => {},
  };
})(Drupal);
