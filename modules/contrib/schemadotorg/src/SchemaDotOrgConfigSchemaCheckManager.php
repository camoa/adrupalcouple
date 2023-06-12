<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\Core\Config\TypedConfigManagerInterface;

/**
 * Schema.org config schema check manager.
 */
class SchemaDotOrgConfigSchemaCheckManager implements SchemaDotOrgConfigSchemaCheckManagerInterface {
  use SchemaCheckTrait;

  /**
   * Constructs a SchemaDotOrgConfigSchemaCheckManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed configuration manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected TypedConfigManagerInterface $typedConfigManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function checkConfigValue(string $config_name, string $key, mixed $value): bool|array {
    $config = clone $this->configFactory->getEditable($config_name);
    // Purge all config except the config key/value.
    $config_data = $config->setData([])->set($key, $value)->get();
    return $this->checkConfigSchema($this->typedConfigManager, $config_name, $config_data);
  }

}
