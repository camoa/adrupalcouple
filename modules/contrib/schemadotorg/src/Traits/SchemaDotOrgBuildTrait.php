<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Traits;

use Drupal\Core\Link;
use Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface;

/**
 * Trait for building Schema.org types.
 */
trait SchemaDotOrgBuildTrait {

  /**
   * Build a Schema.org type's details.
   *
   * @param string $type
   *   A Schema.org type.
   * @param array $mapping_defaults
   *   A Schema.org type's mapping defaults.
   *
   * @see \Drupal\schemadotorg_starterkit\Form\SchemaDotOrgStarterkitConfirmForm::buildSchemaType
   */
  protected function buildSchemaType(string $type, array $mapping_defaults): array {
    [$entity_type_id, $schema_type] = explode(':', $type);

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager
      ->getStorage('schemadotorg_mapping');

    $mapping = $mapping_storage->loadBySchemaType($entity_type_id, $schema_type);
    if ($mapping) {
      if ($mapping->getTargetEntityBundleEntity()) {
        $entity_type = $mapping->getTargetEntityBundleEntity()
          ->toLink($type, 'edit-form')->toRenderable();
      }
      elseif ($mapping->getTargetEntityTypeId() === 'user') {
        $entity_type = Link::createFromRoute($type, 'entity.user.admin_form')->toRenderable();
      }
      else {
        $entity_type = [
          '#markup' => $entity_type_id . ':' . $mapping_defaults['entity']['id'],
        ];
      }
    }
    else {
      $entity_type = [
        '#markup' => $entity_type_id . ':' . $mapping_defaults['entity']['id'],
      ];
    }

    $t_args = [
      '@label' => $mapping_defaults['entity']['label'],
      '@type' => $type,
    ];
    $details = [
      '#type' => 'details',
      '#title' => $this->t('@label (@type)', $t_args),
    ];

    // Entity.
    $details['schema_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Schema.org type'),
      'link' => $this->schemaTypeBuilder->buildItemsLinks($schema_type, ['attributes' => ['target' => '_blank']]),
    ];
    $details['entity_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Entity type and bundle'),
      'item' => $entity_type,
    ];
    $details['label'] = [
      '#type' => 'item',
      '#title' => $this->t('Entity label'),
      '#markup' => $mapping_defaults['entity']['label'],
    ];

    $details['entity_description'] = [
      '#type' => 'item',
      '#title' => $this->t('Entity description'),
      '#markup' => $mapping_defaults['entity']['description'],
    ];

    // Properties.
    $rows = [];
    /** @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names */
    $schema_names = \Drupal::service('schemadotorg.names');
    $field_prefix = $schema_names->getFieldPrefix();
    foreach ($mapping_defaults['properties'] as $property_name => $property_definition) {
      if (empty($property_definition['name'])) {
        continue;
      }

      if (empty($property_definition['name'])
        || empty($property_definition['label'])) {
        continue;
      }
      $range_includes = $this->schemaTypeManager->getPropertyRangeIncludes($property_name);

      $row = [];
      $row['label'] = [
        'data' => [
          'name' => [
            '#markup' => $property_definition['label'],
            '#prefix' => '<strong>',
            '#suffix' => '</strong></br>',
          ],
          'description' => [
            '#markup' => $property_definition['description'] ?? '',
            '#suffix' => '</br>',
          ],
          'range_includes' => $range_includes ? [
            'links' => $this->schemaTypeBuilder->buildItemsLinks($range_includes, ['attributes' => ['target' => '_blank']]),
            '#prefix' => '(',
            '#suffix' => ')',
          ] : [],
        ],
      ];
      $row['property'] = $property_name;
      $row['arrow'] = '→';
      $exists = ($property_definition['name'] !== SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD);
      if ($exists) {
        $row['name'] = $property_definition['name'];
        $row['existing'] = $this->t('Yes');
      }
      else {
        $row['name'] = $field_prefix . $property_definition['machine_name'];
        $row['existing'] = $this->t('No');
      }
      $row['type'] = $property_definition['type'];
      $row['unlimited'] = !empty($property_definition['unlimited']) ? $this->t('Yes') : $this->t('No');
      $row['required'] = !empty($property_definition['required']) ? $this->t('Yes') : $this->t('No');
      $rows[] = [
        'data' => $row,
        'class' => [$exists ? 'color-success' : 'color-warning'],
      ];
    }
    $details['properties'] = [
      '#type' => 'table',
      '#header' => [
        'label' => ['data' => $this->t('Label / Description'), 'width' => '35%'],
        'property' => ['data' => $this->t('Schema.org property'), 'width' => '15%'],
        'arrow' => ['data' => '', 'width' => '1%'],
        'name' => ['data' => $this->t('Field name'), 'width' => '15%'],
        'existing' => ['data' => $this->t('Existing field'), 'width' => '10%'],
        'type' => ['data' => $this->t('Field type'), 'width' => '15%'],
        'unlimited' => ['data' => $this->t('Unlimited values'), 'width' => '5%'],
        'required' => ['data' => $this->t('Required field'), 'width' => '5%'],
      ],
      '#rows' => $rows,
    ];
    $details['#attached']['library'][] = 'schemadotorg/schemadotorg.dialog';
    return $details;
  }

}
