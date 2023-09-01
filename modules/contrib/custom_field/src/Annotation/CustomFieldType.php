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
   * The default value for the check empty field setting.
   *
   * @var bool
   *
   * @ingroup plugin_translatable
   * @see \Drupal\custom_field\Plugin\Field\FieldType\CustomItem
   */
  public bool $check_empty = FALSE;

  /**
   * Flag to restrict this type from empty row checking.
   *
   * @var bool
   *
   * @ingroup plugin_translatable
   * @see \Drupal\custom_field\Plugin\CustomFieldType\Uuid
   */
  public bool $never_check_empty = FALSE;

  /**
   * The category under which the field type should be listed in the UI.
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public string $category = 'general';

  /**
   * An array of data types the widget supports.
   *
   * @var array
   */
  public array $data_types = [];

}
