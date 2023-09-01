<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Config;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for configuring Schema.org Blueprints settings.
 */
abstract class SchemaDotOrgSettingsFormBase extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config_names = $this->getEditableConfigNames();
    $config_name = reset($config_names);
    $config = $this->config($config_name);

    $settings_name = explode('.', $config_name)[1];
    if (isset($form[$settings_name])) {
      $elements = $form[$settings_name];
    }
    else {
      $elements = $form;
    }
    static::setDefaultValuesRecursive($elements, $config);

    $form['#after_build'][] = [get_class($this), 'afterBuildDetails'];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form #after_build callback: Track details element's open/close state.
   */
  public static function afterBuildDetails(array $form, FormStateInterface $form_state): array {
    $form_id = $form_state->getFormObject()->getFormId();

    // Only open the first details element.
    $is_first = TRUE;
    $has_details = FALSE;
    foreach (Element::children($form) as $child_key) {
      if (NestedArray::getValue($form, [$child_key, '#type']) === 'details') {
        $form[$child_key]['#open'] = $is_first;
        $is_first = FALSE;
        $has_details = TRUE;
        $form[$child_key]['#attributes']['data-schemadotorg-details-key'] = "details-$form_id-$child_key";
      }
    }
    $form['#attached']['library'][] = 'schemadotorg/schemadotorg.details';

    // Hide the submit button if the form has no details elements.
    if (!$has_details) {
      $form['actions']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Update configuration for schemadotorg_* sub-modules.
    foreach (Element::children($form) as $element_key) {
      if (str_starts_with($element_key, 'schemadotorg_')
        && $this->moduleHandler->moduleExists($element_key)
        && !$this->configFactory()->get($element_key . '.settings')->isNew()) {
        $config = $this->configFactory()->getEditable($element_key . '.settings');
        $data = $config->getRawData();
        $values = $form_state->getValue($element_key);
        foreach ($values as $key => $value) {
          if (array_key_exists($key, $data)) {
            $config->set($key, $value);
          }
        }
        $config->save();
      }
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Alter Schema.org settings forms.
   *
   * Automatically set the default values for Schema.org settings forms that
   * are altered by sub-modules.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function formAlter(array &$form, FormStateInterface $form_state): void {
    if (!$form_state->getFormObject() instanceof SchemaDotOrgSettingsFormBase) {
      return;
    }

    foreach (Element::children($form) as $module_name) {
      $config = \Drupal::configFactory()->getEditable("$module_name.settings");
      if (!$config->isNew()) {
        static::setDefaultValuesRecursive($form[$module_name], $config);
      }
    }
  }

  /**
   * Set Schema.org settings form element default values.
   *
   * @param array $element
   *   A form element.
   * @param \Drupal\Core\Config\Config $config
   *   The form elements associated module config.
   * @param array $parents
   *   The form element's parent and config key path.
   */
  protected static function setDefaultValuesRecursive(array &$element, Config $config, array $parents = []): void {
    $children = Element::children($element);
    if ($children) {
      foreach ($children as $child) {
        static::setDefaultValuesRecursive($element[$child], $config, array_merge($parents, [$child]));
      }
    }
    else {
      $config_key = implode('.', $element['#parents'] ?? $parents);
      $config_value = $config->get($config_key);
      if (!isset($element['#default_value']) && !is_null($config_value)) {
        $element['#default_value'] = $config_value;
      }
    }
  }

}
