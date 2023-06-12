<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonapi_preview;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_extras\EntityToJsonApi;
use Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApiManagerInterface;

/**
 * Schema.org JSON:API preview builder.
 */
class SchemaDotOrgJsonApiPreviewBuilder implements SchemaDotOrgJsonApiPreviewBuilderInterface {
  use StringTranslationTrait;

  /**
   * Constructs a SchemaDotOrgJsonApiPreviewBuilder object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resourceTypeRepository
   *   The resource type repository.
   * @param \Drupal\jsonapi_extras\EntityToJsonApi $entityToJsonApi
   *   The entity to JSON:API service.
   * @param \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApiManagerInterface $schemaJsonApiManager
   *   The Schema.org JSON:API manager.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected RendererInterface $renderer,
    protected ResourceTypeRepositoryInterface $resourceTypeRepository,
    protected EntityToJsonApi $entityToJsonApi,
    protected SchemaDotOrgJsonApiManagerInterface $schemaJsonApiManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function build(): ?array {
    $entity = $this->getRouteMatchEntity();
    if (!$entity) {
      return NULL;
    }

    // Get includes.
    $resource_type = $this->resourceTypeRepository->get(
      $entity->getEntityTypeId(),
      $entity->bundle()
    );
    $includes = $this->schemaJsonApiManager->getResourceIncludes($resource_type);

    // Retrieve JSON API representation of this node.
    $render_context = new RenderContext();
    $data = $this->renderer->executeInRenderContext($render_context, function () use ($entity, $includes) {
      try {
        return $this->entityToJsonApi->normalize($entity, $includes);
      }
      catch (\Exception $exception) {
        return NULL;
      }
    });

    if (!$data) {
      return NULL;
    }

    // Display the JSON:API using a details element.
    $build = [
      '#type' => 'details',
      '#title' => $this->t('Schema.org JSON:API'),
      '#weight' => 1010,
      '#attributes' => [
        'data-schemadotorg-details-key' => 'schemadotorg-jsonapi-preview',
        'class' => ['schemadotorg-jsonapi-preview'],
      ],
      '#attached' => ['library' => ['schemadotorg_jsonapi_preview/schemadotorg_jsonapi_preview']],
    ];

    // Make the JSON pretty and enhance it.
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    // Escape HTML special characters.
    $json_markup = htmlspecialchars($json);
    // Add <span> tag to properties.
    $json_markup = preg_replace('/&quot;([^&]+)&quot;: /', '<span>&quot;$1&quot;</span>: ', $json_markup);
    // Add links to URLs.
    $json_markup = preg_replace('@(https?://([-\w.]+)+(:\d+)?(/([\w/_.-]*(\?\S+)?)?)?)@', '<a href="$1">$1</a>', $json_markup);
    $build['json'] = [
      '#type' => 'html_tag',
      '#tag' => 'pre',
      '#attributes' => ['class' => ['schemadotorg-jsonapi-preview-code']],
      '#value' => $json_markup,
    ];

    // JSON:API endpoint.
    $entity_type_id = $entity->getEntityTypeId();
    $jsonapi_url = $this->entityToJsonApiUrl($entity, $includes);
    // Allow other modules to link to additional endpoints.
    $build['endpoints'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['schemadotorg-jsonapi-preview-endpoints']],
    ];
    $build['endpoints'][$entity_type_id] = [
      '#type' => 'item',
      '#title' => $this->t('JSON:API endpoint'),
      '#wrapper_attributes' => ['class' => ['container-inline']],
      'link' => [
        '#type' => 'link',
        '#url' => $jsonapi_url,
        '#title' => $jsonapi_url->toString(),
      ],
    ];
    return $build;
  }

  /**
   * Return the requested entity's JSON:API URL.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to generate the JSON from.
   * @param string[] $includes
   *   The list of includes.
   *
   * @return \Drupal\Core\Url
   *   The entity's JSON:API URL.
   *
   * @see \Drupal\jsonapi_extras\EntityToJsonApi::normalize
   */
  protected function entityToJsonApiUrl(EntityInterface $entity, array $includes = []): Url {
    $resource_type = $this->resourceTypeRepository->get(
      $entity->getEntityTypeId(),
      $entity->bundle()
    );
    $route_name = sprintf('jsonapi.%s.individual', $resource_type->getTypeName());
    $route_options = ['absolute' => TRUE];
    if ($resource_type->isVersionable() && $entity instanceof RevisionableInterface && $revision_id = $entity->getRevisionId()) {
      $route_options['query']['resourceVersion'] = 'id:' . $revision_id;
    }
    if ($includes) {
      $route_options['query']['include'] = implode(',', $includes);
    }
    return Url::fromRoute($route_name, ['entity' => $entity->uuid()], $route_options);
  }

  /**
   * Returns the entity of the current route.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity or NULL if this is not an entity route.
   *
   * @see metatag_get_route_entity()
   */
  protected function getRouteMatchEntity(): EntityInterface|NULL {
    $route_name = $this->routeMatch->getRouteName();
    if (preg_match('/entity\.(.*)\.(latest[_-]version|canonical)/', $route_name, $matches)) {
      return $this->routeMatch->getParameter($matches[1]);
    }
    else {
      return NULL;
    }
  }

}
