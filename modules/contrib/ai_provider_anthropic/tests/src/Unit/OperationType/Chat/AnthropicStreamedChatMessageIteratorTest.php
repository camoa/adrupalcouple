<?php

namespace Drupal\Tests\ai_provider_anthropic\Unit\OperationType\Chat;

use Anthropic\Messages\InputJSONDelta;
use Anthropic\Messages\MessageDeltaUsage;
use Anthropic\Messages\RawContentBlockDeltaEvent;
use Anthropic\Messages\RawContentBlockStartEvent;
use Anthropic\Messages\RawMessageDeltaEvent;
use Anthropic\Messages\RawMessageDeltaEvent\Delta;
use Anthropic\Messages\TextDelta;
use Anthropic\Messages\ThinkingDelta;
use Anthropic\Messages\ToolUseBlock;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\ai\Service\HostnameFilter;
use Drupal\ai_provider_anthropic\OperationType\Chat\AnthropicStreamedChatMessageIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the native Anthropic streaming iterator.
 *
 * Covers the typed-event dispatch and — critically — the one-shot
 * re-entrancy guard added in #3572402 after a production crash
 * ("Cannot traverse an already closed generator").
 */
#[CoversClass(AnthropicStreamedChatMessageIterator::class)]
#[Group('ai_provider_anthropic')]
class AnthropicStreamedChatMessageIteratorTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // createStreamedChatMessage() flushes through the AI module's hostname
    // filter service. Stub it so unit tests need no Drupal bootstrap.
    $filter = $this->createMock(HostnameFilter::class);
    $filter->method('filterText')->willReturnArgument(0);
    $container = new ContainerBuilder();
    $container->set('ai.hostname_filter_service', $filter);
    \Drupal::setContainer($container);
  }

  /**
   * Builds the iterator over a fixed list of SDK events.
   */
  protected function iteratorOver(array $events): AnthropicStreamedChatMessageIterator {
    return new AnthropicStreamedChatMessageIterator(new \ArrayIterator($events));
  }

  /**
   * A RawContentBlockDeltaEvent wrapping a text delta.
   */
  protected function textDeltaEvent(string $text, int $index = 0): RawContentBlockDeltaEvent {
    return RawContentBlockDeltaEvent::with(
      delta: TextDelta::with(text: $text),
      index: $index,
    );
  }

  /**
   * A RawMessageDeltaEvent carrying stop reason + usage.
   */
  protected function messageDeltaEvent(?string $stop_reason, int $input, int $output, ?int $cache_read = NULL): RawMessageDeltaEvent {
    return RawMessageDeltaEvent::with(
      delta: Delta::with(
        container: NULL,
        stopDetails: NULL,
        stopReason: $stop_reason,
        stopSequence: NULL,
      ),
      usage: MessageDeltaUsage::with(
        cacheCreationInputTokens: NULL,
        cacheReadInputTokens: $cache_read,
        inputTokens: $input,
        outputTokens: $output,
        outputTokensDetails: NULL,
        serverToolUse: NULL,
      ),
    );
  }

  /**
   * Second traversal yields nothing — the SDK BaseStream is one-shot.
   */
  public function testReEntrancyGuardYieldsNothingOnSecondTraversal(): void {
    $iterator = $this->iteratorOver([
      $this->textDeltaEvent('Hello'),
      $this->messageDeltaEvent('end_turn', 10, 5),
    ]);

    $first = iterator_to_array($iterator->doIterate(), FALSE);
    $second = iterator_to_array($iterator->doIterate(), FALSE);

    $this->assertNotEmpty($first, 'First traversal drains the stream.');
    $this->assertSame([], $second, 'Second traversal yields nothing — no re-read, no duplicate emit.');
  }

  /**
   * The consumed guard trips even when the first traversal is empty.
   */
  public function testReEntrancyGuardTripsOnEmptyStream(): void {
    $iterator = $this->iteratorOver([]);

    $first = iterator_to_array($iterator->doIterate(), FALSE);
    $second = iterator_to_array($iterator->doIterate(), FALSE);

    $this->assertSame([], $first);
    $this->assertSame([], $second);
  }

  /**
   * A text delta yields one assistant chunk.
   */
  public function testTextDeltaYieldsAssistantChunk(): void {
    $iterator = $this->iteratorOver([$this->textDeltaEvent('Hello world')]);
    $chunks = iterator_to_array($iterator->doIterate(), FALSE);

    $this->assertCount(1, $chunks);
    $this->assertSame('assistant', $chunks[0]->getRole());
  }

  /**
   * A thinking delta surfaces the thinking text in chunk metadata.
   */
  public function testThinkingDeltaYieldsThinkingMetadata(): void {
    $event = RawContentBlockDeltaEvent::with(
      delta: ThinkingDelta::with(thinking: 'Step-by-step reasoning.'),
      index: 0,
    );
    $iterator = $this->iteratorOver([$event]);
    $chunks = iterator_to_array($iterator->doIterate(), FALSE);

    $this->assertCount(1, $chunks);
    $this->assertSame('Step-by-step reasoning.', $chunks[0]->getMetadata()['thinking'] ?? NULL);
  }

  /**
   * The final message-delta event yields a chunk carrying token usage.
   */
  public function testMessageDeltaYieldsFinalUsageChunk(): void {
    $iterator = $this->iteratorOver([
      $this->messageDeltaEvent('end_turn', 120, 45, 80),
    ]);
    $chunks = iterator_to_array($iterator->doIterate(), FALSE);

    $this->assertCount(1, $chunks);
    $final = $chunks[0];
    $this->assertSame(120, $final->getInputTokenUsage());
    $this->assertSame(45, $final->getOutputTokenUsage());
    $this->assertSame(80, $final->getCachedTokenUsage());
  }

  /**
   * A tool-use block start is tracked in pendingTools; InputJSONDelta accrues.
   */
  public function testToolUseBlockStartTracksPendingToolAndAccumulatesJson(): void {
    $start = RawContentBlockStartEvent::with(
      contentBlock: ToolUseBlock::with(
        id: 'toolu_123',
        input: [],
        name: 'get_weather',
      ),
      index: 0,
    );
    $json_a = RawContentBlockDeltaEvent::with(
      delta: InputJSONDelta::with(partialJSON: '{"city":'),
      index: 0,
    );
    $json_b = RawContentBlockDeltaEvent::with(
      delta: InputJSONDelta::with(partialJSON: '"Athens"}'),
      index: 0,
    );

    $iterator = $this->iteratorOver([$start, $json_a, $json_b]);
    iterator_to_array($iterator->doIterate(), FALSE);

    $pending = (new \ReflectionProperty($iterator, 'pendingTools'))->getValue($iterator);
    $this->assertArrayHasKey(0, $pending);
    $this->assertSame('toolu_123', $pending[0]['id']);
    $this->assertSame('get_weather', $pending[0]['name']);
    $this->assertSame('{"city":"Athens"}', $pending[0]['input']);
  }

  /**
   * Tool-use blocks and text deltas do not emit user-visible chunks.
   *
   * RawContentBlockStartEvent and InputJSONDelta are state-only; only the
   * text delta produces a chunk.
   */
  public function testToolEventsDoNotYieldChunks(): void {
    $start = RawContentBlockStartEvent::with(
      contentBlock: ToolUseBlock::with(id: 't', input: [], name: 'n'),
      index: 0,
    );
    $json = RawContentBlockDeltaEvent::with(
      delta: InputJSONDelta::with(partialJSON: '{}'),
      index: 0,
    );
    $iterator = $this->iteratorOver([$start, $json]);
    $chunks = iterator_to_array($iterator->doIterate(), FALSE);

    $this->assertSame([], $chunks, 'Tool start + JSON deltas accumulate state, yield nothing.');
  }

}
