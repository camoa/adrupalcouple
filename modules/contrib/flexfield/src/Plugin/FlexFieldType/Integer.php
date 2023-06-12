<?php

namespace Drupal\flexfield\Plugin\FlexFieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'integer' flexfield type.
 *
 * The numeric base plugin has everything we need for an integer field but
 * we have this separate plugin definition class in case we want to do any
 * integer-specific stuff later.
 *
 * @FlexFieldType(
 *   id = "integer",
 *   label = @Translation("Integer"),
 *   description = @Translation("")
 * )
 */
class Integer extends NumericBase {

}
