<?php

namespace Drupal\config_overlay\Config;

use Drupal\config_overlay\Exception\MissingExtensionListException;
use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ProfileExtensionList;

/**
 * Provides a factory to create a configuration overlay extension storage.
 */
class ExtensionStorageFactory {

  /**
   * The profile extension list.
   *
   * @var \Drupal\Core\Extension\ProfileExtensionList
   */
  protected ProfileExtensionList $profileExtensionList;

  /**
   * Creates an extension storage factory.
   *
   * @param \Drupal\Core\Extension\ProfileExtensionList $profileExtensionList
   *   The profile extension list.
   */
  public function __construct(ProfileExtensionList $profileExtensionList) {
    $this->profileExtensionList = $profileExtensionList;
  }

  /**
   * Creates an extension storage.
   *
   * A base storage needs to be passed in to read the list of extensions from.
   * The returned storage is similar to an ExtensionInstallStorage except that
   * it also supports optional extension configuration.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The base storage to read the extension list form.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The extension storage.
   */
  public function create(StorageInterface $storage): StorageInterface {
    if (!$storage->exists('core.extension')) {
      throw new MissingExtensionListException('The extension list must be present to create an extension storage');
    }

    $extension_config = $storage->read('core.extension') + ['profile' => NULL];
    $profile = $extension_config['profile'];

    $extension_install_storage = new ExtensionInstallStorage(
      $storage,
      InstallStorage::CONFIG_INSTALL_DIRECTORY,
      $storage->getCollectionName(),
      TRUE,
      $profile
    );

    $storages = [];
    if ($profile) {
      // If the profile has a config/sync directory add that first, so that
      // configuration there can override module-provided configuration.
      /* @see install_profile_info() */
      $profile_sync_path = $this->profileExtensionList->getPath($profile) . '/config/sync';
      if (is_dir($profile_sync_path)) {
        $storages[] = new FileStorage($profile_sync_path, $storage->getCollectionName());
      }
    }
    $storages[] = $extension_install_storage;
    $storages[] = new ExtensionOptionalStorage(
      $storage,
      $extension_install_storage,
      $profile,
      $storage->getCollectionName()
    );

    return new ReadOnlyUnionStorage($storages);
  }

}
