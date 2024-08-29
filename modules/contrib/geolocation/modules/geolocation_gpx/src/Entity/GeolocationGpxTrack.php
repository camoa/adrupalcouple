<?php

namespace Drupal\geolocation_gpx\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the Track entity.
 *
 * @ingroup geolocation_gpx
 *
 * @ContentEntityType(
 *   id = "geolocation_gpx_track",
 *   label = @Translation("Geolocation GPX Track"),
 *   base_table = "geolocation_gpx_track",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 * )
 */
class GeolocationGpxTrack extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Track entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Track entity.'))
      ->setReadOnly(TRUE);

    $fields['track_segments'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Links'))
      ->setDescription(t('Links to external information about the route.'))
      ->setSetting('target_type', 'geolocation_gpx_track_segment')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('GPS name of route.'))
      ->setTranslatable(TRUE);

    $fields['comment'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Comment'))
      ->setDescription(t('GPS comment for route.'))
      ->setTranslatable(TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Text description of route for user. Not sent to GPS.'))
      ->setTranslatable(TRUE);

    $fields['source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source'))
      ->setDescription(t('Source of data. Included to give user some idea of reliability and accuracy of data.'))
      ->setTranslatable(TRUE);

    $fields['link'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Links'))
      ->setDescription(t('Links to external information about the route.'))
      ->setSetting('target_type', 'geolocation_gpx_link')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Number'))
      ->setDescription(t('GPS route number.'));

    $fields['type'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Type'))
      ->setDescription(t('Type (classification) of route.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities): void {
    /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpxTrack $track */
    foreach ($entities as $track) {

      foreach ($track->link as $link) {
        $link->entity?->delete();
      }

      foreach ($track->track_segments as $segment) {
        $segment->entity?->delete();
      }
    }
    parent::preDelete($storage, $entities);
  }

  /**
   * Get name to display for element.
   *
   * @return string
   *   Name.
   */
  public function getDisplayName(): string {
    return $this->name->value ?? '';
  }

  /**
   * Get segments.
   *
   * @return \Drupal\geolocation_gpx\Entity\GeolocationGpxTrackSegment[]
   *   Segements.
   */
  public function getSegments(): array {
    return $this->get('track_segments')->referencedEntities();
  }

}
