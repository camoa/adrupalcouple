<?php

namespace Drupal\single_content_sync\Plugin\SingleContentSyncFieldProcessor;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin for the entity reference revisions field processor plugin.
 *
 * @SingleContentSyncFieldProcessor(
 *   id = "entity_reference_revisions",
 *   label = @Translation("Entity reference revisions field processor"),
 *   field_type = "entity_reference_revisions",
 * )
 */
class EntityReferenceRevisions extends EntityReference {

  /**
   * {@inheritdoc}
   */
  public function exportFieldValue(FieldItemListInterface $field): array {
    $value = [];
    $fieldDefinition = $field->getFieldDefinition();

    if ($fieldDefinition->getSetting('target_type') == 'paragraph') {
      $ids = array_column($field->getValue(), 'target_id');
      $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');

      /** @var \Drupal\paragraphs\ParagraphInterface[] $paragraphs */
      $paragraphs = $paragraph_storage->loadMultiple($ids);

      foreach ($paragraphs as $paragraph) {
        $value[] = $this->exporter->doExportToArray($paragraph);
      }
    }
    else {
      $value = parent::exportFieldValue($field);
    }

    return $value;
  }

}
