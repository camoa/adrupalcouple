<?php

namespace Drupal\klaro\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Creates the Klaro! text config form.
 */
class TextSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'klaro_text_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'klaro.texts',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('klaro.texts');

    $form['klaro'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Texts', [], ['context' => 'klaro']),
    ];

    $form['initial'] = [
      '#type' => 'details',
      '#title' => $this->t('Initial view', [], ['context' => 'klaro']),
      '#group' => 'klaro',
      '#weight' => 1,
      '#tree' => TRUE,
    ];

    $form['initial']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description', [], ['context' => 'klaro']),
      '#default_value' => $config->get('consentNotice.description'),
      '#description' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('You can use following placeholders: %placeholders', [
            '%placeholders' => '{' . implode('}, {', [
              'privacyPolicy',
              'purposes',
            ]) . '}',
          ], ['context' => 'klaro']),
        ],
      ],
    ];
    $form['initial']['changes_description'] = [
      '#type' => 'textfield',
      '#size' => 150,
      '#title' => $this->t('Changes notification', [], ['context' => 'klaro']),
      '#description' => $this->t('If apps change but the user previously gave consent, a change notification is shown so the user can confirm these changes.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('consentNotice.changeDescription'),
    ];

    $form['initial']['privacy_policy'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Privacy policy', [], ['context' => 'klaro']),
    ];
    $form['initial']['privacy_policy']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('URL', [], ['context' => 'klaro']),
      '#description' => $this->t('You can enter an internal path such as %path or an external URL such as %url', [
        '%path' => 'internal:/node/add',
        '%url' => 'https://example.org',
      ], ['context' => 'klaro']),
      '#default_value' => $config->get('consentModal.privacyPolicy.url'),
      '#placeholder' => 'internal:/<front>',
      '#required' => TRUE,
      '#element_validate' => [
        [get_class($this), 'validateUrl'],
      ],
    ];
    $form['initial']['privacy_policy']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title', [], ['context' => 'klaro']),
      '#default_value' => $config->get('consentModal.privacyPolicy.name'),
    ];

    $form['initial']['operations'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Operations', [], ['context' => 'klaro']),
    ];
    $form['initial']['operations']['ok'] = [
      '#type' => 'textfield',
      '#title' => $this->t('%button label', ['%button' => $this->t('OK', [], ['context' => 'klaro'])], ['context' => 'klaro']),
      '#default_value' => $config->get('ok'),
    ];
    $form['initial']['operations']['accept_all'] = [
      '#type' => 'textfield',
      '#title' => $this->t('%button label', ['%button' => $this->t('Accept all', [], ['context' => 'klaro'])], ['context' => 'klaro']),
      '#default_value' => $config->get('acceptAll'),
    ];
    $form['initial']['operations']['decline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('%button label', ['%button' => $this->t('Decline', [], ['context' => 'klaro'])], ['context' => 'klaro']),
      '#default_value' => $config->get('decline'),
    ];
    $form['initial']['operations']['learn_more'] = [
      '#type' => 'textfield',
      '#title' => $this->t('%label text', ['%label' => $this->t('Learn more', [], ['context' => 'klaro'])], ['context' => 'klaro']),
      '#description' => $this->t('By clicking this link, the user can view and customize which apps are used on the website.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('consentNotice.learnMore'),
    ];

    $form['manage_apps'] = [
      '#type' => 'details',
      '#title' => $this->t('Manage apps', [], ['context' => 'klaro']),
      '#group' => 'klaro',
      '#weight' => 1,
      '#tree' => TRUE,
    ];

    $form['manage_apps']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title', [], ['context' => 'klaro']),
      '#description' => $this->t('The title of the consent modal.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('consentModal.title'),
    ];
    $form['manage_apps']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description', [], ['context' => 'klaro']),
      '#description' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('The description within the consent modal, you can use following placeholders: %placeholders', [
            '%placeholders' => '{' . implode('}, {', [
              'purposes',
            ]) . '}',
          ], ['context' => 'klaro']),
        ],
      ],
      '#default_value' => $config->get('consentModal.description'),
    ];
    $form['manage_apps']['privacy_policy'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Privacy policy text', [], ['context' => 'klaro']),
      '#default_value' => $config->get('consentModal.privacyPolicy.text'),
      '#description' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('You can use following placeholders: %placeholders.', [
            '%placeholders' => '{privacyPolicy}',
          ], ['context' => 'klaro']),
        ],
      ],
    ];
    $form['manage_apps']['powered_by'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Powered by', [], ['context' => 'klaro']),
      '#default_value' => $config->get('poweredBy'),
      '#description' => $this->t('Clear to remove this information from the Klaro! consent manager modal.', [], ['context' => 'klaro']),
    ];

    // "Toggle all" slider.
    $form['manage_apps']['toggle_all'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Toggle all', [], ['context' => 'klaro']),
      '#open' => TRUE,
    ];
    $form['manage_apps']['toggle_all']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title', [], ['context' => 'klaro']),
      '#default_value' => $config->get('app.disableAll.title'),
    ];
    $form['manage_apps']['toggle_all']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description', [], ['context' => 'klaro']),
      '#default_value' => $config->get('app.disableAll.description'),
    ];

    $form['manage_apps']['operations'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Operations', [], ['context' => 'klaro']),
    ];
    $form['manage_apps']['operations']['save'] = [
      '#type' => 'textfield',
      '#title' => $this->t('%button label', ['%button' => $this->t('Save', [], ['context' => 'klaro'])], ['context' => 'klaro']),
      // @todo Describe where this text is used.
      '#default_value' => $config->get('save'),
    ];
    $form['manage_apps']['operations']['accept_selected'] = [
      '#type' => 'textfield',
      '#title' => $this->t('%button label', ['%button' => $this->t('Accept selected', [], ['context' => 'klaro'])], ['context' => 'klaro']),
      '#default_value' => $config->get('acceptSelected'),
    ];
    $form['manage_apps']['operations']['close'] = [
      '#type' => 'textfield',
      '#title' => $this->t('%label text', ['%label' => $this->t('Close', [], ['context' => 'klaro'])], ['context' => 'klaro']),
      '#description' => $this->t('The description that will appear if user hovers over the close link of the modal.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('close'),
    ];

    $form['app'] = [
      '#type' => 'details',
      '#title' => $this->t('App item', [], ['context' => 'klaro']),
      '#group' => 'klaro',
      '#weight' => 3,
      '#tree' => TRUE,
    ];

    // Required marker.
    $form['app']['required'] = [
      '#type' => 'details',
      '#title' => $this->t('Required', [], ['context' => 'klaro']),
      '#open' => TRUE,
    ];
    $form['app']['required']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title', [], ['context' => 'klaro']),
      '#default_value' => $config->get('app.required.title'),
    ];
    $form['app']['required']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description', [], ['context' => 'klaro']),
      '#default_value' => $config->get('app.required.description'),
    ];

    // Opt out marker.
    $form['app']['opt_out'] = [
      '#type' => 'details',
      '#title' => $this->t('Opt out', [], ['context' => 'klaro']),
      '#open' => TRUE,
    ];
    $form['app']['opt_out']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title', [], ['context' => 'klaro']),
      '#default_value' => $config->get('app.optOut.title'),
    ];
    $form['app']['opt_out']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description', [], ['context' => 'klaro']),
      '#default_value' => $config->get('app.optOut.description'),
    ];

    // "Purposes" list.
    $form['app']['purpose'] = [
      '#type' => 'fieldgroup',
      '#title' => $this->t('Purpose', [], ['context' => 'klaro']),
    ];
    $form['app']['purpose']['singular'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Singular form', [], ['context' => 'klaro']),
      '#default_value' => $config->get('app.purpose'),
    ];
    $form['app']['purpose']['plural'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plural form', [], ['context' => 'klaro']),
      '#default_value' => $config->get('app.purposes'),
    ];

    $form['contextualConsent'] = [
      '#type' => 'details',
      '#title' => $this->t('Contextual consent', [], ['context' => 'klaro']),
      '#group' => 'klaro',
      '#weight' => 4,
      '#tree' => TRUE,
    ];
    $form['contextualConsent']['acceptAlways'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Accept always', [], ['context' => 'klaro']),
      '#default_value' => $config->get('contextualConsent.acceptAlways'),
    ];
    $form['contextualConsent']['acceptOnce'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Accept once', [], ['context' => 'klaro']),
      '#default_value' => $config->get('contextualConsent.acceptOnce'),
    ];
    $form['contextualConsent']['description'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Use {title} as a placeholder for the appname', [], ['context' => 'klaro']),
      '#title' => $this->t('Description', [], ['context' => 'klaro']),
      '#default_value' => $config->get('contextualConsent.description'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('klaro.texts');

    // "Consent Notice" settings.
    $config
      ->set('consentNotice.description', $form_state->getValue([
        'initial',
        'description',
      ]))
      ->set('consentNotice.changeDescription', $form_state->getValue([
        'initial',
        'changes_description',
      ]))

      ->set('ok', $form_state->getValue(['initial', 'operations', 'ok']))
      ->set('acceptAll', $form_state->getValue([
        'initial',
        'operations',
        'accept_all',
      ]))
      ->set('decline', $form_state->getValue([
        'initial',
        'operations',
        'decline',
      ]))
      ->set('consentNotice.learnMore', $form_state->getValue([
        'initial',
        'operations',
        'learn_more',
      ]));

    // "Consent Modal" settings.
    $config
      ->set('consentModal.title', $form_state->getValue([
        'manage_apps',
        'title',
      ]))
      ->set('consentModal.description', $form_state->getValue([
        'manage_apps',
        'description',
      ]))
      ->set('consentModal.privacyPolicy.text', $form_state->getValue([
        'manage_apps',
        'privacy_policy',
      ]))
      ->set('consentModal.privacyPolicy.name', $form_state->getValue([
        'initial',
        'privacy_policy',
        'title',
      ]))
      ->set('consentModal.privacyPolicy.url', $form_state->getValue([
        'initial',
        'privacy_policy',
        'url',
      ]))
      ->set('poweredBy', $form_state->getValue(['manage_apps', 'powered_by']))

      ->set('save', $form_state->getValue(['manage_apps', 'operations', 'save']))
      ->set('acceptSelected', $form_state->getValue([
        'manage_apps',
        'operations',
        'accept_selected',
      ]))
      ->set('close', $form_state->getValue([
        'manage_apps',
        'operations',
        'close',
      ]))

      ->set('app.required', $form_state->getValue(['app', 'required']))
      ->set('app.optOut', $form_state->getValue(['app', 'opt_out']))
      ->set('app.purpose', $form_state->getValue(['app', 'purpose', 'singular']))
      ->set('app.purposes', $form_state->getValue(['app', 'purpose', 'plural']))
      ->set('app.disableAll', $form_state->getValue([
        'manage_apps',
        'toggle_all',
      ]));

    // Contextual Blocking.
    $config
      ->set('contextualConsent.acceptAlways', $form_state->getValue([
        'contextualConsent',
        'acceptAlways',
      ]))
      ->set('contextualConsent.acceptOnce', $form_state->getValue([
        'contextualConsent',
        'acceptOnce',
      ]))
      ->set('contextualConsent.description', $form_state->getValue([
        'contextualConsent',
        'description',
      ]));

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Form element validation handler replacement for #type 'url'.
   */
  public static function validateUrl(&$element, FormStateInterface $form_state) {
    $value = trim($element['#value']);
    $form_state->setValueForElement($element, $value);

    if ($value !== '' && !UrlHelper::isValid($value, TRUE)) {
      // Value is not a valid absolute url.
      try {
        // Make sure the uri looks sensible.
        $url = Url::fromUri($value);
        // Make sure we can actually build a link with this uri.
        $url->toString();
      }
      // @todo Provide translated messages for the various error states.
      catch (InvalidArgumentException $e) {
        $form_state->setError($element, t('The URL %url is not valid: @error', [
          '%url' => $value,
          '@error' => $e->getMessage(),
        ]));
      }
      catch (RouteNotFoundException $e) {
        $form_state->setError($element, t('The specified route does not exist.'));
      }
    }
  }

}
