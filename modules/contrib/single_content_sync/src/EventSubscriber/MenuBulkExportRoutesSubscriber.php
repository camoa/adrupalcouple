<?php

namespace Drupal\single_content_sync\EventSubscriber;

use Drupal\single_content_sync\Event\BulkExportRoutesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds menu routes to the bulk export local action.
 */
class MenuBulkExportRoutesSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      BulkExportRoutesEvent::EVENT_NAME => 'onBulkExportRoutes',
    ];
  }

  /**
   * Adds menu routes to the bulk export local action.
   *
   * @param \Drupal\single_content_sync\Event\BulkExportRoutesEvent $event
   *   The bulk export routes event.
   */
  public function onBulkExportRoutes(BulkExportRoutesEvent $event): void {
    $event->addRoute('entity.menu.edit_form', 'menu_link_content', NULL, 'menu');
  }

}
