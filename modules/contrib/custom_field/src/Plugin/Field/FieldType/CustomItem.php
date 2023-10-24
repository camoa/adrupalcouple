<?php

namespace Drupal\custom_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\CustomFieldGenerateDataInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

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
          'size' => 'normal',
          'datetime_type' => CustomFieldTypeInterface::DATETIME_TYPE_DATETIME,
        ],
      ],
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeManager $plugin_service */
    $plugin_service = \Drupal::service('plugin.manager.custom_field_type');
    $schema = [];

    foreach ($field_definition->getSetting('columns') as $item) {
      $plugin = $plugin_service->createInstance($item['type']);
      $field_schema = $plugin->schema($item);
      $schema['columns'][$item['name']] = $field_schema;
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeManager $plugin_service */
    $plugin_service = \Drupal::service('plugin.manager.custom_field_type');
    $properties = [];

    foreach ($field_definition->getSetting('columns') as $item) {
      $plugin = $plugin_service->createInstance($item['type']);
      $properties[$item['name']] = $plugin->propertyDefinitions($item);
      // Add computed properties.
      if ($item['type'] == 'uri') {
        $properties[$item['name'] . '__url'] = DataDefinition::create('uri')
          ->setLabel(new TranslatableMarkup('%name url', ['%name' => $item['name']]))
          ->setComputed(TRUE)
          ->setClass('\Drupal\custom_field\Computed\UriUrl')
          ->setSetting('uri source', $item['name'])
          ->setInternal(FALSE);
      }
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    $field_settings = $field_definition->getSetting('field_settings');
    $columns = $field_definition->getSetting('columns');
    $generator = static::getCustomFieldGenerator();
    $generated_columns = $generator->generateFieldData($columns, $field_settings);
    $values = [];
    foreach ($generated_columns as $name => $generated_value) {
      $values[$name] = $generated_value;
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(): array {
    $constraints = parent::getConstraints();
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeManager $plugin_service */
    $plugin_service = \Drupal::service('plugin.manager.custom_field_type');
    $field_constraints = [];
    $field_settings = $this->getSetting('field_settings');
    foreach ($this->getSetting('columns') as $id => $item) {
      $plugin = $plugin_service->createInstance($item['type']);
      if (method_exists($plugin, 'getConstraints')) {
        $widget_settings = $field_settings[$id]['widget_settings']['settings'] ?? [];
        $settings = $item;
        if (isset($widget_settings['min'])) {
          $settings['min'] = $widget_settings['min'];
        }
        if (isset($widget_settings['max'])) {
          $settings['max'] = $widget_settings['max'];
        }
        $field_constraints[$item['name']] = $plugin->getConstraints($settings);
      }
    }
    $constraints[] = $constraint_manager->create('ComplexData', $field_constraints);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    $settings = $this->getSetting('columns');

    foreach ($settings as $name => $setting) {
      switch ($setting['type']) {
        case 'color':
          $color = is_string($this->{$name}) ? trim($this->{$name}) : '';

          if (str_starts_with($color, '#')) {
            $color = substr($color, 1);
          }

          // Make sure we have a valid hexadecimal color.
          $this->{$name} = strlen($color) === 6 ? '#' . strtoupper($color) : NULL;
          break;

        case 'map':
          if (!is_array($this->{$name}) || empty($this->{$name})) {
            $this->{$name} = NULL;
          }
          $map_values = $this->get($name)->getValue();
          // The table widget has a default value of data until values exist.
          if (isset($map_values['data'])) {
            $this->{$name} = NULL;
          }
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data): array {
    $default_settings = self::defaultStorageSettings()['columns']['value'];
    $elements = [];
    $settings = $form_state->getValue('settings');
    if (empty($settings)) {
      $settings = $this->getSettings();
      $settings['items'] = array_values($settings['columns']);
    }
    if ($form_state->isRebuilding()) {
      $remove = $form_state->get('remove');
      if (!is_null($remove)) {
        unset($settings['items'][$remove]);
        $form_state->set('remove', NULL);
      }
    }

    // Add a new item if there aren't any or we're rebuilding.
    if ($form_state->get('add') || count($settings['items']) === 0) {
      $settings['items'][] = [
        'name' => uniqid('value_'),
      ];
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

    $items_count = count($settings['items']);

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

      $elements['items'][$i]['name'] = [
        '#type' => 'machine_name',
        '#description' => $this->t('A unique machine-readable name containing only letters, numbers, or underscores. This will be used in the column name on the field table in the database.'),
        '#default_value' => $item['name'],
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
        '#options' => $this->getCustomFieldManager()->dataTypeOptions(),
        '#default_value' => $item['type'] ?? '',
        '#required' => TRUE,
        '#empty_option' => $this->t('- Select -'),
        '#disabled' => $has_data,
      ];
      $elements['items'][$i]['max_length'] = [
        '#type' => 'number',
        '#title' => $this->t('Maximum length'),
        '#default_value' => !empty($item['max_length']) ? $item['max_length'] : $default_settings['max_length'],
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
      $elements['items'][$i]['size'] = [
        '#type' => 'select',
        '#title' => $this->t('Size'),
        '#default_value' => $item['size'] ?? $default_settings['size'],
        '#disabled' => $has_data,
        '#options' => [
          'tiny' => $this->t('Tiny'),
          'small' => $this->t('Small'),
          'medium' => $this->t('Medium'),
          'big' => $this->t('Big'),
          'normal' => $this->t('Normal'),
        ],
        '#states' => [
          'visible' => [
            ':input[name="settings[items][' . $i . '][type]"]' => [
              ['value' => 'integer'],
              ['value' => 'float'],
            ],
          ],
        ],
      ];
      $elements['items'][$i]['unsigned'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Unsigned'),
        '#default_value' => $item['unsigned'] ?? $default_settings['unsigned'],
        '#disabled' => $has_data,
        '#states' => [
          'visible' => [
            ':input[name="settings[items][' . $i . '][type]"]' => [
              ['value' => 'integer'],
              ['value' => 'float'],
              ['value' => 'decimal'],
            ],
          ],
        ],
      ];
      $elements['items'][$i]['precision'] = [
        '#type' => 'number',
        '#title' => $this->t('Precision'),
        '#min' => 10,
        '#max' => 32,
        '#default_value' => $item['precision'] ?? $default_settings['precision'],
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
        '#default_value' => $item['scale'] ?? $default_settings['scale'],
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
      $elements['items'][$i]['datetime_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Date type'),
        '#description' => $this->t('Choose the type of date to create.'),
        '#default_value' => $item['datetime_type'] ?? $default_settings['datetime_type'],
        '#disabled' => $has_data,
        '#options' => [
          CustomFieldTypeInterface::DATETIME_TYPE_DATETIME => $this->t('Date and time'),
          CustomFieldTypeInterface::DATETIME_TYPE_DATE => $this->t('Date only'),
        ],
        '#required' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="settings[items][' . $i . '][type]"]' => ['value' => 'datetime'],
          ],
        ],
      ];
      $elements['items'][$i]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => [get_class($this) . '::removeSubmit'],
        '#name' => 'remove:' . $i,
        '#delta' => $i,
        '#disabled' => $has_data || $items_count === 1,
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
    foreach ($customItems as $name => $customItem) {
      $definition = $customItem->getPluginDefinition();
      $check = $customItem->checkEmpty();
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
  public static function addSubmit(array &$form, FormStateInterface $form_state): void {
    $form_state->set('add', TRUE);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public static function removeSubmit(array &$form, FormStateInterface $form_state): void {
    $form_state->set('remove', $form_state->getTriggeringElement()['#delta']);
    $form_state->setRebuild();
  }

  /**
   * Get the existing custom field storage config options.
   *
   * @param string $entity_type_id
   *   The entity type to match.
   *
   * @return array
   *   An array of existing field configurations.
   */
  protected function getExistingCustomFieldStorageOptions(string $entity_type_id): array {
    $sources = [];
    $existingCustomFields = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('custom');
    $bundleInfo = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id);
    foreach ($existingCustomFields[$entity_type_id] as $field_name => $info) {
      // Skip ourself.
      if ($this->getFieldDefinition()->getName() != $field_name) {
        foreach ($info['bundles'] as $bundleName) {
          $group = (string) $bundleInfo[$bundleName]['label'] ?? '';
          $info = FieldConfig::loadByName($entity_type_id, $bundleName, $field_name);
          $sources[$group][$bundleName . '.' . $info->getName()] = $info->getLabel();
        }
      }
    }
    return $sources;
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
  public function fieldSettingsForm(array $form, FormStateInterface $form_state): array {

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
        $this->t('Check empty?'),
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
      $type = $field_settings[$name]['type'] ?? $definition['default_widget'];
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

      // Add our plugin widget settings form.
      $widget_manager = \Drupal::service('plugin.manager.custom_field_widget');
      $widget = $widget_manager->createInstance($type);
      $elements['field_settings'][$name]['widget_settings'] = $widget->widgetSettingsForm($form_state, $customItem);

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
   *   Returns the 'custom' field type plugin manager.
   */
  public function getCustomFieldManager(): CustomFieldTypeManagerInterface {
    return \Drupal::service('plugin.manager.custom_field_type');
  }

  /**
   * An instance of the generator service.
   *
   * @return \Drupal\custom_field\CustomFieldGenerateDataInterface
   *   Returns an instance of the service.
   */
  public static function getCustomFieldGenerator(): CustomFieldGenerateDataInterface {
    return \Drupal::service('custom_field.generate_data');
  }

}
