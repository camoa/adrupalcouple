<?php

namespace Drupal\geolocation_gpx\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'geofield' formatter.
 *
 * @FieldFormatter(
 *   id = "geolocation_gpx_table",
 *   module = "geolocation",
 *   label = @Translation("Geolocation GPX Formatter - Data Table"),
 *   field_types = {
 *     "geolocation_gpx"
 *   }
 * )
 */
class GeolocationGpxTableFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {

    if ($items->count() === 0) {
      return [];
    }

    $element = [];

    foreach ($items as $delta => $item) {
      /** @var \Drupal\geolocation_gpx\Entity\GeolocationGpx $gpx */
      $gpx = \Drupal::entityTypeManager()->getStorage('geolocation_gpx')->load($item->getValue()['gpx_id']);

      if (!$gpx) {
        return [];
      }

      $element[$delta] = [
        'elevation' => $gpx->renderedTracksElevationChart(),
        'summary' => $gpx->renderedSummaryTable(),
      ];
    }

    return $element;
  }

}
