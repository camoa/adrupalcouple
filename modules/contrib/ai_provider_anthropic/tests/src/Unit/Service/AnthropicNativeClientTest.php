<?php

namespace Drupal\Tests\ai_provider_anthropic\Unit\Service;

use Anthropic\Client;
use Drupal\ai_provider_anthropic\Service\AnthropicNativeClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the AnthropicNativeClient lazy-init wrapper.
 */
#[CoversClass(AnthropicNativeClient::class)]
#[Group('ai_provider_anthropic')]
class AnthropicNativeClientTest extends TestCase {

  /**
   * Is initialized returns false by default.
   */
  public function testIsInitializedReturnsFalseByDefault(): void {
    $client = new AnthropicNativeClient();
    $this->assertFalse($client->isInitialized());
  }

  /**
   * Initialize marks client as initialized.
   */
  public function testInitializeMarksClientAsInitialized(): void {
    $client = new AnthropicNativeClient();
    $client->initialize('test-api-key');
    $this->assertTrue($client->isInitialized());
  }

  /**
   * Get client throws when not initialized.
   */
  public function testGetClientThrowsWhenNotInitialized(): void {
    $client = new AnthropicNativeClient();
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Anthropic native client is not initialized');
    $client->getClient();
  }

  /**
   * Get client returns sdk client after init.
   */
  public function testGetClientReturnsSdkClientAfterInit(): void {
    $client = new AnthropicNativeClient();
    $client->initialize('test-api-key');
    $this->assertInstanceOf(Client::class, $client->getClient());
  }

}
