<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_address;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * The Schema.org address manager.
 */
class SchemaDotOrgAddressManager implements SchemaDotOrgAddressManagerInterface {

  /**
   * Constructs a SchemaDotOrgAddressManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function propertyFieldAlter(
    string $schema_type,
    string $schema_property,
    array &$field_storage_values,
    array &$field_values,
    ?string &$widget_id,
    array &$widget_settings,
    ?string &$formatter_id,
    array &$formatter_settings,
  ): void {
    // Make sure the field type is set to 'address'.
    if ($field_storage_values['type'] !== 'address') {
      return;
    }

    $config = $this->configFactory->get('schemadotorg_address.settings');

    $field_overrides = [];
    $field_overrides += $this->schemaTypeManager->getSetting(
      $config->get('field_overrides'),
      ['schema_type' => $schema_type, 'schema_property' => $schema_property]
    ) ?? [];
    $field_overrides += $this->schemaTypeManager->getSetting(
      $config->get('field_overrides'),
      ['schema_property' => $schema_property]
    ) ?? [];

    $field_values['settings']['field_overrides'] = [];
    foreach ($field_overrides as $property => $override) {
      $field_values['settings']['field_overrides'][$property] = ['override' => $override];
    }
  }

}
