<?php

namespace Drupal\geolocation_geometry\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\geolocation_geometry\GeometryFormat\GeoJSON;
use Drupal\geolocation_geometry\GeometryFormat\WKT;

/**
 * Plugin implementation of the 'geolocation_wkt' widget.
 *
 * @FieldWidget(
 *   id = "geolocation_geometry_wkt",
 *   label = @Translation("Geolocation Geometry WKT"),
 *   field_types = {
 *     "geolocation_geometry_geometry",
 *     "geolocation_geometry_geometrycollection",
 *     "geolocation_geometry_point",
 *     "geolocation_geometry_linestring",
 *     "geolocation_geometry_polygon",
 *     "geolocation_geometry_multipoint",
 *     "geolocation_geometry_multilinestring",
 *     "geolocation_geometry_multipolygon",
 *   }
 * )
 */
class GeolocationGeometryWKTWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {

    $description_link = Link::fromTextAndUrl($this->t('WKT data'), Url::fromUri('//en.wikipedia.org/wiki/Well-known_text', ['attributes' => ['target' => '_blank']]))->toString();

    $geometry = GeoJSON::geometryByText($items[$delta]->geojson ?? '');

    $element['wkt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('WKT / Well Known Text') . '(' . $element['#title'] . ')',
      '#default_value' => $geometry?->toWKT() ?? '',
      '#empty_value' => '',
      '#description' => $this->t('Please enter valid %wikipedia.', ['%wikipedia' => $description_link]),
      '#required' => $element['#required'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $values = parent::massageFormValues($values, $form, $form_state);

    foreach ($values as &$value) {
      $geometry = WKT::geometryByText($value['wkt']);
      $value['geojson'] = $geometry?->toGeoJSON() ?? '';
    }

    return $values;
  }

}
