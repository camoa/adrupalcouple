<?php

/* phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint */
/* phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint */

declare(strict_types = 1);

namespace Drupal\schemadotorg\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element\Textarea;
use Drupal\Core\Render\Markup;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Provides a form element for Schema.org Blueprints settings.
 *
 * @FormElement("schemadotorg_settings")
 */
class SchemaDotOrgSettings extends Textarea {

  /**
   * Indexed.
   */
  const INDEXED = 'indexed';

  /**
   * Indexed grouped.
   */
  const INDEXED_GROUPED = 'indexed_grouped';

  /**
   * Indexed grouped named.
   */
  const INDEXED_GROUPED_NAMED = 'indexed_grouped_named';

  /**
   * Associative.
   */
  const ASSOCIATIVE = 'associative';

  /**
   * Associative grouped.
   */
  const ASSOCIATIVE_GROUPED = 'associative_grouped';

  /**
   * Associative grouped names.
   */
  const ASSOCIATIVE_GROUPED_NAMED = 'associative_grouped_named';

  /**
   * Links.
   */
  const LINKS = 'links';

  /**
   * Links grouped.
   */
  const LINKS_GROUPED = 'links_grouped';

  /**
   * YAML.
   */
  const YAML = 'yaml';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [
        [$class, 'processSchemaDotOrgSettings'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#settings_type' => static::INDEXED,
      '#group_name' => 'label',
      '#array_name' => 'items',
      '#settings_description' => TRUE,
      '#settings_format' => '',
      '#description' => '',
      '#description_link' => '',
      '#config_name' => '',
      '#config_key' => '',
      '#attributes' => ['wrap' => 'off'],
    ] + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Validate the #default_value by converting it to settings string
    // and parsing settings string.
    if ($element['#settings_type'] !== static::YAML) {
      $default_value = static::getDefaultValue($element);
      $string_value = static::convertSettingsToValue($element, $default_value);
      $settings_value = static::convertValueToSettings($element, $string_value);
      if ($default_value !== $settings_value) {
        $element['#settings_type'] = static::YAML;
        $element += ['#description' => ''];
        $element['#description'] .= ' <strong>' . t('Unable parse %title settings.', ['%title' => $element['#title']]) . '</strong>';
      }
    }

    return ($input === FALSE)
    ? static::convertSettingsToElementDefaultValue($element)
    : NULL;
  }

  /**
   * Processes a 'schemadotorg_settings' element.
   */
  public static function processSchemaDotOrgSettings(&$element, FormStateInterface $form_state, &$complete_form) {
    $config_name = static::getConfigName($element);
    $config_key = static::getConfigKey($element);
    if (!isset($complete_form['schemadotorg_settings_toggle'])
      && $element['#settings_type'] !== static::YAML
      && $config_name
      && $config_key
    ) {
      $edit_yaml = static::editYaml($element);

      $title = $edit_yaml
        ? t('Hide YAML')
        : t('Show YAML');
      $url = Url::fromRoute('<current>', [], ['query' => ['yaml' => (int) !$edit_yaml]]);
      $complete_form = [
        'schemadotorg_settings_toggle' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['compact-link', 'schemadotorg-settings-element-toggle']],
          'link' => [
            '#type' => 'link',
            '#title' => $title,
            '#url' => $url,
            '#attributes' => [
              'title' => $title,
              'class' => [
                'action-link',
                'action-link--extrasmall',
                'action-link--icon-' . ($edit_yaml ? 'hide' : 'show'),
              ],
            ],
            '#parents' => ['schemadotorg_settings_toggle', 'link'],
          ],
          '#parents' => ['schemadotorg_settings_toggle'],
          '#weight' => -100,
        ],
      ] + $complete_form;
    }

    // Append Schema.org browse types or properties link to the description.
    $link_table = $element['#description_link'];
    if (in_array($link_table, ['types', 'properties'])
      && \Drupal::moduleHandler()->moduleExists('schemadotorg_report')) {
      $link_text = ($link_table === 'types')
        ? t('Browse Schema.org types.')
        : t('Browse Schema.org properties.');
      $link_url = Url::fromRoute("schemadotorg_report.$link_table");
      $element['#description'] .= (!empty($element['#description'])) ? '<br/>' : '';
      $element['#description'] .= Link::fromTextAndUrl($link_text, $link_url)->toString();
      $element['#attached']['library'][] = 'schemadotorg/schemadotorg.dialog';
    }

    // Append settings example to the element's description.
    if ($element['#settings_description']) {
      if (static::editYaml($element)) {
        static::appendYamlExampleToElementDescription($element);
      }
      else {
        static::appendStringExampleToElementDescription($element);
      }
    }

    $element['#attached']['library'][] = 'schemadotorg/schemadotorg.settings.element';

    // Set CodeMirror YAML mode attributes and classes.
    if (static::editYaml($element)) {
      $element['#attached']['library'][] = 'schemadotorg/codemirror.yaml';
      $element['#attributes']['class'][] = 'schemadotorg-codemirror';
      $element['#attributes']['data-mode'] = 'yaml';
    }

    // Set validation.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [static::class, 'validateSchemaDotOrgSettings']);
    return $element;
  }

  /**
   * Append YAML example to an element's description.
   *
   * @param array &$element
   *   The element.
   */
  protected static function appendYamlExampleToElementDescription(array &$element): void {
    $format = static::getSettingsFormat($element);
    if (!$format) {
      return;
    }

    $code_examples = explode(' or ', $format);
    $data = [];
    foreach ($code_examples as $code_example) {
      try {
        $settings = static::convertValueToSettings($element, $code_example);
        $data += $settings;
      }
      catch (\Exception $exception) {
        \Drupal::messenger()->addError(t('Unable parse <code>@code</code> settings.', ['@code' => $code_example]));
      }
    }
    if ($data) {
      $element['#description'] = [
        'content' => ['#markup' => $element['#description']],
        'example' => [
          '#type' => 'details',
          '#title' => t('Example'),
          '#open' => (\Drupal::routeMatch()->getRouteName() === 'schemadotorg_settings_element_test.form'),
          'yaml' => [
            '#plain_text' => static::encodeYaml($data),
            '#prefix' => '<pre>',
            '#suffix' => '</pre>',
          ],
        ],
      ];
    }
  }

  /**
   * Append string example to an element's description.
   *
   * @param array &$element
   *   The element.
   */
  protected static function appendStringExampleToElementDescription(array &$element): void {
    $format = static::getSettingsFormat($element);
    $element['#description'] .= (!empty($element['#description'])) ? '<br/><br/>' : '';
    if ($format) {
      $code_examples = explode(' or ', $format);
      $code_prefix = '<code><strong>';
      $code_separator = '</strong></code> ' . t('or') . ' <code><strong>';
      $code_suffix = '</strong></code>';
      $code_example = Markup::create($code_prefix . implode($code_separator, $code_examples) . $code_suffix);
      $element['#description'] .= t('Enter one value per line, in the format @code.', ['@code' => $code_example]);
    }
    else {
      $element['#description'] .= t('Enter one value per line.');
    }
  }

  /**
   * Form element validation handler for #type 'schemadotorg_settings'.
   */
  public static function validateSchemaDotOrgSettings(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    // Convert element value to settings and catch any errors.
    try {
      $settings = static::convertElementValueToSettings($element, $form_state);
      $form_state->setValueForElement($element, $settings);
    }
    catch (\Exception $exception) {
      $settings = NULL;
      $form_state->setError($element, $exception->getMessage());
    }

    // Validate the settings against the config's schema.
    $config_name = static::getConfigName($element);
    $config_key = static::getConfigKey($element);
    if ($settings && $config_name && $config_key) {
      /** @var \Drupal\schemadotorg\SchemaDotOrgConfigSchemaCheckManagerInterface $config_schema_check_manager */
      $config_schema_check_manager = \Drupal::service('schemadotorg.config_schema_check_manager');
      $t_args = ['@name' => $element['#title']];
      try {
        $errors = $config_schema_check_manager->checkConfigValue($config_name, $config_key, $settings);
        if (is_array($errors)) {
          // Prefix the error with the exact config key triggering the error.
          [, $error_config_key] = explode(':', array_key_first($errors));
          $t_args['%error'] = $error_config_key . ' - ' . reset($errors);
          $form_state->setError($element, new TranslatableMarkup('@name field is invalid.<br/>%error', $t_args));
        }
      }
      catch (\Exception $exception) {
        $t_args['%error'] = $exception->getMessage();
        $form_state->setError($element, new TranslatableMarkup('@name field is invalid.<br/>%error', $t_args));
      }
    }
  }

  /**
   * Get the array item format for Schema.org settings form element.
   *
   * @param array $element
   *   The Schema.org settings form element.
   *
   * @return string
   *   The array item format for the Schema.org settings form element.
   */
  protected static function getSettingsFormat(array $element): string {
    $edit_yaml = static::editYaml($element);
    $formats = [
      static::INDEXED => ($edit_yaml)
        ? implode(PHP_EOL, ['item_1', 'item_2', 'item_3'])
        : '',
      static::INDEXED_GROUPED => 'name|item_1,item_2,item_3',
      static::INDEXED_GROUPED_NAMED => 'name|label|item_1,item_2,item_3',
      static::ASSOCIATIVE => 'key|value',
      static::ASSOCIATIVE_GROUPED => 'name|key_1:value_1,key_2:value_2,key_3:value_3',
      static::ASSOCIATIVE_GROUPED_NAMED => 'name|label|key_1:value_1,key_2:value_2,key_3:value_3',
      static::LINKS => 'https://somewhere.com|Page Title',
      static::LINKS_GROUPED => ($edit_yaml)
        ? 'group' . PHP_EOL . 'http://somewhere.com|Page Title'
        : 'group or https://somewhere.com|Page Title',
    ];
    return $element['#settings_format'] ?: $formats[$element['#settings_type']] ?? '';
  }

  /**
   * Converted Schema.org settings to an element's default value string.
   *
   * @param array $element
   *   The Schema.org settings form element.
   *
   * @return array|mixed|string
   *   An element's default value string.
   */
  protected static function convertSettingsToElementDefaultValue(array $element): mixed {
    // Set default value from configuration settings.
    $settings = static::getDefaultValue($element);
    if (static::editYaml($element)) {
      return static::encodeYaml($settings);
    }
    elseif (!is_array($settings)) {
      return $settings;
    }
    else {
      return static::convertSettingsToValue($element, $settings);
    }
  }

  /**
   * Convert a Schema.org settings array to settings value.
   *
   * @param array $element
   *   The Schema.org settings form element.
   * @param array|null $settings
   *   A Schema.org settings array.
   *
   * @return string|array
   *   The Schema.org settings array to settings value.
   */
  protected static function convertSettingsToValue(array $element, ?array $settings): string|array {
    if (empty($settings)) {
      return '';
    }

    switch ($element['#settings_type']) {
      case static::INDEXED:
        return static::convertIndexedArrayToString($settings);

      case static::INDEXED_GROUPED:
        $lines = [];
        foreach ($settings as $name => $values) {
          $lines[] = $name . '|' . static::convertIndexedArrayToString($values, ',');
        }
        return static::convertIndexedArrayToString($lines);

      case static::INDEXED_GROUPED_NAMED:
        $group_name = $element['#group_name'];
        $array_name = $element['#array_name'];

        $lines = [];
        foreach ($settings as $name => $group) {
          $label = $group[$group_name] ?? $name;
          $array = $group[$array_name] ?? [];
          $lines[] = $name . '|' . $label . '|' . static::convertIndexedArrayToString($array, ',');
        }
        return static::convertIndexedArrayToString($lines);

      case static::ASSOCIATIVE:
        return static::convertAssociativeArrayToString($settings);

      case static::ASSOCIATIVE_GROUPED:
        $lines = [];
        foreach ($settings as $name => $array) {
          $lines[] = $name . '|' . static::convertAssociativeArrayToString($array, ':', ',');
        }
        return static::convertIndexedArrayToString($lines);

      case static::ASSOCIATIVE_GROUPED_NAMED:
        $group_name = $element['#group_name'];
        $array_name = $element['#array_name'];

        $lines = [];
        foreach ($settings as $name => $group) {
          $label = $group[$group_name] ?? $name;
          $array = $group[$array_name] ?? [];
          $lines[] = $name . '|' . $label . '|' . static::convertAssociativeArrayToString($array, ':', ',');
        }
        return static::convertIndexedArrayToString($lines);

      case static::LINKS:
        $lines = [];
        foreach ($settings as $link) {
          $lines[] = $link['uri'] . '|' . $link['title'];
        }
        return implode("\n", $lines);

      case static::LINKS_GROUPED:
        $lines = [];
        foreach ($settings as $group => $links) {
          $lines[] = $group;
          foreach ($links as $link) {
            $lines[] = $link['uri'] . '|' . $link['title'];
          }
        }
        return implode("\n", $lines);
    }

    return $settings;
  }

  /**
   * Convert a Schema.org settings form element's value to an array of settings.
   *
   * @param array $element
   *   The Schema.org settings form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|null
   *   An array of setting.
   *
   * @throws \Exception
   *   Throw an exception when there is a validation error.
   */
  protected static function convertElementValueToSettings(array $element, FormStateInterface $form_state): ?array {
    if (static::editYaml($element)) {
      return Yaml::decode($element['#value']) ?: [];
    }
    else {
      return static::convertValueToSettings($element, $element['#value']);
    }
  }

  /**
   * Convert a Schema.org settings form element's value to an array of settings.
   *
   * @param array $element
   *   The Schema.org settings form element.
   * @param string $value
   *   The value being converted to an array of settings.
   *
   * @return array|null
   *   An array of setting.
   *
   * @throws \Exception
   *   Throw an exception when there is a validation error.
   */
  protected static function convertValueToSettings(array $element, string $value): ?array {
    switch ($element['#settings_type']) {
      case static::INDEXED:
        return static::convertStringToIndexedArray($value);

      case static::INDEXED_GROUPED:
        $settings = [];
        $groups = static::convertStringToIndexedArray($value);
        foreach ($groups as $group) {
          if (substr_count($group, '|') !== 1) {
            $message = (string) t('The @value is not valid.', ['@value' => $group]);
            throw new \Exception($message);
          }

          [$name, $items] = explode('|', $group);
          $name = trim($name);
          $settings[$name] = static::convertStringToIndexedArray($items, ',');
        }
        return $settings;

      case static::INDEXED_GROUPED_NAMED;
        $group_name = $element['#group_name'];
        $array_name = $element['#array_name'];

        $settings = [];
        $groups = static::convertStringToIndexedArray($value);
        foreach ($groups as $group) {
          if (substr_count($group, '|') !== 2) {
            $message = (string) t('The @value is not valid.', ['@value' => $group]);
            throw new \Exception($message);
          }

          [$name, $label, $items] = explode('|', $group);

          $name = trim($name);
          $settings[$name] = [
            $group_name => $label ?: $name,
            $array_name => static::convertStringToIndexedArray($items, ','),
          ];
        }
        return $settings;

      case static::ASSOCIATIVE:
        $settings = static::convertStringToAssociativeArray($value);
        // Cast associative array values to integers.
        // @todo This is a very dirty casting hack that should be removed.
        switch ($element['#name']) {
          case 'default_component_weights';
            $settings = array_map('intval', $settings);
            break;
        }
        return $settings;

      case static::ASSOCIATIVE_GROUPED:
        $settings = [];
        $groups = static::convertStringToIndexedArray($value);
        foreach ($groups as $item) {
          if (substr_count($item, '|') !== 1) {
            $message = (string) t('The @value is not valid.', ['@value' => $item]);
            throw new \Exception($message);
          }

          [$name, $items] = explode('|', $item);

          $name = trim($name);
          $settings[$name] = static::convertStringToAssociativeArray($items, ':', ',');
        }
        return $settings;

      case static::ASSOCIATIVE_GROUPED_NAMED;
        $group_name = $element['#group_name'];
        $array_name = $element['#array_name'];

        $settings = [];
        $groups = static::convertStringToIndexedArray($value);
        foreach ($groups as $group) {
          if (substr_count($group, '|') !== 2) {
            $message = (string) t('The @value is not valid.', ['@value' => $group]);
            throw new \Exception($message);
          }

          [$name, $label, $items] = explode('|', $group);

          $name = trim($name);
          $settings[$name] = [
            $group_name => $label ?: $name,
            $array_name => static::convertStringToAssociativeArray($items, ':', ','),
          ];
        }
        return $settings;

      case static::LINKS:
        $settings = [];
        $array = static::convertStringToAssociativeArray($value);
        foreach ($array as $key => $value) {
          $settings[] = [
            'title' => $value ?? static::getLinkTitle($value),
            'uri' => $key,
          ];
        }
        return $settings;

      case static::LINKS_GROUPED:
        $settings = [];
        $group = NULL;
        $array = static::convertStringToIndexedArray($value);
        foreach ($array as $item) {
          if (str_starts_with($item, 'http')) {
            if ($group === NULL) {
              $message = (string) t('The @value is not valid.', ['@value' => $item]);
              throw new \Exception($message);
            }
            $items = preg_split('/\s*\|\s*/', $item);
            $uri = $items[0];
            $title = $items[1] ?? static::getLinkTitle($uri);
            $settings[$group][] = ['title' => $title, 'uri' => $uri];
          }
          else {
            $group = $item;
            $settings[$group] = [];
          }
        }
        return $settings;
    }

    return [];
  }

  /**
   * Convert as indexed array to a string.
   *
   * @param array $array
   *   An indexed array.
   * @param string $delimiter
   *   The item delimiter.
   *
   * @return string
   *   The indexed array converted to a string.
   */
  protected static function convertIndexedArrayToString(array $array, string $delimiter = "\n"): string {
    return ($array) ? implode($delimiter, $array) : '';
  }

  /**
   * Convert an associative array to a string.
   *
   * @param array $array
   *   An associative array.
   * @param string $assoc_delimiter
   *   The associative delimiter.
   * @param string $item_delimiter
   *   The item delimiter.
   *
   * @return string
   *   The associative array converted to a string.
   */
  protected static function convertAssociativeArrayToString(array $array, string $assoc_delimiter = '|', string $item_delimiter = "\n"): string {
    $lines = [];
    foreach ($array as $key => $value) {
      $lines[] = ($value !== NULL) ? "$key$assoc_delimiter$value" : $key;
    }
    return implode($item_delimiter, $lines);
  }

  /**
   * Convert string to an indexed array.
   *
   * @param string $string
   *   The raw string to convert into an indexed array.
   * @param string $delimiter
   *   The item delimiter.
   *
   * @return array
   *   An indexed array.
   */
  protected static function convertStringToIndexedArray(string $string, string $delimiter = "\n"): array {
    $list = explode($delimiter, $string);
    $list = array_map('trim', $list);
    return array_filter($list, 'strlen');
  }

  /**
   * Convert string to an associative array.
   *
   * @param string $string
   *   The raw string to convert into an associative array.
   * @param string $assoc_delimiter
   *   The association delimiter.
   * @param string $item_delimiter
   *   The item delimiter.
   *
   * @return array
   *   An associative array.
   */
  protected static function convertStringToAssociativeArray(string $string, string $assoc_delimiter = '|', string $item_delimiter = "\n"): array {
    $items = static::convertStringToIndexedArray($string, $item_delimiter);
    $array = [];
    foreach ($items as $item) {
      $parts = explode($assoc_delimiter, $item);
      $key = trim($parts[0]);
      $value = $parts[1] ?? NULL;
      $value = (!is_null($value)) ? trim($value) : $value;

      // @todo This is a very dirty casting hack that should be removed.
      switch ($key) {
        case 'auto_create':
        case 'unlimited':
        case 'required':
          $value = (boolean) $value;
          break;

        case 'height':
        case 'width':
        case 'max_length':
          if (is_numeric($value)) {
            $value = (int) $value;
          }
          break;
      }

      $array[$key] = $value;
    }
    return $array;
  }

  /**
   * Get a remote URI's page title.
   *
   * @param string $uri
   *   The remote URI.
   *
   * @return string
   *   The remote URI's page title.
   */
  protected static function getLinkTitle(string $uri): string {
    $contents = file_get_contents($uri);
    $dom = new \DOMDocument();
    @$dom->loadHTML($contents);
    $title_node = $dom->getElementsByTagName('title');
    $title = $title_node->item(0)->nodeValue;
    [$title] = preg_split('/\s*\|\s*/', $title);
    return $title;
  }

  /* ************************************************************************ */
  // YAML methods.
  /* ************************************************************************ */

  /**
   * Determine if user wants to edit YAML.
   *
   * @param array $element
   *   The Schema.org settings form element.
   *
   * @return bool
   *   TRUE if user wants to edit YAML.
   */
  protected static function editYaml(array $element): bool {
    if ($element['#settings_type'] === static::YAML) {
      return TRUE;
    }

    $config_name = static::getConfigName($element);
    $config_key = static::getConfigKey($element);
    if (!$config_name || !$config_key) {
      return FALSE;
    }

    if (\Drupal::currentUser()->isAnonymous()) {
      return FALSE;
    }

    /** @var \Drupal\user\UserDataInterface $user_data */
    $user_data = \Drupal::service('user.data');
    $uid = \Drupal::currentUser()->id();
    if (\Drupal::request()->query->has('yaml')) {
      $edit_yaml = (bool) \Drupal::request()->query->get('yaml');
      $user_data->set('schemadotorg', $uid, 'yaml', $edit_yaml);
      return $edit_yaml;
    }
    else {
      return (bool) ($user_data->get('schemadotorg', $uid, 'yaml') ?? FALSE);
    }
  }

  /**
   * Encodes data into YAML.
   *
   * @param array|null $data
   *   The data to encode.
   *
   * @return string
   *   The data encoded into YAML.
   */
  protected static function encodeYaml(?array $data): string {
    $yaml = $data ? Yaml::encode($data) : '';
    // Remove return after array delimiter.
    $yaml = preg_replace('#((?:\n|^)[ ]*-)\n[ ]+(\w|[\'"])#', '\1 \2', $yaml);
    return $yaml;
  }

  /* ************************************************************************ */
  // Default value methods.
  /* ************************************************************************ */

  /**
   * Get a settings element's default value.
   *
   * @param array $element
   *   A settings element.
   *
   * @return array|null
   *   A settings element's default value.
   */
  protected static function getDefaultValue(array $element): ?array {
    // Set default value from configuration settings.
    $config_name = static::getConfigName($element);
    $config_key = static::getConfigKey($element);
    return \Drupal::config($config_name)->get($config_key)
      ?: $element['#default_value']
      ?? NULL;
  }

  /* ************************************************************************ */
  // Config methods.
  /* ************************************************************************ */

  /**
   * Get the config key.
   *
   * @param array $element
   *   The Schema.org settings form element.
   *
   * @return string
   *   The config key.
   */
  protected static function getConfigName(array $element): string {
    static::setConfigKeyProperty($element);
    return $element['#config_name'];
  }

  /**
   * Get the config key.
   *
   * @param array $element
   *   The Schema.org settings form element.
   *
   * @return string
   *   The config key.
   */
  protected static function getConfigKey(array $element): string {
    static::setConfigKeyProperty($element);
    return $element['#config_key'];
  }

  /**
   * Set config name and key from the element's parents.
   *
   * This assumes the element has two parents which are the module name
   * and the config key.
   *
   * @param array &$element
   *   The Schema.org settings form element.
   *
   * @see MODULE_form_schemadotorg_types_settings_form_alter
   * @see MODULE_form_schemadotorg_properties_settings_form_alter
   */
  protected static function setConfigKeyProperty(array &$element): void {
    if ($element['#config_name'] || $element['#config_key']) {
      return;
    }

    $configs = [];

    // Get config name/key via [MODULE_NAME][KEY][KEY].
    $parents = $element['#parents'];
    $module_name = array_shift($parents);
    $config_key = implode('.', $parents);
    $config_name = $module_name . '.settings';
    $configs[] = [$config_name, $config_key];

    // Get config name/key via [CONFIG_NAME][CONFIG_KEY][CONFIG_KEY].
    $parents = $element['#parents'];
    $settings_name = array_shift($parents);
    $config_key = implode('.', $parents);
    $config_name = 'schemadotorg.' . $settings_name;
    $configs[] = [$config_name, $config_key];

    // Get config name/key via [CONFIG_KEY][CONFIG_KEY][CONFIG_KEY].
    $config_key = implode('.', $element['#parents']);
    $config_names = ['schemadotorg.settings', 'schemadotorg.names'];
    foreach ($config_names as $config_name) {
      $configs[] = [$config_name, $config_key];
    }

    foreach ($configs as $config) {
      [$config_name, $config_key] = $config;
      if ($config_key
        && !is_null(\Drupal::config($config_name)->get($config_key))) {
        $element['#config_name'] = $config_name;
        $element['#config_key'] = $config_key;
        return;
      }
    }
  }

}
