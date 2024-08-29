import { YandexLayerFeature } from "./YandexLayerFeature.js";

export default class YandexClusterer extends YandexLayerFeature {
  constructor(settings, map) {
    super(settings, map);

    const clusterObjects = [];

    this.layer.map.yandexMap.geoObjects.each((el, i) => {
      if (el.geometry === null) {
        return true;
      }
      if (typeof el.geometry === "undefined") {
        return true;
      }

      clusterObjects[i] = new ymaps.GeoObject({
        geometry: el.geometry,
      });
    });

    const clusterer = new ymaps.Clusterer();
    clusterer.add(clusterObjects);
    map.yandexMap.geoObjects.add(clusterer);
  }
}
