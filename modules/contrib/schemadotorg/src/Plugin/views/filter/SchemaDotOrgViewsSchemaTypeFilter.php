<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter class which allows filtering by Schema.org types.
 *
 * @ingroup views_filter_handlers
 * @see \Drupal\views\Plugin\views\filter\Bundle
 *
 * @ViewsFilter("schemadotorg_type")
 */
class SchemaDotOrgViewsSchemaTypeFilter extends InOperator {

  /**
   * The entity type for the filter.
   */
  protected string $entityTypeId;

  /**
   * The entity type definition.
   */
  protected EntityTypeInterface $entityType;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Schema.org schema type manager.
   */
  protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->entityTypeId = $this->getEntityType();
    $this->entityType = $this->entityTypeManager->getDefinition($this->entityTypeId);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueTitle = $this->t('Schema.org type');

      // Get all the available Schema.org types with their parent types.
      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $mappings */
      $mappings = $this->entityTypeManager
        ->getStorage('schemadotorg_mapping')
        ->loadByProperties(['target_entity_type_id' => $this->entityTypeId]);
      $parent_types = [];
      foreach ($mappings as $mapping) {
        $parent_types += $this->schemaTypeManager->getParentTypes($mapping->getSchemaType());
      }

      // Set the options to be hierarchical.
      $this->valueOptions = array_intersect_key(
        $this->schemaTypeManager->getAllTypeChildrenAsOptions('Thing', ['Intangible']),
        $parent_types
      );
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple(): void {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');
    $bundles = $mapping_storage->getRangeIncludesTargetBundles($this->entityTypeId, $this->value, FALSE);

    // Replace $this->value which is Schema.org types with bundles so that
    // parent::opSimple() continues to work as expected.
    $schema_types = $this->value;
    $this->value = $bundles;
    // Make sure that the entity base table is in the query.
    $this->ensureMyTable();
    parent::opSimple();
    $this->value = $schema_types;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    $summary = (string) parent::adminSummary();
    if ($summary) {
      $summary = preg_replace('/ -+ /', ' ', $summary);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $dependencies['module'][] = 'schemadotorg';
    return $dependencies;
  }

}
