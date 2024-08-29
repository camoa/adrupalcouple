import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";
import { GeolocationMapBase } from "../../../../js/MapProvider/GeolocationMapBase.js";
import { GeolocationBoundaries } from "../../../../js/Base/GeolocationBoundaries.js";
import { BaiduMapMarker } from "../BaiduMapMarker.js";

/* global BMAP_ANCHOR_TOP_LEFT */

/**
 * @typedef BaiduMapSettings
 *
 * @extends GeolocationMapSettings
 *
 * @prop {String} baidu_api_url
 * @prop {MapOptions} baidu_settings
 */

/**
 * @prop {BMapGL.Map} baiduMap
 * @prop {BMapGL.Control[]} customControls
 * @prop {BaiduMapSettings} settings
 */
export default class Baidu extends GeolocationMapBase {
  /**
   * @constructor
   *
   * @param {BaiduMapSettings} mapSettings
   *   Settings.
   */
  constructor(mapSettings) {
    super(mapSettings);

    this.customControls = [];

    // Set the container size.
    this.container.style.height = this.settings.baidu_settings.height;
    this.container.style.width = this.settings.baidu_settings.width;
  }

  initialize() {
    return super
      .initialize()
      .then(() => {
        return new Promise((resolve) => {
          Drupal.geolocation.maps.addMapProviderCallback("Baidu", resolve);
        });
      })
      .then(() => {
        return new Promise((resolve) => {
          this.baiduMap = new BMapGL.Map(this.container, this.settings.baidu_settings);
          this.baiduMap.centerAndZoom(new BMapGL.Point(this.settings.lng, this.settings.lat), this.settings.zoom ?? 2);
          resolve();
        }).then(() => {
          return new Promise((resolve) => {
            let singleClick;

            this.baiduMap.addEventListener("click", (event) => {
              singleClick = setTimeout(() => {
                this.features.forEach((feature) => {
                  feature.onClick(new GeolocationCoordinates(event.point.lat, event.point.lng));
                });
              }, 500);
            });

            this.baiduMap.addEventListener("dblclick", (event) => {
              clearTimeout(singleClick);
              this.features.forEach((feature) => {
                feature.onDoubleClick(new GeolocationCoordinates(event.point.lat, event.point.lng));
              });
            });

            this.baiduMap.addEventListener("rightclick", (event) => {
              this.features.forEach((feature) => {
                feature.onContextClick(new GeolocationCoordinates(event.point.lat, event.point.lng));
              });
            });

            this.baiduMap.addEventListener("moveend", () => {
              this.updatingBounds = false;

              this.features.forEach((feature) => {
                feature.onMapIdle();
              });
            });

            this.baiduMap.addEventListener("moveend", () => {
              const bounds = this.getBoundaries();
              if (!bounds) {
                return;
              }

              this.features.forEach((feature) => {
                feature.onBoundsChanged(bounds);
              });
            });

            resolve(this);
          });
        });
      });
  }

  createMarker(coordinates, settings) {
    const marker = new BaiduMapMarker(coordinates, settings, this);
    this.baiduMap.addOverlay(marker.baiduMarker);

    return marker;
  }

  addTitleToShape(shape, title) {
    /** @type BMapGL.InfoWindow */
    const infoWindow = new BMapGL.InfoWindow(title);
    shape.addEventListener("mouseover", (e) => {
      this.baiduMap.openInfoWindow(infoWindow, e.point);
    });
    shape.addEventListener("mouseout", () => {
      this.baiduMap.closeInfoWindow();
    });
  }

  createShapeLine(geometry, settings) {
    const shape = super.createShapeLine(geometry, settings);

    shape.baiduShapes = [];

    const points = [];
    geometry.points.forEach((value) => {
      points.push(new BMapGL.Point(value.lng, value.lat));
    });

    const line = new BMapGL.Polyline(points, {
      strokeColor: settings.strokeColor,
      strokeOpacity: parseFloat(settings.strokeOpacity),
      strokeWeight: parseInt(settings.strokeWidth),
    });

    if (settings.title) {
      this.addTitleToShape(line, settings.title);
    }

    this.baiduMap.addOverlay(line);

    shape.baiduShapes.push(line);

    return shape;
  }

  createShapePolygon(geometry, settings) {
    const shape = super.createShapePolygon(geometry, settings);

    shape.baiduShapes = [];

    const points = [];
    geometry.points.forEach((value) => {
      points.push(new BMapGL.Point(value.lng, value.lat));
    });

    const polygon = new BMapGL.Polygon(points, {
      strokeColor: settings.strokeColor,
      strokeOpacity: parseFloat(settings.strokeOpacity),
      strokeWeight: parseInt(settings.strokeWidth),
      fillColor: settings.fillColor,
      fillOpacity: parseFloat(settings.fillOpacity),
    });

    if (settings.title) {
      this.addTitleToShape(polygon, settings.title);
    }

    this.baiduMap.addOverlay(polygon);

    shape.baiduShapes.push(polygon);

    return shape;
  }

  createShapeMultiLine(geometry, settings) {
    const shape = super.createShapeMultiLine(geometry, settings);

    shape.baiduShapes = [];
    shape.geometry.lines.forEach((lineGeometry) => {
      const points = [];
      lineGeometry.points.forEach((value) => {
        points.push(new BMapGL.Point(value.lng, value.lat));
      });

      const line = new BMapGL.Polyline(points, {
        strokeColor: settings.strokeColor,
        strokeOpacity: parseFloat(settings.strokeOpacity),
        strokeWeight: parseInt(settings.strokeWidth),
      });

      if (settings.title) {
        this.addTitleToShape(line, settings.title);
      }

      this.baiduMap.addOverlay(line);

      shape.baiduShapes.push(line);
    });

    return shape;
  }

  createShapeMultiPolygon(geometry, settings) {
    const shape = super.createShapeMultiPolygon(geometry, settings);

    shape.baiduShapes = [];
    shape.geometry.polygons.forEach((polygonGeometry) => {
      const points = [];
      polygonGeometry.points.forEach((value) => {
        points.push(new BMapGL.Point(value.lng, value.lat));
      });

      const polygon = new BMapGL.Polygon(points, {
        strokeColor: settings.strokeColor,
        strokeOpacity: parseFloat(settings.strokeOpacity),
        strokeWeight: parseInt(settings.strokeWidth),
        fillColor: settings.fillColor,
        fillOpacity: parseFloat(settings.fillOpacity),
      });
      if (settings.title) {
        this.addTitleToShape(polygon, settings.title);
      }

      this.baiduMap.addOverlay(polygon);

      shape.baiduShapes.push(polygon);
    });

    return shape;
  }

  removeShape(shape) {
    if (!shape) {
      return;
    }

    if (shape.baiduShapes) {
      shape.baiduShapes.forEach((baiduShape) => {
        baiduShape.remove();
      });
    }

    shape.remove();
  }

  getBoundaries() {
    super.getBoundaries();

    return this.normalizeBoundaries(this.baiduMap.getBounds());
  }

  getShapeBoundaries(shapes) {
    super.getShapeBoundaries(shapes);

    shapes = shapes || this.dataLayers.get("default").shapes;
    if (!shapes.length) {
      return null;
    }

    let bounds;

    shapes.forEach((shape) => {
      shape.baiduShapes.forEach((baiduShape) => {
        baiduShape.getPath().forEach((point) => {
          if (!bounds) {
            bounds = new BMapGL.Bounds(point, point);
          } else {
            bounds.extend(point);
          }
        });
      });
    });

    return this.normalizeBoundaries(bounds);
  }

  getMarkerBoundaries(markers) {
    super.getMarkerBoundaries(markers);

    markers = markers || this.dataLayers.get("default").markers;
    if (!markers) {
      return null;
    }

    let bounds;

    markers.forEach((marker) => {
      if (!bounds) {
        bounds = new BMapGL.Bounds(marker.baiduMarker.getPosition(), marker.baiduMarker.getPosition());
      } else {
        bounds.extend(marker.baiduMarker.getPosition());
      }
    });

    return this.normalizeBoundaries(bounds);
  }

  setBoundaries(boundaries) {
    if (super.setBoundaries(boundaries) === false) {
      return false;
    }

    /** @type {BMapGL.Bounds} */
    boundaries = this.denormalizeBoundaries(boundaries);

    this.baiduMap.setViewport([boundaries.getNorthEast(), boundaries.getSouthWest()]);

    return this;
  }

  getZoom() {
    this.baiduMap.getZoom();
  }

  setZoom(zoom, defer) {
    if (!zoom) {
      zoom = this.settings.baidu_settings.zoom;
    }
    zoom = parseInt(zoom);

    this.baiduMap.setZoom(zoom);
  }

  getCenter() {
    const center = this.baiduMap.getCenter();

    return new GeolocationCoordinates(center.lat, center.lng);
  }

  setCenterByCoordinates(coordinates, accuracy) {
    super.setCenterByCoordinates(coordinates, accuracy);

    if (typeof accuracy === "undefined") {
      this.baiduMap.panTo(new BMapGL.Point(coordinates.lng, coordinates.lat));
      return;
    }

    const circle = this.addAccuracyIndicatorCircle(coordinates, accuracy);

    // Set the zoom level to the accuracy circle's size.
    this.setBoundaries(this.normalizeBoundaries(circle.getBounds()));

    // Fade circle away.
    setInterval(() => {
      let fillOpacity = circle.getFillOpacity();
      fillOpacity -= 0.01;

      let strokeOpacity = circle.getStrokeOpacity();
      strokeOpacity -= 0.02;

      if (strokeOpacity > 0 && fillOpacity > 0) {
        circle.setFillOpacity(fillOpacity);
        circle.setStrokeOpacity(strokeOpacity);
      } else {
        this.baiduMap.removeOverlay(circle);
      }
    }, 200);
  }

  normalizeBoundaries(boundaries) {
    if (boundaries instanceof GeolocationBoundaries) {
      return boundaries;
    }

    if (boundaries instanceof BMapGL.Bounds) {
      if (boundaries.isEmpty()) {
        return null;
      }
      return new GeolocationBoundaries({
        north: boundaries.getNorthEast().lat,
        east: boundaries.getNorthEast().lng,
        south: boundaries.getSouthWest().lat,
        west: boundaries.getSouthWest().lng,
      });
    }

    return null;
  }

  denormalizeBoundaries(boundaries) {
    if (boundaries instanceof BMapGL.Bounds) {
      return boundaries;
    }

    if (boundaries instanceof GeolocationBoundaries) {
      return new BMapGL.Bounds(new BMapGL.Point(boundaries.east, boundaries.north), new BMapGL.Point(boundaries.west, boundaries.south));
    }

    return false;
  }

  addControl(element) {
    element.classList.remove("hidden");
    element.style.position = "absolute";
    element.style.zIndex = "400";
    const control = new BMapGL.Control({
      anchor: window[element.getAttribute("data-map-control-position")] ?? BMAP_ANCHOR_TOP_LEFT,
      offset: new BMapGL.Size(50, 50),
    });

    control.initialize = (map) => {
      map.getContainer().appendChild(element);
    };

    this.baiduMap.addControl(control);
  }

  removeControls() {
    this.customControls.forEach((control) => {
      this.baiduMap.removeControl(control);
    });
  }

  addAccuracyIndicatorCircle(coordinates, accuracy) {
    const circle = new BMapGL.Circle(new BMapGL.Point(coordinates.lng, coordinates.lat), accuracy, {
      fillColor: "#4285F4",
      fillOpacity: 0.15,
      strokeColor: "#4285F4",
      strokeOpacity: 0.3,
      strokeWeight: 1,
      enableClicking: false,
    });

    this.baiduMap.addOverlay(circle);

    return circle;
  }

  wgs84ToWebMercator(lon, lat) {
    const x = (lon * 20037508.34) / 180;
    let y = Math.log(Math.tan(((90 + lat) * Math.PI) / 360)) / (Math.PI / 180);
    y = (y * 20037508.34) / 180;
    return { x, y };
  }

  loadTileLayer(layerId, layerSettings) {
    const layer = new BMapGL.TileLayer();

    layer.getTilesUrl = (tileCoord, zoom) => {
      const offset = 2 ** (zoom - 1);
      const tileX = tileCoord.x + offset;
      const tileY = offset - tileCoord.y - 1;

      return layerSettings.url.replace("{s}", "a").replace("{x}", tileX.toString()).replace("{y}", tileY.toString()).replace("{z}", zoom.toString());
    };

    this.baiduMap.addTileLayer(layer);

    this.tileLayers.set(layerId, layer);
  }

  unloadTileLayer(layerId) {
    if (!this.tileLayers.has(layerId)) {
      return;
    }

    const layer = this.tileLayers.get(layerId);
    this.baiduMap.removeTileLayer(layer);
  }
}
