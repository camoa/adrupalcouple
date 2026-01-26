<?php

namespace Drupal\hreflang\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the config save event for hreflang.settings.
 */
class HreflangConfigSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs the HreflangConfigSubscriber.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   Cache tags invalidator.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(CacheTagsInvalidatorInterface $cacheTagsInvalidator, MessengerInterface $messenger) {
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
    $this->messenger = $messenger;
  }

  /**
   * Invalidates page caches on config change.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The ConfigCrudEvent to process.
   */
  public function onSave(ConfigCrudEvent $event): void {
    if ($event->getConfig()->getName() !== 'hreflang.settings') {
      return;
    }
    if (!$event->isChanged('x_default') && !$event->isChanged('x_default_fallback') && !$event->isChanged('defer_to_content_translation')) {
      return;
    }
    $this->messenger->addStatus($this->t('Page caches are being cleared for new Hreflang settings to take effect.'));
    $this->cacheTagsInvalidator->invalidateTags(['http_response']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[ConfigEvents::SAVE][] = ['onSave'];
    return $events;
  }

}
