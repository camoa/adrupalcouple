<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_taxonomy;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface;
use Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Schema.org taxonomy JSON-LD manager.
 */
class SchemaDotOrgTaxonomyJsonLdManager implements SchemaDotOrgTaxonomyJsonLdManagerInterface {
  use StringTranslationTrait;

  /**
   * Constructs a SchemaDotOrgTaxonomyJsonLdManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface|null $schemaJsonLdManager
   *   The Schema.org JSON-LD manager service.
   * @param \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface|null $schemaJsonLdBuilder
   *   The Schema.org JSON-LD builder service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?SchemaDotOrgJsonLdManagerInterface $schemaJsonLdManager = NULL,
    protected ?SchemaDotOrgJsonLdBuilderInterface $schemaJsonLdBuilder = NULL) {}

  /**
   * {@inheritdoc}
   */
  public function load(array &$data, EntityInterface $entity): void {
    if (!$entity instanceof VocabularyInterface) {
      return;
    }

    // Alter a vocabulary's Schema.org type data to use DefinedTermSet @type.
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $mappings */
    $mappings = $this->getMappingStorage()->loadByProperties([
      'target_entity_type_id' => 'taxonomy_term',
      'target_bundle' => $entity->id(),
    ]);
    if (!$mappings) {
      return;
    }

    $mapping = reset($mappings);
    $schema_type = $mapping->getSchemaType();
    $data['@type'] = "{$schema_type}Set";
    $data['name'] = $entity->label();
    if ($entity->getDescription()) {
      $data['description'] = $entity->getDescription();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alter(array &$data, EntityInterface $entity): void {
    if (!$entity instanceof TermInterface) {
      return;
    }

    // Alter a term's Schema.org type data to include isDefinedTermSet property.
    $mapping = $this->getMappingStorage()->loadByEntity($entity);
    if (!$mapping) {
      return;
    }

    // Check that the term is mapping to a DefinedTerm or CategoryCode.
    $schema_type = $mapping->getSchemaType();
    $is_defined_term = in_array($schema_type, ['DefinedTerm', 'CategoryCode']);
    if (!$is_defined_term) {
      return;
    }

    // Append isDefinedTermSet or isCategoryCodeSet data to the type data.
    $vocabulary = $entity->get('vid')->entity;
    $vocabulary_data = $this->schemaJsonLdBuilder->buildEntity($vocabulary);
    $data["in{$schema_type}Set"] = $vocabulary_data;
  }

  /**
   * Preprocess HTML alter JSON-LD Term endpoint.
   *
   * @param array $variables
   *   An array of variables.
   */
  public function preprocessHtml(array &$variables): void {
    if (empty($this->schemaJsonLdBuilder)) {
      return;
    }

    // Make sure the current route's entity is a taxonomy term.
    $route_entity = $this->schemaJsonLdManager->getRouteMatchEntity();
    if (!$route_entity instanceof TermInterface) {
      return;
    }

    // Get JSON-LD endpoint render array.
    $build_endpoints = &NestedArray::getValue($variables, ['page', 'content', 'schemadotorg_jsonld_preview', 'endpoints']);

    // Make sure the Schema.org JSON-LD taxonomy term preview with
    // endpoints exists.
    if (!$build_endpoints || !isset($build_endpoints['taxonomy_term'])) {
      return;
    }

    // Alter the term's JSON-LD preview title to be more specific.
    $build_endpoints['taxonomy_term']['#title'] = $this->t('JSON-LD Term endpoint');

    // Append the vocabulary's JSON-LD preview link.
    $vocabulary = $route_entity->get('vid')->entity;
    $jsonld_url = Url::fromRoute(
      'schemadotorg_jsonld_endpoint.taxonomy_vocabulary',
      ['entity' => $vocabulary->uuid()],
      ['absolute' => TRUE],
    );
    $build_endpoints['taxonomy_vocabulary'] = [
      '#type' => 'item',
      '#title' => $this->t('JSON-LD Vocabulary endpoint'),
      '#wrapper_attributes' => ['class' => ['container-inline']],
      'link' => [
        '#type' => 'link',
        '#url' => $jsonld_url,
        '#title' => $jsonld_url->toString(),
      ],
    ];
  }

  /**
   * Gets the Schema.org mapping storage.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface|\Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   *   The Schema.org mapping storage
   */
  protected function getMappingStorage(): SchemaDotOrgMappingStorageInterface|ConfigEntityStorageInterface {
    return $this->entityTypeManager->getStorage('schemadotorg_mapping');
  }

}
