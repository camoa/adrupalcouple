<?php

namespace Drupal\geolocation\Plugin\views\field;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\geolocation\LocationInputManager;
use Drupal\geolocation\LocationManager;
use Drupal\geolocation\ProximityTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler for geolocation field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("geolocation_field_proximity_form")
 */
class ProximityFormField extends ProximityField implements ContainerFactoryPluginInterface {

  use ProximityTrait;

  /**
   * Center value.
   *
   * @var array
   */
  protected array $centerValue = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LocationManager $location_manager,
    protected LocationInputManager $locationInputManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $location_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ProximityField {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geolocation.location'),
      $container->get('plugin.manager.geolocation.locationinput')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);

    $user_input = $form_state->getUserInput();
    $proximity_center_options = NestedArray::getValue(
      $user_input,
      ['options', 'center'],
    );
    if (empty($proximity_center_options)) {
      $proximity_center_options = $this->options['center'];
    }
    if (empty($proximity_center_options)) {
      $proximity_center_options = [];
    }
    $form['center'] = $this->locationInputManager->getOptionsForm($proximity_center_options, ['views_field' => $this]);
  }

  /**
   * Views form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function viewsForm(array &$form, FormStateInterface $form_state): void {
    $form['#tree'] = TRUE;
    $form['center'] = $this->locationInputManager->getForm($this->options['center'], ['views_field' => $this], $this->getCenter());

    $form['actions']['submit']['#value'] = $this->t('Calculate proximity');

    // #weight will be stripped from 'output' in preRender callback.
    // Offset negatively to compensate.
    foreach (Element::children($form) as $key) {
      if (isset($form[$key]['#weight'])) {
        $form[$key]['#weight'] = $form[$key]['#weight'] - 2;
      }
      else {
        $form[$key]['#weight'] = -2;
      }
    }
    $form['actions']['#weight'] = -1;
  }

  /**
   * Submit handler for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user tried to access an action without access to it.
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state): void {
    if ($form_state->get('step') == 'views_form_views_form') {
      $form_state->disableRedirect();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getCenter(): array {
    if (empty($this->centerValue)) {
      $this->centerValue = $this->locationInputManager->getCoordinates((array) $this->view->getRequest()->get('center', []), $this->options['center'], ['views_field' => $this]);
    }
    return $this->centerValue;
  }

}
