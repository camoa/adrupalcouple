<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_report\Controller;

use Drupal\Core\Link;
use Drupal\field_ui\FieldUI;
use Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface;

/**
 * Returns responses for Schema.org report mapping routes.
 */
class SchemaDotOrgReportMappingsController extends SchemaDotOrgReportControllerBase {

  /**
   * Builds the Schema.org mapping recommendations.
   *
   * @return array
   *   A renderable array containing the Schema.org mapping recommendations.
   */
  public function recommendations(): array {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager()->getStorage('schemadotorg_mapping_type');

    $header = [
      ['data' => $this->t('Type'), 'width' => '20%'],
      ['data' => $this->t('Breadcrumb'), 'width' => '20%'],
      ['data' => $this->t('Default Properties'), 'width' => '40%'],
      ['data' => $this->t('Unsorted Properties'), 'width' => '20%'],
    ];

    $build = parent::buildHeader();

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface[] $mapping_types */
    $mapping_types = $mapping_type_storage->loadMultiple();
    foreach ($mapping_types as $entity_type_id => $mapping_type) {
      $recomended_types = $mapping_type->getRecommendedSchemaTypes();
      if (empty($recomended_types)) {
        continue;
      }

      $build[$entity_type_id] = [
        '#type' => 'details',
        '#title' => $mapping_type->label(),
        '#open' => TRUE,
      ];

      $base_field_mappings = $mapping_type->getBaseFieldMappings();
      $sorted_properties = $this->getSortedProperties($mapping_type);
      $expected_properties = [];

      // Recommended types.
      foreach ($recomended_types as $recomendedation_name => $recommendation_type) {
        $rows = [];
        foreach ($recommendation_type['types'] as $type) {
          // Display message when a recommended type does not exist.
          if (!$this->schemaTypeManager->isType($type)) {
            $t_args = [
              '@entity' => $mapping_type->id(),
              '%type' => $type,
            ];
            $message = $this->t('Schema.org type %type does not exists. Please update the @entity recommended types.', $t_args);
            $this->messenger()->addWarning($message);
            continue;
          }

          $properties = $mapping_type->getDefaultSchemaTypeProperties($type);

          $unsorted_properties = array_diff_key($properties, $sorted_properties);
          $base_field_mappings = $mapping_type->getBaseFieldMappings();
          $unsorted_properties = array_diff_key($unsorted_properties, $base_field_mappings);

          $expected_properties += $properties;

          $row = [];
          $row[] = ['data' => $this->schemaTypeBuilder->buildItemsLinks($type)];
          $row[] = ['data' => $this->buildTypeBreadcrumbs($type)];
          $row[] = $properties ? ['data' => $this->schemaTypeBuilder->buildItemsLinks($properties)] : '';
          $row[] = $unsorted_properties ? ['data' => $this->schemaTypeBuilder->buildItemsLinks($unsorted_properties)] : '';
          if (empty($properties) || !empty($unsorted_properties)) {
            $rows[] = ['data' => $row, 'class' => ['color-warning']];
          }
          else {
            $rows[] = $row;
          }
        }

        // Mapping type summary.
        $build[$entity_type_id][$recomendedation_name] = [
          '#type' => 'fieldset',
          '#title' => $recommendation_type['label'],
          '#open' => TRUE,
        ];
        // Recommended types.
        $build[$entity_type_id][$recomendedation_name]['table'] = [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $rows,
        ];
      }

      // Expected properties.
      ksort($expected_properties);
      $build[$entity_type_id]['expected_properties'] = [
        '#type' => 'item',
        '#title' => $this->t('Expected properties'),
        'links' => $this->schemaTypeBuilder->buildItemsLinks($expected_properties),
      ];

      // Sorted properties.
      if ($sorted_properties) {
        $build[$entity_type_id]['sorted_properties'] = [
          '#type' => 'item',
          '#title' => $this->t('Sorted properties'),
          'links' => $this->schemaTypeBuilder->buildItemsLinks($sorted_properties),
        ];

        // Unsorted properties.
        $unsorted_properties = array_diff_key($expected_properties, $sorted_properties);
        $base_field_mappings = $mapping_type->getBaseFieldMappings();
        $unsorted_properties = array_diff_key($unsorted_properties, $base_field_mappings);
        if ($unsorted_properties) {
          ksort($unsorted_properties);
          $build[$entity_type_id]['unsorted_properties'] = [
            '#type' => 'item',
            '#title' => $this->t('Unsorted properties'),
            'links' => $this->schemaTypeBuilder->buildItemsLinks($unsorted_properties),
          ];
        }
      }
    }

    return $build;
  }

  /**
   * Builds the Schema.org mapping relationships.
   *
   * @return array
   *   A renderable array containing the Schema.org mapping relationships.
   *
   * @see \Drupal\schemadotorg\Commands\SchemaDotOrgCommands::repair
   */
  public function relationships(): array {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager()->getStorage('schemadotorg_mapping');

    /** @var \Drupal\field\FieldConfigStorage $field_storage */
    $field_storage = $this->entityTypeManager()->getStorage('field_config');

    $header = [
      $this->t('Entity type'),
      $this->t('Bundle'),
      $this->t('Field name'),
      $this->t('Property'),
      $this->t('Range includes'),
      $this->t('Target type'),
      $this->t('Expected targets'),
      $this->t('Actual targets'),
      $this->t('Operation'),
    ];

    $entity_ids = $field_storage->getQuery()
      ->condition('field_type', ['entity_reference', 'entity_reference_revisions'], 'IN')
      ->sort('id')
      ->accessCheck(FALSE)
      ->execute();
    /** @var \Drupal\Core\Field\FieldConfigInterface[] $fields */
    $fields = $field_storage->loadMultiple($entity_ids);
    $rows = [];
    foreach ($fields as $field) {
      $field_name = $field->getName();
      $entity_type_id = $field->getTargetEntityTypeId();
      $bundle = $field->getTargetBundle();
      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
      $mapping = $mapping_storage->load("$entity_type_id.$bundle");
      if (!$mapping) {
        continue;
      }

      $schema_type = $mapping->getSchemaType();
      $schema_property = $mapping->getSchemaPropertyMapping($field_name);
      if (!$schema_property) {
        continue;
      }

      // Get range includes.
      $range_includes = $mapping_storage->getSchemaPropertyRangeIncludes($schema_type, $schema_property);

      // Get expected target bundles.
      $target_type = $field->getSetting('target_type');
      $expected_target_bundles = $mapping_storage->getSchemaPropertyTargetBundles($target_type, $schema_type, $schema_property);

      // Get actual target bundles.
      $handler_settings = $field->getSetting('handler_settings');
      $actual_target_bundles = $handler_settings['target_bundles'] ?? [];

      // Manually sync paragraph:from_library.
      if ($target_type === 'paragraph' && isset($actual_target_bundles['from_library'])) {
        $expected_target_bundles['from_library'] = 'from_library';
      }

      // Manage link.
      if ($route_info = FieldUI::getOverviewRouteInfo($entity_type_id, $bundle)) {
        $link = Link::fromTextAndUrl($this->t('Manage'), $route_info)->toRenderable()
          + ['#attributes' => ['class' => ['button', 'button--small']]];
      }
      else {
        $link = [];
      }

      $row = [];
      $row[] = $entity_type_id;
      $row[] = $bundle;
      $row[] = $field_name;
      $row[] = $schema_property;
      $row[] = implode('; ', $range_includes);
      $row[] = $target_type;
      $row[] = implode('; ', $expected_target_bundles);
      $row[] = implode('; ', $actual_target_bundles);
      $row[] = ['data' => $link];
      if ($expected_target_bundles != $actual_target_bundles) {
        $rows[] = ['data' => $row, 'class' => ['color-warning']];
      }
      else {
        $rows[] = $row;
      }
    }

    $build = [];
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are no Schema.org relationships yet.'),
      '#sticky' => TRUE,
    ];
    return $build;
  }

  /**
   * Get sorted properties for a mapping type.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface $mapping_type
   *   The Schema.org mapping type.
   *
   * @return array
   *   The sorted properties for a mapping type.
   */
  protected function getSortedProperties(SchemaDotOrgMappingTypeInterface $mapping_type): array {
    $sorted_properties = [];

    // Get properties from default field groups from
    // the schemadotorg_field_group.module.
    $entity_type_id = $mapping_type->get('target_entity_type_id');
    $groups = $this->config('schemadotorg_field_group.settings')
      ->get('default_field_groups.' . $entity_type_id) ?? [];
    foreach ($groups as $group) {
      $group_properties = array_combine($group['properties'], $group['properties']);
      $duplicate_properties = array_intersect_key($sorted_properties, $group_properties);
      if ($duplicate_properties) {
        $t_args = [
          '@entity_type' => $mapping_type->label(),
          '@group_label' => $group['label'],
          '@properties' => implode(', ', $duplicate_properties),
        ];
        $message = $this->t('Group @entity_type:@group_label has duplicate properties @properties', $t_args);
        $this->messenger()->addWarning($message);
      }
      $sorted_properties += $group_properties;
    }

    // Get properties from default field weights.
    $default_field_weights = $this->config('schemadotorg.settings')
      ->get('schema_properties.default_field_weights');
    $sorted_properties += array_combine($default_field_weights, $default_field_weights);

    return $sorted_properties;
  }

}
