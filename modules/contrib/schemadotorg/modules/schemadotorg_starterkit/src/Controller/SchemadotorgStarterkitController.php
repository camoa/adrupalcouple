<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_starterkit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg\Traits\SchemaDotOrgBuildTrait;
use Drupal\schemadotorg_starterkit\SchemaDotOrgStarterkitManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Schema.org Blueprints Starter Kit routes.
 */
class SchemadotorgStarterkitController extends ControllerBase {
  use SchemaDotOrgBuildTrait;

  /**
   * The Schema.org schema type manager.
   */
  protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager;

  /**
   * The Schema.org schema type builder.
   */
  protected SchemaDotOrgSchemaTypeBuilderInterface $schemaTypeBuilder;

  /**
   * The module list service.
   */
  protected ModuleExtensionList $moduleList;

  /**
   * The Schema.org starter kit manager service.
   */
  protected SchemaDotOrgStarterkitManagerInterface $schemaStarterkitManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    $instance->schemaTypeBuilder = $container->get('schemadotorg.schema_type_builder');
    $instance->moduleList = $container->get('extension.list.module');
    $instance->schemaStarterkitManager = $container->get('schemadotorg_starterkit.manager');
    return $instance;
  }

  /**
   * Builds the response for the starter kits overview page.
   */
  public function overview(): array {
    // Header.
    $header = [
      'title' => ['data' => $this->t('Title'), 'width' => '30%'],
      'installed' => ['data' => $this->t('Installed'), 'width' => '5%'],
      'types' => ['data' => $this->t('Types'), 'width' => '25%'],
      'dependencies' => ['data' => $this->t('Dependencies'), 'width' => '30%'],
      'operations' => ['data' => $this->t('Operations'), 'width' => '10%'],
    ];

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager()->getStorage('schemadotorg_mapping');

    $module_data = $this->moduleList->getList();

    // Rows.
    $rows = [];
    $starterkits = $this->schemaStarterkitManager->getStarterkits();
    foreach ($starterkits as $module_name => $starterkit) {
      $is_installable = TRUE;
      $is_installed = $this->moduleHandler()->moduleExists($module_name);

      $settings = $this->schemaStarterkitManager->getStarterkitSettings($module_name);

      // Skip hidden module.
      if (!empty($module_data[$module_name]->info['hidden'])
        && !drupal_valid_test_ua()) {
        continue;
      }

      // Types.
      $types = [];
      if (!empty($settings['types'])) {
        foreach ($settings['types'] as $type => $type_settings) {
          [$entity_type_id, $schema_type] = explode(':', $type);
          $mapping = $mapping_storage->loadBySchemaType($entity_type_id, $schema_type);
          if ($mapping) {
            if ($mapping->getTargetEntityBundleEntity()) {
              $types[$type] = $mapping->getTargetEntityBundleEntity()
                ->toLink($type, 'edit-form')->toString();
            }
            elseif ($mapping->getTargetEntityTypeId() === 'user') {
              $types[$type] = Link::createFromRoute($type, 'entity.user.admin_form')->toString();
            }
            else {
              $types[$type] = $type;
            }
          }
          else {
            $types[$type] = $type;
          }
        }
      }

      // Dependencies.
      $dependencies = [];
      foreach ($settings['dependencies'] as $dependency) {
        if (isset($module_data[$dependency])) {
          $dependencies[] = $module_data[$dependency]->info['name'];
        }
        else {
          $is_installable = FALSE;
          $dependencies[] = ['#markup' => $dependency . ' <em>(' . $this->t('Missing') . ')</em>'];
        }
      };

      $title = $starterkit['name'];
      $title = str_replace('Schema.org Blueprints Starter Kit: ', '', $title);
      $title = str_replace('Schema.org Blueprints ', '', $title);

      $view_url = Url::fromRoute('schemadotorg_starterkit.details', ['name' => $module_name]);
      $row = [];
      $row['title'] = [
        'data' => [
          'link' => [
            '#type' => 'link',
            '#title' => $title,
            '#url' => $view_url,
          ],
        ],
      ];
      $row['installed'] = $is_installed ? $this->t('Yes') : $this->t('No');
      $row['types'] = [
        'data' => [
          '#markup' => implode(', ', $types),
        ],
      ];
      $row['dependencies'] = [
        'data' => [
          '#theme' => 'item_list',
          '#items' => $dependencies,
        ],
      ];

      $operations = ($is_installable)
        ? $this->getOperations($module_name)
        : [];
      $row['operations'] = ($operations) ? [
        'data' => [
          '#type' => 'operations',
          '#links' => $operations,
        ],
        'style' => 'white-space: nowrap',
      ] : [];
      $rows[] = ($is_installed)
        ? ['data' => $row, 'class' => ['color-success']]
        : $row;
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
   * Builds the response for the starter kit detail page.
   */
  public function details(string $name): array {
    if (!$this->schemaStarterkitManager->isStarterkit($name)) {
      throw new NotFoundHttpException();
    }

    $info = $this->schemaStarterkitManager->getStarterkit($name);

    $build = [];
    $build['#title'] = $info['name'];
    $build['description'] = [
      '#markup' => $info['description'],
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['summary'] = $this->buildSummary($name);
    $build['details'] = $this->buildDetails($name, 'view');
    return $build;
  }

  /**
   * Build a starter kit's summary.
   *
   * @param string $name
   *   The starter kit's name.
   *
   * @return array
   *   A renderable array containing a starter kit's summary.
   */
  public function buildSummary(string $name): array {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager()
      ->getStorage('schemadotorg_mapping');

    $settings = $this->schemaStarterkitManager->getStarterkitSettings($name);
    foreach ($settings['types'] as $type => $mapping_defaults) {
      [$entity_type_id, $schema_type] = explode(':', $type);

      $mapping = $mapping_storage->loadBySchemaType($entity_type_id, $schema_type);

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
            '#markup' => $mapping_defaults['entity']['description'],
          ],
        ],
      ];
      $row['status'] = [
        'data' => ($mapping)
          ? $this->t('Exists') :
          [
            '#markup' => $this->t('Missing'),
            '#prefix' => '<em>',
            '#suffix' => '</em>',
          ],
      ];

      $rows[] = [
        'data' => $row,
        'class' => [
          ($mapping) ? 'color-success' : 'color-warning',
        ],
      ];
    }

    $header = [
      'schema_type' => ['data' => $this->t('Schema.org type'), 'width' => '15%'],
      'entity_type' => ['data' => $this->t('Entity label (type) / description'), 'width' => '70%'],
      'status' => ['data' => $this->t('Status'), 'width' => '15%'],
    ];

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

  /**
   * Build a starter kit's details.
   *
   * @param string $name
   *   The starter kit's name.
   * @param string $operation
   *   The current operation.
   *
   * @return array
   *   A renderable array containing a starter kit's details.
   */
  public function buildDetails(string $name, string $operation = 'view'): array {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager()
      ->getStorage('schemadotorg_mapping');

    $build = [];
    $settings = $this->schemaStarterkitManager->getStarterkitSettings($name);
    foreach ($settings['types'] as $type => $mapping_defaults) {
      [$entity_type_id, $schema_type] = explode(':', $type);

      $mapping = $mapping_storage->loadBySchemaType($entity_type_id, $schema_type);

      $details = $this->buildSchemaType($type, $mapping_defaults);
      $details['#title'] .= ' - ' . ($mapping ? $this->t('Exists') : '<em>' . $this->t('Missing') . '</em>');
      $details['#summary_attributes']['class'] = [($mapping) ? 'color-success' : 'color-warning'];
      $build[$type] = $details;
    }
    return $build;
  }

  /**
   * Get a starter kit's operations based on its status.
   *
   * @param string $module_name
   *   The name of the starter kit.
   * @param array $options
   *   An array of route options.
   *
   * @return array
   *   A starter kit's operations based on its status.
   */
  protected function getOperations(string $module_name, array $options = []): array {
    $operations = [];
    if (!$this->moduleHandler()->moduleExists($module_name)) {
      if ($this->currentUser()->hasPermission('administer modules')) {
        $operations['install'] = $this->t('Install starter kit');
      }
    }
    else {
      if ($this->moduleHandler()->moduleExists('devel_generate')) {
        $settings = $this->schemaStarterkitManager->getStarterkitSettings($module_name);
        if (!empty($settings['types'])) {
          $operations['generate'] = $this->t('Generate content');
          $operations['kill'] = $this->t('Kill content');
        }
      }
      $operations['update'] = $this->t('Update starter kit');
    }
    foreach ($operations as $operation => $title) {
      $operations[$operation] = [
        'title' => $title,
        'url' => Url::fromRoute(
          'schemadotorg_starterkit.confirm_form',
          ['name' => $module_name, 'operation' => $operation],
          $options
        ),
      ];
    }
    return $operations;
  }

}
