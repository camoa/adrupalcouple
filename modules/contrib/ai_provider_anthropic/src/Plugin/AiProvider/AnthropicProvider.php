<?php

namespace Drupal\ai_provider_anthropic\Plugin\AiProvider;

use Anthropic\Core\Exceptions\APIException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException as SdkAuthException;
use Anthropic\Core\Exceptions\BadRequestException as SdkBadRequestException;
use Anthropic\Core\Exceptions\PermissionDeniedException as SdkPermissionException;
use Anthropic\Core\Exceptions\RateLimitException as SdkRateLimitException;
use Anthropic\ErrorType;
use Anthropic\Messages\Base64ImageSource;
use Anthropic\Messages\Base64ImageSource\MediaType;
use Anthropic\Messages\Base64PDFSource;
use Anthropic\Messages\CacheControlEphemeral;
use Anthropic\Messages\DocumentBlockParam;
use Anthropic\Messages\ImageBlockParam;
use Anthropic\Messages\Message;
use Anthropic\Messages\MessageCreateParams;
use Anthropic\Messages\MessageParam;
use Anthropic\Messages\OutputConfig\Effort;
use Anthropic\Messages\TextBlock;
use Anthropic\Messages\TextBlockParam;
use Anthropic\Messages\ThinkingBlock;
use Anthropic\Messages\ThinkingConfigAdaptive;
use Anthropic\Messages\ThinkingConfigEnabled;
use Anthropic\Messages\Tool as SdkTool;
use Anthropic\Messages\ToolResultBlockParam;
use Anthropic\Messages\ToolUseBlock;
use Anthropic\Messages\ToolUseBlockParam;
use Anthropic\Models\ModelCapabilities;
use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai\Attribute\AiProvider;
use Drupal\ai\Base\OpenAiBasedProviderClientBase;
use Drupal\ai\Dto\TokenUsageDto;
use Drupal\ai\Enum\AiModelCapability;
use Drupal\ai\Enum\AiProviderCapability;
use Drupal\ai\Exception\AiBadRequestException;
use Drupal\ai\Exception\AiQuotaException;
use Drupal\ai\Exception\AiRateLimitException;
use Drupal\ai\Exception\AiRequestErrorException;
use Drupal\ai\Exception\AiSetupFailureException;
use Drupal\ai\Exception\AiUnsafePromptException;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\Chat\ChatOutput;
use Drupal\ai\OperationType\Chat\Tools\ToolsFunctionOutput;
use Drupal\ai\Traits\OperationType\ChatTrait;
use Drupal\ai_provider_anthropic\OperationType\Chat\AnthropicStreamedChatMessageIterator;
use Drupal\ai_provider_anthropic\Service\AnthropicNativeClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'anthropic' provider.
 */
#[AiProvider(
  id: 'anthropic',
  label: new TranslatableMarkup('Anthropic'),
)]
class AnthropicProvider extends OpenAiBasedProviderClientBase {

  use ChatTrait;

  /**
   * {@inheritdoc}
   */
  protected string $endpoint = 'https://api.anthropic.com/v1';

  /**
   * Run moderation call, before a normal call.
   *
   * @var bool
   */
  protected bool $moderation = TRUE;

  /**
   * The Anthropic native SDK client wrapper.
   *
   * @var \Drupal\ai_provider_anthropic\Service\AnthropicNativeClient
   */
  protected AnthropicNativeClient $nativeClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->nativeClient = $container->get('ai_provider_anthropic.native_client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguredModels(?string $operation_type = NULL, array $capabilities = []): array {
    $models = $this->fetchAvailableModels();

    // ChatJsonOutput filtering: prefer the typed ModelCapabilities API —
    // `structuredOutputs.supported` is the authoritative per-model flag.
    // We cached capabilities alongside the models, so this is cheap.
    if (in_array(AiModelCapability::ChatJsonOutput, $capabilities) && in_array(AiModelCapability::ChatStructuredResponse, $capabilities) ||
      in_array(AiModelCapability::ChatTools, $capabilities)) {
      return array_filter($models, function ($id) {
        $caps = $this->getModelCapabilities($id);
        if ($caps !== NULL) {
          return $caps->structuredOutputs->supported;
        }
        // Capability unavailable: fall back to a last-known-good prefix
        // match so fresh installs aren't blocked. Narrower than before.
        return (bool) preg_match('/^claude-(opus|sonnet|haiku|3[-.]5|3[-.]7|4|[5-9])/i', $id);
      }, ARRAY_FILTER_USE_KEY);
    }

    // Do the same for vision - even if all models currently support it.
    if (in_array(AiModelCapability::ChatWithImageVision, $capabilities)) {
      return array_filter($models, function ($id) {
        $caps = $this->getModelCapabilities($id);
        if ($caps !== NULL) {
          return $caps->imageInput->supported;
        }
        // Capability unavailable: fall back to a last-known-good prefix
        // match so fresh installs aren't blocked. Narrower than before.
        return (bool) preg_match('/^claude-(opus|sonnet|haiku|3[-.]5|3[-.]7|4|[5-9])/i', $id);
      }, ARRAY_FILTER_USE_KEY);
    }

    // Filter to PDF-capable models.
    if (in_array(AiModelCapability::ChatWithPdf, $capabilities)) {
      return array_filter($models, function ($id) {
        $caps = $this->getModelCapabilities($id);
        if ($caps !== NULL) {
          return $caps->pdfInput->supported;
        }
        // Capability unavailable: fall back to last-known-good prefix match.
        return (bool) preg_match('/^claude-(opus|sonnet|haiku|3[-.]5|3[-.]7|4|[5-9])/i', $id);
      }, ARRAY_FILTER_USE_KEY);
    }

    return $models;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedOperationTypes(): array {
    return [
      'chat',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Anthropic native streaming is supported via AnthropicStreamedChat
   * MessageIterator. Fiber support from the OpenAI-compat parent is NOT
   * carried forward — the native streaming path doesn't yet suspend on
   * \Fiber::getCurrent().
   */
  public function getSupportedCapabilities(): array {
    return [
      AiProviderCapability::StreamChatOutput,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSetupData(): array {
    try {
      $models = $this->getConfiguredModels();
    }
    catch (\Exception $e) {
      // If we fail to get models dynamically, fall back to empty array.
      $models = [];
    }

    // Get the 4.5 models for complex tasks from the list.
    $default_complex_model = 'claude-opus-4-5-20251101';
    foreach ($models as $model_id => $model_name) {
      if (str_starts_with($model_id, 'claude-opus-4-5')) {
        // We found a 4.5 model, we can use it.
        $default_complex_model = $model_id;
        break;
      }
    }
    // Get the 4.5 sonnet model for general tasks from the list.
    $default_chat_model = 'claude-sonnet-4-5-20250929';
    foreach ($models as $model_id => $model_name) {
      if (str_starts_with($model_id, 'claude-sonnet-4-5')) {
        // We found a 4.5 sonnet model, we can use it.
        $default_chat_model = $model_id;
        break;
      }
    }

    $setup['key_config_name'] = 'api_key';
    if ($default_complex_model) {
      $setup['default_models']['chat_with_complex_json'] = $default_complex_model;
      $setup['default_models']['chat_with_tools'] = $default_complex_model;
      $setup['default_models']['chat_with_structured_response'] = $default_complex_model;
    }
    if ($default_chat_model) {
      $setup['default_models']['chat'] = $default_chat_model;
      $setup['default_models']['chat_with_image_vision'] = $default_chat_model;
    }
    return $setup;
  }

  /**
   * {@inheritdoc}
   */
  public function getModelSettings(string $model_id, array $generalConfig = []): array {
    // Claude 4.x+ hides top_p — the Anthropic API rejects temperature and
    // top_p together on these models.
    if (preg_match('/^claude(?:-[a-z]+)*-(4(\.\d+)?|[5-9](\.\d+)?)(?:[.-]|$)/i', $model_id)) {
      unset($generalConfig['top_p']);
    }

    // Ask the API what this model supports. When capabilities are
    // unavailable (fresh install, no API key yet, transient failure), we
    // return generalConfig without effort/thinking fields — admin must
    // configure the provider first, which is a prerequisite anyway.
    $capabilities = $this->getModelCapabilities($model_id);
    if ($capabilities !== NULL) {
      return $this->buildSettingsFromCapabilities($capabilities, $generalConfig, $model_id);
    }

    return $generalConfig;
  }

  /**
   * Retrieves model capabilities from the Anthropic API.
   *
   * Caches results for 24 hours keyed by model ID. Returns NULL when the
   * capabilities cannot be fetched — missing API key, network failure, or any
   * SDK exception — so callers can fall back to regex-based detection.
   *
   * @param string $model_id
   *   The model identifier (e.g. 'claude-opus-4-7-20260416').
   *
   * @return \Anthropic\Models\ModelCapabilities|null
   *   The typed capabilities object, or NULL if unavailable.
   */
  protected function getModelCapabilities(string $model_id): ?ModelCapabilities {
    $cache_key = 'ai_provider_anthropic:capabilities:' . $model_id;

    // Wrap the cache read defensively for bare unit test harnesses where
    // cacheBackend may be unset.
    try {
      $cached = $this->cacheBackend->get($cache_key);
    }
    catch (\Throwable $ignored) {
      $cached = FALSE;
    }
    // Only trust cached data that is actually a ModelCapabilities instance.
    if ($cached && isset($cached->data) && $cached->data instanceof ModelCapabilities) {
      return $cached->data;
    }

    // Capability lookup from the API. Catch only SDK exceptions — typed
    // AI module exceptions must bubble to ProviderProxy (see dev-guide
    // `drupal/ai-module/exceptions.md`).
    try {
      $this->ensureNativeClient();
      $client = $this->nativeClient->getClient();
      $modelInfo = $client->models->retrieve($model_id);
      $capabilities = $modelInfo->capabilities;
      if ($capabilities !== NULL) {
        try {
          $this->cacheBackend->set(
            $cache_key,
            $capabilities,
            time() + 86400,
            ['ai_provider_anthropic:capabilities'],
          );
        }
        catch (\Throwable $ignored) {
        }
      }
      return $capabilities;
    }
    catch (APIException $e) {
      $this->logWarning('Failed to fetch capabilities for @model: @error', [
        '@model' => $model_id,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
    catch (\RuntimeException $e) {
      // nativeClient->getClient() throws \RuntimeException when init fails
      // silently (missing key, etc.). Treat as unavailable.
      $this->logWarning('Native client unavailable for @model: @error', [
        '@model' => $model_id,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Ensures the native SDK client is initialized with the active API key.
   *
   * Idempotent: safe to call multiple times per request. Extracted to
   * eliminate the init-block duplication previously present at call sites
   * of the native API (chat, streaming, capability lookup).
   *
   * @throws \Drupal\ai\Exception\AiSetupFailureException
   *   When the API key cannot be loaded (propagated from loadApiKey()).
   */
  protected function ensureNativeClient(): void {
    if ($this->nativeClient->isInitialized()) {
      return;
    }
    $api_key = $this->apiKey ?: $this->loadApiKey();
    $this->nativeClient->initialize($api_key);
  }

  /**
   * Safe logger helper — never let logger failures cascade.
   */
  protected function logWarning(string $message, array $context = []): void {
    try {
      $this->loggerFactory->get('ai_provider_anthropic')->warning($message, $context);
    }
    catch (\Throwable $ignored) {
    }
  }

  /**
   * Builds model settings from typed SDK capabilities.
   *
   * Uses the ModelCapabilities object returned by the Anthropic API to build
   * the config UI fields — effort levels, thinking modes, thinking budget —
   * based on what the model actually supports. This replaces regex-based
   * detection and correctly handles models like Opus 4.7 that support xhigh
   * effort and reject manual enabled thinking.
   *
   * @param \Anthropic\Models\ModelCapabilities $capabilities
   *   The typed capabilities from the API.
   * @param array $generalConfig
   *   The existing configuration array to extend.
   * @param string $model_id
   *   The model identifier, used by modelAcceptsXhigh() heuristic when
   *   the capability tree omits xhigh for a known xhigh-enabled model.
   *
   * @return array
   *   The extended configuration array with effort/thinking fields added
   *   according to capabilities.
   */
  protected function buildSettingsFromCapabilities(ModelCapabilities $capabilities, array $generalConfig, string $model_id = ''): array {
    // Effort: add options only for levels the capability marks as supported.
    if ($capabilities->effort->supported) {
      $options = ['' => '-- Default --'];
      if ($capabilities->effort->low->supported) {
        $options['low'] = 'Low';
      }
      if ($capabilities->effort->medium->supported) {
        $options['medium'] = 'Medium';
      }
      if ($capabilities->effort->high->supported) {
        $options['high'] = 'High';
      }
      // Xhigh is a documented Opus 4.7+ effort level. Two gotchas:
      // 1. The capability tree for Opus 4.7 currently does NOT include the
      // xhigh subfield in the API response, even though the server-side
      // validator accepts `effort: "xhigh"` for that model. Verified via
      // direct /v1/messages probe.
      // 2. SDK's #[Required] ?CapabilitySupport $xhigh is left
      // uninitialized when omitted, so isset() returns FALSE even when
      // effort is supported.
      // We therefore offer xhigh when EITHER the capability tree lists
      // it (forward-compat for when Anthropic fixes the tree) OR the
      // model ID matches the known xhigh-enabled family. Unknown models
      // that accept xhigh in the future will show up via the tree path.
      $xhigh_in_tree = isset($capabilities->effort->xhigh) && $capabilities->effort->xhigh->supported;
      if ($xhigh_in_tree || $this->modelAcceptsXhigh($model_id)) {
        $options['xhigh'] = 'Extra High';
      }
      if ($capabilities->effort->max->supported) {
        $options['max'] = 'Max';
      }
      $generalConfig['effort'] = [
        'type' => 'select',
        'label' => 'Effort',
        'description' => 'Controls token budget for response quality.',
        'default' => '',
        'constraints' => ['options' => $options],
      ];
    }

    // Thinking: add modes only for types the capability marks as supported.
    if ($capabilities->thinking->supported) {
      $thinking_options = ['' => '-- Disabled --'];
      if ($capabilities->thinking->types->adaptive->supported) {
        $thinking_options['adaptive'] = 'Adaptive';
      }
      if ($capabilities->thinking->types->enabled->supported) {
        $thinking_options['enabled'] = 'Enabled (requires budget)';
      }

      // Only render the thinking_mode field when there's a real choice
      // beyond "-- Disabled --". If neither adaptive nor enabled is
      // supported, a single-option dropdown would be useless UI.
      if (count($thinking_options) > 1) {
        $generalConfig['thinking_mode'] = [
          'type' => 'select',
          'label' => 'Thinking Mode',
          'description' => 'Controls extended thinking behavior.',
          'default' => '',
          'constraints' => ['options' => $thinking_options],
        ];

        // Thinking budget is only meaningful when the 'enabled' mode is
        // available. Adaptive-only models (e.g. Opus 4.7) don't accept budget.
        if ($capabilities->thinking->types->enabled->supported) {
          $generalConfig['thinking_budget'] = [
            'type' => 'integer',
            'label' => 'Thinking Budget',
            'description' => 'Max tokens for thinking (only for Enabled mode).',
            'default' => 10000,
            'constraints' => ['min' => 1024, 'max' => 128000],
          ];
        }
      }
    }

    return $generalConfig;
  }

  /**
   * Enables moderation response, for all next coming responses.
   */
  public function enableModeration(): void {
    $this->moderation = TRUE;
  }

  /**
   * Disables moderation response, for all next coming responses.
   */
  public function disableModeration(): void {
    $this->moderation = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function chat(array|string|ChatInput $input, string $model_id, array $tags = []): ChatOutput {
    if ($input instanceof ChatInput && $this->requiresNativeApi()) {
      return $this->streamed
        ? $this->nativeChatStream($input, $model_id, $tags)
        : $this->nativeChat($input, $model_id, $tags);
    }
    return parent::chat($input, $model_id, $tags);
  }

  /**
   * Sends a chat request via the native Anthropic API.
   *
   * @param \Drupal\ai\OperationType\Chat\ChatInput $input
   *   The chat input.
   * @param string $model_id
   *   The model ID.
   * @param array $tags
   *   Tags for the request.
   *
   * @return \Drupal\ai\OperationType\Chat\ChatOutput
   *   The chat output.
   */

  /**
   * Sends a streaming chat request via the native Anthropic API.
   *
   * Returns a ChatOutput wrapping a StreamedChatMessageIterator that emits
   * text/thinking deltas as they arrive. The raw SDK BaseStream is
   * consumed by AnthropicStreamedChatMessageIterator.
   */
  protected function nativeChatStream(ChatInput $input, string $model_id, array $tags): ChatOutput {
    $this->ensureNativeClient();
    $params = $this->buildMessageCreateParams($input, $model_id);

    try {
      $client = $this->nativeClient->getClient();

      $args = $this->buildMessageArguments($params);

      $stream = $client->messages->createStream(
        ...$args,
      );
      $iterator = new AnthropicStreamedChatMessageIterator($stream);
      $iterator->setInput($input);
      $iterator->setProviderId('anthropic');
      $iterator->setModelId($model_id);
      $iterator->setTags($tags);
      return new ChatOutput($iterator, $stream, []);
    }
    catch (APIException $e) {
      $this->handleApiException($e);
      throw $e;
    }
  }

  /**
   * Sends a chat request via the native Anthropic API.
   */
  protected function nativeChat(ChatInput $input, string $model_id, array $tags): ChatOutput {
    $this->ensureNativeClient();
    $params = $this->buildMessageCreateParams($input, $model_id);

    try {
      $client = $this->nativeClient->getClient();

      $args = $this->buildMessageArguments($params);
      $message = $client->messages->create(
        ...$args,
      );
      return $this->parseMessageResponse($message, $input);
    }
    catch (APIException $e) {
      $this->handleApiException($e);
      throw $e;
    }
  }

  /**
   * Builds the message arguments as an assoc-array for names arguments.
   *
   * @param \Anthropic\Messages\MessageCreateParams $params
   *   The message create parameters.
   *
   * @return array
   *   The message arguments as an associative array.
   */
  public function buildMessageArguments(MessageCreateParams $params): array {
    // Build arguments.
    $args = [
      'maxTokens' => $params->maxTokens,
      'messages' => $params->messages,
      'model' => $params->model,
    ];
    // Just gracefully ignore any params that are null, to avoid future SDK
    // validation errors.
    if (isset($params['cacheControl'])) {
      $args['cacheControl'] = $params['cacheControl'];
    }
    if (isset($params['outputConfig'])) {
      $args['outputConfig'] = $params['outputConfig'];
    }
    if (isset($params['system'])) {
      $args['system'] = $params['system'];
    }
    if (isset($params['temperature'])) {
      $args['temperature'] = $params['temperature'];
    }
    if (isset($params['thinking'])) {
      $args['thinking'] = $params['thinking'];
    }
    if (isset($params['tools'])) {
      $args['tools'] = $params['tools'];
    }
    if (isset($params['topK'])) {
      $args['topK'] = $params['topK'];
    }
    if (isset($params['topP'])) {
      $args['topP'] = $params['topP'];
    }
    return $args;
  }

  /**
   * {@inheritdoc}
   */
  protected function loadClient(): void {
    // Set custom endpoint from host config if available.
    if (!empty($this->getConfig()->get('host'))) {
      $this->setEndpoint($this->getConfig()->get('host'));
    }

    try {
      parent::loadClient();
    }
    catch (AiSetupFailureException $e) {
      throw new AiSetupFailureException('Failed to initialize Anthropic client: ' . $e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Fetches available models from Anthropic API.
   *
   * @return array
   *   Array of models keyed by model ID with display names as values.
   */
  protected function fetchAvailableModels(): array {
    $cache_key = 'ai_provider_anthropic:models';
    try {
      $cached = $this->cacheBackend->get($cache_key);
    }
    catch (\Throwable $ignored) {
      $cached = FALSE;
    }
    if ($cached && !empty($cached->data) && is_array($cached->data)) {
      return $cached->data;
    }

    try {
      $this->ensureNativeClient();
      $client = $this->nativeClient->getClient();

      // Walk pagination via typed Page — aggregate all pages into a single
      // id => display_name array. The SDK handles cursor/limit internally
      // via Page->lastID / hasMore.
      $models = [];
      $after_id = NULL;
      $iterations = 0;
      do {
        $page = $client->models->list(afterID: $after_id, limit: 100);
        foreach ($page->getItems() as $model_info) {
          if (!empty($model_info->id) && !empty($model_info->displayName)) {
            $models[$model_info->id] = $model_info->displayName;
          }
        }
        $after_id = $page->lastID ?? NULL;
        $has_more = $page->hasMore ?? FALSE;
        // Safety bound — Anthropic has ~20 models; 10 pages of 100 is
        // already absurd. Prevents infinite loop on a misbehaving API.
        $iterations++;
      } while ($has_more && $after_id !== NULL && $iterations < 10);

      $cache_ttl = $this->getConfig()->get('models_cache_ttl') ?? 86400;
      try {
        $this->cacheBackend->set(
          $cache_key,
          $models,
          time() + $cache_ttl,
          ['ai_provider_anthropic:capabilities'],
        );
      }
      catch (\Throwable $ignored) {
      }

      return $models;
    }
    catch (\Throwable $e) {
      $this->logWarning('Failed to fetch Anthropic models dynamically: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Clears the cached models list.
   *
   * This can be called from an admin form or drush command.
   */
  public function clearModelsCache(): void {
    $this->cacheBackend->delete('ai_provider_anthropic:models');
    $this->loggerFactory->get('ai_provider_anthropic')
      ->info('Anthropic models cache cleared.');
  }

  /**
   * Builds a typed MessageCreateParams from ChatInput and configuration.
   *
   * Uses SDK v0.16 typed classes for effort, thinking, and tool config
   * instead of raw JSON arrays. The message content blocks themselves are
   * still built as arrays — the SDK accepts both and this keeps the block
   * construction readable.
   *
   * Capability validation: if the user previously selected
   * `thinking_mode=enabled` on a model that supports it, but switched to a
   * model where only adaptive is supported (e.g. Opus 4.7), we downgrade
   * to adaptive rather than sending an invalid request.
   *
   * @param \Drupal\ai\OperationType\Chat\ChatInput $input
   *   The chat input.
   * @param string $model_id
   *   The model ID to use.
   *
   * @return \Anthropic\Messages\MessageCreateParams
   *   Typed params ready for $client->messages->create().
   */
  protected function buildMessageCreateParams(ChatInput $input, string $model_id): MessageCreateParams {
    // Anthropic's Messages API forbids role=system inside `messages` and
    // requires the system prompt at top-level. Callers (Drush ai:chat,
    // ai_assistant_api, etc.) sometimes pass it as a ChatMessage with
    // role=system; promote it before building message content.
    [$system_from_messages, $filtered_input] = $this->extractSystemPrompt($input);
    $messages = $this->buildMessageContent($filtered_input);

    // When thinking will be active, the Anthropic API requires
    // max_tokens > thinking.budget_tokens. Adaptive mode implicitly uses
    // the same budget_tokens constraint. Auto-bump max_tokens if the
    // configured value would violate the constraint.
    $max_tokens = (int) ($this->configuration['max_tokens'] ?? 4096);
    $thinking_budget = (int) ($this->configuration['thinking_budget'] ?? 10000);
    if (!empty($this->configuration['thinking_mode']) && $max_tokens <= $thinking_budget) {
      $max_tokens = $thinking_budget + 4096;
    }

    $params = MessageCreateParams::with(
      maxTokens: $max_tokens,
      messages: $messages,
      model: $model_id,
    );

    // Resolve cache settings up front — needed for the system-block path
    // below and for the top-level cacheControl marker further down.
    [$cache_enabled, $cache_ttl] = $this->resolvePromptCacheSettings();

    // Precedence: ChatInput->getSystemPrompt() → role=system messages
    // (concatenated) → deprecated chatSystemRole property.
    $system_prompt = $input->getSystemPrompt();
    if (empty($system_prompt) && !empty($system_from_messages)) {
      $system_prompt = $system_from_messages;
    }
    if (empty($system_prompt) && !empty($this->chatSystemRole)) {
      $system_prompt = $this->chatSystemRole;
    }
    if (!empty($system_prompt)) {
      // When caching is enabled, the bulk of cacheable content is almost
      // always the system prompt. Anthropic's API caches content blocks,
      // not bare strings — so we send the system prompt as a typed
      // TextBlockParam list with cache_control attached on the last
      // (and only) block. This lets Anthropic create a cache breakpoint
      // at the end of the system prompt, where the cacheable content ends
      // and the per-call message history begins.
      if ($cache_enabled) {
        $system_block = TextBlockParam::with(
          text: $system_prompt,
          cacheControl: $this->buildCacheControl($cache_ttl),
        );
        $params = $params->withSystem([$system_block]);
      }
      else {
        $params = $params->withSystem($system_prompt);
      }
    }

    // Resolve thinking first — when active, Anthropic REQUIRES
    // top_p/top_k unset and temperature = 1. Config-provided values for
    // those params must be ignored in that case.
    $thinking = $this->resolveThinking($model_id);
    $thinking_active = ($thinking !== NULL);

    if (!$thinking_active) {
      // Non-zero check: admin forms sometimes default these to 0, and
      // Anthropic rejects top_p/top_k when value is 0 with thinking. For
      // consistency, treat 0 as "not set" for all three.
      if (!empty($this->configuration['temperature'])) {
        $params = $params->withTemperature((float) $this->configuration['temperature']);
      }
      if (!empty($this->configuration['top_p'])) {
        $params = $params->withTopP((float) $this->configuration['top_p']);
      }
      if (!empty($this->configuration['top_k'])) {
        $params = $params->withTopK((int) $this->configuration['top_k']);
      }
    }

    if ($input->getChatTools()) {
      $tools = [];
      foreach ($input->getChatTools()->getFunctions() as $function) {
        $rendered = $function->renderFunctionArray();
        // ToolsFunctionInput renders `parameters: NULL` when no property
        // definitions exist. Anthropic's Tool.inputSchema requires a JSON
        // Schema object — substitute an empty object-schema.
        $schema = $rendered['parameters'] ?? ['type' => 'object', 'properties' => new \stdClass()];
        $tools[] = SdkTool::with(
          inputSchema: $schema,
          name: $rendered['name'],
          description: $rendered['description'] ?? NULL,
        );
      }
      if ($tools) {
        $params = $params->withTools($tools);
      }
    }

    $output_config = [];

    // Build structured input when its set.
    if ($schema = $input->getChatStructuredJsonSchema()) {
      // drupal/ai 1.4 normalizes a `strict` key onto the returned array,
      // but Anthropic's JSONOutputFormat exposes only `type` and `schema` —
      // there is no strict toggle on the API. Claude interprets the schema
      // directly, so the flag is intentionally not forwarded.
      $output_config['format'] = [
        'type' => 'json_schema',
        'schema' => $schema['schema'],
      ];
    }

    // Top-level cache_control marker. The SDK applies this to the last
    // cacheable block in the request. When a system prompt is present we
    // ALSO attach cache_control directly to the system TextBlockParam
    // (above), so the SDK treats both as cache breakpoints — Anthropic
    // supports up to 4 per request. Top-level alone wouldn't cache the
    // system prompt because it's typically much larger than the user
    // message and the SDK's auto-placement picks the last message block.
    if ($cache_enabled) {
      $params = $params->withCacheControl($this->buildCacheControl($cache_ttl));
    }

    if (!empty($this->configuration['effort'])) {
      $effort = $this->resolveEffort($this->configuration['effort']);
      if ($effort !== NULL) {
        $output_config['effort'] = $effort;
      }
    }

    // If output_config has any entries, attach it to params.
    if (!empty($output_config)) {
      $params = $params->withOutputConfig($output_config);
    }

    if ($thinking_active) {
      $params = $params->withThinking($thinking);
      // Anthropic API requires temperature = 1 when thinking is active.
      $params = $params->withTemperature(1.0);
    }

    return $params;
  }

  /**
   * Extracts role=system ChatMessages from input, returning [text, cleaned].
   *
   * Anthropic's Messages API only accepts system prompts at the top-level
   * `system` parameter — never as a message role. Callers (Drush ai:chat,
   * ai_assistant_api, etc.) occasionally pass system as a ChatMessage; we
   * concatenate any role=system messages and return a cleaned ChatInput
   * without them.
   *
   * @return array{0: string, 1: \Drupal\ai\OperationType\Chat\ChatInput}
   *   [concatenated system text (empty if none), ChatInput without system
   *   role messages]
   */
  protected function extractSystemPrompt(ChatInput $input): array {
    $system_parts = [];
    $filtered = [];
    foreach ($input->getMessages() as $message) {
      if (strtolower($message->getRole()) === 'system') {
        if ($message->getText() !== '') {
          $system_parts[] = $message->getText();
        }
        continue;
      }
      $filtered[] = $message;
    }
    if (empty($system_parts)) {
      return ['', $input];
    }
    $cleaned = new ChatInput($filtered);
    // Preserve any pre-existing top-level system prompt from the original
    // input — tests assume getSystemPrompt() survives round-trip.
    if (!empty($input->getSystemPrompt())) {
      $cleaned->setSystemPrompt($input->getSystemPrompt());
    }
    return [implode("\n\n", $system_parts), $cleaned];
  }

  /**
   * Builds typed message content blocks for a ChatInput.
   *
   * Returns per-message associative arrays with `role` + `content`, where
   * content blocks are typed SDK classes (TextBlockParam, ImageBlockParam,
   * ToolUseBlockParam, ToolResultBlockParam). The SDK validates wire
   * format at construction time rather than at send.
   *
   * @return list<\Anthropic\Messages\MessageParam>
   *   Typed message params; SDK validates wire format at construction.
   */
  protected function buildMessageContent(ChatInput $input): array {
    $messages = [];
    foreach ($input->getMessages() as $message) {
      // Tool result messages: Anthropic requires role=user with a
      // tool_result block carrying the function's output.
      if ($message->getToolsId()) {
        $messages[] = MessageParam::with(
          content: [
            ToolResultBlockParam::with(
              toolUseID: $message->getToolsId(),
              content: $message->getText(),
            ),
          ],
          role: 'user',
        );
        continue;
      }

      $content = [];
      if ($message->getText() !== '') {
        $content[] = TextBlockParam::with(text: $message->getText());
      }
      foreach ($message->getImages() as $image) {
        $content[] = ImageBlockParam::with(
          source: Base64ImageSource::with(
            // getAsBase64EncodedString('') returns RAW base64. With no
            // argument the AI module helper prepends a `data:<mime>;base64,`
            // URL scheme — Anthropic's Base64ImageSource/Base64PDFSource
            // `data` field wants the bare base64 string, so pass ''.
            data: $image->getAsBase64EncodedString(''),
            mediaType: $this->resolveMediaType($image->getMimeType()),
          ),
        );
      }
      // PDF files: emit a typed DocumentBlockParam with Base64PDFSource.
      // Non-PDF MIME types in getFiles() (audio/video/octet-stream/etc.) are
      // silently skipped — Anthropic's Messages API only accepts PDFs as
      // document blocks today.
      foreach ($message->getFiles() as $file) {
        // MIME types are case-insensitive per RFC 2045, so normalize before
        // matching — image branch uses resolveMediaType() for the same reason.
        if (strtolower($file->getMimeType()) !== 'application/pdf') {
          continue;
        }
        $content[] = DocumentBlockParam::with(
          source: Base64PDFSource::with(data: $file->getAsBase64EncodedString('')),
        );
      }
      if ($message->getTools()) {
        foreach ($message->getTools() as $tool) {
          $rendered = $tool->getOutputRenderArray();
          $args = Json::decode($rendered['function']['arguments']) ?? [];
          $content[] = ToolUseBlockParam::with(
            id: $rendered['id'],
            input: is_array($args) ? $args : [],
            name: $rendered['function']['name'],
          );
        }
      }
      // Anthropic rejects empty content arrays.
      if (empty($content)) {
        $content[] = TextBlockParam::with(text: '');
      }

      $messages[] = MessageParam::with(
        content: $content,
        role: $message->getRole(),
      );
    }
    return $messages;
  }

  /**
   * Maps a MIME type string to the typed Base64ImageSource MediaType enum.
   *
   * Accepts known Anthropic image types; unknown types fall back to PNG so
   * the request builder doesn't throw on wire-format validation. If a
   * model later rejects the image, the error surfaces via the normal
   * exception pipeline rather than crashing payload building.
   */
  protected function resolveMediaType(string $mime): MediaType {
    return match (strtolower($mime)) {
      'image/jpeg', 'image/jpg' => MediaType::IMAGE_JPEG,
      'image/gif' => MediaType::IMAGE_GIF,
      'image/webp' => MediaType::IMAGE_WEBP,
      'image/png' => MediaType::IMAGE_PNG,
      default => MediaType::IMAGE_PNG,
    };
  }

  /**
   * Builds a CacheControlEphemeral with the resolved TTL applied.
   *
   * Centralizes the "5m default → omit ttl, 1h → pass explicitly" choice so
   * both the system-block path and the top-level marker path agree. Anthropic
   * accepts cache_control without an explicit ttl (server-side default 5m);
   * we only stamp ttl when the caller opts into 1h.
   *
   * @param string $cache_ttl
   *   Either '5m' (default) or '1h'.
   *
   * @return \Anthropic\Messages\CacheControlEphemeral
   *   The typed cache-control marker ready to attach to a content block or
   *   pass to withCacheControl().
   */
  protected function buildCacheControl(string $cache_ttl): CacheControlEphemeral {
    return $cache_ttl === '1h'
      ? CacheControlEphemeral::with(ttl: '1h')
      : CacheControlEphemeral::with();
  }

  /**
   * Resolves prompt-cache enablement and TTL from runtime + admin-form config.
   *
   * Precedence: per-call $this->configuration → provider admin form → default.
   *
   * @return array{0: bool, 1: string}
   *   Tuple of [$cache_enabled, $cache_ttl].
   */
  protected function resolvePromptCacheSettings(): array {
    $config = $this->loadProviderSettings();

    $cache_enabled = !empty($this->configuration['prompt_cache'])
      || (bool) ($config['prompt_cache_enabled'] ?? FALSE);

    $cache_ttl = $this->configuration['prompt_cache_ttl']
      ?? $config['prompt_cache_ttl']
      ?? NULL;
    if (empty($cache_ttl)) {
      $cache_ttl = '5m';
    }

    return [$cache_enabled, $cache_ttl];
  }

  /**
   * Reads the provider-level settings, or an empty array when unavailable.
   *
   * The getConfig() method resolves the settings name from
   * $this->pluginDefinition['provider'] and reads the config.factory
   * service. Both are absent on a mock built with
   * disableOriginalConstructor() in pure unit tests; accessing a null
   * pluginDefinition offset emits a PHP warning that no try/catch can
   * trap (a warning is not a Throwable). An is_array() + isset() probe
   * checks the offset without warning — so guard with it before
   * touching getConfig().
   *
   * @return array
   *   The prompt-cache settings keyed by config name, or [] when the
   *   provider config cannot be read (e.g. unit-test context).
   */
  private function loadProviderSettings(): array {
    if (!is_array($this->pluginDefinition) || !isset($this->pluginDefinition['provider'])) {
      return [];
    }
    try {
      $config = $this->getConfig();
      return [
        'prompt_cache_enabled' => $config->get('prompt_cache_enabled'),
        'prompt_cache_ttl' => $config->get('prompt_cache_ttl'),
      ];
    }
    catch (\Exception $ignored) {
      return [];
    }
  }

  /**
   * Maps a string effort value to the typed Effort enum case.
   *
   * Returns NULL if the string is empty or doesn't match a known level —
   * callers treat NULL as "don't emit effort at all".
   */
  protected function resolveEffort(string $effort): ?Effort {
    return match (strtolower($effort)) {
      'low' => Effort::LOW,
      'medium' => Effort::MEDIUM,
      'high' => Effort::HIGH,
      'xhigh' => Effort::XHIGH,
      'max' => Effort::MAX,
      default => NULL,
    };
  }

  /**
   * Heuristic: does the model ID accept the xhigh effort level?
   *
   * Anthropic's /v1/models/{id}.capabilities tree currently omits the
   * xhigh subfield under EffortCapability, even for models where the
   * server-side validator accepts `effort: "xhigh"` on
   * messages.create. Until the capability tree catches up, we fall back
   * to a narrow model-ID prefix match for the families known to accept
   * it (Opus 4.7 and above). Verified via direct API probe 2026-04-21.
   *
   * @return bool
   *   TRUE if the model_id matches the xhigh-enabled family.
   */
  protected function modelAcceptsXhigh(string $model_id): bool {
    // Opus 4.7 and any future Opus major with version >= 4.7, or any
    // Opus / Sonnet / Haiku version >= 5.
    return (bool) preg_match(
      '/^claude-opus-4[-.]?(7|[89])|^claude-(opus|sonnet|haiku)-[5-9]/i',
      $model_id,
    );
  }

  /**
   * Resolves the configured thinking mode into a typed SDK thinking config.
   *
   * Validates against ModelCapabilities when available — if the configured
   * mode isn't supported by the model (e.g. 'enabled' requested on Opus 4.7
   * which is adaptive-only), downgrades to a supported mode rather than
   * sending an invalid request. Returns NULL when thinking is disabled or
   * unsupported entirely.
   *
   * @return \Anthropic\Messages\ThinkingConfigAdaptive|\Anthropic\Messages\ThinkingConfigEnabled|null
   *   Typed config, or NULL if thinking should not be sent.
   */
  protected function resolveThinking(string $model_id): ThinkingConfigAdaptive|ThinkingConfigEnabled|NULL {
    $mode = $this->configuration['thinking_mode'] ?? '';
    if (empty($mode)) {
      return NULL;
    }

    $capabilities = $this->getModelCapabilities($model_id);
    // Fail-safe fallback: when capabilities can't be verified (transient
    // API failure, cold cache), prefer adaptive thinking — newer models
    // like Opus 4.7 / Mythos ACCEPT adaptive but REJECT manual `enabled`
    // with 400. Optimistically allowing `enabled` would regress those.
    // If the user explicitly asked for `enabled` and we can verify the
    // model supports it, honor that; otherwise downgrade.
    if ($capabilities === NULL) {
      return ThinkingConfigAdaptive::with();
    }

    $adaptive_supported = $capabilities->thinking->types->adaptive->supported;
    $enabled_supported = $capabilities->thinking->types->enabled->supported;

    if ($mode === 'adaptive') {
      if ($adaptive_supported) {
        return ThinkingConfigAdaptive::with();
      }
      if ($enabled_supported) {
        return ThinkingConfigEnabled::with(
          budgetTokens: (int) ($this->configuration['thinking_budget'] ?? 10000),
        );
      }
      return NULL;
    }

    // Mode is 'enabled' (or any other truthy legacy value).
    if ($enabled_supported) {
      return ThinkingConfigEnabled::with(
        budgetTokens: (int) ($this->configuration['thinking_budget'] ?? 10000),
      );
    }
    if ($adaptive_supported) {
      // Downgrade so the request doesn't 400 on Opus 4.7 / Mythos etc.
      return ThinkingConfigAdaptive::with();
    }
    return NULL;
  }

  /**
   * Parses a typed SDK Message response into a ChatOutput.
   *
   * Iterates Message->content with instanceof on typed block classes (text,
   * thinking, tool_use, redacted_thinking). Populates cached-input-tokens
   * on TokenUsageDto — fixes the compat-layer bug where cache was NULL.
   *
   * @param \Anthropic\Messages\Message $message
   *   Typed SDK response.
   * @param \Drupal\ai\OperationType\Chat\ChatInput|null $input
   *   Original input, used to resolve tool_use block names.
   */
  protected function parseMessageResponse(Message $message, ?ChatInput $input = NULL): ChatOutput {
    $text = '';
    $tools = [];
    $thinking = '';
    foreach ($message->content as $block) {
      if ($block instanceof TextBlock) {
        $text .= $block->text;
      }
      elseif ($block instanceof ToolUseBlock) {
        $function_input = $input?->getChatTools()?->getFunctionByName($block->name);
        if ($function_input === NULL) {
          continue;
        }
        $tools[] = new ToolsFunctionOutput(
          $function_input, $block->id, $block->input,
        );
      }
      elseif ($block instanceof ThinkingBlock) {
        $thinking .= $block->thinking;
      }
      // RedactedThinkingBlock: intentional no-op. The content is encrypted
      // and opaque to us; it exists only for continuity in multi-turn
      // conversations and should not be surfaced.
    }

    $chatMessage = new ChatMessage('assistant', $text);
    if (!empty($tools)) {
      $chatMessage->setTools($tools);
    }

    $metadata = [];
    if ($thinking !== '') {
      $metadata['thinking'] = $thinking;
    }

    $usage = $message->usage;
    $input_tokens = $usage->inputTokens;
    $output_tokens = $usage->outputTokens;
    $total = (int) ($input_tokens + $output_tokens);
    // output_tokens includes thinking tokens — API doesn't separate them.
    $reasoning = ($thinking !== '') ? $output_tokens : NULL;
    // Surface cache-write accounting via metadata. AI core's TokenUsageDto
    // tracks cache_read (the "hit") in $cached; the "write" counterpart
    // (tokens billed when a cache is first created) has no typed field,
    // so we expose it via ChatOutput->getMetadata() for AI Logging and
    // cost-tracking consumers.
    if (!empty($usage->cacheCreationInputTokens)) {
      $metadata['cache_creation_tokens'] = $usage->cacheCreationInputTokens;
    }

    $token_usage = new TokenUsageDto(
      input: $input_tokens,
      output: $output_tokens,
      total: $total,
      reasoning: $reasoning,
      cached: $usage->cacheReadInputTokens,
    );

    // Preserve the raw Message on ChatOutput->$rawOutput. Cast to array
    // for downstream code that still expects array access.
    return new ChatOutput($chatMessage, $message, $metadata, $token_usage);
  }

  /**
   * Whether to route this chat through the native Anthropic Messages API.
   *
   * Native is the default: the typed SDK path delivers correct
   * cache_read_input_tokens, thinking blocks, tool_use typing, and the
   * typed Usage DTO. The OpenAI compat layer strips these. We fall back
   * to compat only for setups that actively opt out (future config flag)
   * — which is not currently exposed.
   *
   * @return bool
   *   TRUE when native is used for this request.
   */
  protected function requiresNativeApi(): bool {
    // Opt-out hook for future use: setting configuration['use_compat_layer']
    // to TRUE forces the OpenAI-compat path. Default is native.
    return empty($this->configuration['use_compat_layer']);
  }

  /**
   * {@inheritdoc}
   *
   * Maps typed SDK exceptions (from $client->messages->create and friends)
   * to AI module typed exceptions. Falls back to the parent handler (which
   * understands OpenAI-compat-layer exceptions) for anything not matched
   * here, so behavior on the OpenAI-compat path is unchanged.
   */
  protected function handleApiException(\Exception $e): void {
    // Typed SDK path — prefer the structured `error.type` from the response
    // body (Anthropic\ErrorType enum) over any text matching. Preserve the
    // original exception via $previous so callers retain the SDK stack.
    if ($e instanceof APIStatusException && $e->type !== NULL) {
      switch ($e->type) {
        case ErrorType::BILLING_ERROR:
          throw new AiQuotaException($e->getMessage(), $e->getCode(), $e);

        case ErrorType::RATE_LIMIT_ERROR:
        case ErrorType::OVERLOADED_ERROR:
          throw new AiRateLimitException($e->getMessage(), $e->getCode(), $e);

        case ErrorType::AUTHENTICATION_ERROR:
        case ErrorType::PERMISSION_ERROR:
          throw new AiSetupFailureException($e->getMessage(), $e->getCode(), $e);

        case ErrorType::INVALID_REQUEST_ERROR:
          // Anthropic routes safety-policy blocks through invalid_request.
          // Heuristic: scan message for moderation-related language.
          if (self::looksLikeSafetyBlock($e->getMessage())) {
            throw new AiUnsafePromptException($e->getMessage(), $e->getCode(), $e);
          }
          throw new AiBadRequestException($e->getMessage(), $e->getCode(), $e);

        case ErrorType::NOT_FOUND_ERROR:
          throw new AiBadRequestException($e->getMessage(), $e->getCode(), $e);

        case ErrorType::TIMEOUT_ERROR:
        case ErrorType::API_ERROR:
          throw new AiRequestErrorException($e->getMessage(), $e->getCode(), $e);

        default:
          // Unknown future ErrorType — fall through to class-hierarchy
          // checks rather than silently misclassifying.
          break;
      }
    }
    // Fallback on exception class hierarchy when the response body didn't
    // carry a typed `error.type` (proxies stripping body, early init,
    // future ErrorType additions).
    if ($e instanceof SdkRateLimitException) {
      throw new AiRateLimitException($e->getMessage(), $e->getCode(), $e);
    }
    if ($e instanceof SdkAuthException || $e instanceof SdkPermissionException) {
      throw new AiSetupFailureException($e->getMessage(), $e->getCode(), $e);
    }
    if ($e instanceof SdkBadRequestException) {
      throw new AiBadRequestException($e->getMessage(), $e->getCode(), $e);
    }
    if ($e instanceof APIException) {
      throw new AiRequestErrorException($e->getMessage(), $e->getCode(), $e);
    }
    // Compat-layer path — parent catches raw \Exception from the OpenAI
    // SDK which has no typed Anthropic errors. Delegate there.
    parent::handleApiException($e);
  }

  /**
   * Heuristic: does an INVALID_REQUEST_ERROR message indicate a safety block?
   *
   * Anthropic routes content-moderation blocks through invalid_request_error
   * rather than a dedicated error type. Centralized here so a single update
   * covers future API changes.
   */
  protected static function looksLikeSafetyBlock(string $message): bool {
    $needles = [
      'content was blocked',
      'safety',
      'harmful',
      'policy',
      'unsafe',
    ];
    foreach ($needles as $needle) {
      if (stripos($message, $needle) !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
