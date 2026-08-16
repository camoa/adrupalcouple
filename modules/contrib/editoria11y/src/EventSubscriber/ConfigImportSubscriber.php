<?php

namespace Drupal\editoria11y\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Increments the config version when editoria11y config is saved.
 *
 * Form submit handlers already increment the version, but config imports
 * (drush cim, config sync UI) bypass forms. This subscriber catches all
 * saves so browsers fetch fresh config via the versioned API URL.
 */
final class ConfigImportSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected readonly StateInterface $state,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ConfigEvents::SAVE => 'onConfigSave',
    ];
  }

  /**
   * Increments config_version when relevant config objects are saved.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    $name = $event->getConfig()->getName();
    if (str_starts_with($name, 'editoria11y')) {
      $v = $this->state->get('editoria11y.config_version', 0);
      $this->state->set('editoria11y.config_version', $v + 1);
    }
  }

}
