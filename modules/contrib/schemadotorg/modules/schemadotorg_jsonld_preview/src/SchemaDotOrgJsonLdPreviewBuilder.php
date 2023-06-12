<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonld_preview;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface;

/**
 * Schema.org JSON-LD preview builder.
 */
class SchemaDotOrgJsonLdPreviewBuilder implements SchemaDotOrgJsonLdPreviewBuilderInterface {
  use StringTranslationTrait;

  /**
   * Constructs a SchemaDotOrgJsonLdPreviewBuilder object.
   *
   * @param \Drupal\Core\Routing\AccessAwareRouterInterface $routeProvider
   *   The route provider.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface $schemaJsonLdManager
   *   The Schema.org JSON-LD manager service.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface $schemaJsonLdBuilder
   *   The Schema.org JSON-LD builder service.
   */
  public function __construct(
    protected AccessAwareRouterInterface $routeProvider,
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgJsonLdManagerInterface $schemaJsonLdManager,
    protected SchemaDotOrgJsonLdBuilderInterface $schemaJsonLdBuilder
  ) {}

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // Build the entity's Schema.org data.
    $data = $this->schemaJsonLdBuilder->build();
    if (!$data) {
      return [];
    }

    // Display the JSON-LD using a details element.
    $build = [
      '#type' => 'details',
      '#title' => $this->t('Schema.org JSON-LD'),
      '#weight' => 1020,
      '#attributes' => [
        'data-schemadotorg-details-key' => 'schemadotorg-jsonld-preview',
        'class' => ['schemadotorg-jsonld-preview', 'js-schemadotorg-jsonld-preview'],
      ],
      '#attached' => ['library' => ['schemadotorg_jsonld_preview/schemadotorg_jsonld_preview']],
    ];

    // Make it easy for someone to copy the JSON.
    $t_args = [
      ':schema_href' => 'https://validator.schema.org/',
      ':google_href' => 'https://search.google.com/test/rich-results',
    ];
    $description = $this->t('Please copy-n-paste the below JSON-LD into the <a href=":schema_href">Schema Markup Validator</a> or  <a href=":google_href">Google\'s Rich Results Test</a>.', $t_args);
    $build['copy'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['schemadotorg-jsonld-preview-copy']],
      'description' => [
        '#type' => 'container',
        '#markup' => $description,
      ],
      'button' => [
        '#type' => 'button',
        '#button_type' => 'small',
        '#attributes' => ['class' => ['schemadotorg-jsonld-preview-copy-button', 'button--extrasmall']],
        '#value' => $this->t('Copy JSON-LD'),
      ],
      'message' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => ['class' => ['schemadotorg-jsonld-preview-copy-message']],
        '#plain_text' => $this->t('JSON-LD copied to clipboard…'),
      ],
    ];

    // JSON.
    // Make the JSON pretty and enhance it.
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    // Escape HTML special characters.
    $json_markup = htmlspecialchars($json);
    // Add <span> tag to properties.
    $json_markup = preg_replace('/&quot;([^&]+)&quot;: /', '<span>&quot;$1&quot;</span>: ', $json_markup);
    // Add links to URLs.
    $json_markup = preg_replace('@(https?://([-\w.]+)+(:\d+)?(/([\w/_.-]*(\?\S+)?)?)?)@', '<a href="$1">$1</a>', $json_markup);
    $build['json'] = [
      'input' => [
        '#type' => 'hidden',
        '#value' => $json,
      ],
      'code' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#attributes' => ['class' => ['schemadotorg-jsonld-preview-code']],
        '#value' => $json_markup,
      ],
    ];

    // JSON-LD endpoint.
    // @see schemadotorg_jsonld_endpoint.module
    $entity = $this->schemaJsonLdManager->getRouteMatchEntity();
    if ($entity && $this->moduleHandler->moduleExists('schemadotorg_jsonld_endpoint')) {
      $entity_type_id = $entity->getEntityTypeId();
      $route_name = 'schemadotorg_jsonld_endpoint.' . $entity_type_id;
      $route_parameters = ['entity' => $entity->uuid()];
      $route_options = ['absolute' => TRUE];

      // Make sure the JSON-LD route exists.
      // @see \Drupal\schemadotorg_jsonld_endpoint\Routing\SchemaDotOrgJsonLdEndpointRoutes::routes
      if ($this->routeProvider->getRouteCollection()->get($route_name)) {
        $jsonld_url = Url::fromRoute($route_name, $route_parameters, $route_options);

        // Allow other modules to link to additional endpoints.
        // @see schemadotorg_taxonomy_entity_view_alter()
        $build['endpoints'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['schemadotorg-jsonld-preview-endpoints']],
        ];
        $build['endpoints'][$entity_type_id] = [
          '#type' => 'item',
          '#title' => $this->t('JSON-LD endpoint'),
          '#wrapper_attributes' => ['class' => ['container-inline']],
          'link' => [
            '#type' => 'link',
            '#url' => $jsonld_url,
            '#title' => $jsonld_url->toString(),
          ],
        ];
      }
    }
    return $build;
  }

}
