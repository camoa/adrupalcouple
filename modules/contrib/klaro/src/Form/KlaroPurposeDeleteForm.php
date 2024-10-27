<?php

namespace Drupal\klaro\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete a Klaro! purpose.
 */
class KlaroPurposeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the Klaro! purpose "%name"?', [
      '%name' => $this->entity->label(),
    ], ['context' => 'klaro']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.klaro_purpose.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete', [], ['context' => 'klaro']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\klaro\Utility\KlaroHelper $helper */
    $helper = \Drupal::service('klaro.helper');
    $id = $this->entity->id();
    $usage = [];
    foreach ($helper->getApps(FALSE) as $app) {
      if (in_array($id, $app->purposes())) {
        $usage[$app->id()] = $app->label();
      }
    }

    if (!empty($usage)) {
      $form_state->setError($form, $this->t('This purpose is still in use by following Klaro! apps: %apps. Please update the apps first before deleting this purpose.', [
        '%apps' => implode(', ', array_values($usage)),
      ], ['context' => 'klaro']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $this->messenger()->addMessage($this->t('Klaro! app %label has been deleted.', [
      '%label' => $this->entity->label(),
    ], ['context' => 'klaro']));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
