/**
 * @prop {GeolocationWidgetBroker} broker
 */

/**
 * @prop {String} id
 * @prop {Object} settings
 * @prop {int} settings.cardinality
 * @prop {String} settings.fieldName
 */
export class WidgetSubscriberBase {
  constructor(broker, settings) {
    this.broker = broker;
    this.settings = settings;
  }

  initialize() {}

  /**
   * @param {Number[]} newOrder
   *   New order.
   * @param {String} source
   *   Source.
   */
  reorder(newOrder, source) {}

  /**
   * @param {GeolocationCoordinates} coordinates
   *   Coordinates.
   * @param {Number} index
   *   Index.
   * @param {String} source
   *   Source.
   */
  addCoordinates(coordinates, index, source) {}

  /**
   * @param {Number} index
   *   Index.
   * @param {String} source
   *   Source.
   */
  removeCoordinates(index, source) {}

  /**
   * @param {GeolocationCoordinates} coordinates
   *   Coordinates.
   * @param {Number} index
   *   Index.
   * @param {String} source
   *   Source.
   */
  alterCoordinates(coordinates, index, source) {}
}
