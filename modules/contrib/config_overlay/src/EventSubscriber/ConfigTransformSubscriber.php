<?php

declare(strict_types=1);

namespace Drupal\config_overlay\EventSubscriber;

use Drupal\config_overlay\Config\ExtensionStorageFactory;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\Core\Extension\ProfileExtensionList;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a base configuration transform subscriber for Config Overlay.
 */
class ConfigTransformSubscriber implements EventSubscriberInterface {

  /**
   * The export priorities for this subscriber.
   *
   * This is a list of priorities because the subscriber is idempotent and may
   * be run multiple times (which is needed if Config Split is installed, for
   * example).
   *
   * Note that the import priorities are the respective inverse values, in
   * particular a single priority of 50 by default.
   *
   * By default, shipped configuration is removed late during export and
   * re-added early during import. This makes other transformers without
   * explicit priorities see the full set of configuration as though Config
   * Overlay was not installed. Note that Config Ignore runs even later (with
   * priority -100 for both export and import) to make sure that the specified
   * configuration is ignored regardless of whether it is shipped configuration
   * or not. Config Splits run with a priority of 0 by default, which allows
   * shipping configuration splits in modules and having them detected in the
   * initial configuration import, in particular.
   *
   * @var int[]
   *
   * @see \Drupal\config_overlay\EventSubscriber\ConfigTransformSubscriber::getSubscribedEvents()
   * @see \Drupal\config_overlay\EventSubscriber\ConfigTransformSubscriber::setExportPriorities())
   */
  protected static array $exportPriorities = [-50];

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
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [
      ConfigEvents::STORAGE_TRANSFORM_IMPORT => [
        // In order to overlay the configuration Config Overlay relies on the
        // list of extensions and the list of deleted shipped configuration. If
        // either of those are themselves shipped by the installation profile,
        // they need to be added explicitly first.
        ['ensureCoreExtension', 100],
        ['ensureConfigOverlayDeleted', 100],
      ],
      ConfigEvents::STORAGE_TRANSFORM_EXPORT => [],
    ];
    foreach (static::$exportPriorities as $exportPriority) {
      $events[ConfigEvents::STORAGE_TRANSFORM_EXPORT][] = ['removeShipped', $exportPriority];
      $events[ConfigEvents::STORAGE_TRANSFORM_IMPORT][] = ['overlayShipped', -$exportPriority];
    }
    return $events;
  }

  /**
   * Sets the export priorities for this subscriber.
   *
   * @param int[] $priorities
   *   A list of export priorities to set.
   *
   * @see \Drupal\config_overlay\EventSubscriber\ConfigTransformSubscriber::$exportPriorities
   * @see \Drupal\config_overlay\ConfigOverlayServiceProvider::alter()
   */
  public static function setExportPriorities(array $priorities): void {
    static::$exportPriorities = $priorities;
  }

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
   * Makes sure the list of installed extensions is present in the storage.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The configuration storage import event.
   */
  public function ensureCoreExtension(StorageTransformEvent $event): void {
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
  }

  /**
   * Makes sure the list of deleted shipped configuration is present.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The configuration storage import event.
   */
  public function ensureConfigOverlayDeleted(StorageTransformEvent $event): void {
    $storage = $event->getStorage();
    if (!$storage->exists('config_overlay.deleted')) {
      $profilePath = $this->profileExtensionList->getPath($this->profile);
      // Check the config/sync directory in the profile first, then the
      // config/install directory.
      foreach (['sync', 'install'] as $configDirectory) {
        $profileConfigPath = "$profilePath/config/$configDirectory";
        if (!is_dir($profileConfigPath)) {
          continue;
        }

        $profileConfigStorage = new FileStorage($profileConfigPath, $storage->getCollectionName());
        if (!$profileConfigStorage->exists('config_overlay.deleted')) {
          continue;
        }

        $deletedConfiguration = $profileConfigStorage->read('config_overlay.deleted');
        $storage->write('config_overlay.deleted', $deletedConfiguration);
        break;
      }
    }
  }

  /**
   * Overlays shipped configuration when importing configuration.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The configuration storage import event.
   */
  public function overlayShipped(StorageTransformEvent $event): void {
    $storage = $event->getStorage();
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
