<?php

namespace Drupal\config_overlay\EventSubscriber;

use Drupal\config_overlay\Config\ExtensionStorageFactory;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\Core\Extension\ProfileExtensionList;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a base configuration transform subscriber for Config Overlay.
 */
abstract class ConfigTransformSubscriberBase implements EventSubscriberInterface {

  /**
   * The profile extension list.
   *
   * @var \Drupal\Core\Extension\ProfileExtensionList
   */
  protected ProfileExtensionList $profileExtensionList;

  /**
   * The installation profile.
   *
   * @var string
   */
  protected $profile;

  /**
   * The configuration overlay extension storage factory.
   *
   * @var \Drupal\config_overlay\Config\ExtensionStorageFactory
   */
  protected ExtensionStorageFactory $extensionStorageFactory;

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected StorageInterface $activeStorage;

  /**
   * Configuration keys to ignore in the configuration to be transformed.
   *
   * In case any of these keys are present in the source configuration but not
   * in the extension configuration, the configuration will still be considered
   * equal.
   *
   * @var string[]
   */
  protected array $ignoreKeys = ['_core', 'uuid'];

  /**
   * Constructs a configuration subscriber for Config Overlay.
   *
   * @param \Drupal\Core\Extension\ProfileExtensionList $profileExtensionList
   *   The profile extension list.
   * @param string $profile
   *   The installation profile.
   * @param \Drupal\config_overlay\Config\ExtensionStorageFactory $extensionStorageFactory
   *   The configuration overlay extension storage factory.
   * @param \Drupal\Core\Config\StorageInterface $activeStorage
   *   The active configuration storage.
   */
  public function __construct(ProfileExtensionList $profileExtensionList, string $profile, ExtensionStorageFactory $extensionStorageFactory, StorageInterface $activeStorage) {
    $this->profileExtensionList = $profileExtensionList;
    $this->profile = $profile;
    $this->extensionStorageFactory = $extensionStorageFactory;
    $this->activeStorage = $activeStorage;
  }

  /**
   * Overlays shipped configuration when importing configuration.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The configuration storage import event.
   */
  public function overlayShipped(StorageTransformEvent $event): void {
    $storage = $event->getStorage();
    if (!$storage->exists('core.extension')) {
      // In case the synchronization directory does not contain a
      // core.extension.yml, the site was installed from configuration with a
      // profile that ships a core.extension.yml in its config/sync directory.
      // As the configuration overlay depends on the list of extensions, the
      // shipped core.extension configuration needs to be added manually
      // first. If the extension configuration cannot be found, abort.
      $profileSyncPath = $this->profileExtensionList->getPath($this->profile) . '/config/sync';
      if (!is_dir($profileSyncPath)) {
        return;
      }

      $profileSyncStorage = new FileStorage($profileSyncPath, $storage->getCollectionName());
      if (!$profileSyncStorage->exists('core.extension')) {
        return;
      }

      $storage->write('core.extension', $profileSyncStorage->read('core.extension'));
    }

    // Fetch all shipped configuration that has not been deleted and is not
    // overridden.
    $extensionStorage = $this->extensionStorageFactory->create($storage);
    $extensionNames = array_diff(
      $extensionStorage->listAll(),
      $storage->read('config_overlay.deleted')['names'] ?? [],
      $storage->listAll(),
    );

    if (!$extensionNames) {
      return;
    }

    // Add ignored data from the active configuration to the shipped
    // configuration and copy it into the storage to be imported.
    $allExtensionData = $extensionStorage->readMultiple($extensionNames);
    $allActiveData = $this->activeStorage->readMultiple($extensionNames);
    foreach ($extensionNames as $extensionName) {
      $extensionData = $allExtensionData[$extensionName];

      if (isset($allActiveData[$extensionName])) {
        $activeData = $allActiveData[$extensionName];
        foreach ($this->ignoreKeys as $ignoreKey) {
          // The system.site configuration specifies an empty UUID, so checking
          // whether the 'uuid' key is set is not sufficient.
          if (empty($extensionData[$ignoreKey]) && isset($activeData[$ignoreKey])) {
            $extensionData[$ignoreKey] = $activeData[$ignoreKey];
          }
        }
        // Make sure that the amended data is positioned in the same place in
        // the data array as it is in the active configuration so that strict
        // equality between the exported and active configuration can be
        // achieved. The intersection makes sure that other keys that are
        // available in the active configuration but not in the exported
        // configuration are not merged.
        $extensionData = array_intersect_key(array_merge($activeData, $extensionData), $extensionData);
      }

      $storage->write($extensionName, $extensionData);
    }
  }

  /**
   * Removes any unchanged, shipped configuration when exporting configuration.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The configuration storage export event.
   */
  public function removeShipped(StorageTransformEvent $event): void {
    $storage = $event->getStorage();

    // Compare the configuration to be exported with the shipped configuration.
    $names = $storage->listAll();
    $extensionStorage = $this->extensionStorageFactory->create($storage);
    $allExtensionData = $extensionStorage->readMultiple($names);
    $allData = $storage->readMultiple(array_keys($extensionStorage->readMultiple($names)));
    foreach ($names as $name) {
      if (isset($allExtensionData[$name])) {
        $extensionData = $allExtensionData[$name];
        $data = $allData[$name];

        foreach ($this->ignoreKeys as $ignoreKey) {
          // Generally shipped configuration does not contain a UUID or a
          // default config hash, but if it does it should not be removed for
          // the comparison.
          if (!isset($extensionData[$ignoreKey])) {
            unset($data[$ignoreKey]);
          }
        }

        // If the configuration to be exported matches the shipped
        // configuration, do not export it.
        if ($data === $extensionData) {
          $storage->delete($name);
        }
      }
    }
  }

}
