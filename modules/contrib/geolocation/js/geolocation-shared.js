/**
 * @file
 * Javascript for the Geolocation shared functionality.
 */

(function (Drupal) {
  "use strict";

  Drupal.geolocation = Drupal.geolocation ?? {};
  Drupal.geolocation.addedScripts = Drupal.geolocation.addedScripts ?? {};
  Drupal.geolocation.addedStylesheets = Drupal.geolocation.addedStylesheets ?? {};

  Drupal.geolocation.hash = (url) => {
    let hash = 0;
    for (let i = 0, len = url.length; i < len; i++) {
      let chr = url.charCodeAt(i);
      hash = (hash << 5) - hash + chr;
      hash |= 0; // Convert to 32bit integer
    }

    return hash;
  };

  Drupal.geolocation.addScript = (url, async = false) => {
    if (!url) {
      return Promise.reject("geolocation-shared: Cannot add script as URL is missing.");
    }

    let hash = Drupal.geolocation.hash(url);

    if (typeof Drupal.geolocation.addedScripts[hash] !== "undefined") {
      return Drupal.geolocation.addedScripts[hash];
    }

    let promise = new Promise((resolve) => {
      let script = document.createElement("script");
      script.src = url;
      script.onload = (event) => {
        resolve(event);
      };
      if (async) {
        script.async = true;
      }
      document.body.appendChild(script);
    });

    Drupal.geolocation.addedScripts[hash] = promise;

    return promise;
  };

  Drupal.geolocation.addStylesheet = (url) => {
    if (!url) {
      return Promise.reject("geolocation-shared: Cannot add stylesheet as URL is missing.");
    }

    let hash = Drupal.geolocation.hash(url);

    if (typeof Drupal.geolocation.addedStylesheets[hash] !== "undefined") {
      return Drupal.geolocation.addedStylesheets[hash];
    }

    let link = document.createElement("link");
    link.href = url;
    link.rel = "stylesheet";
    document.head.appendChild(link);

    Drupal.geolocation.addedStylesheets[hash] = true;

    return Promise.resolve();
  };
})(Drupal);
