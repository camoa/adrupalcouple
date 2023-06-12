<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_starterkit\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form before clearing out the examples.
 */
class SchemaDotOrgStarterkitConfirmForm extends ConfirmFormBase {

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Schema.org starterkitmanager service.
   *
   * @var \Drupal\schemadotorg_starterkit\SchemaDotOrgStarterkitManagerInterface
   */
  protected $schemaStarterkitManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->moduleHandler = $container->get('module_handler');
    $instance->schemaStarterkitManager = $container->get('schemadotorg_starterkit.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_starterkit_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    $t_args = [
      '@action' => $this->getAction(),
      '%name' => $this->getLabel(),
    ];
    return $this->t("Are you sure you want to @action the %name starterkit?", $t_args);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    $t_args = [
      '@action' => $this->getAction(),
      '%name' => $this->getLabel(),
    ];
    return $this->t('Please confirm that you want @action the %name starterkit.', $t_args);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('schemadotorg_starterkit.overview');
  }

  /**
   * The starterkitname.
   *
   * @var string
   */
  protected $name;

  /**
   * The starterkitoperation to be performed.
   *
   * @var string
   */
  protected $operation;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $name = NULL, ?string $operation = NULL): array {
    $this->name = $name;
    $this->operation = $operation;

    $form = parent::buildForm($form, $form_state);
    switch ($this->operation) {
      case 'install':
        // Add note after the actions element which has a weight of 100.
        $form['note'] = [
          '#weight' => 101,
          '#markup' => $this->t('Please note that the installation and setting up of multiple entity types and fields may take a minute or two to complete.'),
          '#prefix' => '<div><em>',
          '#suffix' => '</em></div>',
        ];
        break;
    }

    if ($form_state->isMethodType('get')
      && in_array($this->operation, ['generate', 'kill'])) {
      $this->messenger()->addWarning($this->t('All existing content will be deleted.'));
    }

    $form['#attributes']['class'][] = 'js-schemadotorg-submit-once';
    $form['#attached'] = ['library' => ['schemadotorg/schemadotorg.form']];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Execute the operation.
    $operation = $this->operation;
    $name = $this->name;

    $operations = [];
    $operations['install'] = $this->t('installed');
    $operations['generate'] = $this->t('generated');
    $operations['kill'] = $this->t('killed');

    try {
      $this->schemaStarterkitManager->$operation($name);

      // Display a custom message.
      $t_args = [
        '@action' => $operations[$this->operation],
        '%name' => $this->getLabel(),
      ];
      $this->messenger()->addStatus($this->t('The %name starterkit has been @action.', $t_args));
    }
    catch (\Exception $exception) {
      // Display a custom message.
      $t_args = [
        '@action' => $operations[$this->operation],
        '%name' => $this->getLabel(),
      ];
      $this->messenger()->addStatus($this->t('The %name starterkit has failed to be @action.', $t_args));
      $this->messenger->addError($exception->getMessage());
    }

    // Redirect to the starterkit manage page.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Get the current starterkit's label.
   *
   * @return string
   *   The current starterkit's label.
   */
  protected function getLabel(): string {
    $starterkit = $this->schemaStarterkitManager->getStarterkit($this->name);
    if (!$starterkit) {
      throw new NotFoundHttpException();
    }
    return $starterkit['name'];
  }

  /**
   * Get the current starterkit's action.
   *
   * @return string
   *   The current starterkit's action.
   */
  protected function getAction(): TranslatableMarkup {
    $is_installed = $this->moduleHandler->moduleExists($this->name);
    $operations = [];
    if (!$is_installed) {
      if ($this->currentUser()->hasPermission('administer modules')) {
        $operations['install'] = $this->t('install');
      }
    }
    elseif ($this->moduleHandler->moduleExists('devel_generate')) {
      $operations['generate'] = $this->t('generate');
      $operations['kill'] = $this->t('kill');
    }
    if (!isset($operations[$this->operation])) {
      throw new NotFoundHttpException();
    }
    return $operations[$this->operation];
  }

  /**
   * Get the current starterkit's name.
   *
   * @return string
   *   the current starterkit's name.
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * Get the current starterkit's operation.
   *
   * @return string
   *   the current starterkit's operation.
   */
  public function getOperation(): string {
    return $this->operation;
  }

}
