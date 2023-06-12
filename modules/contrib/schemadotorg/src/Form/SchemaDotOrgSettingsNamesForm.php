<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Schema.org settings for names.
 */
class SchemaDotOrgSettingsNamesForm extends SchemaDotOrgSettingsFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_names_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['schemadotorg.names'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Display warning about updating names.
    $message = $this->t('Adjusting prefixes, suffixes, and abbreviations can impact existing Schema.org mappings because the expected Drupal field names can change.');
    $this->messenger()->addWarning($message);

    $form['names'] = [
      '#type' => 'details',
      '#title' => $this->t('Name settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['names']['custom_words'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'search|replace',
      '#title' => $this->t('Custom words'),
      '#description' => $this->t('Enter titles used when Schema.org types and properties are converted to Drupal entity and field machine names.'),
    ];
    $form['names']['custom_names'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'search|replace',
      '#title' => $this->t('Custom names'),
      '#description' => $this->t('Enter custom names used when Schema.org types and properties are converted to Drupal entity and field machine names.'),
    ];
    $form['names']['prefixes'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'search|replace',
      '#title' => $this->t('Prefixes'),
      '#description' => $this->t('Enter replacement prefixes used when Schema.org types and properties are converted to Drupal entity and field machine names.')
      . '<br/>' .
      $this->t('Prefixes are always applied to Schema.org types and properties.'),
    ];
    $form['names']['abbreviations'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'search|replace',
      '#title' => $this->t('Abbreviations'),
      '#description' => $this->t('Enter replacement abbreviation used when Schema.org types and properties are converted to Drupal entity and field machine names.')
      . '<br/>' .
      $this->t('Abbreviations are only applied to Schema.org types and properties that exceeed the maxium number of allowed characters.'),
    ];
    $form['names']['suffixes'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'search|replace',
      '#title' => $this->t('Suffixes'),
      '#description' => $this->t('Enter replacement suffixes used when Schema.org types and properties are converted to Drupal entity and field machine names.')
      . '<br/>' .
      $this->t('Suffixes are only applied to Schema.org types and properties that exceeed the maxium number of allowed characters.'),
    ];
    $form['names']['acronyms'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Acronyms'),
      '#description' => $this->t('Enter acronyms used when creating labels.'),
    ];
    $form['names']['minor_words'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Minor words'),
      '#description' => $this->t('Enter minor word used when creating capitalized labels.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('schemadotorg.names');
    $values = $form_state->getValue('names');
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
