<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_export\EventSubscriber;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Alters Schema.org mapping list builder and adds a 'Download CSV' link.
 *
 * @see \Drupal\schemadotorg_export\Controller\SchemaDotOrgExportMappingController
 */
class SchemaDotOrgExportEventSubscriber extends ServiceProviderBase implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * Constructs a SchemaDotOrgJsonApiExtrasEventSubscriber object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager) {}

  /**
   * Alters Schema.org mapping list builder and adds a 'Download CSV' link.
   *
   * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
   *   The event to process.
   */
  public function onView(ViewEvent $event): void {
    $route_name = $this->routeMatch->getRouteName();
    $route = [
      'entity.schemadotorg_mapping.collection' => 'entity.schemadotorg_mapping.export',
      'schemadotorg_mapping_set.overview' => 'schemadotorg_mapping_set.overview.export',
      'schemadotorg_mapping_set.details' => 'schemadotorg_mapping_set.details.export',
    ];

    if ($route_name === 'schemadotorg_report'
      && $this->schemaTypeManager->isType($this->routeMatch->getParameter('id'))) {
      $route['schemadotorg_report'] = 'schemadotorg_report.type.export';
    }
    if (isset($route[$route_name])) {
      $route_parameters = $this->routeMatch->getRawParameters()->all();
      $result = $event->getControllerResult();
      $result['export'] = [
        '#type' => 'link',
        '#title' => $this->t('<u>⇩</u> Download CSV'),
        '#url' => Url::fromRoute($route[$route_name], $route_parameters),
        '#attributes' => ['class' => ['button', 'button--small', 'button--extrasmall']],
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
      $event->setControllerResult($result);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run before main_content_view_subscriber.
    $events[KernelEvents::VIEW][] = ['onView', 100];
    return $events;
  }

}
