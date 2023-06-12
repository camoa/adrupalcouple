<?php

namespace Drupal\custom_field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\Url;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;

/**
 * Plugin implementation of the 'custom' field type.
 *
 * @FieldType(
 *   id = "custom",
 *   label = @Translation("Custom Field"),
 *   description = @Translation("This field stores simple multi-value fields in the database."),
 *   default_widget = "custom_stacked",
 *   default_formatter = "custom_formatter"
 * )
 */
class CustomItem extends FieldItemBase {

  use StringTranslationTrait;

  /**
   * The default max length for each custom field item.
   *
   * @var int
   */
  protected int $maxLengthDefault = 255;

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings(): array {
    // Need to have at least one item by default because the table is created
    // before the user gets a chance to customize and will throw an Exception
    // if there isn't at least one column defined.
    return [
      'columns' => [
        'value' => [
          'name' => 'value',
          'max_length' => 255,
          'type' => 'string',
          'unsigned' => FALSE,
          'scale' => 2,
          'precision' => 10,
        ],
      ],
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {

    $properties = [];

    // Prevent early t() calls by using the TranslatableMarkup.
    foreach ($field_definition->getSetting('columns') as $item) {
      $data_type = 'string';
      switch ($item['type']) {
        case 'boolean':
          $data_type = 'boolean';
          break;

        case 'decimal':
          $data_type = 'string';
          break;

        case 'float':
          $data_type = 'float';
          break;

        case 'integer':
          $data_type = 'integer';
          break;

        case 'email':
          $data_type = 'email';
          break;

        case 'map':
          $data_type = 'map';
          break;

        case 'timestamp':
          $data_type = 'timestamp';
          break;

        case 'uri':
          $data_type = 'uri';
          break;
      }
      if ($data_type == 'map') {
        // The properties are dynamic and can not be defined statically.
        $properties[$item['name']] = MapDataDefinition::create()
          ->setLabel(new TranslatableMarkup($item['name'] . ' value'));
      }
      else {
        $properties[$item['name']] = DataDefinition::create($data_type)
          ->setLabel(new TranslatableMarkup($item['name'] . ' value'))
          ->setRequired(FALSE);
      }
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {

    $schema = [];

    foreach ($field_definition->getSetting('columns') as $item) {
      switch ($item['type']) {
        case 'string':
          $schema['columns'][$item['name']] = [
            'type' => 'varchar',
            'length' => $item['max_length'],
          ];
          break;

        case 'string_long':
          $schema['columns'][$item['name']] = [
            'type' => 'text',
            'size' => 'big',
          ];
          break;

        case 'boolean':
          $schema['columns'][$item['name']] = [
            'type' => 'int',
            'size' => 'tiny',
          ];
          break;

        case 'color':
          $schema['columns'][$item['name']] = [
            'type' => 'varchar',
            'description' => 'The hexadecimal color value',
            'length' => 7,
          ];
          break;

        case 'decimal':
          $schema['columns'][$item['name']] = [
            'type' => 'numeric',
            'precision' => $item['precision'],
            'scale' => $item['scale'],
          ];
          break;

        case 'float':
          $schema['columns'][$item['name']] = [
            'type' => 'float',
          ];
          break;

        case 'integer':
          $schema['columns'][$item['name']] = [
            'type' => 'int',
            'unsigned' => $item['unsigned'],
            'size' => 'normal',
          ];
          break;

        case 'email':
          $schema['columns'][$item['name']] = [
            'type' => 'varchar',
            'length' => 254,
          ];
          break;

        case 'map':
          $schema['columns'][$item['name']] = [
            'type' => 'blob',
            'size' => 'big',
            'serialize' => TRUE,
            'description' => 'A serialized array of values.'
          ];
          break;

        case 'timestamp':
          $schema['columns'][$item['name']] = [
            'type' => 'int',
          ];
          break;

        case 'uuid':
          $schema['columns'][$item['name']] = [
            'type' => 'varchar_ascii',
            'length' => 128,
          ];
          break;

        case 'uri':
          $schema['columns'][$item['name']] = [
            'type' => 'varchar',
            'length' => 2048,
          ];
          break;

        default:
          $schema['columns'][$item['name']] = [
            'type' => 'varchar',
            'length' => (int) $item['max_length'],
          ];
      }
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    $random = new Random();
    $field_settings = $field_definition->getSetting('field_settings');
    foreach ($field_definition->getSetting('columns') as $item) {
      $widget_settings = isset($field_settings[$item['name']]) && isset($field_settings[$item['name']]['widget_settings']) ? $field_settings[$item['name']]['widget_settings'] : [];
      switch ($item['type']) {
        case 'boolean':
          $values[$item['name']] = mt_rand(0, 1);
          break;

        case 'color':
          $random_colors = [
            '#ac725e',
            '#d06b64',
            '#f83a22',
            '#fa573c',
            '#ff7537',
            '#ffad46',
            '#42d692',
            '#16a765',
            '#7bd148',
            '#b3dc6c',
            '#fbe983',
            '#92e1c0',
            '#9fe1e7',
            '#9fc6e7',
            '#4986e7',
            '#9a9cff',
            '#b99aff',
            '#c2c2c2',
            '#cabdbf',
            '#cca6ac',
            '#f691b2',
            '#cd74e6',
            '#a47ae2',
          ];
          $color_key = array_rand($random_colors, 1);
          $values[$item['name']] = $random_colors[$color_key];
          break;

        case 'decimal':
          $precision = $item['precision'];
          $scale = $item['scale'];
          if (!empty($widget_settings)) {
            if (isset($widget_settings['min'])) {
              $min = $widget_settings['min'];
            }
            if (isset($widget_settings['max'])) {
              $max = $widget_settings['max'];
            }
            if (isset($widget_settings['scale'])) {
              $scale = $widget_settings['scale'];
            }
          }
          else {
            // The minimum number you can get with 3 digits is -1 * (10^3 - 1).
            $min = -pow(10, ($precision - $scale)) + 1;
            // The maximum number you can get with 3 digits is 10^3 - 1 --> 999.
            $max = pow(10, ($precision - $scale)) - 1;
          }
          // Get the number of decimal digits for the $max.
          $decimal_digits = self::getDecimalDigits($max);
          // Do the same for the min and keep the higher number of decimal
          // digits.
          $decimal_digits = max(self::getDecimalDigits($min), $decimal_digits);
          // If $min = 1.234 and $max = 1.33 then $decimal_digits = 3.
          $scale = rand($decimal_digits, $scale);

          // @see "Example #1 Calculate a random floating-point number" in
          // http://php.net/manual/function.mt-getrandmax.php
          $random_decimal = $min + mt_rand() / mt_getrandmax() * ($max - $min);
          $values[$item['name']] = self::truncateDecimal($random_decimal, $scale);
          break;

        case 'email':
          $values[$item['name']] = $random->name() . '@example.com';
          break;

        case 'float':
          $precision = rand(10, 32);
          $scale = rand(0, 2);
          $max = pow(10, ($precision - $scale)) - 1;
          $min = -pow(10, ($precision - $scale)) + 1;
          if (!empty($widget_settings)) {
            if (isset($widget_settings['max'])) {
              $max = $widget_settings['max'];
            }
            if (isset($widget_settings['min'])) {
              $min = $widget_settings['min'];
            }
          }
          // @see "Example #1 Calculate a random floating-point number" in
          // http://php.net/manual/function.mt-getrandmax.php
          $random_decimal = $min + mt_rand() / mt_getrandmax() * ($max - $min);
          $values[$item['name']] = self::truncateDecimal($random_decimal, $scale);
          break;

        case 'integer':
          $min = 0;
          $max = 999;
          if (!empty($widget_settings)) {
            // Generate values from option list.
            if (isset($widget_settings['allowed_values'])) {
              $values[$item['name']] = self::getRandomOptions($widget_settings['allowed_values']);
            }
            else {
              if (isset($widget_settings['min'])) {
                $min = $widget_settings['min'];
              }
              if (isset($widget_settings['max'])) {
                $max = $widget_settings['max'];
              }
              $values[$item['name']] = mt_rand($min, $max);
            }
          }
          else {
            $values[$item['name']] = mt_rand($min, $max);
          }
          break;

        case 'map':
          $values[$item['name']] = [
            'data-1' => $random->word(mt_rand(10, 10)),
            'data-2' => $random->word(mt_rand(10, 10)),
            'data-3' => $random->word(mt_rand(10, 10)),
            'data-4' => $random->word(mt_rand(10, 10)),
            'data-5' => $random->word(mt_rand(10, 10)),
          ];
          break;

        case 'string':
          $limit = $item['max_length'] < 30 ? $item['max_length'] : 30;
          if (!empty($widget_settings)) {
            if (isset($widget_settings['allowed_values'])) {
              // Generate values from option list.
              $values[$item['name']] = self::getRandomOptions($widget_settings['allowed_values']);
            }
            else {
              $values[$item['name']] = $random->word(mt_rand(1, $limit));
            }
          }
          else {
            $values[$item['name']] = $random->word(mt_rand(1, $limit));
          }
          break;

        case 'string_long':
          $values[$item['name']] = $random->paragraphs();
          break;

        case 'uuid':
          $values[$item['name']] = \Drupal::service('uuid')->generate();
          break;

        default:
          $values[$item['name']] = $random->word(mt_rand(1, $item['max_length']));
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(): array {
    $constraints = parent::getConstraints();

    foreach ($this->getSetting('columns') as $item) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      switch ($item['type']) {
        case 'string':
          if ($max_length = $item['max_length']) {
            $constraints[] = $constraint_manager->create('ComplexData', [
              $item['name'] => [
                'Length' => [
                  'max' => $max_length,
                  'maxMessage' => $this->t('%name: may not be longer than @max characters.', [
                    '%name' => $item['name'],
                    '@max' => $max_length,
                  ]),
                ],
              ],
            ]);
          }
          break;

        case 'integer':
          // If this is an unsigned integer, add a validation constraint for
          // the integer to be positive.
          if ($item['unsigned']) {
            $constraints[] = $constraint_manager->create('ComplexData', [
              $item['name'] => [
                'Range' => [
                  'min' => 0,
                  'minMessage' => $this->t('%name: The integer must be larger or equal to %min.', [
                    '%name' => $item['name'],
                    '%min' => 0,
                  ]),
                ],
              ],
            ]);
          }
          break;

        case 'email':
          $constraints[] = $constraint_manager->create('ComplexData', [
            $item['name'] => [
              'Length' => [
                'max' => 254,
                'maxMessage' => $this->t('%name: the email address can not be longer than @max characters.', [
                  '%name' => $item['name'],
                  '%max' => 254,
                ]),
              ],
            ],
          ]);
          break;
      }
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    $settings = $this->getSetting('columns');
    $field_settings = $this->getSetting('field_settings');
    foreach ($settings as $name => $setting) {
      switch ($setting['type']) {
        case 'color':
          $color = $this->{$name};

          // Clean up data and format it.
          $color = trim($color);

          if (substr($color, 0, 1) === '#') {
            $color = substr($color, 1);
          }
          $this->{$name} = '#' . strtoupper($color);
          break;

        case 'map':
          if (!is_array($this->{$name})) {
            $this->{$name} = NULL;
          }
          if ($field_settings[$name]['type'] == 'map_key_value') {
            $map_values = $this->get($name)->getValue();
            // The table widget has a default value of data until values exist.
            if (isset($map_values['data'])) {
              $this->{$name} = NULL;
            }
          }
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data): array {

    $elements = [];

    if ($form_state->isRebuilding()) {
      $settings = $form_state->getValue('settings');
    }
    else {
      $settings = $this->getSettings();
      $settings['items'] = $settings['columns'];
    }

    // Add a new item if there aren't any or we're rebuilding.
    if ($form_state->get('add') || count($settings['items']) == 0) {
      $settings['items'][] = [];
      $form_state->set('add', NULL);
    }

    $wrapper_id = 'customfield-items-wrapper';
    $elements['#tree'] = TRUE;

    // Need to pass the columns on so that it persists in the settings between
    // ajax rebuilds.
    $elements['columns'] = [
      '#type' => 'value',
      '#value' => $settings['columns'],
    ];

    // Support copying settings from another custom field.
    if (!$has_data) {
      $sources = $this->getExistingCustomFieldStorageOptions($form_state->get('entity_type_id'));
      if (!empty($sources)) {
        $elements['clone'] = [
          '#type' => 'select',
          '#title' => $this->t('Clone Settings From:'),
          '#options' => [
              '' => $this->t("- Don't Clone Settings -"),
            ] + $sources,
          '#attributes' => [
            'data-id' => 'customfield-settings-clone',
          ],
        ];

        $elements['clone_message'] = [
          '#type' => 'container',
          '#states' => [
            'invisible' => [
              'select[data-id="customfield-settings-clone"]' => ['value' => ''],
            ],
          ],
          // Initialize the display so we don't see it flash on init page load.
          '#attributes' => [
            'style' => 'display: none;',
          ],
        ];

        $elements['clone_message']['message'] = [
          '#markup' => 'The selected custom field field settings will be cloned. Any existing settings for this field will be overwritten. Field widget and formatter settings will not be cloned.',
          '#prefix' => '<div class="messages messages--warning" role="alert" style="display: block;">',
          '#suffix' => '</div>',
        ];
      }
    }

    // We're using the 'items' container for the form configuration rather than
    // putting it directly in 'columns' because the schema method gets run
    // between ajax form rebuilds and would be given any new 'columns' that
    // were added (but not created yet) which results in a missing column
    // database error.
    $elements['items'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('The custom field items'),
      '#description' => $this->t('These can be re-ordered on the main field settings form after the field is created'),
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#states' => [
        'visible' => [
          'select[data-id="customfield-settings-clone"]' => ['value' => ''],
        ],
      ],
    ];

    foreach ($settings['items'] as $i => $item) {
      if ($i === $form_state->get('remove')) {
        $form_state->set('remove', NULL);
        continue;
      }

      $elements['items'][$i]['name'] = [
        '#type' => 'machine_name',
        '#description' => $this->t('A unique machine-readable name containing only letters, numbers, or underscores. This will be used in the column name on the field table in the database.'),
        '#default_value' => !empty($item['name']) ? $item['name'] : uniqid('value_'),
        '#disabled' => $has_data,
        '#machine_name' => [
          'exists' => [$this, 'machineNameExists'],
          'label' => $this->t('Machine-readable name'),
          'standalone' => TRUE,
        ],
      ];

      $elements['items'][$i]['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#options' => [
          'string' => $this->t('Text (plain)'),
          'string_long' => $this->t('Text (plain, long)'),
          'boolean' => $this->t('Boolean'),
          'color' => $this->t('Color'),
          'decimal' => $this->t('Number (decimal)'),
          'float' => $this->t('Number (float)'),
          'integer' => $this->t('Number (integer)'),
          'email' => $this->t('Email'),
          'uuid' => $this->t('UUID'),
          'map' => $this->t('Map (serialized array)'),
          'uri' => $this->t('URI'),
        ],
        '#default_value' => $item['type'] ?? '',
        '#required' => TRUE,
        '#empty_option' => $this->t('- Select -'),
        '#disabled' => $has_data,
      ];
      $elements['items'][$i]['max_length'] = [
        '#type' => 'number',
        '#title' => $this->t('Maximum length'),
        '#default_value' => !empty($item['max_length']) ? $item['max_length'] : $this->maxLengthDefault,
        '#required' => TRUE,
        '#description' => $this->t('The maximum length of the field in characters.'),
        '#min' => 1,
        '#disabled' => $has_data,
        '#states' => [
          'visible' => [
            ':input[name="settings[items][' . $i . '][type]"]' => ['value' => 'string'],
          ],
        ],
      ];
      $elements['items'][$i]['unsigned'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Unsigned'),
        '#default_value' => $item['unsigned'] ?? $this->getSetting('unsigned'),
        '#disabled' => $has_data,
        '#states' => [
          'visible' => [
            ':input[name="settings[items][' . $i . '][type]"]' => ['value' => 'integer'],
          ],
        ],
      ];
      $elements['items'][$i]['precision'] = [
        '#type' => 'number',
        '#title' => $this->t('Precision'),
        '#min' => 10,
        '#max' => 32,
        '#default_value' => $item['precision'] ?? 10,
        '#description' => $this->t('The total number of digits to store in the database, including those to the right of the decimal.'),
        '#disabled' => $has_data,
        '#required' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="settings[items][' . $i . '][type]"]' => ['value' => 'decimal'],
          ],
        ],
      ];
      $elements['items'][$i]['scale'] = [
        '#type' => 'number',
        '#title' => $this->t('Scale'),
        '#description' => $this->t('The number of digits to the right of the decimal.'),
        '#default_value' => $item['scale'] ?? 2,
        '#disabled' => $has_data,
        '#min' => 0,
        '#max' => 10,
        '#required' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="settings[items][' . $i . '][type]"]' => ['value' => 'decimal'],
          ],
        ],
      ];
      $elements['items'][$i]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => [get_class($this) . '::removeSubmit'],
        '#name' => 'remove:' . $i,
        '#delta' => $i,
        '#disabled' => $has_data,
        '#ajax' => [
          'callback' => [$this, 'actionCallback'],
          'wrapper' => $wrapper_id,
        ],
      ];
    }

    if (!$has_data) {
      $elements['actions'] = [
        '#type' => 'actions',
      ];
      $elements['actions']['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add another'),
        '#submit' => [get_class($this) . '::addSubmit'],
        '#ajax' => [
          'callback' => [$this, 'actionCallback'],
          'wrapper' => $wrapper_id,
        ],
        '#states' => [
          'visible' => [
            'select[data-id="customfield-settings-clone"]' => ['value' => ''],
          ],
        ],
      ];
    }

    $form_state->setCached(FALSE);

    return $elements;
  }

  /**
   * Submit handler for the StorageConfigEditForm.
   *
   * This handler is added in custom_field.module since it has to be placed
   * directly on the submit button (which we don't have access to in our
   * ::storageSettingsForm() method above).
   */
  public static function submitStorageConfigEditForm(array &$form, FormStateInterface $form_state) {
    // Rekey our column settings and overwrite the values in form_state so that
    // we have clean settings saved to the db.
    $columns = [];

    if ($field_name = $form_state->getValue(['settings', 'clone'])) {
      [$bundle_name, $field_name] = explode('.', $field_name);
      // Grab the columns from the field storage config.
      $columns = FieldStorageConfig::loadByName($form_state->get('entity_type_id'), $field_name)->getSetting('columns');
      // Grab the field settings too as a starting point.
      $source_field_config = FieldConfig::loadByName($form_state->get('entity_type_id'), $bundle_name, $field_name);
      $form_state->get('field_config')->setSettings($source_field_config->getSettings())->save();
    }
    else {
      foreach ($form_state->getValue(['settings', 'items']) as $item) {
        $columns[$item['name']] = $item;
        unset($columns[$item['name']]['remove']);
      }
    }
    $form_state->setValue(['settings', 'columns'], $columns);
    $form_state->setValue(['settings', 'items'], NULL);

    // Reset the field storage config property - it will be recalculated when
    // accessed via the property definitions getter.
    // @see Drupal\field\Entity\FieldStorageConfig::getPropertyDefinitions()
    // If we don't do this, an exception is thrown during the table update that
    // is very difficult to recover from since the original field tables have
    // already been removed at that point.
    $field_storage_config = $form_state->getBuildInfo()['callback_object']->getEntity();
    $field_storage_config->set('propertyDefinitions', NULL);
  }

  /**
   * Check for duplicate names on our columns settings.
   */
  public function machineNameExists($value, array $form, FormStateInterface $form_state): bool {
    $count = 0;
    foreach ($form_state->getValue(['settings', 'items']) as $item) {
      if ($item['name'] == $value) {
        $count++;
      }
    }
    return $count > 1;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $settings = $this->getSettings();
    $customItems = $this->getCustomFieldManager()->getCustomFieldItems($settings);
    $emptyCounter = 0;
    $field_count = count($customItems);
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface $customItem */
    foreach ($customItems as $name => $customItem) {
      $definition = $customItem->getPluginDefinition();
      $check = array_key_exists('check_empty', $definition) && $definition['check_empty'];
      $no_check = array_key_exists('never_check_empty', $definition) && $definition['never_check_empty'];
      $item_value = $this->get($name)->getValue();
      if ($item_value === '' || $item_value === NULL || $no_check) {
        $emptyCounter++;
        // If any of the empty check fields are filled or all fields are empty.
        if ($check || $emptyCounter === $field_count) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function actionCallback(array &$form, FormStateInterface $form_state) {
    return $form['settings']['items'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function addSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set('add', TRUE);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public static function removeSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set('remove', $form_state->getTriggeringElement()['#delta']);
    $form_state->setRebuild();
  }

  /**
   * Get the existing custom field storage config options.
   *
   * @param string $entity_type_id
   *
   * @return array
   */
  protected function getExistingCustomFieldStorageOptions(string $entity_type_id): array {
    $sources = [];
    $existingCustomFields = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('custom');
    $bundleInfo = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id);
    foreach ($existingCustomFields[$entity_type_id] as $field_name => $info) {
      // Skip ourself.
      if ($this->getFieldDefinition()->getName() != $field_name) {
        foreach ($info['bundles'] as $bundleName) {
          $group = $bundleInfo[$bundleName]['label'] ?? '';
          $info = FieldConfig::loadByName($entity_type_id, $bundleName, $field_name);
          $sources[$group][$bundleName . '.' . $info->getName()] = $info->getLabel();
        }
      }
    }
    return $sources;
  }

  /**
   * Helper method to flatten an array of allowed values and randomize.
   *
   * @param $allowed_values
   *
   * @return array|int|string
   */
  protected static function getRandomOptions($allowed_values) {
    $randoms = [];
    foreach ($allowed_values as $value) {
      $randoms[$value['key']] = $value['value'];
    }
    return array_rand($randoms, 1);
  }

  /**
   * Helper method to get the number of decimal digits out of a decimal number.
   *
   * @param int $decimal
   *   The number to calculate the number of decimals digits from.
   *
   * @return int
   *   The number of decimal digits.
   */
  protected static function getDecimalDigits($decimal): int {
    $digits = 0;
    while ($decimal - round($decimal)) {
      $decimal *= 10;
      $digits++;
    }
    return $digits;
  }

  /**
   * Helper method to truncate a decimal number to a given number of decimals.
   *
   * @param float $decimal
   *   Decimal number to truncate.
   * @param int $num
   *   Number of digits the output will have.
   *
   * @return float
   *   Decimal number truncated.
   */
  protected static function truncateDecimal(float $decimal, int $num): float {
    return floor($decimal * pow(10, $num)) / pow(10, $num);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings(): array {
    return [
        'field_settings' => [],
      ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $elements = [
      '#type' => 'fieldset',
      '#title' => $this->t('Custom Field Items'),
    ];

    $settings = $this->getSettings();
    $columns = $settings['columns'];
    if ($form_state->isRebuilding()) {
      $field_settings = $form_state->getValue('settings')['field_settings'];
      $settings['field_settings'] = $field_settings;
    }
    else {
      $field_settings = $this->getSetting('field_settings');
    }

    $customItems = $this->getCustomFieldManager()->getCustomFieldItems($settings);

    $wrapper_id = 'customfield-settings-wrapper';
    $elements['field_settings'] = [
      '#type' => 'table',
      '#header' => [
        '',
        $this->t('Type'),
        $this->t('Settings'),
        $this->t('Output Settings'),
        $this->t('Check Empty?'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
      '#attributes' => [
        'class' => ['customfield-settings-table'],
      ],
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'field-settings-order-weight',
        ],
      ],
      '#attached' => [
        'library' => ['custom_field/customfield-admin'],
      ],
      '#weight' => -99,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    // Build the table rows and columns.
    foreach ($customItems as $name => $customItem) {
      $definition = $customItem->getPluginDefinition();
      $weight = $field_settings[$name]['weight'] ?? 0;

      // TableDrag: Mark the table row as draggable.
      $elements['field_settings'][$name]['#attributes']['class'][] = 'draggable';
      // TableDrag: Sort the table row according to its existing/configured
      // weight.
      // @todo Table row weight property not working. Drupal core bug!
      $elements['field_settings'][$name]['#weight'] = $weight;

      $elements['field_settings'][$name]['handle'] = [
        '#markup' => '<span></span>',
      ];
      $column = $columns[$name];

      $options = $this->getCustomFieldManager()->getCustomFieldWidgetOptions($column['type']);

      switch ($column['type']) {
        case 'boolean':
          $default_option = 'checkbox';
          break;

        case 'color':
          $default_option = 'color';
          break;

        case 'decimal':
          $default_option = 'decimal';
          break;

        case 'float':
          $default_option = 'float';
          break;

        case 'integer':
          $default_option = 'integer';
          break;

        case 'string_long':
          $default_option = 'textarea';
          break;

        case 'email':
          $default_option = 'email';
          break;

        case 'uuid':
          $default_option = 'uuid';
          break;

        case 'map':
          $default_option = 'map_key_value';
          break;

        case 'uri':
          $default_option = 'url';
          break;

        default:
          $default_option = 'text';
      }
      $type = $field_settings[$name]['type'] ?? $default_option;
      $options_count = count($options);
      $elements['field_settings'][$name]['type'] = [
        '#type' => 'select',
        '#title' => $this->t('%name type', ['%name' => $name]),
        '#options' => $options,
        '#default_value' => $type,
        '#ajax' => [
          'callback' => [$this, 'widgetSelectionCallback'],
          'wrapper' => $wrapper_id,
        ],
        '#attributes' => [
          'disabled' => $options_count <= 1,
        ],
      ];

      // Add our plugin widget and formatter settings form.
      $elements['field_settings'][$name]['widget_settings'] = $customItem->widgetSettingsForm($form, $form_state);
      $elements['field_settings'][$name]['formatter_settings'] = $customItem->formatterSettingsForm($form, $form_state);

      $elements['field_settings'][$name]['check_empty'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Check Empty?'),
        '#description' => $this->t('When saving the field, if an element with this value checked is empty, the row will be removed.'),
        '#default_value' => $field_settings[$name]['check_empty'] ?? FALSE,
      ];

      if (!empty($definition['never_check_empty'])) {
        $elements['field_settings'][$name]['check_empty']['#default_value'] = FALSE;
        $elements['field_settings'][$name]['check_empty']['#disabled'] = TRUE;
        $elements['field_settings'][$name]['check_empty']['#description'] = $this->t("<em>This custom field type can't be empty checked.</em>");
      }

      // TableDrag: Weight column element.
      $elements['field_settings'][$name]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $name]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        // Classify the weight element for #tabledrag.
        '#attributes' => ['class' => ['field-settings-order-weight']],
      ];

    }

    return $elements;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function widgetSelectionCallback(array &$form, FormStateInterface $form_state) {
    return $form['settings']['field_settings'];
  }

  /**
   * Get the custom field_type manager plugin.
   *
   * @return \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  public function getCustomFieldManager(): CustomFieldTypeManagerInterface {
    return \Drupal::service('plugin.manager.customfield_type');
  }

}
