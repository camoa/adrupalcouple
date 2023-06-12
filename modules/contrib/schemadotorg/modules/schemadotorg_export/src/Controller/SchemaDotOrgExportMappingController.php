<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Returns responses for Schema.org mapping export.
 */
class SchemaDotOrgExportMappingController extends ControllerBase {

  /**
   * Returns response for Schema.org mapping export request.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   A streamed HTTP response containing a Schema.org mapping CSV export.
   */
  public function index(): StreamedResponse {
    $response = new StreamedResponse(function (): void {
      $subtype_installed = $this->moduleHandler()->moduleExists('schemadotorg_subtype');

      $handle = fopen('php://output', 'r+');

      // Header.
      $header = [];
      $header[] = 'entity_type';
      $header[] = 'bundle';
      $header[] = 'schema_type';
      if ($subtype_installed) {
        $header[] = 'schema_subtyping';
      }
      $header[] = 'schema_properties';
      fputcsv($handle, $header);

      // Rows.
      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $mappings */
      $mappings = $this->entityTypeManager()->getStorage('schemadotorg_mapping')->loadMultiple();
      foreach ($mappings as $mapping) {
        $row = [];
        $row[] = $mapping->getTargetEntityTypeId();
        $row[] = $mapping->getTargetBundle();
        $row[] = $mapping->getSchemaType();
        if ($subtype_installed) {
          $row[] = ($mapping->getSchemaPropertyFieldName('subtype')) ? $this->t('Yes') : $this->t('No');
        }
        $row[] = implode('; ', $mapping->getSchemaProperties());
        fputcsv($handle, $row);
      }
      fclose($handle);
    });

    $response->headers->set('Content-Type', 'application/force-download');
    $response->headers->set('Content-Disposition', 'attachment; filename="schemadotorg_mapping.csv"');
    return $response;
  }

}
