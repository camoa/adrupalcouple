<?php

namespace Drupal\geolocation\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a LayerFeature annotation object.
 *
 * @see \Drupal\geolocation\LayerFeatureManager
 * @see plugin_api
 *
 * @Annotation
 */
class LayerFeature extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The name of the LayerFeature.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $name;

  /**
   * The description of the LayerFeature.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

  /**
   * The map type supported by this LayerFeature.
   *
   * @var string
   */
  public string $type;

}
