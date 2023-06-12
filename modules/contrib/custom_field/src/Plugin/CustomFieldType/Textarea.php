<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Render\FilteredMarkup;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'textarea' customfield type.
 *
 * @CustomFieldType(
 *   id = "textarea",
 *   label = @Translation("Text area (multiple rows)"),
 *   description = @Translation(""),
 *   category = @Translation("Text"),
 *   data_types = {
 *     "string_long",
 *   }
 * )
 */
class Textarea extends CustomFieldTypeBase {

  /**
   * Cached processed text.
   *
   * @var \Drupal\filter\FilterProcessResult|null
   */
  protected $processed = NULL;

  /**
   * {@inheritdoc}
   */
  public static function defaultWidgetSettings(): array {
    return [
        'settings' => [
          'rows' => 5,
          'placeholder' => '',
          'maxlength' => '',
          'maxlength_js' => FALSE,
          'formatted' => FALSE,
          'default_format' => filter_fallback_format(),
          'format' => [
            'guidelines' => TRUE,
            'help' => TRUE,
          ],
        ],
      ] + parent::defaultWidgetSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);
    $settings = $this->getWidgetSetting('settings');
    // Add our widget type and additional properties and return.
    $type = isset($settings['formatted']) && $settings['formatted'] ? 'text_format' : 'textarea';

    if (isset($settings['formatted']) && $settings['formatted'] && $settings['default_format']) {
      $element['#format'] = $settings['default_format'];
      $element['#allowed_formats'] = [$settings['default_format']];
      $element['#after_build'][] = [$this, 'unsetFilters'];
    }

    if (isset($settings['maxlength'])) {
      $element['#attributes']['data-maxlength'] = $settings['maxlength'];
    }
    if (isset($settings['maxlength_js']) && $settings['maxlength_js']) {
      $element['#maxlength_js'] = TRUE;
    }

    return [
      '#type' => $type,
      '#rows' => $settings['rows'] ?? 5,
      '#size' => NULL,
      '#placeholder' => $settings['placeholder'] ?? NULL,
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::widgetSettingsForm($form, $form_state);
    $formats = filter_formats();
    $format_options = [];
    $settings = $this->widget_settings['settings'] + self::defaultWidgetSettings()['settings'];

    foreach ($formats as $key => $format) {
      $format_options[$key] = $format->get('name');
    }

    $element['settings']['rows'] = [
      '#type' => 'number',
      '#title' => t('Rows'),
      '#description' => t('Text editors (like CKEditor) may override this setting.'),
      '#default_value' => $settings['rows'],
      '#required' => TRUE,
      '#min' => 1,
    ];
    $element['settings']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $settings['placeholder'],
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    $element['settings']['formatted'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Wysiwyg'),
      '#default_value' => $settings['formatted'],
    ];
    $element['settings']['default_format'] = [
      '#type' => 'select',
      '#title' => t('Default Format'),
      '#options' => $format_options,
      '#default_value' => $settings['default_format'],
      '#states' => [
        'visible' => [
          ':input[name="settings[field_settings][' . $this->getName() . '][widget_settings][settings][formatted]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['settings']['format'] = [
      '#type' => 'fieldset',
      '#title' => t('Format settings'),
      '#states' => [
        'visible' => [
          ':input[name="settings[field_settings][' . $this->getName() . '][widget_settings][settings][formatted]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['settings']['format']['guidelines'] = [
      '#type' => 'checkbox',
      '#title' => t('Show format guidelines'),
      '#default_value' => $settings['format']['guidelines'],
    ];
    $element['settings']['format']['help'] = [
      '#type' => 'checkbox',
      '#title' => t('Show format help'),
      '#default_value' => $settings['format']['help'],
    ];
    $element['settings']['maxlength'] = [
      '#type' => 'number',
      '#title' => t('Max length'),
      '#description' => t('The maximum amount of characters in the field'),
      '#default_value' => is_numeric($settings['maxlength']) ? $settings['maxlength'] : NULL,
      '#min' => 1,
    ];
    $element['settings']['maxlength_js'] = [
      '#type' => 'checkbox',
      '#title' => t('Show max length character count'),
      '#default_value' => $settings['maxlength_js'],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function value(CustomItem $item): ?string {
    $settings = $this->getWidgetSetting('settings');
    $value = parent::value($item);
    if ($settings['formatted'] && $settings['default_format']) {
      $langcode = $item->getEntity()->language()->getId();
      // Avoid doing unnecessary work on empty strings.
      if (!isset($value) || $value === '') {
        $this->processed = new FilterProcessResult('');
      }
      else {
        $build = [
          '#type' => 'processed_text',
          '#text' => $value,
          '#format' => $settings['default_format'],
          '#langcode' => $langcode,
        ];
        // Capture the cacheability metadata associated with the processed text.
        $processed_text = $this->getRenderer()->renderPlain($build);
        $this->processed = FilterProcessResult::createFromRenderArray($build)->setProcessedText((string) $processed_text);
      }
      return FilteredMarkup::create($this->processed->getProcessedText());
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    if ($violation->arrayPropertyPath == ['format'] && isset($element['format']['#access']) && !$element['format']['#access']) {
      // Ignore validation errors for formats if formats may not be changed,
      // such as when existing formats become invalid.
      // See \Drupal\filter\Element\TextFormat::processFormat().
      return FALSE;
    }
    return $element;
  }

  /**
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *
   * @return array
   */
  public function unsetFilters($element, FormStateInterface $formState) {
    $settings = $this->getWidgetSetting('settings');
    $hide_guidelines = FALSE;
    $hide_help = FALSE;
    if (!$settings['format']['guidelines']) {
      $hide_guidelines = TRUE;
      unset($element['format']['guidelines']);
    }
    if (!$settings['format']['help']) {
      $hide_help = TRUE;
      unset($element['format']['help']);
    }
    if ($hide_guidelines && $hide_help) {
      unset($element['format']['#theme_wrappers']);
    }
    $element['format']['format']['#access'] = FALSE;

    return $element;
  }

  /**
   * Returns the renderer service.
   *
   * @return \Drupal\Core\Render\RendererInterface
   */
  protected function getRenderer() {
    return \Drupal::service('renderer');
  }

}
