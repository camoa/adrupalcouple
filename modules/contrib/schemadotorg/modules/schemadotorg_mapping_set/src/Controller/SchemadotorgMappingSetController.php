<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_mapping_set\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface;
use Drupal\schemadotorg\Utility\SchemaDotOrgStringHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Schema.org Blueprints Mapping Sets routes.
 */
class SchemadotorgMappingSetController extends ControllerBase {

  /**
   * Link options.
   *
   * @var array
   */
  protected $linkOptions = ['attributes' => ['target' => '_blank']];

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The Schema.org mapping manager service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface
   */
  protected $schemaMappingManager;

  /**
   * The Schema.org mapping set manager service.
   *
   * @var \Drupal\schemadotorg_mapping_set\SchemaDotOrgMappingSetManagerInterface
   */
  protected $schemaMappingSetManager;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * The Schema.org schema type builder.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface
   */
  protected $schemaTypeBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->redirectDestination = $container->get('redirect.destination');
    $instance->schemaMappingManager = $container->get('schemadotorg.mapping_manager');
    $instance->schemaMappingSetManager = $container->get('schemadotorg_mapping_set.manager');
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    $instance->schemaTypeBuilder = $container->get('schemadotorg.schema_type_builder');
    return $instance;
  }

  /**
   * Builds the response for the mapping sets overview page.
   */
  public function overview(): array {
    // Header.
    $header = [
      'title' => ['data' => $this->t('Title / Name'), 'width' => '30%'],
      'setup' => ['data' => $this->t('Setup'), 'width' => '10%'],
      'types' => ['data' => $this->t('Types'), 'width' => '50%'],
      'operations' => ['data' => $this->t('Operations'), 'width' => '10%'],
    ];

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager()->getStorage('schemadotorg_mapping');

    // Rows.
    $rows = [];
    $mapping_sets = $this->config('schemadotorg_mapping_set.settings')->get('sets');
    foreach ($mapping_sets as $name => $mapping_set) {
      $is_setup = $this->schemaMappingSetManager->isSetup($name);

      // Types.
      $invalid_types = [];
      $types = $mapping_set['types'];
      foreach ($types as $index => $type) {
        if ($this->schemaMappingSetManager->isValidType($type)) {
          [$entity_type_id, $schema_type] = explode(':', $type);
          $mapping = $mapping_storage->loadBySchemaType($entity_type_id, $schema_type);
          if ($mapping) {
            $entity_type_bundle = $mapping->getTargetEntityBundleEntity();
            $types[$index] = $entity_type_bundle->toLink($type, 'edit-form')->toString();
          }
        }
        else {
          $invalid_types[] = $type;
          $types[$index] = '<strong>' . $type . '</strong>';
        }
      }

      $view_url = Url::fromRoute('schemadotorg_mapping_set.details', ['name' => $name]);
      $row = [];
      $row['title'] = [
        'data' => [
          'link' => [
            '#type' => 'link',
            '#title' => $mapping_set['label'],
            '#url' => $view_url,
          ],
          'name' => [
            '#markup' => ' (' . $name . ')',
          ],
        ],
      ];
      $row['setup'] = $is_setup ? $this->t('Yes') : $this->t('No');
      $row['types'] = ['data' => ['#markup' => implode(', ', $types)]];
      // Only show operation when there are no invalid types.
      if (!$invalid_types) {
        $operations = $this->getOperations($name);
        $operations['view'] = [
          'title' => $this->t('View details'),
          'url' => $view_url,
        ];
        $row['operations'] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $operations,
          ],
          'style' => 'white-space: nowrap',
        ];
      }
      else {
        $row['operations'] = '';
      }

      if ($invalid_types) {
        $rows[] = ['data' => $row, 'class' => ['color-error']];
      }
      elseif ($is_setup) {
        $rows[] = ['data' => $row, 'class' => ['color-success']];
      }
      else {
        $rows[] = $row;
      }

      // Display error message able invalid types.
      if ($invalid_types) {
        $t_args = [
          '%set' => $mapping_set['label'],
          '%types' => implode(', ', $invalid_types),
          ':href' => Url::fromRoute('schemadotorg_mapping_set.settings')->toString(),
        ];
        $message = $this->t('%types in %set are not valid. <a href=":href">Please update this information.</a>', $t_args);
        $this->messenger()->addError($message);
      }
    }

    return [
      'table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ],
    ];
  }

  /**
   * Builds the response for the mapping set detail page.
   */
  public function details(string $name): array {
    $mapping_set = $this->config('schemadotorg_mapping_set.settings')->get("sets.$name");
    if (empty($mapping_set)) {
      throw new NotFoundHttpException();
    }

    $build = [];
    $build['#title'] = $this->t('@label Schema.org mapping set', ['@label' => $mapping_set['label']]);
    $build['summary'] = $this->buildSummary($name);
    $build['details'] = $this->buildDetails($name, 'view');
    return $build;
  }

  /**
   * Build a mapping set's summary.
   *
   * @param string $name
   *   The mapping set's name.
   *
   * @return array
   *   A renderable array containing a mapping set's summary.
   */
  public function buildSummary(string $name): array {
    $mapping_set = $this->config('schemadotorg_mapping_set.settings')->get("sets.$name");

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager()
      ->getStorage('schemadotorg_mapping');

    foreach ($mapping_set['types'] as $type) {
      if (!$this->schemaMappingSetManager->isValidType($type)) {
        continue;
      }
      [$entity_type_id, $schema_type] = explode(':', $type);

      $mapping = $mapping_storage->loadBySchemaType($entity_type_id, $schema_type);
      $mapping_defaults = $this->schemaMappingManager->getMappingDefaults($entity_type_id, NULL, $schema_type);

      if ($mapping) {
        $status = $this->t('Exists');

        $operation = $mapping->getTargetEntityBundleEntity()
          ->toLink($this->t('Edit type'), 'edit-form')
          ->toRenderable();
      }
      else {
        $status = [
          'data' => [
            '#markup' => $this->t('Missing'),
            '#prefix' => '<em>',
            '#suffix' => '</em>',
          ],
        ];

        $bundle_entity_type = $this->entityTypeManager
          ->getDefinition($entity_type_id)
          ->getBundleEntityType();
        $route_name = "schemadotorg.{$bundle_entity_type}.type_add";
        $route_options = [
          'query' => ['type' => $schema_type] + $this->redirectDestination->getAsArray(),
        ];
        $url = Url::fromRoute($route_name, [], $route_options);
        $operation = Link::fromTextAndUrl($this->t('Add type'), $url)->toRenderable();
      }

      $row = [];
      $row['schema_type'] = $schema_type;
      $row['entity_type'] = [
        'data' => [
          'label' => [
            '#markup' => $mapping_defaults['entity']['label'],
            '#prefix' => '<strong>',
            '#suffix' => '</strong> (' . $entity_type_id . ')<br/>',
          ],
          'comment' => [
            '#markup' => SchemaDotOrgStringHelper::getFirstSentence($mapping_defaults['entity']['description']),
          ],
        ],
      ];
      $row['status'] = $status;
      $row['operations'] = [
        'data' => $operation + [
          '#attributes' => [
            'class' => ['button', 'button--extrasmall'],
          ],
        ],
        'style' => 'white-space: nowrap',
      ];

      $rows[] = [
        'data' => $row,
        'class' => [
          ($mapping) ? 'color-success' : 'color-warning',
        ],
      ];
    }

    // Append operations as the last row in the table.
    $rows[] = [
      ['colspan' => 3],
      'operations' => [
        'data' => [
          '#type' => 'operations',
          '#links' => $this->getOperations($name, ['query' => $this->redirectDestination->getAsArray()]),
        ],
        'style' => 'white-space: nowrap',
      ],
    ];

    $header = [
      'schema_type' => ['data' => $this->t('Schema.org type'), 'width' => '15%'],
      'entitu_type' => ['data' => $this->t('Entity label (type) / description'), 'width' => '65%'],
      'status' => ['data' => $this->t('Status'), 'width' => '10%'],
      'operation' => ['data' => $this->t('Operations'), 'width' => '10%'],
    ];

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

  /**
   * Build a mapping set's details.
   *
   * @param string $name
   *   The mapping set's name.
   * @param string $operation
   *   The current operation.
   *
   * @return array
   *   A renderable array containing a mapping set's details.
   */
  public function buildDetails(string $name, string $operation = 'view'): array {
    $mapping_set = $this->config('schemadotorg_mapping_set.settings')->get("sets.$name");

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager()
      ->getStorage('schemadotorg_mapping');

    $build = [];
    foreach ($mapping_set['types'] as $type) {
      if (!$this->schemaMappingSetManager->isValidType($type)) {
        continue;
      }

      [$entity_type_id, $schema_type] = explode(':', $type);

      $mapping = $mapping_storage->loadBySchemaType($entity_type_id, $schema_type);
      $mapping_defaults = $this->schemaMappingManager->getMappingDefaults($entity_type_id, NULL, $schema_type);
      if ($mapping) {
        $entity_type = $mapping->getTargetEntityBundleEntity()
          ->toLink($type, 'edit-form')
          ->toRenderable();
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
      switch ($operation) {
        case 'view':
          $details['#title'] .= ' - ' . ($mapping ? $this->t('Exists') : '<em>' . $this->t('Missing') . '</em>');
          $details['#summary_attributes']['class'] = [($mapping) ? 'color-success' : 'color-warning'];
          break;

        case 'setup':
          $details['#title'] .= ' - ' . ($mapping ? $this->t('Exists') : '<em>' . $this->t('Creating') . '</em>');
          $details['#summary_attributes']['class'] = [($mapping) ? 'color-success' : 'color-warning'];
          break;

        case 'teardown':
          $mapping_sets = $this->schemaMappingSetManager->getMappingSets($entity_type_id, $schema_type, TRUE);
          if (count($mapping_sets) > 1) {
            unset($mapping_sets[$name]);
            $labels = array_map(
              function ($mapping_set) {
                return $mapping_set['label'];
              },
              $mapping_sets
            );
            $t_args = ['%labels' => implode(', ', $labels)];
            $details['#title'] .= ' - ' . $this->t('Used by %labels', $t_args);
            $details['#summary_attributes']['class'] = ['color-warning'];
          }
          break;
      }

      // Entity.
      $details['schema_type'] = [
        '#type' => 'item',
        '#title' => $this->t('Schema.org type'),
        'link' => $this->schemaTypeBuilder->buildItemsLinks($schema_type, $this->linkOptions),
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
      $field_prefix = $this->config('schemadotorg.settings')->get('field_prefix');
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
              '#markup' => $property_definition['description'],
              '#suffix' => '</br>',
            ],
            'range_includes' => $range_includes ? [
              'links' => $this->schemaTypeBuilder->buildItemsLinks($range_includes, $this->linkOptions),
              '#prefix' => '(',
              '#suffix' => ')',
            ] : [],
          ],
        ];
        $row['property'] = $property_name;
        $row['arrow'] = '→';
        if ($property_definition['name'] === SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD) {
          $row['name'] = $field_prefix . $property_definition['machine_name'];
          $row['existing'] = $this->t('No');
        }
        else {
          $row['name'] = $property_definition['name'];
          $row['existing'] = $this->t('Yes');
        }
        $row['type'] = $property_definition['type'];
        $row['unlimited'] = !empty($property_definition['unlimited']) ? $this->t('Yes') : $this->t('No');
        $row['required'] = !empty($property_definition['required']) ? $this->t('Yes') : $this->t('No');
        $rows[] = $row;
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
      $build[$type] = $details;
    }

    $build['#attached']['library'][] = 'schemadotorg/schemadotorg.dialog';

    return $build;
  }

  /**
   * Get a mapping set's operations based on its status.
   *
   * @param string $name
   *   The name of the mapping set.
   * @param array $options
   *   An array of route options.
   *
   * @return array
   *   A mapping set's operations based on its status.
   */
  protected function getOperations(string $name, array $options = []): array {
    $operations = [];

    $is_setup = $this->schemaMappingSetManager->isSetup($name);
    if (!$is_setup) {
      $operations['setup'] = $this->t('Setup types');
    }
    else {
      if ($this->moduleHandler()->moduleExists('devel_generate')) {
        $operations['generate'] = $this->t('Generate content');
        $operations['kill'] = $this->t('Kill content');
      }
      $operations['teardown'] = $this->t('Teardown types');
    }
    foreach ($operations as $operation => $title) {
      $operations[$operation] = [
        'title' => $title,
        'url' => Url::fromRoute(
          'schemadotorg_mapping_set.confirm_form',
          ['name' => $name, 'operation' => $operation],
          $options
        ),
      ];
    }

    return $operations;
  }

}
