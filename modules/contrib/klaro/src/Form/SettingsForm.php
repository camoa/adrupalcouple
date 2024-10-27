<?php

namespace Drupal\klaro\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\klaro\Utility\KlaroHelper;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * General settings form for the Klaro! consent manager.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Drupal\klaro\Utility\KlaroHelper.
   *
   * @var \Drupal\klaro\Utility\KlaroHelper
   */
  protected $klaro;

  /**
   * The constructor.
   *
   * @param Drupal\klaro\Utility\KlaroHelper $klaro
   *   The Klaro Helper.
   */
  public function __construct(KlaroHelper $klaro) {
    $this->klaro = $klaro;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('klaro.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'klaro_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'klaro.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('klaro.settings');

    if (!$this->klaro->hasLibraryFiles() && !$this->klaro->hasDeprecatedLibraryFiles()) {
      $form['lib_error'] = [
        'message' => [
          '#theme' => 'status_messages',
          '#message_list' => [
            'error' => [
              $this->t('The klaro-js library is not found at libraries folder, please read the install instructions in the README of the klaro drupal module.'),
            ],
          ],
          '#status_headings' => [
            'error' => $this
              ->t('Error message'),
          ],
        ],
      ];
    }

    $anon_role = Role::load('anonymous');

    $role_with_permission = FALSE;
    foreach (Role::loadMultiple() as $rolename => $role) {
      if ($rolename != 'administrator' && $role->hasPermission('use klaro')) {
        $role_with_permission = TRUE;
      }
    }
    if (!$role_with_permission) {
      $form['role_hint'] = [
        'message' => [
          '#theme' => 'status_messages',
          '#message_list' => [
            'warning' => [
              $this->t('Currently only the administrator role has the "use klaro" permission. To let the visitors of your site manager their consents with klaro, add the "use klaro" permission to role "anonymous"'),
            ],
          ],
          '#status_headings' => [
            'warning' => $this
              ->t('No permissions set'),
          ],
        ],
      ];
    }

    $form['about'] = [
      '#markup' => $this->t('The Klaro! consent <em>notice</em> briefly informs the user about third-party uses. The consent <em>modal</em> can be used to toggle individual apps or purposes.', [], ['context' => 'klaro']),
    ];

    $form['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Library settings', [], ['context' => 'klaro']),
    ];

    // General settings.
    $form['general_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('General', [], ['context' => 'klaro']),
      '#description' => $this->t('Configure behavior and options for the Klaro! element.', [], ['context' => 'klaro']),
      '#group' => 'vertical_tabs',
    ];

    $form['general_settings']['must_consent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require user action', [], ['context' => 'klaro']),
      '#description' => $this->t('Check to immediately display the consent manager modal and prevent site interaction until the user accepts/declines the apps.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('library.must_consent'),
    ];
    $form['general_settings']['notice_as_modal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show notice as modal', [], ['context' => 'klaro']),
      '#description' => $this->t('Same as "Require user action" but shows notice element as a modal.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('library.notice_as_modal'),
      '#states' => [
        'visible' => [
          ':input[name="must_consent"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['general_settings']['apps'] = [
      '#type' => 'fieldgroup',
      '#title' => $this->t('Apps', [], ['context' => 'klaro']),
      '#tree' => TRUE,
    ];

    $form['general_settings']['apps']['group_by_purpose'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Group by purpose', [], ['context' => 'klaro']),
      '#description' => $this->t('Allow the user to enable or disable entire groups of apps at once. This also reduces the space taken up by the modal, which is important especially for websites that use many third-party applications.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('library.group_by_purpose'),
    ];
    $form['general_settings']['apps']['process_descriptions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verbose app descriptions'),
      '#description' => $this->t('If enabled, all Klaro! app descriptions will be processed. As for now they will get extended by the privacy policy url and the info url. If you enable "Allow HTML in texts" at settings->styling the links will be formatted as anchors, otherwise they can only be displayed as text and are not clickable', [], ['context' => 'klaro']),
      '#default_value' => $config->get('process_descriptions'),
    ];

    $form['general_settings']['buttons'] = [
      '#type' => 'fieldgroup',
      '#title' => $this->t('Buttons', [], ['context' => 'klaro']),
      '#tree' => TRUE,
    ];
    $form['general_settings']['buttons']['accept_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Accept all', [], ['context' => 'klaro']),
      '#description' => $this->t('If checked, <em>all</em> apps are accepted, instead of only required apps and those enabled by default.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('library.accept_all'),
    ];
    $form['general_settings']['buttons']['decline_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Decline all', [], ['context' => 'klaro']),
      '#description' => $this->t('Show decline button in notice and decline all button in consent modal.', [], ['context' => 'klaro']),
      '#default_value' => !$config->get('library.hide_decline_all'),
    ];
    $form['general_settings']['buttons']['learn_more'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Learn more', [], ['context' => 'klaro']),
      '#description' => $this->t('Show a link in the notice element that opens the consent modal.', [], ['context' => 'klaro']),
      '#default_value' => !$config->get('library.hide_learn_more'),
    ];
    $form['general_settings']['buttons']['learn_more_as_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Learn more as a button', [], ['context' => 'klaro']),
      '#description' => $this->t('Displays the learn more link with a button style.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('library.learn_more_as_button'),
      '#states' => [
        'visible' => [':input[name="buttons[learn_more]"]' => ['checked' => TRUE]],
      ],
    ];
    $form['general_settings']['buttons']['show_toggle_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show button to toggle the consent modal', [], ['context' => 'klaro']),
      '#description' => $this->t('Adds a toggle button that is shipped with this module to open the consent modal after user action.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('show_toggle_button'),
      '#attributes' => [
        'name' => 'buttons[show_toggle_button]',
      ],
    ];

    // Storage settings.
    $form['storage_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Storage', [], ['context' => 'klaro']),
      '#group' => 'vertical_tabs',
    ];

    $form['storage_settings']['storage_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Storage type', [], ['context' => 'klaro']),
      '#description' => $this->t('How Klaro! should store the preferences of the user.', [], ['context' => 'klaro']),
      '#required' => TRUE,
      '#options' => [
        'cookie' => $this->t('Cookie', [], ['context' => 'klaro']),
        'localStorage' => $this->t('Local storage', [], ['context' => 'klaro']),
      ],
      '#default_value' => $config->get('library.storage_method'),
    ];

    $form['storage_settings']['cookie_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie name', [], ['context' => 'klaro']),
      '#description' => $this->t('You can customize the name of the cookie that Klaro! uses for storing user consent decisions.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('library.cookie_name'),
      '#states' => [
        'visible' => [':input[name="storage_method"]' => ['value' => 'cookie']],
      ],
    ];
    $form['storage_settings']['cookie_expires_after_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Cookie expires after', [], ['context' => 'klaro']),
      '#description' => $this->t('You can also set a custom expiration time for the Klaro! cookie.', [], ['context' => 'klaro']),
      '#min' => 0,
      '#max' => 365,
      '#step' => 1,
      '#field_suffix' => $this->t('days', [], ['context' => 'klaro']),
      '#default_value' => $config->get('library.cookie_expires_after_days'),
      '#states' => [
        'visible' => [':input[name="storage_method"]' => ['value' => 'cookie']],
      ],
    ];
    $form['storage_settings']['cookie_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie domain', [], ['context' => 'klaro']),
      '#description' => $this->t('You can change to cookie domain for the consent manager itself. Use this if you want to get consent once for multiple matching domains. If undefined, Klaro! will use the current domain.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('library.cookie_domain'),
      '#states' => [
        'visible' => [':input[name="storage_method"]' => ['value' => 'cookie']],
      ],
    ];

    // Consent settings.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced', [], ['context' => 'klaro']),
      '#description' => $this->t('Configure advanced settings.', [], ['context' => 'klaro']),
      '#group' => 'vertical_tabs',
    ];

    $form['advanced']['deletable_cookie_domains'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#title' => $this->t('Matching cookie domains', [], ['context' => 'klaro']),
      '#description' => $this->t('Enter one domain per line for cookie deletion. Leave empty to delete the current domain of the visitor only.', [], ['context' => 'klaro']),
      '#default_value' => implode("\n", $config->get('deletable_cookie_domains')),
    ];

    $form['advanced']['exclude_urls'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#title' => $this->t('Disable Klaro and block attributed ressources on following url patterns', [], ['context' => 'klaro']),
      '#description' => $this->t('Enter one regular expression per line without delimiters, i.e  \/admin\/ will match all paths that contain /admin/ while i.e ^\/en will match all routes that start with /en. On these paths all ressources remain blocked and Klaro will be disabled.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('exclude_urls') ? implode("\n", $config->get('exclude_urls')) : '',
    ];

    $form['advanced']['disable_urls'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#title' => $this->t('Disable Klaro element and dont block attributed ressources on following url patterns', [], ['context' => 'klaro']),
      '#description' => $this->t('Enter one regular expression per line without delimiters, i.e  \/admin\/ will match all paths that contain /admin/ while i.e ^\/en will match all routes that start with /en. On these paths no ressources are blocked and Klaro will be disabled.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('disable_urls') ? implode("\n", $config->get('disable_urls')) : '',
    ];

    // Auto decorate settings.
    $form['auto_decorate'] = [
      '#type' => 'details',
      '#title' => $this->t('Automatic attribution'),
      '#description' => $this->t('To make Klaro! block ressources, they need <a target="_blank" href="@website">special html attributes</a> which the klaro library expects you to add manually, however this module can try to set them automatically.', ['@website' => "https://heyklaro.com/docs/getting-started"], ['context' => 'klaro']),
      '#group' => 'vertical_tabs',
    ];
    $form['auto_decorate']['block_unknown'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Block unknown external resources'),
      '#description' => $this->t('Matches and decorates resources that are external and did not match a configured app (Only works if "Process final HTML" is activated).', [], ['context' => 'klaro']),
      '#default_value' => $config->get('block_unknown'),
    ];

    $form['auto_decorate']['block_unknown_logger'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log unknown resources', [], ['context' => 'klaro']),
      '#description' => $this->t('Creates a notice in recent log messages whenever an unknown external resource is requested.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('block_unknown_logger'),
      '#states' => [
        'visible' => [
          ':input[name="block_unknown"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['auto_decorate']['block_unknown_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label for the unknown app.', [], ['context' => 'klaro']),
      '#description' => $this->t('Matches and decorates resources that are external and did not match a configured app.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('block_unknown_label'),
      '#states' => [
        'visible' => [
          ':input[name="block_unknown"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['auto_decorate']['block_unknown_description'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#title' => $this->t('Description text for the unknown app.', [], ['context' => 'klaro']),
      '#description' => $this->t('Matches and decorates resources that are external and did not match a configured app.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('block_unknown_description'),
      '#states' => [
        'visible' => [
          ':input[name="block_unknown"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['auto_decorate']['js_alter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Process js_alter', [], ['context' => 'klaro']),
      '#description' => $this->t('Matches and decorates script files added from libraries against the configurated apps.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('auto_decorate_js_alter'),
    ];
    $form['auto_decorate']['page_attachments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Process page_attachments', [], ['context' => 'klaro']),
      '#description' => $this->t('Matches and decorates manually attached JS files against the configurated apps.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('auto_decorate_page_attachments'),
    ];
    $form['auto_decorate']['final_html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Process final HTML', [], ['context' => 'klaro']),
      '#description' => $this->t('Adds contextual blocking to iframe, img, audio and video tags and adds attributes to all matching script tags that are not attributed yet. This feature is rather experimental, invalid or malformed html might lead to unknown behaviour.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('auto_decorate_final_html'),
    ];

    // Inbuilt styling settings.
    $form['styling'] = [
      '#type' => 'details',
      '#title' => $this->t('Styling', [], ['context' => 'klaro']),
      '#description' => $this->t('Configure styling settings.', [], ['context' => 'klaro']),
      '#group' => 'vertical_tabs',
    ];

    $form['styling']['element_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element ID', [], ['context' => 'klaro']),
      '#description' => $this->t('Specify the HTML CSS identifier for the Klaro! container.', [], ['context' => 'klaro']),
      '#default_value' => $config->get('library.element_id'),
      '#required' => TRUE,
      // In theory, others are allowed, but we like to KISS and stay sane.
      '#pattern' => '[^0-9-][a-zA-Z0-9_-]*',
    ];

    $form['styling']['additional_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional CSS classes', [], ['context' => 'klaro']),
      '#description' => $this->t('Add custom classes seperated by spaces to the Klaro! container, i.e. "custom-class-one custom-class-two"', [], ['context' => 'klaro']),
      '#default_value' => $config->get('library.additional_class'),
    ];

    $form['styling']['styles'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override Klaro css variables', [], ['context' => 'klaro']),
      '#description' => $this->t('Override inbuilt klaro css variables seperated by a comma, i.e. "light, top" to use the light theme and position the notice at the top, <a href="@website" target="_blank"> More infos </a>', ['@website' => 'https://github.com/kiprotect/klaro/blob/fb4e393d2cd8aeedc3e751d103dfbfd35ffae0f2/src/themes.js'], ['context' => 'klaro']),
      '#default_value' => $config->get('styles') ? implode(',', $config->get('styles')) : '',
    ];

    $form['styling']['html_texts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow HTML in texts.', [], ['context' => 'klaro']),
      '#description' => $this->t('Setting this to true will render the descriptions of the consent. Use with care! (Does not work for button texts)', [], ['context' => 'klaro']),
      '#default_value' => $config->get('library.html_texts'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('klaro.settings');

    $cookie_domains = array_map('trim', explode("\n", $form_state->getValue('deletable_cookie_domains')));
    $exclude_urls = array_map('trim', explode("\n", $form_state->getValue('exclude_urls')));
    $disable_urls = array_map('trim', explode("\n", $form_state->getValue('disable_urls')));
    $styles = array_map('trim', explode(",", $form_state->getValue('styles')));

    $config
      ->set('library.element_id', $form_state->getValue('element_id'))
      ->set('library.storage_method', $form_state->getValue('storage_method'))
      ->set('library.cookie_name', $form_state->getValue('cookie_name'))
      ->set('library.cookie_expires_after_days', $form_state->getValue('cookie_expires_after_days'))
      ->set('library.cookie_domain', $form_state->getValue('cookie_domain'))
      ->set('library.must_consent', $form_state->getValue('must_consent'))
      ->set('library.notice_as_modal', $form_state->getValue('notice_as_modal'))
      ->set('library.additional_class', $form_state->getValue('additional_class'))
      ->set('library.html_texts', $form_state->getValue('html_texts'))
      ->set('library.group_by_purpose', $form_state->getValue([
        'apps',
        'group_by_purpose',
      ]))
      ->set('library.accept_all', !!$form_state->getValue([
        'buttons',
        'accept_all',
      ]))
      ->set('library.hide_decline_all', !$form_state->getValue([
        'buttons',
        'decline_all',
      ]))
      ->set('library.hide_learn_more', !$form_state->getValue([
        'buttons',
        'learn_more',
      ]))
      ->set('library.learn_more_as_button', $form_state->getValue([
        'buttons',
        'learn_more_as_button',
      ]))
      ->set('show_toggle_button', $form_state->getValue([
        'buttons',
        'show_toggle_button',
      ]))
      ->set('block_unknown', $form_state->getValue('block_unknown'))
      ->set('block_unknown_logger', $form_state->getValue('block_unknown_logger'))
      ->set('block_unknown_label', $form_state->getValue('block_unknown_label'))
      ->set('block_unknown_description', $form_state->getValue('block_unknown_description'))
      ->set('auto_decorate_js_alter', $form_state->getValue('js_alter'))
      ->set('auto_decorate_page_attachments', $form_state->getValue('page_attachments'))
      ->set('auto_decorate_final_html', $form_state->getValue('final_html'))
      ->set('deletable_cookie_domains', array_filter($cookie_domains))
      ->set('exclude_urls', array_filter($exclude_urls))
      ->set('disable_urls', array_filter($disable_urls))
      ->set('styles', $styles)
      ->set('process_descriptions', $form_state->getValue([
        'apps',
        'process_descriptions',
      ]));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
