<?php

namespace Drupal\brevo_commerce\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Url;
use Drupal\brevo\BrevoFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\commerce_checkout\Attribute\CommerceCheckoutPane;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\brevo\BrevoHandlerInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\brevo\ContactsApiClientHelper;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\Token;

/**
 * Provides the BrevoListsSubscriber pane.
 */
#[CommerceCheckoutPane(
  id: "brevo_lists_subscriber",
  label: new TranslatableMarkup("Brevo Lists Subscriber"),
  default_step: "order_information",
)]
class BrevoListsSubscriber extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('config.factory')->get(BrevoHandlerInterface::CONFIG_NAME),
      $container->get('brevo.brevo_client_factory'),
      $container->get('logger.channel.brevo'),
      $container->get('brevo.contacts_api_client_helper'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('token'),
    );
  }

  /**
   * Constructs a new CheckoutPaneBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ImmutableConfig $brevoConfig
   *   The Brevo config.
   * @param \Drupal\brevo\BrevoFactory $brevoFactory
   *   The Brevo factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   * @param \Drupal\brevo\ContactsApiClientHelper $contactsHelper
   *   The contacts api client helper.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Renderer service.
   * @param \Drupal\Core\Utility\Token $token
   *   The Token service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CheckoutFlowInterface $checkout_flow,
    EntityTypeManagerInterface $entity_type_manager,
    protected ImmutableConfig $brevoConfig,
    protected BrevoFactory $brevoFactory,
    protected LoggerChannelInterface $logger,
    protected ContactsApiClientHelper $contactsHelper,
    protected ModuleHandlerInterface $moduleHandler,
    protected RendererInterface $renderer,
    protected Token $token,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'enabled_lists' => [],
      'checkboxes_title' => $this->t('Subscribe to newsletters'),
      'checkboxes_description' => $this->t('Select the newsletters you would like to subscribe to'),
      'checkboxes_information_top' => [
        'value' => '',
        'format' => 'full_html',
      ],
      'checkboxes_information_bottom' => [
        'value' => '',
        'format' => 'full_html',
      ],
      'enable_double_opt_in' => TRUE,
      'doi_redirect_url' => '',
      'doi_template_id' => NULL,
      'fetched_lists' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    $parent_summary = parent::buildConfigurationSummary();

    if (!empty($this->configuration['enabled_lists'])) {
      $summary = 'Enabled brevo lists: ' . implode(', ', $this->getFormattedEnabledLists());
    }
    else {
      $summary = $this->t('No brevo list selected.');
    }
    $summary .= '<br>';
    $summary .= 'Checkboxes title: ' . $this->configuration['checkboxes_title'];
    $summary .= '<br>';
    $summary .= 'Checkboxes description: ' . (!empty($this->configuration['checkboxes_description']) ? $this->configuration['checkboxes_description'] : 'None');
    $summary .= '<br>';
    $summary .= 'Additional information top: ' . (!empty($this->configuration['checkboxes_information_top']['value']) ? 'In place' : 'None');
    $summary .= '<br>';
    $summary .= 'Additional information bottom: ' . (!empty($this->configuration['checkboxes_information_bottom']['value']) ? 'In place' : 'None');
    $summary .= '<br>';
    $summary .= 'Enable double opt-in: ' . ($this->configuration['enable_double_opt_in'] ? 'Yes' : 'No');
    if ($this->configuration['enable_double_opt_in']) {
      $summary .= '<br>';
      $summary .= 'Double opt-in redirect URL: ' . (!empty($this->configuration['doi_redirect_url']) ? $this->configuration['doi_redirect_url'] : 'None');
      $summary .= '<br>';
      $summary .= 'Double opt-in template ID: ' . (!empty($this->configuration['doi_template_id']) ? $this->configuration['doi_template_id'] : 'None');
    }

    return $parent_summary ? implode('<br>', [$parent_summary, $summary]) : $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $tokenModuleInstalled = $this->moduleHandler->moduleExists('token');
    if ($tokenModuleInstalled) {
      $tokenTree = [
        '#theme' => 'token_tree_link',
      ];
      $renderedTokenTree = $this->renderer->render($tokenTree);
    }
    else {
      $renderedTokenTree = $this->t('Contrib token module is not installed. Please install the <a href="https://www.drupal.org/project/token" target="_blank">Token</a> module to use the token browser.');
    }

    $formattedAvailableLists = $this->getFormattedAvailableLists();
    if (!empty($formattedAvailableLists)) {
      $form['enabled_lists'] = [
        '#type' => 'checkboxes',
        '#required' => TRUE,
        '#title' => $this->t('Enabled Brevo lists'),
        '#options' => $this->getFormattedAvailableLists(),
        '#default_value' => $this->configuration['enabled_lists'],
      ];
    }
    else {
      $form['missing_lists'] = [
        '#type' => 'item',
        '#markup' => $this->t('<b>No Brevo lists available. Please submit this form, to fetch the available lists. If this does not work, either your <a href="@settingsPageLink" target="_blank">API Key is not set</a> or you forgot to <a href="@brevoLink" target="_blank">create brevo lists</a>.</b>', [
          '@settingsPageLink' => Url::fromRoute('brevo.admin_settings_form')->toString(),
          '@brevoLink' => Url::fromUri('https://app.brevo.com/contact/list-listing')->toString(),
        ]),
      ];
      return $form;
    }

    $form['checkboxes_title'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Checkboxes title'),
      '#description' => $this->t('The title displayed above the checkboxes. (Default: "Subscribe to newsletters")'),
      '#default_value' => $this->configuration['checkboxes_title'],
    ];

    $form['checkboxes_description'] = [
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => $this->t('Checkboxes description'),
      '#description' => $this->t('A description rendered on the checkout pane. (Default: "Select the newsletters you would like to subscribe to")'),
      '#default_value' => $this->configuration['checkboxes_description'],
    ];

    $form['checkboxes_information_top'] = [
      '#type' => 'text_format',
      '#required' => FALSE,
      '#title' => $this->t('Additional information top'),
      '#description' => $this->t('Additional information rendered above the checkboxes. <em>You may also use tokens. Empty tokens will be cleared. @browse_tokens_link</em>', [
        '@browse_tokens_link' => $renderedTokenTree,
      ]),
      '#default_value' => $this->configuration['checkboxes_information_top']['value'],
      '#format' => $this->configuration['checkboxes_information_top']['format'],
    ];

    $form['checkboxes_information_bottom'] = [
      '#type' => 'text_format',
      '#required' => FALSE,
      '#title' => $this->t('Additional information bottom'),
      '#description' => $this->t('Additional information rendered under the checkboxes and description. <em>You may also use tokens. Empty tokens will be cleared. @browse_tokens_link</em>', [
        '@browse_tokens_link' => $renderedTokenTree,
      ]),
      '#default_value' => $this->configuration['checkboxes_information_bottom']['value'],
      '#format' => $this->configuration['checkboxes_information_bottom']['format'],
    ];

    $form['enable_double_opt_in'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable double opt-in'),
      '#description' => $this->t('If checked, double opt-in will be used for brevo list subscriptions. As emails are not validated on checkout, this is HIGHLY recommended.'),
      '#default_value' => $this->configuration['enable_double_opt_in'],
    ];

    $form['doi_redirect_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URL'),
      '#description' => $this->t('The URL to redirect to after the double opt-in confirmation. <em>You may also use tokens. Empty tokens will be cleared. @browse_tokens_link</em>', [
        '@browse_tokens_link' => $renderedTokenTree,
      ]),
      '#default_value' => $this->configuration['doi_redirect_url'],
      '#states' => [
        'visible' => [
          ':input[name*="[configuration][enable_double_opt_in]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name*="[configuration][enable_double_opt_in]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['doi_template_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Double opt-in template ID'),
      '#description' => $this->t('The ID of the template to use for the double opt-in email.'),
      '#default_value' => $this->configuration['doi_template_id'],
      '#states' => [
        'visible' => [
          ':input[name*="[configuration][enable_double_opt_in]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name*="[configuration][enable_double_opt_in]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['notice'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<br>Note, that the the brevo lists are being newly fetched, once the submit button is clicked. So if Brevo lists are missing from the "Enabled Brevo lists", submit this form first.'),
    ];

    if (empty($this->brevoConfig->get('api_key'))) {
      $this->messenger()->addWarning($this->t('The Brevo API key is not set. Please configure it in the <a href="@link" target="_blank">Brevo Settings</a> to enable brevo list subscription.', [
        '@link' => Url::fromRoute('brevo.admin_settings_form')->toString(),
      ]));
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (empty($this->brevoConfig->get('api_key'))) {
      $form_state->setErrorByName('missing_lists', $this->t('The Brevo API key is not set. Please configure it in the <a href="@link" target="_blank">Brevo Settings</a> to enable brevo list subscription.', [
        '@link' => Url::fromRoute('brevo.admin_settings_form')->toString(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    // Only if we already fetched the available lists, we can submit the rest of
    // the form values:
    if (!empty($this->configuration['fetched_lists'])) {
      $this->configuration['enabled_lists'] = $values['enabled_lists'];
      $this->configuration['checkboxes_title'] = $values['checkboxes_title'];
      $this->configuration['checkboxes_description'] = $values['checkboxes_description'];
      $this->configuration['checkboxes_information_top'] = $values['checkboxes_information_top'];
      $this->configuration['checkboxes_information_bottom'] = $values['checkboxes_information_bottom'];
      $this->configuration['enable_double_opt_in'] = $values['enable_double_opt_in'];
      $this->configuration['doi_redirect_url'] = $values['doi_redirect_url'];
      $this->configuration['doi_template_id'] = $values['doi_template_id'];
    }
    $this->configuration['fetched_lists'] = $this->contactsHelper->fetchAvailableLists();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['checkboxes_information_top'] = [
      '#type' => 'processed_text',
      '#text' => $this->token->replace($this->configuration['checkboxes_information_top']['value'], [], ['clear' => TRUE]),
      '#format' => $this->configuration['checkboxes_information_top']['format'],
    ];

    $pane_form['lists'] = [
      '#type' => 'checkboxes',
      '#title' => $this->configuration['checkboxes_title'],
      '#description' => $this->configuration['checkboxes_description'],
      '#options' => $this->getFormattedEnabledLists(),
    ];

    $pane_form['checkboxes_information_bottom'] = [
      '#type' => 'processed_text',
      '#text' => $this->token->replace($this->configuration['checkboxes_information_bottom']['value'], [], ['clear' => TRUE]),
      '#format' => $this->configuration['checkboxes_information_bottom']['format'],
    ];

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    $lists = $values['lists'] ?? [];
    // We need to cast the values to int, filter out the empty values and get
    // the array values:
    $selectedLists = array_values(array_filter(array_map(fn($nl) => (int) $nl, $lists)));
    if (empty($selectedLists)) {
      return;
    }
    // Get the email from the order:
    $email = $this->order->getEmail();
    // Get the contact info:
    $contactInfo = $this->contactsHelper->getContactInfo($email);
    // Get the lists, the contact is already subscribed to:
    $contactLists = $contactInfo?->getListIds() ?? [];
    // Get the lists, that are enabled in the configuration:
    $enabledLists = $this->getEnabledListIds();
    // Filter the contact lists on the ones, that are actually enabled in
    // Drupal:
    $contactEnabledLists = array_intersect($enabledLists, $contactLists);
    if ($this->configuration['enable_double_opt_in']) {

      // Because "createDoiContact" is special, as it will always fire and
      // create, update, etc., we should only fire it if:
      // - The contact does not exist.
      // - The contact exists, it is not subscribed to any lists.
      // - There are new lists selected, that the contact is not subscribed to.
      if (empty($contactEnabledLists) || array_diff($selectedLists, $contactEnabledLists)) {
        // Now there can be lists, that are not available in Drupal, but the
        // user is still subscribed to. We need to merge the contact lists, with
        // the selected lists, so we include the old ones as well:
        $newContactLists = array_values(array_unique(array_merge($contactEnabledLists, $selectedLists)));
        $this->contactsHelper->createDoiContact($email, $this->token->replace($this->configuration['doi_redirect_url'], [], ['clear' => TRUE]), $this->configuration['doi_template_id'], $newContactLists);
      }
    }
    else {
      // If contact does not exist, create it:
      if ($contactInfo === NULL) {
        $this->contactsHelper->createContact($email, $selectedLists);
        return;
      }
      // Nothing changed, so we do not need to update the contact:
      if (!array_diff($selectedLists, $contactEnabledLists)) {
        return;
      }
      // Now there can be lists, that are not available in Drupal, but the
      // user is still subscribed to. We need to merge the contact lists, with
      // the selected lists, so we include the old ones as well:
      $newContactLists = array_values(array_unique(array_merge($contactEnabledLists, $selectedLists)));
      $this->contactsHelper->updateContact($email, $newContactLists);
    }
  }

  /**
   * Format the brevo lists for display.
   *
   * @return array
   *   An array of formatted brevo lists for form display.
   */
  protected function getFormattedAvailableLists(): array {
    $availableLists = $this->configuration['fetched_lists'] ?? [];
    $formatted = [];
    foreach ($availableLists as $availableList) {
      $formatted[$availableList['id']] = $availableList['name'];
    }
    return $formatted;
  }

  /**
   * Get the enabled brevo lists formatted for display.
   *
   * @return array
   *   An array of enabled brevo lists.
   */
  protected function getFormattedEnabledLists(): array {
    $enabled_lists = $this->configuration['enabled_lists'] ?? [];
    $availableLists = $this->getFormattedAvailableLists();
    return array_intersect_key($availableLists, array_flip($enabled_lists));
  }

  /**
   * Get the enabled brevo list IDs.
   */
  protected function getEnabledListIds(): array {
    return array_values(array_filter(array_map(fn($nl) => (int) $nl, $this->configuration['enabled_lists'] ?? [])));
  }

}
