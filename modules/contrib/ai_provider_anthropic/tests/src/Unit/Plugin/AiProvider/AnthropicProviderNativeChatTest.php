<?php

namespace Drupal\Tests\ai_provider_anthropic\Unit\Plugin\AiProvider;

use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\BadRequestException as SdkBadRequestException;
use Anthropic\Core\Exceptions\RateLimitException as SdkRateLimitException;
use Anthropic\ErrorType;
use Anthropic\Messages\Base64PDFSource;
use Anthropic\Messages\CacheControlEphemeral;
use Anthropic\Messages\DocumentBlockParam;
use Anthropic\Messages\ImageBlockParam;
use Anthropic\Messages\Message;
use Anthropic\Messages\MessageCreateParams;
use Anthropic\Messages\OutputConfig;
use Anthropic\Messages\OutputConfig\Effort;
use Anthropic\Messages\TextBlock;
use Anthropic\Messages\TextBlockParam;
use Anthropic\Messages\ThinkingBlock;
use Anthropic\Messages\ThinkingConfigAdaptive;
use Anthropic\Messages\ThinkingConfigEnabled;
use Anthropic\Messages\Tool as SdkTool;
use Anthropic\Messages\ToolResultBlockParam;
use Anthropic\Messages\Usage;
use Anthropic\Models\CapabilitySupport;
use Anthropic\Models\ContextManagementCapability;
use Anthropic\Models\EffortCapability;
use Anthropic\Models\ModelCapabilities;
use Anthropic\Models\ThinkingCapability;
use Anthropic\Models\ThinkingTypes;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\ai\Enum\AiModelCapability;
use Drupal\ai\Enum\AiProviderCapability;
use Drupal\ai\Exception\AiBadRequestException;
use Drupal\ai\Exception\AiQuotaException;
use Drupal\ai\Exception\AiRateLimitException;
use Drupal\ai\Exception\AiSetupFailureException;
use Drupal\ai\Exception\AiUnsafePromptException;
use Drupal\ai\OperationType\Chat\Tools\ToolsFunctionInput;
use Drupal\ai\OperationType\Chat\Tools\ToolsInput;
use Drupal\ai\OperationType\GenericType\DocumentFile;
use Drupal\ai\OperationType\GenericType\ImageFile;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\Chat\ChatOutput;
use Drupal\ai_provider_anthropic\Plugin\AiProvider\AnthropicProvider;
use Drupal\ai_provider_anthropic\Service\AnthropicNativeClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests AnthropicProvider typed SDK integration.
 */
#[CoversClass(AnthropicProvider::class)]
#[Group('ai_provider_anthropic')]
class AnthropicProviderNativeChatTest extends TestCase {

  /**
   * Builds a ModelCapabilities fixture for tests.
   */
  protected function buildCapabilities(
    bool $thinkingSupported = TRUE,
    bool $adaptive = TRUE,
    bool $enabled = TRUE,
    bool $effortSupported = TRUE,
    ?bool $xhigh = NULL,
  ): ModelCapabilities {
    $yes = CapabilitySupport::with(supported: TRUE);
    $no = CapabilitySupport::with(supported: FALSE);
    return ModelCapabilities::with(
      batch: $yes,
      citations: $yes,
      codeExecution: $yes,
      contextManagement: ContextManagementCapability::with(
        clearThinking20251015: $yes,
        clearToolUses20250919: $yes,
        compact20260112: $yes,
        supported: TRUE,
      ),
      effort: EffortCapability::with(
        high: $effortSupported ? $yes : $no,
        low: $effortSupported ? $yes : $no,
        max: $effortSupported ? $yes : $no,
        medium: $effortSupported ? $yes : $no,
        supported: $effortSupported,
        xhigh: $xhigh === NULL ? NULL : ($xhigh ? $yes : $no),
      ),
      imageInput: $yes,
      pdfInput: $yes,
      structuredOutputs: $yes,
      thinking: ThinkingCapability::with(
        supported: $thinkingSupported,
        types: ThinkingTypes::with(
          adaptive: $adaptive ? $yes : $no,
          enabled: $enabled ? $yes : $no,
        ),
      ),
    );
  }

  /**
   * Builds a provider with getModelCapabilities() stubbed to return caps.
   */
  protected function buildProviderWithCapabilities(?ModelCapabilities $capabilities): AnthropicProvider {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getModelCapabilities'])
      ->getMock();
    $provider->method('getModelCapabilities')->willReturn($capabilities);
    return $provider;
  }

  /**
   * Invokes a protected method via reflection.
   */
  protected function invoke(AnthropicProvider $provider, string $method, ...$args): mixed {
    $reflection = new \ReflectionMethod(AnthropicProvider::class, $method);
    return $reflection->invoke($provider, ...$args);
  }

  /**
   * Get model capabilities returns cached data.
   */
  public function testGetModelCapabilitiesReturnsCachedData(): void {
    $capabilities = $this->buildCapabilities();
    $cached = (object) ['data' => $capabilities];
    $cacheBackend = $this->createMock(CacheBackendInterface::class);
    $cacheBackend->expects($this->once())->method('get')
      ->with('ai_provider_anthropic:capabilities:claude-opus-4-7-20260416')
      ->willReturn($cached);
    $nativeClient = $this->createMock(AnthropicNativeClient::class);
    $nativeClient->expects($this->never())->method('isInitialized');

    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    (new \ReflectionProperty(AnthropicProvider::class, 'cacheBackend'))
      ->setValue($provider, $cacheBackend);
    (new \ReflectionProperty(AnthropicProvider::class, 'nativeClient'))
      ->setValue($provider, $nativeClient);

    $result = $this->invoke($provider, 'getModelCapabilities', 'claude-opus-4-7-20260416');
    $this->assertSame($capabilities, $result);
  }

  /**
   * Get model capabilities returns null on api failure.
   */
  public function testGetModelCapabilitiesReturnsNullOnApiFailure(): void {
    $cacheBackend = $this->createMock(CacheBackendInterface::class);
    $cacheBackend->method('get')->willReturn(FALSE);
    $nativeClient = $this->createMock(AnthropicNativeClient::class);
    $nativeClient->method('isInitialized')->willReturn(FALSE);
    $nativeClient->method('initialize')
      ->willThrowException(new \RuntimeException('init failed'));
    $loggerFactory = $this->createMock('Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $logger = $this->createMock('Psr\Log\LoggerInterface');
    $logger->expects($this->atLeastOnce())->method('warning');
    $loggerFactory->method('get')->willReturn($logger);

    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods(['loadApiKey'])->getMock();
    $provider->method('loadApiKey')->willReturn('test-key');
    (new \ReflectionProperty(AnthropicProvider::class, 'cacheBackend'))
      ->setValue($provider, $cacheBackend);
    (new \ReflectionProperty(AnthropicProvider::class, 'nativeClient'))
      ->setValue($provider, $nativeClient);
    (new \ReflectionProperty(AnthropicProvider::class, 'loggerFactory'))
      ->setValue($provider, $loggerFactory);

    $result = $this->invoke($provider, 'getModelCapabilities', 'claude-opus-4-7');
    $this->assertNull($result);
  }

  /**
   * Get model capabilities ignores corrupt cache data.
   */
  public function testGetModelCapabilitiesIgnoresCorruptCacheData(): void {
    $cached = (object) ['data' => 'not-a-model-capabilities'];
    $cacheBackend = $this->createMock(CacheBackendInterface::class);
    $cacheBackend->method('get')->willReturn($cached);
    $nativeClient = $this->createMock(AnthropicNativeClient::class);
    $nativeClient->method('isInitialized')->willReturn(FALSE);
    $nativeClient->method('initialize')
      ->willThrowException(new \RuntimeException('No API key'));
    $loggerFactory = $this->createMock('Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $loggerFactory->method('get')
      ->willReturn($this->createMock('Psr\Log\LoggerInterface'));

    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods(['loadApiKey'])->getMock();
    $provider->method('loadApiKey')->willReturn('test-key');
    (new \ReflectionProperty(AnthropicProvider::class, 'cacheBackend'))
      ->setValue($provider, $cacheBackend);
    (new \ReflectionProperty(AnthropicProvider::class, 'nativeClient'))
      ->setValue($provider, $nativeClient);
    (new \ReflectionProperty(AnthropicProvider::class, 'loggerFactory'))
      ->setValue($provider, $loggerFactory);

    $this->assertNull($this->invoke($provider, 'getModelCapabilities', 'm'));
  }

  /**
   * Get model settings exposes xhigh effort for opus 47.
   */
  public function testGetModelSettingsExposesXhighEffortForOpus47(): void {
    $provider = $this->buildProviderWithCapabilities(
      $this->buildCapabilities(adaptive: TRUE, enabled: FALSE, xhigh: TRUE),
    );
    $result = $provider->getModelSettings('claude-opus-4-7-20260416', []);
    $options = $result['effort']['constraints']['options'];
    $this->assertArrayHasKey('xhigh', $options);
    $this->assertArrayHasKey('max', $options);
  }

  /**
   * Anthropic omits xhigh from the capability tree even for Opus 4.7.
   *
   * Live API verification 2026-04-21: the server-side validator accepts
   * effort=xhigh on Opus 4.7 (and rejects it on Sonnet 4.6 / Opus 4.6),
   * but the models/{id}.capabilities.effort tree does not advertise the
   * xhigh subfield. Our modelAcceptsXhigh heuristic fills that gap.
   */
  public function testGetModelSettingsExposesXhighForOpus47WhenCapabilityTreeOmitsIt(): void {
    $provider = $this->buildProviderWithCapabilities(
      $this->buildCapabilities(adaptive: TRUE, enabled: FALSE, xhigh: NULL),
    );
    $result = $provider->getModelSettings('claude-opus-4-7-20260416', []);
    $options = $result['effort']['constraints']['options'];
    $this->assertArrayHasKey('xhigh', $options);
  }

  /**
   * Non-Opus-4.7 models must not get xhigh via the heuristic.
   */
  public function testGetModelSettingsOmitsXhighForNonXhighModels(): void {
    $provider = $this->buildProviderWithCapabilities(
      $this->buildCapabilities(adaptive: TRUE, enabled: TRUE, xhigh: NULL),
    );
    $result = $provider->getModelSettings('claude-sonnet-4-6', []);
    $options = $result['effort']['constraints']['options'];
    $this->assertArrayNotHasKey('xhigh', $options);
  }

  /**
   * Get model settings hides enabled thinking when capability denies.
   */
  public function testGetModelSettingsHidesEnabledThinkingWhenCapabilityDenies(): void {
    $provider = $this->buildProviderWithCapabilities(
      $this->buildCapabilities(adaptive: TRUE, enabled: FALSE, xhigh: TRUE),
    );
    $result = $provider->getModelSettings('claude-opus-4-7-20260416', []);
    $options = $result['thinking_mode']['constraints']['options'];
    $this->assertArrayHasKey('adaptive', $options);
    $this->assertArrayNotHasKey('enabled', $options);
    $this->assertArrayNotHasKey('thinking_budget', $result);
  }

  /**
   * Get model settings omits thinking mode when no types supported.
   */
  public function testGetModelSettingsOmitsThinkingModeWhenNoTypesSupported(): void {
    $provider = $this->buildProviderWithCapabilities(
      $this->buildCapabilities(
        thinkingSupported: TRUE, adaptive: FALSE, enabled: FALSE,
        effortSupported: FALSE, xhigh: NULL,
      ),
    );
    $result = $provider->getModelSettings('m', []);
    $this->assertArrayNotHasKey('thinking_mode', $result);
    $this->assertArrayNotHasKey('thinking_budget', $result);
  }

  /**
   * Get model settings returns config unchanged when no capabilities.
   */
  public function testGetModelSettingsReturnsConfigUnchangedWhenNoCapabilities(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $result = $provider->getModelSettings('claude-sonnet-4-5-20250929', ['top_p' => 0.9]);
    // Claude 4.x+ regex still strips top_p.
    $this->assertArrayNotHasKey('top_p', $result);
    $this->assertArrayNotHasKey('effort', $result);
    $this->assertArrayNotHasKey('thinking_mode', $result);
  }

  /**
   * Get model settings preserves nucleus sampling on legacy.
   */
  public function testGetModelSettingsPreservesNucleusSamplingOnLegacy(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $result = $provider->getModelSettings('claude-3-5-sonnet-20241022', ['top_p' => 0.9]);
    $this->assertEquals(0.9, $result['top_p']);
  }

  /**
   * Native path is the default: empty config still routes to native.
   *
   * Rationale: the OpenAI compat layer strips cache_read_input_tokens,
   * thinking blocks, and Usage typing. Native is correct; compat is the
   * opt-out.
   */
  public function testRequiresNativeApiTrueByDefault(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $provider->configuration = [];
    $this->assertTrue($this->invoke($provider, 'requiresNativeApi'));
  }

  /**
   * Requires native api false when compat opt in.
   */
  public function testRequiresNativeApiFalseWhenCompatOptIn(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $provider->configuration = ['use_compat_layer' => TRUE];
    $this->assertFalse($this->invoke($provider, 'requiresNativeApi'));
  }

  /**
   * Requires native api true with effort.
   */
  public function testRequiresNativeApiTrueWithEffort(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $provider->configuration = ['effort' => 'high'];
    $this->assertTrue($this->invoke($provider, 'requiresNativeApi'));
  }

  /**
   * Build params basic message.
   */
  public function testBuildParamsBasicMessage(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = ['max_tokens' => 1024];
    $input = new ChatInput([new ChatMessage('user', 'Hello')]);

    /** @var \Anthropic\Messages\MessageCreateParams $params */
    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'claude-sonnet-4-5-20250929');
    $this->assertInstanceOf(MessageCreateParams::class, $params);
    $this->assertEquals(1024, $params->maxTokens);
    $this->assertEquals('claude-sonnet-4-5-20250929', $params->model);
    $this->assertCount(1, $params->messages);
    $this->assertEquals('user', $params->messages[0]['role']);
  }

  /**
   * Build params defaults max tokens.
   */
  public function testBuildParamsDefaultsMaxTokens(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = [];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);
    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $this->assertEquals(4096, $params->maxTokens);
  }

  /**
   * Build params with system prompt.
   */
  public function testBuildParamsWithSystemPrompt(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = [];
    $input = new ChatInput([new ChatMessage('user', 'Hello')]);
    $input->setSystemPrompt('You are a helpful assistant.');

    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $this->assertEquals('You are a helpful assistant.', $params->system);
  }

  /**
   * Build params with effort high.
   */
  public function testBuildParamsWithEffortHigh(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = ['effort' => 'high'];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);

    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $this->assertInstanceOf(OutputConfig::class, $params['outputConfig']);
    // SDK stores raw payload values via array access; typed property read
    // is blocked by SdkModel's __get contract.
    $this->assertEquals(Effort::HIGH, $params['outputConfig']['effort']);
  }

  /**
   * Build params with xhigh effort for opus 47.
   */
  public function testBuildParamsWithXhighEffortForOpus47(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = ['effort' => 'xhigh'];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);

    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'claude-opus-4-7');
    $this->assertEquals(Effort::XHIGH, $params['outputConfig']['effort']);
  }

  /**
   * Build params with adaptive thinking.
   */
  public function testBuildParamsWithAdaptiveThinking(): void {
    $provider = $this->buildProviderWithCapabilities(
      $this->buildCapabilities(adaptive: TRUE, enabled: TRUE),
    );
    $provider->configuration = ['thinking_mode' => 'adaptive'];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);

    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $this->assertInstanceOf(ThinkingConfigAdaptive::class, $params->thinking);
    $this->assertEquals(1.0, $params->temperature);
  }

  /**
   * Build params with enabled thinking and budget.
   */
  public function testBuildParamsWithEnabledThinkingAndBudget(): void {
    $provider = $this->buildProviderWithCapabilities(
      $this->buildCapabilities(adaptive: TRUE, enabled: TRUE),
    );
    $provider->configuration = ['thinking_mode' => 'enabled', 'thinking_budget' => 8000];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);

    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $this->assertInstanceOf(ThinkingConfigEnabled::class, $params->thinking);
    $this->assertEquals(8000, $params->thinking->budgetTokens);
    $this->assertEquals(1.0, $params->temperature);
  }

  /**
   * Opus 4.7 is adaptive-only; stored 'enabled' downgrades to adaptive.
   *
   * Otherwise the request would 400 on the newer models.
   */
  public function testBuildParamsDowngradesEnabledToAdaptiveWhenCapabilityDenies(): void {
    $provider = $this->buildProviderWithCapabilities(
      $this->buildCapabilities(adaptive: TRUE, enabled: FALSE, xhigh: TRUE),
    );
    $provider->configuration = ['thinking_mode' => 'enabled', 'thinking_budget' => 10000];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);

    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'claude-opus-4-7');
    $this->assertInstanceOf(ThinkingConfigAdaptive::class, $params->thinking);
  }

  /**
   * If capability denies both modes, thinking is omitted entirely.
   *
   * Prevents sending a request the API is guaranteed to reject.
   */
  public function testBuildParamsOmitsThinkingWhenNoCapabilitySupport(): void {
    $provider = $this->buildProviderWithCapabilities(
      $this->buildCapabilities(adaptive: FALSE, enabled: FALSE),
    );
    $provider->configuration = ['thinking_mode' => 'enabled'];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);

    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $this->assertNull($params->thinking);
  }

  /**
   * Build params includes temperature.
   */
  public function testBuildParamsIncludesTemperature(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = ['temperature' => 0.5];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);
    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $this->assertEquals(0.5, $params->temperature);
  }

  /**
   * Builds a typed Message fixture with the given content blocks and usage.
   */
  protected function buildMessage(array $content, int $input_tokens = 10, int $output_tokens = 5, ?int $cache_read = NULL, ?int $cache_creation = NULL): Message {
    return Message::with(
      id: 'msg_test',
      container: NULL,
      content: $content,
      model: 'claude-test',
      stopDetails: NULL,
      stopReason: 'end_turn',
      stopSequence: NULL,
      usage: Usage::with(
        cacheCreation: NULL,
        cacheCreationInputTokens: $cache_creation,
        cacheReadInputTokens: $cache_read,
        inferenceGeo: NULL,
        inputTokens: $input_tokens,
        outputTokens: $output_tokens,
        outputTokensDetails: NULL,
        serverToolUse: NULL,
        serviceTier: NULL,
      ),
    );
  }

  /**
   * Parse message basic text.
   */
  public function testParseMessageBasicText(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $message = $this->buildMessage([
      TextBlock::with(citations: NULL, text: 'Hello from Claude!'),
    ]);

    /** @var \Drupal\ai\OperationType\Chat\ChatOutput $out */
    $out = $this->invoke($provider, 'parseMessageResponse', $message, NULL);
    $this->assertInstanceOf(ChatOutput::class, $out);
    $this->assertEquals('Hello from Claude!', $out->getNormalized()->getText());
    $this->assertEquals('assistant', $out->getNormalized()->getRole());
  }

  /**
   * Parse message multiple text blocks.
   */
  public function testParseMessageMultipleTextBlocks(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $message = $this->buildMessage([
      TextBlock::with(citations: NULL, text: 'First. '),
      TextBlock::with(citations: NULL, text: 'Second.'),
    ]);
    $out = $this->invoke($provider, 'parseMessageResponse', $message, NULL);
    $this->assertEquals('First. Second.', $out->getNormalized()->getText());
  }

  /**
   * Parse message token usage.
   */
  public function testParseMessageTokenUsage(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $message = $this->buildMessage(
      [TextBlock::with(citations: NULL, text: 'Hi')],
      input_tokens: 100, output_tokens: 50,
    );
    $out = $this->invoke($provider, 'parseMessageResponse', $message, NULL);
    $usage = $out->getTokenUsage();
    $this->assertEquals(100, $usage->input);
    $this->assertEquals(50, $usage->output);
    $this->assertEquals(150, $usage->total);
    $this->assertNull($usage->reasoning);
    $this->assertNull($usage->cached);
  }

  /**
   * Fixes the long-standing NULL-cached-tokens compat-layer bug.
   *
   * Typed Usage populates cacheReadInputTokens natively via the SDK.
   */
  public function testParseMessagePopulatesCachedTokens(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $message = $this->buildMessage(
      [TextBlock::with(citations: NULL, text: 'Cached')],
      cache_read: 80,
    );
    $out = $this->invoke($provider, 'parseMessageResponse', $message, NULL);
    $this->assertEquals(80, $out->getTokenUsage()->cached);
  }

  /**
   * Typed Usage exposes cacheCreationInputTokens via ChatOutput metadata.
   */
  public function testParseMessageSurfacesCacheCreationTokensViaMetadata(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $message = $this->buildMessage(
      [TextBlock::with(citations: NULL, text: 'First call — cache write.')],
      cache_creation: 1234,
    );
    $out = $this->invoke($provider, 'parseMessageResponse', $message, NULL);
    $this->assertSame(1234, $out->getMetadata()['cache_creation_tokens']);
  }

  /**
   * Without cacheCreationInputTokens, no metadata key is emitted.
   */
  public function testParseMessageOmitsCacheCreationMetadataWhenAbsent(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $message = $this->buildMessage(
      [TextBlock::with(citations: NULL, text: 'No cache write.')],
    );
    $out = $this->invoke($provider, 'parseMessageResponse', $message, NULL);
    $this->assertArrayNotHasKey('cache_creation_tokens', $out->getMetadata());
  }

  /**
   * Parse message with thinking block.
   */
  public function testParseMessageWithThinkingBlock(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $message = $this->buildMessage([
      ThinkingBlock::with(signature: 'sig', thinking: 'Deep thought.'),
      TextBlock::with(citations: NULL, text: 'The answer.'),
    ]);
    $out = $this->invoke($provider, 'parseMessageResponse', $message, NULL);
    $this->assertEquals('The answer.', $out->getNormalized()->getText());
    $this->assertEquals('Deep thought.', $out->getMetadata()['thinking']);
  }

  /**
   * Parse message reasoning tokens when thinking present.
   */
  public function testParseMessageReasoningTokensWhenThinkingPresent(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $message = $this->buildMessage(
      [
        ThinkingBlock::with(signature: 's', thinking: 'x'),
        TextBlock::with(citations: NULL, text: 'y'),
      ],
      input_tokens: 50, output_tokens: 200,
    );
    $out = $this->invoke($provider, 'parseMessageResponse', $message, NULL);
    $this->assertEquals(200, $out->getTokenUsage()->reasoning);
  }

  /**
   * Parse message empty content.
   */
  public function testParseMessageEmptyContent(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $message = $this->buildMessage([]);
    $out = $this->invoke($provider, 'parseMessageResponse', $message, NULL);
    $this->assertEquals('', $out->getNormalized()->getText());
  }

  /**
   * Builds a minimal SDK exception mock with an optional typed error.
   *
   * We build partial mocks because SDK exceptions require PSR Request and
   * Response to construct fully. We need only the error-type dispatch and
   * the message — both addressable via reflection.
   */
  protected function mockSdkException(string $class, string $message = 'err', ?ErrorType $type = NULL): \Throwable {
    $e = $this->getMockBuilder($class)
      ->disableOriginalConstructor()
      ->onlyMethods([])
      ->getMock();
    (new \ReflectionProperty(\Exception::class, 'message'))->setValue($e, $message);
    // APIStatusException has a public readonly ?ErrorType $type.
    if (is_subclass_of($class, APIStatusException::class)) {
      (new \ReflectionProperty(APIStatusException::class, 'type'))->setValue($e, $type);
    }
    return $e;
  }

  /**
   * Handle api exception maps billing error to quota.
   */
  public function testHandleApiExceptionMapsBillingErrorToQuota(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $e = $this->mockSdkException(SdkBadRequestException::class, 'billing', ErrorType::BILLING_ERROR);
    $this->expectException(AiQuotaException::class);
    $this->invoke($provider, 'handleApiException', $e);
  }

  /**
   * Handle api exception maps rate limit typed error.
   */
  public function testHandleApiExceptionMapsRateLimitTypedError(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $e = $this->mockSdkException(SdkBadRequestException::class, 'rl', ErrorType::RATE_LIMIT_ERROR);
    $this->expectException(AiRateLimitException::class);
    $this->invoke($provider, 'handleApiException', $e);
  }

  /**
   * Handle api exception maps auth typed error.
   */
  public function testHandleApiExceptionMapsAuthTypedError(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $e = $this->mockSdkException(SdkBadRequestException::class, 'auth', ErrorType::AUTHENTICATION_ERROR);
    $this->expectException(AiSetupFailureException::class);
    $this->invoke($provider, 'handleApiException', $e);
  }

  /**
   * Handle api exception falls back to class hierarchy.
   */
  public function testHandleApiExceptionFallsBackToClassHierarchy(): void {
    // No typed ErrorType (e.g., proxy-stripped body), exception class
    // hierarchy is the fallback.
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $e = $this->mockSdkException(SdkRateLimitException::class, 'rl', NULL);
    $this->expectException(AiRateLimitException::class);
    $this->invoke($provider, 'handleApiException', $e);
  }

  /**
   * Handle api exception maps bad request class fallback.
   */
  public function testHandleApiExceptionMapsBadRequestClassFallback(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $e = $this->mockSdkException(SdkBadRequestException::class, 'bad', NULL);
    $this->expectException(AiBadRequestException::class);
    $this->invoke($provider, 'handleApiException', $e);
  }

  /**
   * When capabilities can't be verified, prefer adaptive (never enabled).
   *
   * Opus 4.7 and Mythos REJECT manual enabled+budget_tokens with 400.
   * Optimistic 'enabled' would cause production regressions whenever the
   * capabilities cache misses concurrently with a transient models.retrieve.
   */
  public function testResolveThinkingPrefersAdaptiveWhenCapabilitiesUnavailable(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = ['thinking_mode' => 'enabled', 'thinking_budget' => 10000];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);

    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'claude-opus-4-7');
    $this->assertInstanceOf(ThinkingConfigAdaptive::class, $params->thinking);
  }

  /**
   * Build content emits typed text block param.
   */
  public function testBuildContentEmitsTypedTextBlockParam(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = [];
    $input = new ChatInput([new ChatMessage('user', 'Hello typed')]);
    $messages = $this->invoke($provider, 'buildMessageContent', $input);
    $this->assertInstanceOf(TextBlockParam::class, $messages[0]['content'][0]);
    $this->assertEquals('Hello typed', $messages[0]['content'][0]['text']);
  }

  /**
   * Build content emits typed image block param.
   */
  public function testBuildContentEmitsTypedImageBlockParam(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = [];
    $msg = new ChatMessage('user', '');
    $msg->setImage(new ImageFile('fake-binary', 'image/png'));
    $input = new ChatInput([$msg]);
    $messages = $this->invoke($provider, 'buildMessageContent', $input);
    $this->assertInstanceOf(ImageBlockParam::class, $messages[0]['content'][0]);
  }

  /**
   * Build content emits typed tool result block param.
   */
  public function testBuildContentEmitsTypedToolResultBlockParam(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = [];
    $toolMsg = new ChatMessage('tool', '{"temp":22}');
    $toolMsg->setToolsId('toolu_123');
    $input = new ChatInput([$toolMsg]);
    $messages = $this->invoke($provider, 'buildMessageContent', $input);
    $this->assertEquals('user', $messages[0]['role']);
    $this->assertInstanceOf(ToolResultBlockParam::class, $messages[0]['content'][0]);
    $this->assertEquals('toolu_123', $messages[0]['content'][0]['toolUseID']);
  }

  /**
   * Build params emits typed tool.
   */
  public function testBuildParamsEmitsTypedTool(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = [];
    $fn = new ToolsFunctionInput('get_weather', [
      'name' => 'get_weather',
      'description' => 'Get weather.',
      'parameters' => ['type' => 'object', 'properties' => ['loc' => ['type' => 'string']]],
    ]);
    $input = new ChatInput([new ChatMessage('user', 'Weather?')]);
    $input->setChatTools(new ToolsInput([$fn]));
    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $tools = $params['tools'] ?? [];
    $this->assertCount(1, $tools);
    $this->assertInstanceOf(SdkTool::class, $tools[0]);
    $this->assertEquals('get_weather', $tools[0]['name']);
  }

  /**
   * Build params attaches cache control when configured.
   */
  public function testBuildParamsAttachesCacheControlWhenConfigured(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = ['prompt_cache' => TRUE];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);
    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $this->assertInstanceOf(CacheControlEphemeral::class, $params['cacheControl']);
  }

  /**
   * Build params omits cache control by default.
   */
  public function testBuildParamsOmitsCacheControlByDefault(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = [];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);
    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $this->assertNull($params['cacheControl'] ?? NULL);
  }

  /**
   * TTL=5m (default) emits CacheControlEphemeral with no explicit TTL value.
   */
  public function testBuildParamsCacheControlDefault5mTtl(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = ['prompt_cache' => TRUE];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);
    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $cache = $params['cacheControl'];
    $this->assertInstanceOf(CacheControlEphemeral::class, $cache);
    $this->assertNull($cache->ttl, 'Default TTL omitted from the wire (SDK applies 5m).');
  }

  /**
   * TTL=1h emits CacheControlEphemeral with ttl explicitly set.
   */
  public function testBuildParamsCacheControl1hTtl(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = [
      'prompt_cache' => TRUE,
      'prompt_cache_ttl' => '1h',
    ];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);
    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $cache = $params['cacheControl'];
    $this->assertInstanceOf(CacheControlEphemeral::class, $cache);
    $this->assertSame('1h', $cache->ttl);
  }

  /**
   * System prompt with caching becomes a typed block with cache_control.
   *
   * The system block carries the cache breakpoint because caching a bare
   * string is a no-op. Anthropic requires cache_control on a content block,
   * and a system prompt is typically large enough to clear the 1024-token
   * minimum.
   */
  public function testBuildParamsSystemPromptIsTypedBlockWithCacheControlWhenCacheEnabled(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = ['prompt_cache' => TRUE];
    $input = new ChatInput([
      new ChatMessage('system', 'You are a careful assistant.'),
      new ChatMessage('user', 'Hi'),
    ]);
    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $this->assertIsArray($params['system']);
    $this->assertInstanceOf(TextBlockParam::class, $params['system'][0]);
    $this->assertInstanceOf(CacheControlEphemeral::class, $params['system'][0]->cacheControl);
  }

  /**
   * System prompt with caching off is still sent as a bare string.
   */
  public function testBuildParamsSystemPromptIsBareStringWhenCacheDisabled(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = [];
    $input = new ChatInput([
      new ChatMessage('system', 'You are a careful assistant.'),
      new ChatMessage('user', 'Hi'),
    ]);
    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $this->assertSame('You are a careful assistant.', $params['system']);
  }

  /**
   * Build message arguments surfaces cacheControl into the SDK args.
   *
   * Regression guard: an earlier version of buildMessageArguments() forgot to
   * extract cacheControl, which silently dropped the top-level breakpoint
   * whenever no system prompt was present.
   */
  public function testBuildMessageArgumentsSurfacesCacheControl(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = ['prompt_cache' => TRUE];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);
    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $args = $provider->buildMessageArguments($params);
    $this->assertArrayHasKey('cacheControl', $args);
    $this->assertInstanceOf(CacheControlEphemeral::class, $args['cacheControl']);
  }

  /**
   * Build message arguments omits cacheControl when caching is off.
   */
  public function testBuildMessageArgumentsOmitsCacheControlWhenAbsent(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = [];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);
    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $args = $provider->buildMessageArguments($params);
    $this->assertArrayNotHasKey('cacheControl', $args);
  }

  /**
   * Returns the example "book" schema from the public API docs.
   */
  protected function bookStructuredSchema(): array {
    return [
      'name' => 'book',
      'strict' => TRUE,
      'schema' => [
        'type' => 'object',
        'title' => 'Book',
        'additionalProperties' => FALSE,
        'properties' => [
          'name' => ['type' => 'string', 'title' => 'Name'],
          'authors' => [
            'type' => 'array',
            'title' => 'Authors',
            'items' => ['type' => 'string'],
          ],
        ],
        'required' => ['name', 'authors'],
      ],
    ];
  }

  /**
   * Build params attaches structured json schema to output config.
   */
  public function testBuildParamsAttachesStructuredJsonSchemaToOutputConfig(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = [];
    $schema = $this->bookStructuredSchema();
    $input = new ChatInput([new ChatMessage('user', 'Describe a book.')]);
    $input->setChatStructuredJsonSchema($schema);

    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $this->assertInstanceOf(OutputConfig::class, $params['outputConfig']);
    $format = $params['outputConfig']['format'];
    $this->assertEquals('json_schema', $format['type']);
    $this->assertSame($schema['schema'], $format['schema']);
  }

  /**
   * The provider must unwrap the schema content from the DTO wrapper.
   *
   * Anthropic's outputConfig.format.schema expects raw JSON Schema, not the
   * {name, strict, schema} envelope produced by ChatInput::setChatStructured-
   * JsonSchema(). Sending the envelope would produce an invalid request.
   */
  public function testBuildParamsUnwrapsSchemaContentFromWrapper(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = [];
    $schema = $this->bookStructuredSchema();
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);
    $input->setChatStructuredJsonSchema($schema);

    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $format = $params['outputConfig']['format'];
    $this->assertArrayNotHasKey('name', $format);
    $this->assertArrayNotHasKey('strict', $format);
    $this->assertArrayHasKey('type', $format['schema']);
    $this->assertEquals('object', $format['schema']['type']);
  }

  /**
   * Schema and effort must coexist on the same OutputConfig.
   *
   * Both features attach to params via withOutputConfig() — a naive
   * implementation could clobber one with the other.
   */
  public function testBuildParamsCombinesStructuredSchemaWithEffort(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = ['effort' => 'high'];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);
    $input->setChatStructuredJsonSchema($this->bookStructuredSchema());

    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $config = $params['outputConfig'];
    $this->assertEquals('json_schema', $config['format']['type']);
    $this->assertEquals(Effort::HIGH, $config['effort']);
  }

  /**
   * Build params omits output config when no schema or effort.
   */
  public function testBuildParamsOmitsOutputConfigWhenNoSchemaOrEffort(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = [];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);

    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $this->assertNull($params['outputConfig'] ?? NULL);
  }

  /**
   * An empty schema (never set on input) must not attach outputConfig.format.
   */
  public function testBuildParamsOmitsFormatWhenSchemaUnset(): void {
    $provider = $this->buildProviderWithCapabilities(NULL);
    $provider->configuration = ['effort' => 'high'];
    $input = new ChatInput([new ChatMessage('user', 'Hi')]);

    $params = $this->invoke($provider, 'buildMessageCreateParams', $input, 'm');
    $config = $params['outputConfig'];
    $this->assertEquals(Effort::HIGH, $config['effort']);
    $this->assertNull($config['format'] ?? NULL);
  }

  /**
   * Handle api exception maps safety block to unsafe prompt.
   */
  public function testHandleApiExceptionMapsSafetyBlockToUnsafePrompt(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $e = $this->mockSdkException(
      SdkBadRequestException::class,
      'Your content was blocked by our safety policy.',
      ErrorType::INVALID_REQUEST_ERROR,
    );
    try {
      $this->invoke($provider, 'handleApiException', $e);
      $this->fail('Expected AiUnsafePromptException was not thrown.');
    }
    catch (AiUnsafePromptException $thrown) {
      $this->assertSame(
        $e,
        $thrown->getPrevious(),
        'AiUnsafePromptException must preserve the SDK exception as $previous.',
      );
    }
  }

  /**
   * Ordinary invalid_request without safety language goes to bad request.
   */
  public function testHandleApiExceptionRoutesNormalInvalidRequest(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $e = $this->mockSdkException(
      SdkBadRequestException::class,
      'max_tokens must be at least 1',
      ErrorType::INVALID_REQUEST_ERROR,
    );
    $this->expectException(AiBadRequestException::class);
    $this->invoke($provider, 'handleApiException', $e);
  }

  /**
   * Unknown ErrorType cases must not silently misclassify.
   *
   * They fall through to the class-hierarchy fallback.
   */
  public function testHandleApiExceptionFallsThroughUnknownErrorType(): void {
    // Build a mock APIStatusException whose type is intentionally NULL
    // (simulating either a proxy-stripped body or a future ErrorType
    // case we haven't listed). Class-hierarchy fallback should kick in.
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $e = $this->mockSdkException(SdkRateLimitException::class, 'rl', NULL);
    $this->expectException(AiRateLimitException::class);
    $this->invoke($provider, 'handleApiException', $e);
  }

  /**
   * All mapped typed exceptions preserve $previous.
   *
   * Ensures the SDK stack trace survives translation to AI module classes.
   */
  public function testHandleApiExceptionPreservesPreviousException(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $e = $this->mockSdkException(SdkBadRequestException::class, 'billing', ErrorType::BILLING_ERROR);
    try {
      $this->invoke($provider, 'handleApiException', $e);
      $this->fail('Expected AiQuotaException');
    }
    catch (AiQuotaException $translated) {
      $this->assertSame($e, $translated->getPrevious());
    }
  }

  /**
   * Provider declares streaming capability.
   */
  public function testProviderDeclaresStreamingCapability(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $this->assertContains(
      AiProviderCapability::StreamChatOutput,
      $provider->getSupportedCapabilities(),
    );
  }

  /**
   * Default 5m TTL produces a CacheControlEphemeral with no explicit ttl.
   */
  public function testBuildCacheControlDefaultTtlOmitsTtl(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $cc = $this->invoke($provider, 'buildCacheControl', '5m');
    $this->assertInstanceOf(CacheControlEphemeral::class, $cc);
    $this->assertNull($cc->ttl);
  }

  /**
   * Extended 1h TTL produces a CacheControlEphemeral with ttl explicitly set.
   */
  public function testBuildCacheControl1hTtlSetsTtl(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $cc = $this->invoke($provider, 'buildCacheControl', '1h');
    $this->assertInstanceOf(CacheControlEphemeral::class, $cc);
    $this->assertSame('1h', $cc->ttl);
  }

  /**
   * Known effort levels map to the typed enum; case-insensitive.
   */
  public function testResolveEffortMapsKnownLevels(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $this->assertSame(Effort::HIGH, $this->invoke($provider, 'resolveEffort', 'high'));
    $this->assertSame(Effort::XHIGH, $this->invoke($provider, 'resolveEffort', 'XHIGH'));
    $this->assertSame(Effort::MAX, $this->invoke($provider, 'resolveEffort', 'max'));
  }

  /**
   * Empty or unknown effort strings resolve to NULL ("don't emit effort").
   */
  public function testResolveEffortReturnsNullForUnknown(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $this->assertNull($this->invoke($provider, 'resolveEffort', ''));
    $this->assertNull($this->invoke($provider, 'resolveEffort', 'bogus'));
  }

  /**
   * Role=system messages are pulled out and concatenated; others remain.
   */
  public function testExtractSystemPromptPromotesSystemMessages(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $input = new ChatInput([
      new ChatMessage('system', 'You are concise.'),
      new ChatMessage('user', 'Hello'),
    ]);
    [$system, $filtered] = $this->invoke($provider, 'extractSystemPrompt', $input);
    $this->assertSame('You are concise.', $system);
    $this->assertCount(1, $filtered->getMessages());
    $this->assertSame('user', $filtered->getMessages()[0]->getRole());
  }

  /**
   * With no role=system message the system string is empty.
   */
  public function testExtractSystemPromptEmptyWhenNoSystemMessage(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $input = new ChatInput([new ChatMessage('user', 'Hello')]);
    [$system, $filtered] = $this->invoke($provider, 'extractSystemPrompt', $input);
    $this->assertSame('', $system);
    $this->assertCount(1, $filtered->getMessages());
  }

  /**
   * The ChatWithPdf capability filter keeps only PDF models.
   */
  public function testGetConfiguredModelsFiltersByPdfInputCapability(): void {
    $caps_with_pdf = $this->buildCapabilities();
    $caps_no_pdf = ModelCapabilities::with(
      batch: CapabilitySupport::with(supported: TRUE),
      citations: CapabilitySupport::with(supported: TRUE),
      codeExecution: CapabilitySupport::with(supported: TRUE),
      contextManagement: ContextManagementCapability::with(
        clearThinking20251015: CapabilitySupport::with(supported: TRUE),
        clearToolUses20250919: CapabilitySupport::with(supported: TRUE),
        compact20260112: CapabilitySupport::with(supported: TRUE),
        supported: TRUE,
      ),
      effort: EffortCapability::with(
        high: CapabilitySupport::with(supported: TRUE),
        low: CapabilitySupport::with(supported: TRUE),
        max: CapabilitySupport::with(supported: TRUE),
        medium: CapabilitySupport::with(supported: TRUE),
        supported: TRUE,
        xhigh: NULL,
      ),
      imageInput: CapabilitySupport::with(supported: TRUE),
      pdfInput: CapabilitySupport::with(supported: FALSE),
      structuredOutputs: CapabilitySupport::with(supported: TRUE),
      thinking: ThinkingCapability::with(
        supported: TRUE,
        types: ThinkingTypes::with(
          adaptive: CapabilitySupport::with(supported: TRUE),
          enabled: CapabilitySupport::with(supported: TRUE),
        ),
      ),
    );

    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['fetchAvailableModels', 'getModelCapabilities'])
      ->getMock();
    $provider->method('fetchAvailableModels')->willReturn([
      'claude-opus-4-7-20260416' => 'Claude Opus 4.7',
      'claude-3-opus-20240229' => 'Claude 3 Opus (no PDF)',
    ]);
    $provider->method('getModelCapabilities')->willReturnCallback(
      fn (string $id) => $id === 'claude-opus-4-7-20260416' ? $caps_with_pdf : $caps_no_pdf,
    );

    $filtered = $provider->getConfiguredModels(NULL, [AiModelCapability::ChatWithPdf]);

    $this->assertArrayHasKey('claude-opus-4-7-20260416', $filtered);
    $this->assertArrayNotHasKey('claude-3-opus-20240229', $filtered);
  }

  /**
   * Without the pdf capability requested, all models pass through.
   */
  public function testGetConfiguredModelsWithoutPdfCapabilityReturnsAllModels(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['fetchAvailableModels'])
      ->getMock();
    $provider->method('fetchAvailableModels')->willReturn([
      'claude-opus-4-7' => 'Opus 4.7',
      'claude-3-opus' => 'Opus 3',
    ]);

    $all = $provider->getConfiguredModels(NULL, []);

    $this->assertCount(2, $all);
  }

  /**
   * Builds a ChatMessage carrying a PDF DocumentFile.
   */
  protected function buildMessageWithPdf(string $binary = 'PDF-CONTENT', string $text = ''): ChatMessage {
    $msg = new ChatMessage('user', $text);
    $msg->setFile(new DocumentFile($binary, 'application/pdf', 'test.pdf'));
    return $msg;
  }

  /**
   * PDF files produce DocumentBlockParam with Base64PDFSource.
   */
  public function testBuildContentEmitsDocumentBlockParamForPdf(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $input = new ChatInput([$this->buildMessageWithPdf('PDF-BINARY-CONTENT', 'summarize this')]);

    $messages = $this->invoke($provider, 'buildMessageContent', $input);

    $this->assertCount(1, $messages);
    $content = $messages[0]['content'];
    // Expect: TextBlockParam (the text) + DocumentBlockParam (the PDF).
    $this->assertCount(2, $content);

    $doc_block = $content[1];
    $this->assertInstanceOf(DocumentBlockParam::class, $doc_block);
    $this->assertInstanceOf(Base64PDFSource::class, $doc_block->source);
    $this->assertSame(base64_encode('PDF-BINARY-CONTENT'), $doc_block->source->data);
  }

  /**
   * Non-PDF files (audio/video/etc.) are ignored by the PDF branch.
   */
  public function testBuildContentSkipsNonPdfFiles(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $msg = new ChatMessage('user', 'hello');
    // application/octet-stream is not a PDF; should NOT produce a
    // DocumentBlockParam.
    $msg->setFile(new DocumentFile('arbitrary', 'application/octet-stream', 'data.bin'));
    $input = new ChatInput([$msg]);

    $messages = $this->invoke($provider, 'buildMessageContent', $input);

    $content = $messages[0]['content'];
    // Only the text block; the unknown file type is dropped.
    foreach ($content as $block) {
      $this->assertNotInstanceOf(DocumentBlockParam::class, $block);
    }
  }

  /**
   * Multiple PDFs in one message each become a DocumentBlockParam.
   */
  public function testBuildContentHandlesMultiplePdfsPerMessage(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()->onlyMethods([])->getMock();
    $msg = new ChatMessage('user', '');
    $msg->setFile(new DocumentFile('PDF-A', 'application/pdf', 'a.pdf'));
    $msg->setFile(new DocumentFile('PDF-B', 'application/pdf', 'b.pdf'));
    $input = new ChatInput([$msg]);

    $messages = $this->invoke($provider, 'buildMessageContent', $input);

    $content = $messages[0]['content'];
    // Two DocumentBlockParam entries (no text block because text was '').
    $this->assertCount(2, $content);
    $this->assertInstanceOf(DocumentBlockParam::class, $content[0]);
    $this->assertInstanceOf(DocumentBlockParam::class, $content[1]);
  }

  /**
   * Falls back to last-known-good regex when capability API unavailable.
   */
  public function testGetConfiguredModelsFallsBackToRegexWhenCapabilityNull(): void {
    $provider = $this->getMockBuilder(AnthropicProvider::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['fetchAvailableModels', 'getModelCapabilities'])
      ->getMock();
    $provider->method('fetchAvailableModels')->willReturn([
      'claude-opus-4-7' => 'Opus 4.7',
      'claude-1-instant' => 'Legacy Claude 1',
    ]);
    $provider->method('getModelCapabilities')->willReturn(NULL);

    $filtered = $provider->getConfiguredModels(NULL, [AiModelCapability::ChatWithPdf]);

    $this->assertArrayHasKey('claude-opus-4-7', $filtered);
    $this->assertArrayNotHasKey('claude-1-instant', $filtered);
  }

}
