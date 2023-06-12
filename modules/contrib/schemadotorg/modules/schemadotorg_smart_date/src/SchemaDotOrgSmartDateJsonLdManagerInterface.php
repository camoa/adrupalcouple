<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_smart_date;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Schema.org Smart Date JSON-LD manager interface.
 */
interface SchemaDotOrgSmartDateJsonLdManagerInterface {

  /**
   * Alter the Schema.org JSON-LD date to include additional Smart Date data.
   *
   * @param array &$data
   *   The JSON-LD date.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The Smart Date field items.
   *
   * @see datetime_range_schemadotorg_jsonld_schema_type_field_alter()
   */
  public function alterProperties(array &$data, FieldItemListInterface $items): void;

}
