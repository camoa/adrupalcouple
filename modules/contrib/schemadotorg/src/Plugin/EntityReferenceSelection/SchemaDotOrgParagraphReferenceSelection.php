<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Paragraph plugin implementation of the Schema.org Entity Selection plugin.
 *
 * The paragraph reference selection plugin must support the auto create
 * interface for adding new paragraphs.
 *
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface
 * @see \Drupal\paragraphs\Plugin\EntityReferenceSelection\ParagraphSelection
 *
 * @EntityReferenceSelection(
 *   id = "schemadotorg:paragraph",
 *   label = @Translation("Schema.org Paragraphs Selection"),
 *   entity_types = {"paragraph"},
 *   group = "schemadotorg",
 *   weight = 1,
 * )
 */
class SchemaDotOrgParagraphReferenceSelection extends SchemaDotOrgEntityReferenceSelection implements SelectionWithAutocreateInterface {

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection::createNewEntity
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $values = [
      $entity_type->getKey('label') => $label,
    ];

    if ($bundle_key = $entity_type->getKey('bundle')) {
      $values[$bundle_key] = $bundle;
    }

    $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($values);

    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId($uid);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection::validateReferenceableNewEntities
   */
  public function validateReferenceableNewEntities(array $entities) {
    return array_filter($entities, function ($entity) {
      $target_bundles = $this->getConfiguration()['target_bundles'];
      if (isset($target_bundles)) {
        return in_array($entity->bundle(), $target_bundles);
      }
      return TRUE;
    });
  }

  /**
   * {@inheritdoc}
   */
  public static function getTargetBundles(array $configuration): array {
    $target_bundles = parent::getTargetBundles($configuration);

    // Track if 'from_library' is being used and make sure to include it.
    $from_library = $configuration['target_bundles']['from_library'] ?? FALSE;
    if ($from_library) {
      $target_bundles['from_library'] = 'from_library';
    }

    return $target_bundles;
  }

}
