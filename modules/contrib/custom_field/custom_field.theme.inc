<?php

use Drupal\Core\Template\Attribute;

/**
 * Preprocess variables for customfield.html.twig
 */
function template_preprocess_customfield(&$variables) {
  // Set the item attributes
  foreach ($variables['items'] as &$item) {
    // Attributes are optional so we check if it's set first and process
    // appropriately
    if (isset($item['attributes'])) {
      $item['attributes'] = new Attribute($item['attributes']);
    }
    else {
      $item['attributes'] = new Attribute();
    }
  }
}
