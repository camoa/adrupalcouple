<?php

namespace Drupal\geolocation\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Plugin implementation of the 'geolocation_map' widget.
 *
 * @FieldWidget(
 *   id = "geolocation_map",
 *   label = @Translation("Geolocation Map"),
 *   field_types = {
 *     "geolocation"
 *   }
 * )
 */
class GeolocationMapWidget extends GeolocationMapWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state): void {
    foreach ($violations as $violation) {
      if ($violation->getMessageTemplate() == 'This value should not be null.') {
        $form_state->setErrorByName($items->getName(), $this->t('No location has been selected yet for required field %field.', ['%field' => $items->getFieldDefinition()->getLabel()]));
      }
    }
    parent::flagErrors($items, $violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();

    $settings['allow_override_map_settings'] = FALSE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);
    $settings = $this->getSettings();

    $element['allow_override_map_settings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow override the map settings when create/edit an content.'),
      '#default_value' => $settings['allow_override_map_settings'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    $settings = $this->getSettings();

    if (!empty($settings['allow_override_map_settings'])) {
      $summary[] = $this->t('Users will be allowed to override the map settings for each content.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $default_field_values = FALSE;

    if (!empty($this->fieldDefinition->getDefaultValueLiteral()[$delta])) {
      $default_field_values = [
        'lat' => $this->fieldDefinition->getDefaultValueLiteral()[$delta]['lat'],
        'lng' => $this->fieldDefinition->getDefaultValueLiteral()[$delta]['lng'],
      ];
    }

    // '0' is an allowed value, '' is not.
    if (
      isset($items[$delta]->lat)
      && isset($items[$delta]->lng)
    ) {
      $default_field_values = [
        'lat' => $items[$delta]->lat,
        'lng' => $items[$delta]->lng,
      ];
    }

    $element = [
      '#type' => 'geolocation_input',
      '#title' => $element['#title'] ?? '',
      '#title_display' => $element['#title_display'] ?? '',
      '#description' => $element['#description'] ?? '',
      '#attributes' => [
        'class' => [
          'geolocation-widget-input',
        ],
      ],
    ];

    if ($default_field_values) {
      $element['#default_value'] = [
        'lat' => $default_field_values['lat'],
        'lng' => $default_field_values['lng'],
      ];
    }

    if (
      $delta == 0
      && $this->getSetting('allow_override_map_settings')
      // Hide on default value config settings form.
      && !(!empty($form_state->getBuildInfo()['base_form_id']) && $form_state->getBuildInfo()['base_form_id'] == 'field_config_form')
    ) {
      $overridden_map_settings = empty($this->getSetting('map_provider_settings')) ? [] : $this->getSetting('map_provider_settings');

      if (!empty($items->get(0)->getValue()['data']['map_provider_settings'])) {
        $overridden_map_settings = $items->get(0)->getValue()['data']['map_provider_settings'];
      }

      $element['map_provider_settings'] = $this->mapProvider->getSettingsForm(
        $overridden_map_settings,
        [
          $this->fieldDefinition->getName(),
          0,
          'map_provider_settings',
        ]
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL): array {
    $element = parent::form($items, $form, $form_state, $get_delta);

    $element['#attached'] = BubbleableMetadata::mergeAttachments(
      $element['#attached'] ?? [],
      [
        'drupalSettings' => [
          'geolocation' => [
            'widgetSettings' => [
              $element['#attributes']['id'] => [
                'widgetSubscribers' => [
                  'geolocation_map' => [
                    'import_path' => base_path() . $this->moduleHandler->getModule('geolocation')->getPath() . '/js/WidgetSubscriber/GeolocationFieldMapWidget.js',
                    'settings' => [
                      'mapId' => $element['map']['#id'],
                      'cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
                      'field_name' => $this->fieldDefinition->getName(),
                      'featureSettings' => [
                        'import_path' => base_path() . $this->moduleHandler->getModule('geolocation')->getPath() . '/js/MapFeature/GeolocationFieldWidgetMapConnector.js',
                      ],
                    ],
                  ],
                  'geolocation_field' => [
                    'import_path' => base_path() . $this->moduleHandler->getModule('geolocation')->getPath() . '/js/WidgetSubscriber/GeolocationFieldWidget.js',
                    'settings' => [
                      'cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
                      'field_name' => $this->fieldDefinition->getName(),
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ]
    );

    /**
     * @var Integer $index
     * @var \Drupal\geolocation\Plugin\Field\FieldType\GeolocationItem $item
     */
    foreach ($items as $index => $item) {
      if ($item->isEmpty()) {
        continue;
      }
      $element['map']['locations']['location-' . $index] = [
        '#type' => 'geolocation_map_location',
        '#title' => ($index + 1) . ': ' . $item->getValue()['lat'] . ", " . $item->getValue()['lng'],
        '#label' => ($index + 1),
        '#coordinates' => [
          'lat' => $item->getValue()['lat'],
          'lng' => $item->getValue()['lng'],
        ],
        '#draggable' => TRUE,
        '#attributes' => [
          'data-geolocation-widget-index' => $index,
        ],
      ];
    }

    if (
      $this->getSetting('allow_override_map_settings')
      && !empty($items->get(0)->getValue()['data']['map_provider_settings'])
    ) {
      $element['map']['#settings'] = $items->get(0)->getValue()['data']['map_provider_settings'];
    }

    $context = [
      'widget' => $this,
      'form_state' => $form_state,
      'field_definition' => $this->fieldDefinition,
    ];

    if (!$this->isDefaultValueWidget($form_state)) {
      $this->moduleHandler->alter('geolocation_field_map_widget', $element, $context);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $values = parent::massageFormValues($values, $form, $form_state);

    if (!empty($this->settings['allow_override_map_settings'])) {
      if (!empty($values[0]['map_provider_settings'])) {
        $values[0]['data']['map_provider_settings'] = $values[0]['map_provider_settings'];
        unset($values[0]['map_provider_settings']);
      }
    }

    return $values;
  }

}
