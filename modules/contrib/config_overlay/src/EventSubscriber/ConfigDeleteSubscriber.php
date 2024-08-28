<?php

namespace Drupal\config_overlay\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
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
   * Constructs a configuration subscriber for Config Overlay.
   *
   * @param \Drupal\Core\Config\StorageInterface $extensionStorage
   *   The configuration overlay extension storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(StorageInterface $extensionStorage, ConfigFactoryInterface $configFactory) {
    $this->extensionStorage = $extensionStorage;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
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
