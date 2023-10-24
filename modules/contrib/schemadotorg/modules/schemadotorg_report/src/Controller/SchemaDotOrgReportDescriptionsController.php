<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_report\Controller;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Schema.org report descriptions routes.
 */
class SchemaDotOrgReportDescriptionsController extends SchemaDotOrgReportControllerBase {

  /**
   * The route match.
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * Builds the Schema.org types or properties descriptions.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $table
   *   Schema.org types and properties table.
   *
   * @return array
   *   A renderable array containing Schema.org types or properties
   *   descriptions.
   */
  public function index(Request $request, string $table): array {
    $id = $request->query->get('id');
    $descriptions_installed = $this->moduleHandler()->moduleExists('schemadotorg_descriptions');

    // Header.
    $header = [];
    $header['label'] = [
      'data' => $this->t('Label'),
      'width' => '20%',
    ];
    $header['comment'] = [
      'data' => $this->t('Default description'),
      'width' => '40%',
    ];
    if ($descriptions_installed) {
      $header['custom_description'] = [
        'data' => $this->t('Custom description'),
        'width' => '40%',
      ];
    }

    // Base query.
    $base_query = $this->database->select('schemadotorg_' . $table, $table);
    $base_query->fields($table, ['label', 'comment']);
    $base_query->orderBy('label');
    if ($id) {
      $or = $base_query->orConditionGroup()
        ->condition('label', '%' . $id . '%', 'LIKE')
        ->condition('comment', '%' . $id . '%', 'LIKE');
      $base_query->condition($or);
    }

    // Total.
    $total_query = clone $base_query;
    $count = $total_query->countQuery()->execute()->fetchField();

    // Result.
    $result_query = clone $base_query;
    $result = $result_query->execute();

    // Rows.
    $default_types = $this->config('schemadotorg.settings')
      ->get('schema_types.default_types');

    $custom_descriptions = $this->config('schemadotorg_descriptions.settings')
      ->get('custom_descriptions') ?: [];
    $rows = [];
    while ($record = $result->fetchAssoc()) {
      $label = $record['label'];
      $comment = $default_types[$label]['description'] ?? $record['comment'];
      $custom_description = $custom_descriptions[$label] ?? '';

      $row = [];
      $row['label'] = $this->buildTableCell('label', $label);
      $row['comment'] = $this->buildTableCell('comment', $comment);
      if ($descriptions_installed) {
        $row['custom_description'] = [
          'data' => [
            '#markup' => $this->schemaTypeBuilder->formatComment($custom_description),
          ],
        ];
      }

      if ($custom_description) {
        $rows[] = ['data' => $row, 'class' => ['color-warning']];
      }
      else {
        $rows[] = $row;
      }
    }

    $t_args = [
      '@type' => ($table === 'types') ? $this->t('types') : $this->t('properties'),
    ];

    $build = parent::buildHeader($table);

    $build['info'] = $this->buildInfo($table, $count);
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#empty' => $this->t('No @type found.', $t_args),
    ];
    $build['pager'] = [
      '#type' => 'pager',
      // Use the <current> route to make sure pager links works as expected
      // in a modal.
      // @see Drupal.behaviors.schemaDotOrgDialog
      '#route_name' => '<current>',
    ];
    return $build;
  }

}
