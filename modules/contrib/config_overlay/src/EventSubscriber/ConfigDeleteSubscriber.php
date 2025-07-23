<?php

declare(strict_types=1);

namespace Drupal\config_overlay\EventSubscriber;

use Drupal\config_ignore\ConfigIgnoreConfig;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to deletion of configuration on behalf of Config Overlay.
 */
class ConfigDeleteSubscriber implements EventSubscriberInterface {

  /**
   * The configuration overlay extension storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected StorageInterface $extensionStorage;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Constructs a configuration subscriber for Config Overlay.
   *
   * @param \Drupal\Core\Config\StorageInterface $extensionStorage
   *   The configuration overlay extension storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    StorageInterface $extensionStorage,
    ConfigFactoryInterface $configFactory,
    ModuleHandlerInterface $moduleHandler,
  ) {
    $this->extensionStorage = $extensionStorage;
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ConfigEvents::SAVE => 'onSave',
      ConfigEvents::DELETE => 'onDelete',
    ];
  }

  /**
   * Removes any re-added shipped configuration from the deletion list.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration save event.
   */
  public function onSave(ConfigCrudEvent $event): void {
    $config_name = $event->getConfig()->getName();
    $deleted = $this->configFactory->getEditable('config_overlay.deleted');
    // When installing Drupal from configuration this may be called before the
    // module configuration has been installed.
    if (!$deleted->isNew() && in_array($config_name, $deleted->get('names'), TRUE)) {
      $deleted_names = array_values(array_diff($deleted->get('names'), [$config_name]));
      $deleted->set('names', $deleted_names)->save();
    }
  }

  /**
   * Records any shipped configuration that is deleted.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration deletion event.
   */
  public function onDelete(ConfigCrudEvent $event): void {
    $config_name = $event->getConfig()->getName();

    // Do not record a deletion that should be ignored via Config Ignore.
    // Integration with Config Ignore 1.x or 2.x are not supported (because
    // those versions are not supported themselves), but avoid a fatal error
    // by checking that the ConfigIgnoreConfig class, which was introduced in
    // 3.x, exists.
    if ($this->moduleHandler->moduleExists('config_ignore') && class_exists(ConfigIgnoreConfig::class)) {
      $ignore_config = ConfigIgnoreConfig::fromConfig(
        $this->configFactory->get('config_ignore.settings'),
      );
      $collection = $event->getConfig()->getStorage()->getCollectionName();
      $ignoreImport = $ignore_config->isIgnored(
        $collection,
        $config_name,
        'import',
        'delete',
      );
      $ignoreExport = $ignore_config->isIgnored(
        $collection,
        $config_name,
        'export',
        'delete',
      );
      // If an advanced configuration only ignores the deletion on either import
      // or export, the intended behavior for Config Overlay is not inherently
      // clear so in that case we do record the deletion and assume that the
      // "config_overlay.deleted" configuration is ignored explicitly on import
      // or export depending on the use-case.
      if ($ignoreImport && $ignoreExport) {
        return;
      }
    }

    if ($this->extensionStorage->exists($config_name)) {
      $deleted = $this->configFactory->getEditable('config_overlay.deleted');
      $deleted_names = $deleted->get('names') ?: [];
      $deleted_names = array_unique(array_merge($deleted_names, [$config_name]));
      $deleted->set('names', $deleted_names)->save();
    }
  }

  /**
   * Sets the extension storage used by the configuration subscriber.
   *
   * This should be used to update the extension storage when the extension list
   * changes.
   *
   * @param \Drupal\Core\Config\StorageInterface $extensionStorage
   *   The extension storage to set.
   *
   * @see config_overlay_module_preinstall()
   * @see config_overlay_module_preuninstall()
   */
  public function setExtensionStorage(StorageInterface $extensionStorage): void {
    $this->extensionStorage = $extensionStorage;
  }

}
