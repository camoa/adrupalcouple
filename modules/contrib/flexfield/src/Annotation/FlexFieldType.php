<?php

namespace Drupal\flexfield\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Flexfield Type item annotation object.
 *
 * @see \Drupal\flexfield\Plugin\FlexFieldTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class FlexFieldType extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short human readable description for the flexfield type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The default value for the check empty field setting. Defaults to TRUE
   *
   * @see \Drupal\flexfield\Plugin\Field\FieldType\FlexItem
   *
   * @var boolean
   *
   * @ingroup plugin_translatable
   */
  public $check_empty = TRUE;

  /**
   * Flag to determine if this type should never be checked to determine if
   * the flexfield row is empty. This will override $check_rmpty.
   *
   * @see \Drupal\flexfield\Plugin\FlexFieldType\Uuid
   *
   * @var boolean
   *
   * @ingroup plugin_translatable
   */
  public $never_check_empty = FALSE;

}
