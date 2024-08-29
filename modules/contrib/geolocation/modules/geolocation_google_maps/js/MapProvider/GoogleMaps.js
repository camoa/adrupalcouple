import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";
import { GeolocationMapBase } from "../../../../js/MapProvider/GeolocationMapBase.js";
import { GeolocationBoundaries } from "../../../../js/Base/GeolocationBoundaries.js";
import { GoogleMapMarker } from "../GoogleMapMarker.js";

/**
 * @typedef GoogleMapSettings
 *
 * @extends GeolocationMapSettings
 *
 * @prop {String} google_url
 * @prop {MapOptions} google_map_settings
 */

/**
 * @prop {GoogleMapSettings} settings
 * @prop {google.maps.Map} googleMap
 */
export default class GoogleMaps extends GeolocationMapBase {
  constructor(mapSettings) {
    super(mapSettings);

    // Set the container size.
    this.container.style.height = this.settings.google_map_settings.height;
    this.container.style.width = this.settings.google_map_settings.width;
  }

  initialize() {
    return super
      .initialize()
      .then(() => {
        return new Promise((resolve) => {
          Drupal.geolocation.maps.addMapProviderCallback("Google", resolve);
        });
      })
      .then(() => {
        return new Promise((resolve) => {
          this.googleMap = new google.maps.Map(
            this.container,
            Object.assign(this.settings.google_map_settings, {
              zoom: this.settings.google_map_settings.zoom ?? 2,
              maxZoom: this.settings.google_map_settings.maxZoom ?? 20,
              minZoom: this.settings.google_map_settings.minZoom ?? 0,
              center: new google.maps.LatLng(this.settings.lat, this.settings.lng),
              mapTypeId: google.maps.MapTypeId[this.settings.google_map_settings.type] ?? "roadmap",
              mapTypeControl: false, // Handled by feature.
              zoomControl: false, // Handled by feature.
              streetViewControl: false, // Handled by feature.
              rotateControl: false, // Handled by feature.
              fullscreenControl: false, // Handled by feature.
              scaleControl: this.settings.google_map_settings.scaleControl ?? false,
              panControl: this.settings.google_map_settings.panControl ?? false,
              gestureHandling: this.settings.google_map_settings.gestureHandling ?? "auto",
            })
          );

          resolve();
        })
          .then(() => {
            return new Promise((resolve) => {
              google.maps.event.addListenerOnce(this.googleMap, "idle", () => {
                resolve();
              });
            });
          })
          .then(() => {
            return new Promise((resolve) => {
              let singleClick;

              this.googleMap.addListener("click", (event) => {
                singleClick = setTimeout(() => {
                  this.features.forEach((feature) => {
                    feature.onClick(new GeolocationCoordinates(event.latLng.lat(), event.latLng.lng()));
                  });
                }, 500);
              });

              this.googleMap.addListener("dblclick", (event) => {
                clearTimeout(singleClick);
                this.features.forEach((feature) => {
                  feature.onDoubleClick(new GeolocationCoordinates(event.latLng.lat(), event.latLng.lng()));
                });
              });

              this.googleMap.addListener("contextmenu", (event) => {
                this.features.forEach((feature) => {
                  feature.onContextClick(new GeolocationCoordinates(event.latLng.lat(), event.latLng.lng()));
                });
              });

              this.googleMap.addListener("idle", () => {
                this.updatingBounds = false;

                this.features.forEach((feature) => {
                  feature.onMapIdle();
                });
              });

              this.googleMap.addListener("bounds_changed", () => {
                const bounds = this.googleMap.getBounds();
                if (!bounds) {
                  return;
                }

                this.features.forEach((feature) => {
                  feature.onBoundsChanged(this.normalizeBoundaries(bounds));
                });
              });

              resolve(this);
            });
          });
      });
  }

  createMarker(coordinates, settings) {
    return new GoogleMapMarker(coordinates, settings, this);
  }

  getBoundaries() {
    super.getBoundaries();

    return this.normalizeBoundaries(this.googleMap.getBounds());
  }

  getMarkerBoundaries(markers) {
    super.getMarkerBoundaries(markers);

    markers = markers || this.dataLayers.get("default").markers;
    if (!markers) {
      return false;
    }

    // A Google Maps API tool to re-center the map on its content.
    const bounds = new google.maps.LatLngBounds();

    markers.forEach((marker) => {
      bounds.extend(marker.googleMarker.getPosition());
    });

    return this.normalizeBoundaries(bounds);
  }

  setBoundaries(boundaries) {
    if (super.setBoundaries(boundaries) === false) {
      return false;
    }

    return this.googleMap.fitBounds(this.denormalizeBoundaries(boundaries) ?? null, 0);
  }

  getZoom() {
    return new Promise((resolve) => {
      google.maps.event.addListenerOnce(this.googleMap, "idle", () => {
        resolve(this.googleMap.getZoom());
      });
    });
  }

  setZoom(zoom, defer) {
    if (!zoom) {
      zoom = this.settings.google_map_settings.zoom;
    }
    zoom = parseInt(zoom);

    this.googleMap.setZoom(zoom);

    if (defer) {
      google.maps.event.addListenerOnce(this.googleMap, "idle", () => {
        this.googleMap.setZoom(zoom);
      });
    }
  }

  getCenter() {
    const center = this.googleMap.getCenter();

    return new GeolocationCoordinates(center.lat(), center.lng());
  }

  setCenterByCoordinates(coordinates, accuracy) {
    super.setCenterByCoordinates(coordinates, accuracy);

    if (typeof accuracy === "undefined") {
      this.googleMap.setCenter(coordinates);
      return;
    }

    const circle = this.addAccuracyIndicatorCircle(coordinates, accuracy);

    // Set the zoom level to the accuracy circle's size.
    this.googleMap.fitBounds(circle.getBounds());

    // Fade circle away.
    setInterval(() => {
      let fillOpacity = circle.get("fillOpacity");
      fillOpacity -= 0.01;

      let strokeOpacity = circle.get("strokeOpacity");
      strokeOpacity -= 0.02;

      if (strokeOpacity > 0 && fillOpacity > 0) {
        circle.setOptions({
          fillOpacity,
          strokeOpacity,
        });
      } else {
        circle.setMap(null);
      }
    }, 200);
  }

  normalizeBoundaries(boundaries) {
    if (boundaries instanceof GeolocationBoundaries) {
      return boundaries;
    }

    if (boundaries instanceof google.maps.LatLngBounds) {
      const northEast = boundaries.getNorthEast();
      const southWest = boundaries.getSouthWest();

      return new GeolocationBoundaries({
        north: northEast.lat(),
        east: northEast.lng(),
        south: southWest.lat(),
        west: southWest.lng(),
      });
    }

    return false;
  }

  denormalizeBoundaries(boundaries) {
    if (boundaries instanceof google.maps.LatLngBounds) {
      return boundaries;
    }

    if (boundaries instanceof GeolocationBoundaries) {
      return new google.maps.LatLngBounds({ lat: boundaries.south, lng: boundaries.west }, { lat: boundaries.north, lng: boundaries.east });
    }

    return false;
  }

  addControl(element) {
    let position = google.maps.ControlPosition.TOP_LEFT;

    const customPosition = element.getAttribute("data-map-control-position") ?? null;
    if (google.maps.ControlPosition[customPosition]) {
      position = google.maps.ControlPosition[customPosition];
    }

    let controlIndex = -1;
    this.googleMap.controls.forEach((control, index) => {
      if (element.classList === control.classList) {
        controlIndex = index;
      }
    });

    if (controlIndex === -1) {
      element.classList.remove("hidden");
      this.googleMap.controls[position].push(element);
      return element;
    }

    element.remove();
    return this.googleMap.controls[position].getAt(controlIndex);
  }

  removeControls() {
    this.googleMap.controls.forEach((item) => {
      if (typeof item === "undefined") {
        return;
      }

      if (typeof item.clear === "function") {
        item.clear();
      }
    });
  }

  addAccuracyIndicatorCircle(location, accuracy) {
    return new google.maps.Circle({
      center: location,
      radius: accuracy,
      map: this.googleMap,
      fillColor: "#4285F4",
      fillOpacity: 0.15,
      strokeColor: "#4285F4",
      strokeOpacity: 0.3,
      strokeWeight: 1,
      clickable: false,
    });
  }

  addTitleToShape(shape, title) {
    const infoWindow = new google.maps.InfoWindow();
    google.maps.event.addListener(shape, "mouseover", (e) => {
      infoWindow.setPosition(e.latLng);
      infoWindow.setContent(title);
      infoWindow.open(this.googleMap);
    });
    google.maps.event.addListener(shape, "mouseout", () => {
      infoWindow.close();
    });
  }

  createShapeLine(geometry, settings) {
    const shape = super.createShapeLine(geometry, settings);

    shape.googleShapes = [];

    const line = new google.maps.Polyline({
      path: geometry.points,
      strokeColor: settings.strokeColor,
      strokeOpacity: parseFloat(settings.strokeOpacity),
      strokeWeight: parseInt(settings.strokeWidth),
    });

    if (settings.title) {
      this.addTitleToShape(line, settings.title);
    }

    line.setMap(this.googleMap);

    shape.googleShapes.push(line);

    return shape;
  }

  createShapePolygon(geometry, settings) {
    const shape = super.createShapePolygon(geometry, settings);

    shape.googleShapes = [];
    const polygon = new google.maps.Polygon({
      paths: geometry.points,
      strokeColor: settings.strokeColor,
      strokeOpacity: parseFloat(settings.strokeOpacity),
      strokeWeight: parseInt(settings.strokeWidth),
      fillColor: settings.fillColor,
      fillOpacity: parseFloat(settings.fillOpacity),
    });

    if (settings.title) {
      this.addTitleToShape(polygon, settings.title);
    }

    polygon.setMap(this.googleMap);

    shape.googleShapes.push(polygon);

    return shape;
  }

  createShapeMultiLine(geometry, settings) {
    const shape = super.createShapeMultiLine(geometry, settings);

    shape.googleShapes = [];
    shape.geometry.lines.forEach((lineGeometry) => {
      const line = new google.maps.Polyline({
        path: lineGeometry.points,
        strokeColor: settings.strokeColor,
        strokeOpacity: parseFloat(settings.strokeOpacity),
        strokeWeight: parseInt(settings.strokeWidth),
      });

      if (settings.title) {
        this.addTitleToShape(line, settings.title);
      }

      line.setMap(this.googleMap);

      shape.googleShapes.push(line);
    });

    return shape;
  }

  createShapeMultiPolygon(geometry, settings) {
    const shape = super.createShapeMultiPolygon(geometry, settings);

    shape.googleShapes = [];
    shape.geometry.polygons.forEach((polygonGeometry) => {
      const polygon = new google.maps.Polygon({
        paths: polygonGeometry.points,
        strokeColor: settings.strokeColor,
        strokeOpacity: parseFloat(settings.strokeOpacity),
        strokeWeight: parseInt(settings.strokeWidth),
        fillColor: settings.fillColor,
        fillOpacity: parseFloat(settings.fillOpacity),
      });
      if (settings.title) {
        this.addTitleToShape(polygon, settings.title);
      }

      polygon.setMap(this.googleMap);

      shape.googleShapes.push(polygon);
    });

    return shape;
  }

  /**
   *
   * @param {GeolocationShape} shape
   *   Shape.
   * @param {google.maps.MVCObject[]} shape.googleShapes
   *   Google Shapes.
   */
  removeShape(shape) {
    if (!shape) {
      return;
    }

    if (shape.googleShapes) {
      shape.googleShapes.forEach((googleShape) => {
        googleShape.remove();
      });
    }

    shape.remove();
  }

  getShapeBoundaries(shapes) {
    super.getShapeBoundaries(shapes);

    shapes = shapes || this.dataLayers.get("default").shapes;
    if (!shapes.length) {
      return null;
    }

    // A Google Maps API tool to re-center the map on its content.
    const bounds = new google.maps.LatLngBounds();

    shapes.forEach((shape) => {
      shape.googleShapes.forEach((googleShape) => {
        googleShape.getPath().forEach((element) => {
          bounds.extend(element);
        });
      });
    });

    return this.normalizeBoundaries(bounds);
  }

  loadTileLayer(layerId, layerSettings) {
    this.googleMap.mapTypes.unbind(layerId);

    const layer = new google.maps.ImageMapType({
      name: layerId,
      getTileUrl(coord, zoom) {
        return layerSettings.url.replace("{x}", coord.x).replace("{y}", coord.y).replace("{z}", zoom).replace("{s}", "a");
      },
      tileSize: new google.maps.Size(256, 256),
      minZoom: 1,
      maxZoom: 20,
    });

    this.googleMap.mapTypes.set(layerId, layer);
    this.googleMap.setMapTypeId(layerId);

    if (layer) {
      this.tileLayers.set(layerId, layer);
    }

    return layer;
  }

  unloadTileLayer(layerId) {
    this.googleMap.setMapTypeId("roadmap");
    this.googleMap.mapTypes.unbind(layerId);

    if (!this.tileLayers.has(layerId)) {
      return;
    }
    this.tileLayers.delete(layerId);
  }
}
