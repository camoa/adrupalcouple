<?php

/**
 * @file
 * Hooks provided by the Editoria11y module.
 */

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Allows modules to alter configuration being passed to the library.
 *
 * @param array $attach
 *   The array containing values to be altered.
 */
function hook_editoria11y_alter_config(array &$attach, CacheableMetadata $cacheableMetadata) {
  // Add or modify the drupalSettings sent to the library.
  // For example:
  // $attach['custom_tests'] = (int) $attach['custom_tests'] + 1';
  // $cacheableMetadata->setCacheTags(['custom:tag']);
  // .
}

/**
 * Allows modules to alter global configuration returned by the config API.
 *
 * This config is cached server-side by Drupal's Dynamic Page Cache and
 * client-side via Cache-Control headers (the URL contains a cache-buster
 * parameter that changes when config is updated). Use this hook for values
 * that are the same for all users and do not vary per page. Per-user and
 * per-page values should use hook_editoria11y_alter_config() instead.
 *
 * @param array $data
 *   The global config array to be returned as JSON.
 * @param \Drupal\Core\Cache\CacheableMetadata $cacheMetadata
 *   Cache metadata for the response. Add cache tags for any config or
 *   entities your hook reads from so the cached response is invalidated
 *   when they change.
 */
function hook_editoria11y_alter_global_config(array &$data, CacheableMetadata $cacheMetadata) {
  // Add or modify the global config returned by the config API endpoint.
  // For example, to add custom test rules:
  // $data['custom_rules'] = [...];
  // $data['custom_tests'] = (int) ($data['custom_tests'] ?? 0) + 1;
  // $cacheMetadata->addCacheTags(['config:mymodule.settings']);.
}
