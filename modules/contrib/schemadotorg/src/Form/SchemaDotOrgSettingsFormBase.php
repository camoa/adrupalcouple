<?php

declare(strict_types=1);

namespace Drupal\schemadotorg\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Config;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for configuring Schema.org Blueprints settings.
 */
abstract class SchemaDotOrgSettingsFormBase extends ConfigFormBase {

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
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

    // Set the default values for the form being built.
    // Sub-modules default values are set via a form alter hook.
    // @see \Drupal\schemadotorg\Form\SchemaDotOrgSettingsFormBase::formAlter
    $settings_name = explode('.', $config_name)[1];
    if (isset($form[$settings_name])) {
      $elements =& $form[$settings_name];
    }
    else {
      $elements =& $form;
    }
    static::setElementRecursive($elements, $config);

    $form['#tree'] = TRUE;
    $form['#after_build'][] = [get_class($this), 'afterBuildDetails'];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form #after_build callback: Track details element's open/close state.
   */
  public static function afterBuildDetails(array $form, FormStateInterface $form_state): array {
    $form_id = $form_state->getFormObject()->getFormId();

    // Only open the first details element.
    $is_first = ($form_id !== 'schemadotorg_general_settings_form');
    foreach (Element::children($form) as $child_key) {
      if (NestedArray::getValue($form, [$child_key, '#type']) === 'details') {
        $form[$child_key]['#open'] = $is_first;
        $is_first = FALSE;
        $form[$child_key]['#attributes']['data-schemadotorg-details-key'] = "details-$form_id-$child_key";
      }
    }
    $form['#attached']['library'][] = 'schemadotorg/schemadotorg.details';

    // Make sure all the schemadotorg_* module settings are sorted alphabetically.
    $weight = 0;
    $keys = Element::children($form);
    sort($keys);
    foreach ($keys as $key) {
      if (str_starts_with($key, 'schemadotorg_')
        && !NestedArray::keyExists($form, [$key, 'weight'])) {
        $form[$key]['#weight'] = $weight++;
      }
    }

    // Hide the actions if they are the only visible element on the form.
    if (Element::getVisibleChildren($form) === ['actions']) {
      $form['actions']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * Alter Schema.org settings forms.
   *
   * Automatically set the default values and additional properties for
   * Schema.org settings forms that are altered by sub-modules.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see schemadotorg_form_alter()
   */
  public static function formAlter(array &$form, FormStateInterface $form_state): void {
    if (!$form_state->getFormObject() instanceof SchemaDotOrgSettingsFormBase) {
      return;
    }

    foreach (Element::children($form) as $module_name) {
      $config = \Drupal::configFactory()->getEditable("$module_name.settings");
      if (!$config->isNew()) {
        static::setElementRecursive($form[$module_name], $config);
      }
    }
  }

  /**
   * Set Schema.org settings form element properties and default values.
   *
   * @param array $element
   *   A form element.
   * @param \Drupal\Core\Config\Config $config
   *   The form elements associated module config.
   * @param array $parents
   *   The form element's parent and config key path.
   */
  protected static function setElementRecursive(array &$element, Config $config, array $parents = []): void {
    $children = Element::children($element);
    if ($children) {
      foreach ($children as $child) {
        static::setElementRecursive($element[$child], $config, array_merge($parents, [$child]));
      }
    }
    elseif (isset($element['#type'])) {
      $type = $element['#type'];
      switch ($type) {
        case 'checkbox':
          // Set checkbox #return_value to TRUE.
          $element['#return_value'] = $element['#return_value'] ?? TRUE;
          break;

        case 'checkboxes':
          // Set checkboxes #element_validate callback to filter submitted values.
          // @see \Drupal\schemadotorg\Utility\SchemaDotOrgElementHelper::validateCheckboxes
          $element['#element_validate'][] = '::validateCheckboxes';
          break;
      }

      // Set #config_target.
      // @see https://www.drupal.org/node/3373502
      $config_name = $config->getName();
      $config_key = implode('.', $element['#parents'] ?? $parents);
      if ($type === 'schemadotorg_settings') {
        // Set the #config_target for the dedicated Schema.org settings element.
        SchemaDotOrgSettings::setConfigTarget($element, $config_name, $config_key);
      }
      else {
        // Set the #config_target for the simple config element.
        if (!is_null($config->get($config_key))) {
          $element['#config_target'] = "$config_name:$config_key";
        }
      }
    }
  }

  /**
   * Form API callback. Remove unchecked options from #value array.
   */
  public static function validateCheckboxes(array &$element, FormStateInterface $form_state, array &$completed_form): void {
    $values = $element['#value'] ?: [];
    // Filter unchecked/unselected options whose value is 0.
    $values = array_filter(
      $values,
      fn($value) => $value !== 0
    );
    $values = array_values($values);
    $form_state->setValueForElement($element, $values);
  }

}
