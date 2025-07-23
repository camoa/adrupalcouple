import { GoogleMapFeature } from "./GoogleMapFeature.js";
import { GeolocationCoordinates } from "../../../../js/Base/GeolocationCoordinates.js";

/**
 * @prop {google.maps.drawing.DrawingManager} drawingManager
 */
export default class GoogleGeometryWidgetMapConnector extends GoogleMapFeature {
  constructor(settings, map) {
    super(settings, map);

    let drawingModes;

    switch (this.settings.field_type.replace("geolocation_geometry_", "")) {
      case "multiline":
      case "line":
        drawingModes = [google.maps.drawing.OverlayType.POLYLINE];
        break;

      case "multipolygon":
      case "polygon":
        drawingModes = [google.maps.drawing.OverlayType.RECTANGLE, google.maps.drawing.OverlayType.POLYGON];
        break;

      case "multipoint":
      case "point":
        drawingModes = [google.maps.drawing.OverlayType.MARKER];
        break;

      default:
        drawingModes = [google.maps.drawing.OverlayType.MARKER, google.maps.drawing.OverlayType.POLYLINE, google.maps.drawing.OverlayType.POLYGON, google.maps.drawing.OverlayType.RECTANGLE];
        break;
    }

    this.drawingManager = new google.maps.drawing.DrawingManager({
      drawingMode: null,
      drawingControl: true,
      drawingControlOptions: {
        position: google.maps.ControlPosition.TOP_CENTER,
        drawingModes,
      },
    });

    this.drawingManager.setMap(this.map.googleMap);
  }

  onMapReady() {
    google.maps.event.addListener(this.drawingManager, "overlaycomplete", (event) => {
      let currentElementCount = 0;
      let newIndex = 0;

      switch (this.settings.field_type.replace("geolocation_geometry_", "")) {
        case "point":
        case "multipoint":
          this.map.dataLayers.get("default").markers.forEach((element) => {
            currentElementCount++;
            const currentElementIndex = this.getIndexByShape(element);
            if (currentElementIndex === null) {
              return;
            }
            if (currentElementIndex >= newIndex) {
              newIndex = currentElementIndex + 1;
            }
          });
          break;

        default:
          this.map.dataLayers.get("default").shapes.forEach((element) => {
            currentElementCount++;
            const currentElementIndex = this.getIndexByShape(element);
            if (currentElementIndex === null) {
              return;
            }
            if (currentElementIndex >= newIndex) {
              newIndex = currentElementIndex + 1;
            }
          });
      }

      if (this.settings.cardinality <= currentElementCount && this.settings.cardinality !== -1) {
        const warning = document.createElement("div");
        warning.innerHTML = `<p>${Drupal.t("Maximum number of element reached.")}</p>`;
        Drupal.dialog(warning, {
          title: Drupal.t("Synchronization"),
        }).showModal();
        return;
      }

      event.overlay.setEditable(true);

      switch (event.type) {
        case google.maps.drawing.OverlayType.MARKER:
          break;

        case google.maps.drawing.OverlayType.POLYLINE:
          break;

        case google.maps.drawing.OverlayType.POLYGON:
        case google.maps.drawing.OverlayType.RECTANGLE:
          const polygon = this.map.createShapePolygon({ type: "Polygon", coordinates: [[]] }, {});
          this.setIndexByShape(polygon, newIndex);
          polygon.updateByGoogleShape(event.overlay);

          this.map.dataLayers.get("default").shapeAdded(polygon);
          break;
      }
    });

    return Promise.resolve();
  }

  setWidgetSubscriber(subscriber) {
    this.subscriber = subscriber;
  }

  /**
   * @param {GeolocationShape} shape
   *   Marker.
   *
   * @return {int|null}
   *   Index.
   */
  getIndexByShape(shape) {
    return Number(shape.wrapper.dataset.geolocationWidgetIndex ?? 0);
  }

  /**
   * @param {int} index
   *   Index.
   *
   * @return {GeolocationShape|null}
   *   Shape.
   */
  getShapeByIndex(index) {
    let returnValue = null;
    this.map.dataLayers.get("default").shapes.forEach((shape) => {
      if (index === this.getIndexByShape(shape)) {
        returnValue = shape;
      }
    });

    return returnValue;
  }

  /**
   * @param {GeolocationShape} shape
   *   Marker.
   * @param {int|false} index
   *   Index.
   */
  setIndexByShape(shape, index = false) {
    if (index === false) {
      delete shape.wrapper.dataset.geolocationWidgetIndex;
    } else {
      shape.wrapper.dataset.geolocationWidgetIndex = index.toString();
    }
  }

  /**
   * @param {Number} index
   *   Index.
   *
   * @return {String}
   *   Title.
   */
  getShapeTitle(index) {
    return `${index + 1}`;
  }

  addShapeSilently(index, geometry) {
    let shape;

    switch (geometry.type) {
      case "Point":
        const coordinates = new GeolocationCoordinates(geometry.coordinates[1], geometry.coordinates[0]);
        const marker = this.map.createMarker(coordinates, {
          title: this.getMarkerTitle(index, coordinates),
          label: index + 1,
          draggable: true,
        });

        this.setIndexByMarker(marker, index);

        marker.geolocationWidgetIgnore = true;
        this.map.dataLayers.get("default").markerAdded(marker);
        delete marker.geolocationWidgetIgnore;

        this.map.fitMapToElements();

        return marker;

      case "LineString":
        shape = this.map.createShapeLine(geometry, {
          title: this.getShapeTitle(index),
          label: index + 1,
          draggable: true,
        });
        break;

      case "Polygon":
        shape = this.map.createShapePolygon(geometry, {
          title: this.getShapeTitle(index),
          label: index + 1,
          draggable: true,
        });
        break;
    }

    if (!shape) return;

    this.setIndexByShape(shape, index);

    shape.geolocationWidgetIgnore = true;
    this.map.dataLayers.get("default").shapeAdded(shape);
    delete shape.geolocationWidgetIgnore;

    this.map.fitMapToElements();

    return shape;
  }

  reorderSilently(newOrder) {
    this.map.dataLayers.get("default").markers.forEach((marker) => {
      const oldIndex = this.getIndexByMarker(marker);
      const newIndex = newOrder.indexOf(oldIndex);

      marker.geolocationWidgetIgnore = true;
      marker.update(null, {
        title: this.getShapeTitle(newIndex),
        label: newIndex + 1,
      });
      delete marker.geolocationWidgetIgnore;

      this.setIndexByMarker(marker, newIndex);
    });
  }

  updateShapeSilently(index, geometry, settings = null) {
    const shape = this.getShapeByIndex(index);

    if (!shape) return this.addShapeSilently(index, geometry);

    if (shape.geometry === geometry && !settings) {
      return;
    }

    shape.geolocationWidgetIgnore = true;
    shape.update(geometry, settings ?? {});
    delete shape.geolocationWidgetIgnore;

    this.map.fitMapToElements();

    return shape;
  }

  removeShapeSilently(index) {
    const shape = this.getShapeByIndex(index);

    shape.geolocationWidgetIgnore = true;
    shape.remove();

    this.map.fitMapToElements();
  }

  onShapeAdded(shape) {
    super.onShapeAdded(shape);

    shape.googleShapes.forEach((googleShape) => {
      if (!googleShape.getEditable()) {
        googleShape.setEditable(true);
      }
    });

    if (shape.geolocationWidgetIgnore ?? false) return;

    this.subscriber?.geometryAdded(shape.geometry, this.getIndexByShape(shape) ?? 0);
  }

  onShapeClicked(shape, coordinates) {
    super.onShapeClicked(shape, coordinates);
    let editing = false;
    shape.googleShapes.forEach((googleShape) => {
      if (googleShape.getEditable()) {
        editing = true;
      }
    });

    if (editing) {
      return;
    }

    // Will trigger onShapeRemove and notify broker.
    shape.remove();
  }

  onShapeUpdated(shape) {
    super.onShapeUpdated(shape);

    if (shape.geolocationWidgetIgnore ?? false) return;

    this.subscriber?.geometryAltered(shape.geometry, this.getIndexByShape(shape));
  }

  onShapeRemove(shape) {
    super.onShapeRemove(shape);

    if (shape.geolocationWidgetIgnore ?? false) return;

    this.subscriber?.geometryRemoved(this.getIndexByShape(shape));
  }
}
