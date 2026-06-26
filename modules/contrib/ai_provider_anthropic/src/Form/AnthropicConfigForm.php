<?php

namespace Drupal\ai_provider_anthropic\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ai\AiProviderPluginManager;
use Drupal\key\KeyRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Anthropic API access.
 */
class AnthropicConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   */
  const CONFIG_NAME = 'ai_provider_anthropic.settings';

  /**
   * Default provider ID.
   */
  const PROVIDER_ID = 'anthropic';

  /**
   * The AI provider manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected $aiProviderManager;

  /**
   * The key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Constructs a new AnthropicConfigForm object.
   */
  final public function __construct(AiProviderPluginManager $ai_provider_manager, KeyRepositoryInterface $key_repository, CacheBackendInterface $cache_backend) {
    $this->aiProviderManager = $ai_provider_manager;
    $this->keyRepository = $key_repository;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  final public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ai.provider'),
      $container->get('key.repository'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anthropic_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG_NAME);

    $form['api_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Anthropic API Key'),
      '#description' => $this->t('The API Key. Can be found on <a href="https://console.anthropic.com/settings/keys">https://console.anthropic.com/settings/keys</a>.'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];

    // Check if any external moderation is configured for Anthropic.
    $externalModerations = $this->config('ai.external_moderation')->get('moderations');
    $externalModerations = is_array($externalModerations) ? $externalModerations : [];
    $hasModeration = FALSE;
    foreach ($externalModerations as $moderation) {
      if (($moderation['provider'] ?? '') === 'anthropic') {
        $hasModeration = TRUE;
        break;
      }
    }

    // Store for use in validation.
    $form['has_moderation'] = [
      '#type' => 'value',
      '#value' => $hasModeration,
    ];

    $moderation_url = Url::fromRoute('ai.external_moderation_settings')->toString();

    if ($hasModeration) {
      $form['moderation_status'] = [
        '#type' => 'container',
        'message' => [
          '#markup' => $this->t('External moderation is configured for Anthropic. You can manage moderation settings at <a href="@url">External Moderation Configuration</a>.', ['@url' => $moderation_url]),
        ],
        '#attributes' => ['class' => ['messages', 'messages--status']],
      ];
    }
    else {
      $form['moderation_status'] = [
        '#type' => 'container',
        'message' => [
          '#markup' => $this->t('No external moderation is currently configured for Anthropic. Running without moderation may result in prompts being flagged as malicious by Anthropic, which could lead to account suspension. You can <a href="@url">configure external moderation</a> or proceed at your own risk without moderation.', ['@url' => $moderation_url]),
        ],
        '#attributes' => ['class' => ['messages', 'messages--warning']],
      ];
    }

    $form['moderation_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('No Moderation Needed'),
      '#default_value' => 0,
      '#description' => $this->t('I understand that running Anthropic without moderation may result in prompts being flagged as malicious, which could lead to my account being suspended or banned by Anthropic.'),
      '#disabled' => $hasModeration,
    ];

    // Prompt caching settings.
    $form['prompt_cache'] = [
      '#type' => 'details',
      '#title' => $this->t('Prompt caching'),
      '#open' => (bool) $config->get('prompt_cache_enabled'),
      '#description' => $this->t('Anthropic prompt caching reuses tokenized context across requests, reducing input cost on cache hits. See <a href="@url" target="_blank">Anthropic prompt caching docs</a>. <strong>PDF input note:</strong> PDFs attached to chat messages are passed to Anthropic byte-for-byte; the model reads both their text and visual content. Treat PDFs from untrusted sources as you would untrusted text — they can carry adversarial instructions that influence model output. The provider does not inspect or sanitize PDF contents.', [
        '@url' => 'https://docs.anthropic.com/en/docs/build-with-claude/prompt-caching',
      ]),
    ];
    $form['prompt_cache']['prompt_cache_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable prompt caching'),
      '#default_value' => (bool) $config->get('prompt_cache_enabled'),
      '#description' => $this->t('When enabled, the last block of the last user message is marked as a cache breakpoint. Subsequent requests with identical preceding content reuse the cache.'),
    ];
    $form['prompt_cache']['prompt_cache_ttl'] = [
      '#type' => 'radios',
      '#title' => $this->t('Cache TTL'),
      '#options' => [
        '5m' => $this->t('5 minutes (default)'),
        '1h' => $this->t('1 hour (extended cache tier)'),
      ],
      '#default_value' => $config->get('prompt_cache_ttl') ?: '5m',
      '#states' => [
        'visible' => [
          ':input[name="prompt_cache_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate the API key.
    $key = $form_state->getValue('api_key');
    if (empty($key)) {
      $form_state->setErrorByName('api_key', $this->t('The API key is required. Please select a valid key from the list.'));
      return;
    }
    $key_entity = $this->keyRepository->getKey($key);
    if (!$key_entity) {
      $form_state->setErrorByName('api_key', $this->t('The selected key does not exist. Please select a valid key from the list.'));
      return;
    }
    $api_key = $key_entity->getKeyValue();
    if (!$api_key) {
      $form_state->setErrorByName('api_key', $this->t('The API key is invalid. Please double-check that the selected key has a value. If you are using a file-based Key, ensure the file is present in the environment and contains a value.'));
      return;
    }

    /** @var \Drupal\ai_provider_anthropic\Plugin\AiProvider\AnthropicProvider $provider */
    $provider = $this->aiProviderManager->createInstance('anthropic');
    $provider->setAuthentication($api_key);

    // Clear the models cache so the provider makes a fresh API call.
    $this->cacheBackend->delete('ai_provider_anthropic:models');

    try {
      $models = $provider->getConfiguredModels();
      if (empty($models)) {
        throw new \RuntimeException('No models returned.');
      }
    }
    catch (\Exception) {
      $form_state->setErrorByName('api_key', $this->t('The selected API key is not working. Please double-check the correct API key was entered and that it has credit(s) available.'));
    }

    // Validate moderation acknowledgment.
    if (!$form_state->getValue('has_moderation') && !$form_state->getValue('moderation_checkbox')) {
      $form_state->setErrorByName('moderation_checkbox', $this->t('You need to verify that you understand the consequences of disabling moderation.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->config(static::CONFIG_NAME)
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('prompt_cache_enabled', (bool) $form_state->getValue('prompt_cache_enabled'))
      ->set('prompt_cache_ttl', $form_state->getValue('prompt_cache_ttl') ?: '5m')
      ->save();

    // Set default models.
    $this->setDefaultModels();

    parent::submitForm($form, $form_state);
  }

  /**
   * Set default models for the AI provider.
   */
  private function setDefaultModels() {
    // Create provider instance.
    $provider = $this->aiProviderManager->createInstance(static::PROVIDER_ID);

    // Check if getSetupData() method exists and is callable.
    if (is_callable([$provider, 'getSetupData'])) {
      // Fetch setup data.
      $setup_data = $provider->getSetupData();

      // Ensure the setup data is valid.
      if (!empty($setup_data) && is_array($setup_data) && !empty($setup_data['default_models']) && is_array($setup_data['default_models'])) {
        // Loop through and set default models for each operation type.
        foreach ($setup_data['default_models'] as $op_type => $model_id) {
          $this->aiProviderManager->defaultIfNone($op_type, static::PROVIDER_ID, $model_id);
        }
      }
    }
  }

}
