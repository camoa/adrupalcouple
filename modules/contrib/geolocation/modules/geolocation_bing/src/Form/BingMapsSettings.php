<?php

namespace Drupal\geolocation_bing\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the Bing Maps form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class BingMapsSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->configFactory->get('bing_maps.settings');

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bing Maps Key'),
      '#default_value' => $config->get('key'),
      '#description' => $this->t('Bing Maps requires users to sign up at <a href="https://www.bingmapsportal.com">https://www.bingmapsportal.com</a>.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'geolocation_bing_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'bing_maps.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->configFactory()->getEditable('bing_maps.settings');
    $config->set('key', $form_state->getValue('key'));

    $config->save();

    // Confirmation on form submission.
    $this->messenger()->addMessage($this->t('The configuration options have been saved.'));

    drupal_flush_all_caches();
  }

}
