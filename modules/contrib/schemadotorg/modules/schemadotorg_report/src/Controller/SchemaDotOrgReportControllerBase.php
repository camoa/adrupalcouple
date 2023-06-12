<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_report\Controller;

use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base controller for Schema.org report routes.
 */
abstract class SchemaDotOrgReportControllerBase extends ControllerBase {
  use AjaxHelperTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * The Schema.org schema type builder service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface
   */
  protected $schemaTypeBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    $instance->blockManager = $container->get('plugin.manager.block');
    $instance->formBuilder = $container->get('form_builder');
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    $instance->schemaTypeBuilder = $container->get('schemadotorg.schema_type_builder');
    return $instance;
  }

  /**
   * Build Ajax dialog header with local tasks block and filter form.
   *
   * @param string $table
   *   The Schema.org table (types or properties).
   *
   * @return array
   *   A render array containing the Ajax dialog header with local tasks block
   *   and filter form.
   */
  protected function buildHeader(string $table = 'types'): array {
    $build = [];
    $build['#attached']['library'][] = 'schemadotorg_report/schemadotorg_report';
    if (!$this->isAjax()) {
      return $build;
    }

    $build['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['schemadotorg-report-dialog-header', 'clearfix']],
      '#weight' => -20,
    ];

    // Build the local tasks block and make sure it is first.
    $block = $this->blockManager->createInstance('local_tasks_block', ['secondary' => FALSE]);
    $local_tasks_block = $block->build();
    // Limit the local tasks block to Schema.org types and properties.
    $dialog_routes = ['schemadotorg_report.types', 'schemadotorg_report.properties'];
    $local_tasks_block['#primary'] = array_intersect_key(
      $local_tasks_block['#primary'],
      array_combine($dialog_routes, $dialog_routes)
    );
    $build['header']['local_tasks_block'] = $local_tasks_block + ['#weight' => -20];

    $build['header']['filter'] = $this->getFilterForm($table);

    return $build;
  }

  /**
   * Gets Schema.org types or properties filter form.
   *
   * @param string $table
   *   Types or properties table name.
   * @param string|null $id
   *   Type or property to filter by.
   *
   * @return array
   *   The form array.
   */
  protected function getFilterForm(string $table, ?string $id = NULL): array {
    return $this->formBuilder->getForm('\Drupal\schemadotorg_report\Form\SchemaDotOrgReportFilterForm', $table, $id);
  }

  /**
   * Build info.
   *
   * @param string $type
   *   Type of info being displayed.
   * @param int|string $count
   *   The item count to display.
   *
   * @return array
   *   A renderable array containing info.
   */
  protected function buildInfo(string $type, int|string $count): array {
    switch ($type) {
      case 'Thing':
        $info = $this->formatPlural($count, '@count thing', '@count things');
        break;

      case 'Intangible':
        $info = $this->formatPlural($count, '@count intangible', '@count intangibles');
        break;

      case 'Enumeration':
        $info = $this->formatPlural($count, '@count enumeration', '@count enumerations');
        break;

      case 'StructuredValue':
        $info = $this->formatPlural($count, '@count structured value', '@count structured values');
        break;

      case 'DataTypes':
        $info = $this->formatPlural($count, '@count data type', '@count data types');
        break;

      case 'types':
        $info = $this->formatPlural($count, '@count type', '@count types');
        break;

      case 'properties':
        $info = $this->formatPlural($count, '@count property', '@count properties');
        break;

      case 'abbreviations':
        $info = $this->formatPlural($count, '@count abbreviation', '@count abbreviations');
        break;

      default:
        $info = $this->formatPlural($count, '@count item', '@count items');
    }
    return [
      '#markup' => $info,
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
  }

  /**
   * Build a table cell.
   *
   * @param string $name
   *   Table cell name.
   * @param string $value
   *   Table cell value.
   *
   * @return array[]|string
   *   A renderable array containing a table cell.
   */
  protected function buildTableCell(string $name, string $value): array|string {
    switch ($name) {
      case 'comment':
        return ['data' => ['#markup' => $this->schemaTypeBuilder->formatComment($value)]];

      default:
        $links = $this->schemaTypeBuilder->buildItemsLinks($value);
        if (count($links) > 20) {
          return [
            'data' => [
              '#type' => 'details',
              '#title' => $this->t('@count items', ['@count' => count($links)]),
              'content' => $links,
            ],
          ];
        }
        else {
          return ['data' => $links];
        }
    }
  }

  /**
   * Build a reference links.
   *
   * @param array $links
   *   An array of link titles and uris.
   *
   * @return array
   *   A renderable containing reference links.
   */
  protected function buildReportLinks(array $links): array {
    $items = [];
    foreach ($links as $link) {
      $host = parse_url($link['uri'], PHP_URL_HOST);
      $items[] = [
        '#type' => 'link',
        '#title' => $link['title'],
        '#url' => Url::fromUri($link['uri']),
        '#suffix' => ' (' . $host . ')',
      ];
    }
    return $items;
  }

  /**
   * Build Schema.org type breadcrumbs.
   *
   * @param string $type
   *   The Schema.org type.
   *
   * @return array
   *   A renderable containing Schema.org type breadcrumbs.
   */
  protected function buildTypeBreadcrumbs(string $type): array {
    $build = [];
    $breadcrumbs = $this->schemaTypeManager->getTypeBreadcrumbs($type);
    foreach ($breadcrumbs as $breadcrumb_path => $breadcrumb) {
      array_walk($breadcrumb, function (&$type): void {
        $type = Link::fromTextAndUrl($type, $this->schemaTypeBuilder->getItemUrl($type));
      });
      $build[$breadcrumb_path] = [
        '#theme' => 'breadcrumb',
        '#links' => $breadcrumb,
      ];
    }
    return $build;
  }

}
