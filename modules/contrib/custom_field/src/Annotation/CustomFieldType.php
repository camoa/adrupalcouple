<?php

namespace Drupal\custom_field\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a Custom field Type item annotation object.
 *
 * @see \Drupal\custom_field\Plugin\CustomFieldTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class CustomFieldType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

  /**
   * A short human readable description for the customfield type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

  /**
   * The default value for the check empty field setting. Defaults to FALSE
   *
   * @see \Drupal\custom_field\Plugin\Field\FieldType\CustomItem
   *
   * @var boolean
   *
   * @ingroup plugin_translatable
   */
  public bool $check_empty = FALSE;

  /**
   * Flag to determine if this type should never be checked to determine if
   * the customfield row is empty. This will override $check_empty.
   *
   * @see \Drupal\custom_field\Plugin\CustomFieldType\Uuid
   *
   * @var boolean
   *
   * @ingroup plugin_translatable
   */
  public bool $never_check_empty = FALSE;

  /**
   * The category under which the field type should be listed in the UI.
   *
   * @ingroup plugin_translatable
   *
   * @var string
   */
  public string $category = 'general';

  /**
   * An array of data types the widget supports.
   *
   * @var array
   */
  public array $data_types = [];

}
