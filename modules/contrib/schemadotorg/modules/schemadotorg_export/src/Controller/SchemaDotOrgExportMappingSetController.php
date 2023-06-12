<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Schema.org mapping set export.
 */
class SchemaDotOrgExportMappingSetController extends ControllerBase {


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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->schemaMappingManager = $container->get('schemadotorg.mapping_manager');
    $instance->schemaMappingSetManager = $container->get('schemadotorg_mapping_set.manager');
    return $instance;
  }

  /**
   * Returns response for Schema.org mapping set CSV export request.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   A streamed HTTP response containing a Schema.org mapping set CSV export.
   */
  public function overview(): StreamedResponse {
    $response = new StreamedResponse(function (): void {
      $handle = fopen('php://output', 'r+');

      // Header.
      fputcsv($handle, [
        'title',
        'name',
        'types',
      ]);

      // Rows.
      $mapping_sets = $this->config('schemadotorg_mapping_set.settings')->get('sets') ?? [];
      foreach ($mapping_sets as $name => $mapping_set) {
        fputcsv($handle, [
          $mapping_set['label'],
          $name,
          implode('; ', $mapping_set['types']),
        ]);
      }
      fclose($handle);
    });

    $response->headers->set('Content-Type', 'application/force-download');
    $response->headers->set('Content-Disposition', 'attachment; filename="schemadotorg_mapping_set.csv"');
    return $response;
  }

  /**
   * Returns response for Schema.org mapping set CSV export request.
   *
   * @param string $name
   *   The name of the Schema.org mapping set.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   A streamed HTTP response containing a Schema.org mapping set CSV export.
   */
  public function details(string $name): StreamedResponse {
    $mapping_set = $this->config('schemadotorg_mapping_set.settings')->get("sets.$name");
    if (empty($mapping_set)) {
      throw new NotFoundHttpException();
    }

    $response = new StreamedResponse(function () use ($mapping_set): void {
      $handle = fopen('php://output', 'r+');

      // Header.
      fputcsv($handle, [
        'schema_type',
        'entity_type',
        'entity_bundle',
        'field_label',
        'field_description',
        'schema_property',
        'field_name',
        'existing_field',
        'field_type',
        'unlimited_field',
        'required_field',
      ]);

      // Rows.
      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
      $mapping_storage = $this->entityTypeManager()->getStorage('schemadotorg_mapping');

      $types = $mapping_set['types'];
      foreach ($types as $type) {
        if (!$this->schemaMappingSetManager->isValidType($type)) {
          continue;
        }

        [$entity_type_id, $schema_type] = explode(':', $type);

        $mapping = $mapping_storage->loadBySchemaType($entity_type_id, $schema_type);
        $mapping_defaults = $this->schemaMappingManager->getMappingDefaults($entity_type_id, NULL, $schema_type);
        $bundle = ($mapping) ? $mapping->getTargetBundle() : $mapping_defaults['entity']['id'];

        // Properties.
        $field_prefix = $this->config('schemadotorg.settings')
          ->get('field_prefix');
        foreach ($mapping_defaults['properties'] as $property_name => $property_definition) {
          if (empty($property_definition['name'])) {
            continue;
          }
          $record = [];
          $record[] = $schema_type;
          $record[] = $entity_type_id;
          $record[] = $bundle;
          $record[] = $property_definition['label'];
          $record[] = $property_definition['description'];
          $record[] = $property_name;
          if ($property_definition['name'] === SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD) {
            $record[] = $field_prefix . '_' . $property_definition['machine_name'];
            $record[] = $this->t('No');
          }
          else {
            $record[] = $property_definition['name'];
            $record[] = $this->t('Yes');
          }
          $record['type'] = $property_definition['type'];
          $record['unlimited'] = !empty($property_definition['unlimited']) ? $this->t('Yes') : $this->t('No');
          $record['required'] = !empty($property_definition['required']) ? $this->t('Yes') : $this->t('No');

          fputcsv($handle, $record);
        }
      }
    });

    $response->headers->set('Content-Type', 'application/force-download');
    $response->headers->set('Content-Disposition', 'attachment; filename="schemadotorg_mapping_set_' . $name . '.csv"');
    return $response;
  }

}
