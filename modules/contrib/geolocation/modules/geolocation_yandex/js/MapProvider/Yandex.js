import { GeolocationMapBase } from "../../../../js/MapProvider/GeolocationMapBase.js";
import { GeolocationBoundaries } from "../../../../js/Base/GeolocationBoundaries.js";
import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";
import { YandexMapMarker } from "../YandexMapMarker.js";

/* global ymaps3 */

/**
 * @typedef YandexMapSettings
 *
 * @extends GeolocationMapSettings
 *
 * @prop {MapOptions} yandex_settings
 * @prop {Number} yandex_settings.zoom
 * @prop {Number} yandex_settings.max_zoom
 * @prop {Number} yandex_settings.min_zoom
 */

/**
 * @prop {ymaps3.YMap} yandexMap
 * @prop {YandexMapSettings} settings
 */
export default class Yandex extends GeolocationMapBase {
  constructor(mapSettings) {
    super(mapSettings);

    this.customControls = [];

    this.container.style.position = "relative";

    // Set the container size.
    this.container.style.height = this.settings.yandex_settings.height;
    this.container.style.width = this.settings.yandex_settings.width;
  }

  initialize() {
    return super
      .initialize()
      .then(() => {
        return ymaps3.ready.then(() => {
          return ymaps3.import("@yandex/ymaps3-markers@0.0.1");
        });
      })
      .then(() => {
        return new Promise((resolve) => {
          const { YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer } = ymaps3;

          this.yandexMap = new YMap(this.container, {
            location: {
              center: [this.settings.lng, this.settings.lat],
              zoom: this.settings.yandex_settings.zoom,
            },
            zoomRange: {
              max: this.settings.yandex_settings.max_zoom ?? 20,
              min: this.settings.yandex_settings.min_zoom ?? 0,
            },
          });

          this.yandexMap.addChild(new YMapDefaultSchemeLayer({}));
          this.yandexMap.addChild(new YMapDefaultFeaturesLayer({ zIndex: 2000 }));

          resolve();
        }).then(() => {
          return new Promise((resolve) => {
            const { YMapListener } = ymaps3;

            let singleClick;

            // Creating a Listener object.
            const mapListener = new YMapListener({
              layer: "any",
              onActionEnd: (object) => {
                this.updatingBounds = false;

                this.features.forEach((feature) => {
                  feature.onMapIdle();
                });

                const bounds = this.yandexMap.bounds;
                if (!bounds) {
                  return;
                }

                this.features.forEach((feature) => {
                  feature.onBoundsChanged(this.normalizeBoundaries(bounds));
                });
              },
              onClick: (object, event) => {
                singleClick = setTimeout(() => {
                  this.features.forEach((feature) => {
                    feature.onClick(this.normalizeCoordinates(event.coordinates));
                  });
                }, 500);
              },
              onDblClick: (object, event) => {
                clearTimeout(singleClick);
                this.features.forEach((feature) => {
                  feature.onDoubleClick(this.normalizeCoordinates(event.coordinates));
                });
              },
              onContextMenu: (object, event) => {
                this.features.forEach((feature) => {
                  feature.onContextClick(this.normalizeCoordinates(event.coordinates));
                });
              },
            });

            // Adding the Listener to the map.
            this.yandexMap.addChild(mapListener);

            resolve(this);
          });
        });
      });
  }

  createMarker(coordinates, settings) {
    return new YandexMapMarker(coordinates, settings, this);
  }

  createShapeLine(geometry, settings) {
    const shape = super.createShapeLine(geometry, settings);

    const { YMapFeature } = ymaps3;

    shape.yandexShapes = [];

    const points = [];
    geometry.points.forEach((value) => {
      points.push([value.lng, value.lat]);
    });

    const line = new YMapFeature({
      geometry: {
        type: "LineString",
        coordinates: points,
      },
      style: { stroke: [{ color: settings.strokeColor, width: parseInt(settings.strokeWidth) }] },
    });

    this.yandexMap.addChild(line);

    shape.yandexShapes.push(line);

    return shape;
  }

  createShapePolygon(geometry, settings) {
    const shape = super.createShapePolygon(geometry, settings);

    const { YMapFeature } = ymaps3;

    shape.yandexShapes = [];

    const coordinates = [];
    geometry.points.forEach((value) => {
      coordinates.push([value.lng, value.lat]);
    });

    const polygon = new YMapFeature({
      geometry: {
        type: "Polygon",
        coordinates: [coordinates],
      },
      style: { stroke: [{ color: settings.strokeColor, width: parseInt(settings.strokeWidth) }], fill: settings.fillColor, fillOpacity: settings.fillOpacity },
    });

    this.yandexMap.addChild(polygon);

    shape.yandexShapes.push(polygon);

    return shape;
  }

  createShapeMultiLine(geometry, settings) {
    const shape = super.createShapeMultiLine(geometry, settings);

    const { YMapFeature } = ymaps3;

    shape.yandexShapes = [];

    const lines = [];

    shape.geometry.lines.forEach((lineGeometry) => {
      const points = [];
      lineGeometry.points.forEach((value) => {
        points.push([value.lng, value.lat]);
      });

      lines.push(points);
    });

    const multiline = new YMapFeature({
      geometry: {
        type: "MultiLineString",
        coordinates: [lines],
      },
      style: { stroke: [{ color: settings.strokeColor, width: parseInt(settings.strokeWidth) }] },
    });

    this.yandexMap.addChild(multiline);

    shape.yandexShapes.push(multiline);

    return shape;
  }

  createShapeMultiPolygon(geometry, settings) {
    const shape = super.createShapeMultiPolygon(geometry, settings);

    const { YMapFeature } = ymaps3;

    shape.yandexShapes = [];

    const polygons = [];
    shape.geometry.polygons.forEach((polygonGeometry) => {
      const points = [];
      polygonGeometry.points.forEach((value) => {
        points.push([value.lng, value.lat]);
      });

      polygons.push(points);
    });

    const multipolygon = new YMapFeature({
      geometry: {
        type: "MultiPolygon",
        coordinates: [polygons],
      },
      style: { stroke: [{ color: settings.strokeColor, width: parseInt(settings.strokeWidth) }], fill: settings.fillColor, fillOpacity: settings.fillOpacity },
    });

    this.yandexMap.addChild(multipolygon);

    shape.yandexShapes.push(multipolygon);

    return shape;
  }

  /** @inheritdoc */
  removeShape(shape) {
    // @todo
    if (!shape) {
      return;
    }

    if (shape.yandexShapes) {
      shape.yandexShapes.forEach((yandexShape) => {
        yandexShape.remove();
      });
    }

    shape.remove();
  }

  getBoundaries() {
    super.getBoundaries();

    return this.normalizeBoundaries(this.yandexMap.bounds);
  }

  getShapeBoundaries(shapes) {
    // @todo
    super.getShapeBoundaries(shapes);

    shapes = shapes || this.dataLayers.get("default").shapes;
    if (!shapes.length) {
      return null;
    }

    const shapeBounds = [];

    shapes.forEach((shape) => {
      shape.yandexShapes.forEach((yandexShape) => {
        shapeBounds.push(yandexShape.geometry.getBounds());
      });
    });

    return this.normalizeBoundaries(ymaps3.util.bounds.fromBounds(shapeBounds));
  }

  getMarkerBoundaries(markers) {
    super.getMarkerBoundaries(markers);

    markers = markers || this.dataLayers.get("default").markers;
    if (!markers) {
      return null;
    }

    const coordinates = [];

    markers.forEach((marker) => {
      coordinates.push(marker.yandexMarker.coordinates);
    });

    let minLat = coordinates[0][1];
    let maxLat = coordinates[0][1];
    let minLng = coordinates[0][0];
    let maxLng = coordinates[0][0];

    for (let i = 1; i < coordinates.length; i++) {
      const lat = coordinates[i][1];
      const lng = coordinates[i][0];

      minLat = Math.min(minLat, lat);
      maxLat = Math.max(maxLat, lat);

      // Adjust for the antimeridian
      if (Math.abs(lng - minLng) > 180) {
        if (minLng < 0) {
          minLng += 360;
        } else {
          minLng -= 360;
        }
      }
      if (Math.abs(lng - maxLng) > 180) {
        if (maxLng < 0) {
          maxLng += 360;
        } else {
          maxLng -= 360;
        }
      }

      minLng = Math.min(minLng, lng);
      maxLng = Math.max(maxLng, lng);
    }

    return new GeolocationBoundaries([minLat, minLng], [maxLat, maxLng]);
  }

  setBoundaries(boundaries) {
    if (super.setBoundaries(boundaries) === false) {
      return false;
    }

    return this.yandexMap.update({ location: { bounds: this.denormalizeBoundaries(boundaries) } });
  }

  getZoom() {
    this.yandexMap.getZoom();
  }

  setZoom(zoom, defer) {
    if (!zoom) {
      zoom = this.settings.yandex_settings.zoom;
    }
    zoom = parseInt(zoom);

    return this.yandexMap.update({ zoom });
  }

  getCenter() {
    return this.normalizeCoordinates(this.yandexMap.getCenter());
  }

  setCenterByCoordinates(coordinates, accuracy) {
    super.setCenterByCoordinates(coordinates, accuracy);

    this.yandexMap.panTo(this.denormalizeCoordinates(coordinates));
  }

  normalizeCoordinates(coordinates) {
    if (coordinates instanceof GeolocationCoordinates) {
      return coordinates;
    }

    return new GeolocationCoordinates(coordinates[0], coordinates[1]);
  }

  denormalizeCoordinates(coordinates) {
    if (!(coordinates instanceof GeolocationCoordinates)) {
      return coordinates;
    }

    return [coordinates.lng, coordinates.lat];
  }

  normalizeBoundaries(boundaries) {
    if (boundaries instanceof GeolocationBoundaries) {
      return boundaries;
    }

    return new GeolocationBoundaries({
      north: boundaries[0][1],
      east: boundaries[1][0],
      south: boundaries[1][1],
      west: boundaries[0][0],
    });
  }

  denormalizeBoundaries(boundaries) {
    if (boundaries instanceof GeolocationBoundaries) {
      return [
        [boundaries.west, boundaries.south],
        [boundaries.east, boundaries.north],
      ];
    }

    return false;
  }

  addControl(element) {
    element.classList.remove("hidden");

    element.style.position = "absolute";
    element.style.zIndex = "400";
    element.style.left = "";
    element.style.right = "";
    element.style.top = "";
    element.style.bottom = "";

    const position = element.dataset.mapControlPosition ?? "left";
    switch (position) {
      case "top":
        element.style.left = "50%";
        element.style.top = "2%";
        element.style.transform = "translateX(-50%)";
        break;
      case "top right":
        element.style.right = "2%";
        element.style.top = "2%";
        break;
      case "top left":
        element.style.left = "2%";
        element.style.top = "2%";
        break;
      case "left":
        element.style.left = "2%";
        element.style.top = "50%";
        element.style.transform = "translateY(-50%)";
        break;
      case "right":
        element.style.right = "2%";
        element.style.top = "50%";
        element.style.transform = "translateY(-50%)";
        break;
      case "bottom":
        element.style.left = "50%";
        element.style.bottom = "2%";
        element.style.transform = "translateX(-50%)";
        break;
      case "bottom right":
        element.style.right = "2%";
        element.style.bottom = "2%";
        break;
      case "bottom left":
        element.style.left = "2%";
        element.style.bottom = "2%";
        break;
    }

    this.container.append(element);
  }

  removeControls() {
    this.customControls.forEach((control) => {
      this.yandexMap.controls.remove(control);
    });
  }

  loadTileLayer(layerId, layerSettings) {
    const { YMapLayer, YMapTileDataSource } = ymaps3;

    const layerSource = new YMapTileDataSource({
      id: layerId,
      raster: {
        type: "tiles",
        fetchTile: (x, y, z) => {
          return layerSettings.url.replace("{s}", "a").replace("{x}", x.toString()).replace("{y}", y.toString()).replace("{z}", z.toString());
        },
      },
    });
    this.yandexMap.addChild(layerSource);

    const layer = new YMapLayer({
      id: layerId,
      source: layerId,
      type: "tiles",
    });
    this.yandexMap.addChild(layer);

    if (layer) {
      this.tileLayers.set(layerId, layer);
    }

    return layer;
  }

  unloadTileLayer(layerId) {
    this.yandexMap.removeChild(this.tileLayers.get(layerId));

    this.tileLayers.delete(layerId);
  }
}
