<?php

namespace Drupal\ai_provider_anthropic\OperationType\Chat;

use Anthropic\Messages\InputJSONDelta;
use Anthropic\Messages\RawContentBlockDeltaEvent;
use Anthropic\Messages\RawContentBlockStartEvent;
use Anthropic\Messages\RawMessageDeltaEvent;
use Anthropic\Messages\TextDelta;
use Anthropic\Messages\ThinkingDelta;
use Anthropic\Messages\ToolUseBlock;
use Drupal\ai\OperationType\Chat\StreamedChatMessageIterator;

/**
 * Streamed chat message iterator for Anthropic native API.
 *
 * Wraps the SDK's typed `BaseStream` of Raw* events and emits
 * `StreamedChatMessage` chunks the AI module's consumers expect. Dispatches
 * on `instanceof` for event and delta types — no string sniffing.
 *
 * Events consumed:
 *  - RawContentBlockStartEvent: new block opened; track block type + index.
 *  - RawContentBlockDeltaEvent: text/thinking/json/citation delta.
 *  - RawMessageDeltaEvent: message-level stop_reason + final usage.
 *
 * Tool use blocks come in two delta flavours:
 *  - RawContentBlockStartEvent with a ToolUseBlock gives `id`, `name`.
 *  - Subsequent RawContentBlockDeltaEvent with InputJSONDelta streams the
 *    arguments JSON incrementally — accumulated and decoded at block-stop.
 */
class AnthropicStreamedChatMessageIterator extends StreamedChatMessageIterator {

  /**
   * Partial tool-use state keyed by block index.
   *
   * @var array<int, array{id: string, name: string, input: string}>
   */
  protected array $pendingTools = [];

  /**
   * Whether the SDK stream has been fully consumed.
   *
   * The Anthropic SDK's BaseStream reads a one-shot PSR-7 HTTP body.
   * Re-iterating throws "Cannot traverse an already closed generator".
   * AI module consumers like ReplayedChatMessageIterator may invoke
   * getIterator() more than once during a single response cycle; on
   * subsequent calls we yield nothing rather than re-reading (which
   * would error) or replaying cached chunks (which SSE accumulators
   * would double-emit).
   */
  protected bool $consumed = FALSE;

  /**
   * {@inheritdoc}
   */
  public function doIterate(): \Generator {
    if ($this->consumed) {
      // Stream already drained; nothing to yield. The initial iteration
      // produced all the SSE chunks; re-emitting would duplicate output.
      return;
    }

    try {
      foreach ($this->iterator as $event) {
        yield from $this->emitChunksForEvent($event);
      }
    }
    finally {
      $this->consumed = TRUE;
    }
  }

  /**
   * Translates one SDK Raw* event into zero-or-more StreamedChatMessages.
   *
   * Split out of doIterate so the buffering wrapper stays readable.
   */
  protected function emitChunksForEvent(object $event): \Generator {
    if ($event instanceof RawContentBlockStartEvent) {
      $block = $event->contentBlock;
      if ($block instanceof ToolUseBlock) {
        $this->pendingTools[$event->index] = [
          'id' => $block->id,
          'name' => $block->name,
          'input' => '',
        ];
      }
      // TextBlock / ThinkingBlock starts don't need state — deltas carry
      // their content and the index distinguishes concurrent blocks.
      return;
    }

    if ($event instanceof RawContentBlockDeltaEvent) {
      $delta = $event->delta;

      if ($delta instanceof TextDelta) {
        yield $this->createStreamedChatMessage('assistant', $delta->text, []);
        return;
      }

      if ($delta instanceof ThinkingDelta) {
        yield $this->createStreamedChatMessage(
          'assistant', '', ['thinking' => $delta->thinking],
        );
        return;
      }

      if ($delta instanceof InputJSONDelta) {
        // Accumulate partial tool-call arguments. Don't yield per-chunk;
        // a tool call is only meaningful once the JSON is complete at
        // block stop.
        if (isset($this->pendingTools[$event->index])) {
          $this->pendingTools[$event->index]['input'] .= $delta->partialJSON;
        }
        return;
      }

      // Remaining delta types (SignatureDelta | CitationsDelta) are consumed
      // and dropped — not currently surfaced to consumers.
      return;
    }

    if ($event instanceof RawMessageDeltaEvent) {
      if ($event->delta->stopReason !== NULL) {
        $this->setFinishReason((string) $event->delta->stopReason);
      }
      // The final usage event doesn't carry any text — yield an empty
      // chunk that carries the token counts. AI module consumers read
      // the last-seen chunk's usage when reconstructing the output.
      $final = $this->createStreamedChatMessage('assistant', '', []);
      $final->setInputTokenUsage($event->usage->inputTokens ?? 0);
      $final->setOutputTokenUsage($event->usage->outputTokens ?? 0);
      $final->setTotalTokenUsage(
        ($event->usage->inputTokens ?? 0) + ($event->usage->outputTokens ?? 0),
      );
      if ($event->usage->cacheReadInputTokens !== NULL) {
        $final->setCachedTokenUsage($event->usage->cacheReadInputTokens);
      }
      yield $final;
      return;
    }

    // RawMessageStartEvent / RawContentBlockStopEvent / RawMessageStopEvent
    // don't yield user-visible chunks; metadata on start/stop could be
    // wired in a later iteration.
  }

}
