<?php

declare(strict_types=1);

namespace Drupal\Tests\config_overlay\Functional\ExistingConfig;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides a base class for testing existing configuration with profiles.
 */
abstract class ExistingConfigProfileTestBase extends ExistingConfigTestBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();

    foreach ($this->getSyncConfig() as $name => $data) {
      file_put_contents("$this->siteDirectory/config/sync/$name.yml", Yaml::encode($data));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverriddenConfig(): array {
    $overriddenConfig = [
      StorageInterface::DEFAULT_COLLECTION => [],
    ];

    foreach ($this->getSyncConfig() as $name => $data) {
      $overriddenConfig[StorageInterface::DEFAULT_COLLECTION][$name] = $data;
    }

    return $overriddenConfig;
  }

  /**
   * Returns an array of synchronization configuration.
   *
   * @return array[]
   *   An array where the keys are configuration names and the values are arrays
   *   containing the respective configuration data.
   */
  protected function getSyncConfig(): array {
    return [
      'core.extension' => $this->getCoreExtensionConfiguration(),
      'system.date' => $this->getSystemDateConfiguration(),
      'system.mail' => $this->getSystemMailConfiguration(),
      'system.site' => $this->getSystemSiteConfiguration(),
    ];
  }

}
