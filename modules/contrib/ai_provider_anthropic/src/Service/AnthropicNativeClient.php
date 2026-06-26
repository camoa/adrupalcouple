<?php

namespace Drupal\ai_provider_anthropic\Service;

use Anthropic\Client;

/**
 * Thin lazy-init wrapper around the Anthropic PHP SDK client.
 *
 * Exists so callers can share a single client instance and defer
 * construction until the API key is known. The underlying SDK client is
 * exposed via getClient() for typed calls such as
 * $client->messages->create() and $client->models->retrieve().
 */
class AnthropicNativeClient {

  /**
   * The Anthropic SDK client.
   *
   * @var \Anthropic\Client|null
   */
  protected ?Client $client = NULL;

  /**
   * Initializes the Anthropic SDK client with an API key.
   *
   * @param string $api_key
   *   The Anthropic API key.
   */
  public function initialize(string $api_key): void {
    $this->client = new Client(apiKey: $api_key);
  }

  /**
   * Whether the SDK client has been initialized.
   *
   * @return bool
   *   TRUE if initialized, FALSE otherwise.
   */
  public function isInitialized(): bool {
    return $this->client !== NULL;
  }

  /**
   * Returns the initialized SDK client for typed API calls.
   *
   * @return \Anthropic\Client
   *   The Anthropic SDK client.
   *
   * @throws \RuntimeException
   *   When the client has not been initialized. Callers should call
   *   initialize() or check isInitialized() before invoking this method.
   */
  public function getClient(): Client {
    if ($this->client === NULL) {
      throw new \RuntimeException('Anthropic native client is not initialized. Call initialize() first.');
    }
    return $this->client;
  }

}
