<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_starterkit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Schema.org Blueprints Starter Kit routes.
 */
class SchemadotorgStarterkitController extends ControllerBase {

  /**
   * The module list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * The Schema.org starterkit manager service.
   *
   * @var \Drupal\schemadotorg_starterkit\SchemaDotOrgStarterkitManagerInterface
   */
  protected $schemaStarterkitManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
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

      $row = [];
      $row['title'] = $title;
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
   * Get a starterkit's operations based on its status.
   *
   * @param string $module_name
   *   The name of the starterkit.
   * @param array $options
   *   An array of route options.
   *
   * @return array
   *   A starterkit's operations based on its status.
   */
  protected function getOperations(string $module_name, array $options = []): array {
    $operations = [];
    if (!$this->moduleHandler()->moduleExists($module_name)) {
      if ($this->currentUser()->hasPermission('administer modules')) {
        $operations['install'] = $this->t('Install starterkit');
      }
    }
    elseif ($this->moduleHandler()->moduleExists('devel_generate')) {
      $settings = $this->schemaStarterkitManager->getStarterkitSettings($module_name);
      if (!empty($settings['types'])) {
        $operations['generate'] = $this->t('Generate content');
        $operations['kill'] = $this->t('Kill content');
      }
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
