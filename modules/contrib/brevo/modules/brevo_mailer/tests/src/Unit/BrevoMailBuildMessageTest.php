<?php

namespace Drupal\Tests\brevo_mailer\Unit;

use Drupal\brevo_mailer\BrevoMailerHandlerInterface;
use Drupal\brevo_mailer\Plugin\Mail\BrevoMail;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the BrevoMail::buildMessage method.
 *
 * @coversDefaultClass \Drupal\brevo_mailer\Plugin\Mail\BrevoMail
 *
 * @group brevo_mailer
 */
class BrevoMailBuildMessageTest extends UnitTestCase {

  /**
   * The BrevoMail plugin instance.
   *
   * @var \Drupal\brevo_mailer\Plugin\Mail\BrevoMail
   */
  protected $brevoMail;

  /**
   * Mock config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config = $this->createMock(ImmutableConfig::class);
    $this->config->method('get')
      ->willReturnMap([
        ['debug_mode', FALSE],
        ['test_mode', FALSE],
        ['format_filter', ''],
        ['use_queue', FALSE],
        ['use_theme', FALSE],
      ]);

    $logger = $this->createMock(LoggerInterface::class);
    $renderer = $this->createMock(RendererInterface::class);
    $queueFactory = $this->createMock(QueueFactory::class);
    $brevoMailerHandler = $this->createMock(BrevoMailerHandlerInterface::class);

    $this->brevoMail = new BrevoMail(
      $this->config,
      $logger,
      $renderer,
      $queueFactory,
      $brevoMailerHandler
    );
  }

  /**
   * Tests buildMessage with a single recipient.
   *
   * @covers ::buildMessage
   */
  public function testBuildMessageSingleRecipient(): void {
    $message = [
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
      'body' => 'Test body content',
      'headers' => [
        'From' => 'sender@example.com',
      ],
    ];

    $result = $this->invokeMethod($this->brevoMail, 'buildMessage', [$message]);

    $this->assertIsArray($result['to']);
    $this->assertCount(1, $result['to']);
    $this->assertEquals('test@example.com', $result['to'][0]['email']);
  }

  /**
   * Tests buildMessage with multiple recipients separated by comma.
   *
   * @covers ::buildMessage
   */
  public function testBuildMessageMultipleRecipients(): void {
    $message = [
      'to' => 'test1@example.com,test2@example.com,test3@example.com',
      'subject' => 'Test Subject',
      'body' => 'Test body content',
      'headers' => [
        'From' => 'sender@example.com',
      ],
    ];

    $result = $this->invokeMethod($this->brevoMail, 'buildMessage', [$message]);

    $this->assertIsArray($result['to']);
    $this->assertCount(3, $result['to']);
    $this->assertEquals('test1@example.com', $result['to'][0]['email']);
    $this->assertEquals('test2@example.com', $result['to'][1]['email']);
    $this->assertEquals('test3@example.com', $result['to'][2]['email']);
  }

  /**
   * Tests buildMessage with multiple recipients separated by comma and space.
   *
   * @covers ::buildMessage
   */
  public function testBuildMessageMultipleRecipientsWithSpaces(): void {
    $message = [
      'to' => 'test1@example.com, test2@example.com, test3@example.com',
      'subject' => 'Test Subject',
      'body' => 'Test body content',
      'headers' => [
        'From' => 'sender@example.com',
      ],
    ];

    $result = $this->invokeMethod($this->brevoMail, 'buildMessage', [$message]);

    $this->assertIsArray($result['to']);
    $this->assertCount(3, $result['to']);
    $this->assertEquals('test1@example.com', $result['to'][0]['email']);
    $this->assertEquals('test2@example.com', $result['to'][1]['email']);
    $this->assertEquals('test3@example.com', $result['to'][2]['email']);
  }

  /**
   * Tests buildMessage with named recipients.
   *
   * @covers ::buildMessage
   */
  public function testBuildMessageNamedRecipients(): void {
    $message = [
      'to' => 'John Doe <john@example.com>,Jane Doe <jane@example.com>',
      'subject' => 'Test Subject',
      'body' => 'Test body content',
      'headers' => [
        'From' => 'sender@example.com',
      ],
    ];

    $result = $this->invokeMethod($this->brevoMail, 'buildMessage', [$message]);

    $this->assertIsArray($result['to']);
    $this->assertCount(2, $result['to']);
    $this->assertEquals('john@example.com', $result['to'][0]['email']);
    $this->assertEquals('John Doe', $result['to'][0]['name']);
    $this->assertEquals('jane@example.com', $result['to'][1]['email']);
    $this->assertEquals('Jane Doe', $result['to'][1]['name']);
  }

  /**
   * Tests buildMessage base64-encodes filecontent attachments.
   *
   * When attachments provide 'filecontent' (raw binary) instead of
   * 'filepath', the content must be base64-encoded for the Brevo API.
   *
   * @covers ::buildMessage
   */
  public function testBuildMessageFilecontentAttachmentIsBase64Encoded(): void {
    $raw_content = 'This is raw binary file content';
    $message = [
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
      'body' => 'Test body content',
      'headers' => [
        'From' => 'sender@example.com',
      ],
      'params' => [
        'attachments' => [
          [
            'filecontent' => $raw_content,
            'filename' => 'test.txt',
            'filemime' => 'text/plain',
          ],
        ],
      ],
    ];

    $result = $this->invokeMethod($this->brevoMail, 'buildMessage', [$message]);

    $this->assertArrayHasKey('attachment', $result);
    $this->assertCount(1, $result['attachment']);
    $this->assertEquals('test.txt', $result['attachment'][0]['name']);
    $this->assertEquals(
      base64_encode($raw_content),
      $result['attachment'][0]['content']
    );
    // Verify it is valid base64 by decoding.
    $this->assertEquals(
      $raw_content,
      base64_decode($result['attachment'][0]['content'])
    );
  }

  /**
   * Tests buildMessage base64-encodes filepath attachments.
   *
   * @covers ::buildMessage
   */
  public function testBuildMessageFilepathAttachmentIsBase64Encoded(): void {
    // Create a temporary file for the test.
    $tmp_file = tempnam(sys_get_temp_dir(), 'brevo_test_');
    file_put_contents($tmp_file, 'File content from disk');

    $message = [
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
      'body' => 'Test body content',
      'headers' => [
        'From' => 'sender@example.com',
      ],
      'params' => [
        'attachments' => [
          [
            'filepath' => $tmp_file,
          ],
        ],
      ],
    ];

    $result = $this->invokeMethod($this->brevoMail, 'buildMessage', [$message]);

    $this->assertArrayHasKey('attachment', $result);
    $this->assertCount(1, $result['attachment']);
    $this->assertEquals(
      base64_encode('File content from disk'),
      $result['attachment'][0]['content']
    );

    // Clean up.
    unlink($tmp_file);
  }

  /**
   * Tests buildMessage skips attachments with missing filepath.
   *
   * @covers ::buildMessage
   */
  public function testBuildMessageSkipsInvalidAttachments(): void {
    $message = [
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
      'body' => 'Test body content',
      'headers' => [
        'From' => 'sender@example.com',
      ],
      'params' => [
        'attachments' => [
          [
            'filepath' => '/nonexistent/path/file.txt',
          ],
          [
            'filecontent' => '',
            'filename' => 'empty.txt',
          ],
        ],
      ],
    ];

    $result = $this->invokeMethod($this->brevoMail, 'buildMessage', [$message]);

    $this->assertArrayNotHasKey('attachment', $result);
  }

  /**
   * Tests that standard MIME headers are excluded from Brevo headers.
   *
   * Standard headers like Content-Type, MIME-Version, From, etc. are already
   * handled by the structured Brevo message format. Passing them in the
   * headers array can interfere with MIME construction.
   *
   * @covers ::buildMessage
   */
  public function testBuildMessageExcludesStandardHeaders(): void {
    $message = [
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
      'body' => 'Test body content',
      'headers' => [
        'From' => 'sender@example.com',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'MIME-Version' => '1.0',
        'Reply-To' => 'reply@example.com',
        'Cc' => 'cc@example.com',
        'Bcc' => 'bcc@example.com',
        'To' => 'test@example.com',
        'Subject' => 'Test Subject',
      ],
    ];

    $result = $this->invokeMethod($this->brevoMail, 'buildMessage', [$message]);

    // None of the standard headers should be in the Brevo headers array.
    $this->assertArrayNotHasKey('headers', $result);
  }

  /**
   * Tests that custom headers are passed through to Brevo.
   *
   * Custom headers like X-Mailer, List-Unsubscribe, Precedence, etc.
   * should be included in the Brevo headers array.
   *
   * @covers ::buildMessage
   */
  public function testBuildMessageIncludesCustomHeaders(): void {
    $message = [
      'to' => 'test@example.com',
      'subject' => 'Test Subject',
      'body' => 'Test body content',
      'headers' => [
        'From' => 'sender@example.com',
        'Content-Type' => 'text/html; charset=UTF-8',
        'X-Mailer' => 'Drupal',
        'X-Custom-Header' => 'custom-value',
        'List-Unsubscribe' => '<mailto:unsub@example.com>',
        'Precedence' => 'bulk',
      ],
    ];

    $result = $this->invokeMethod($this->brevoMail, 'buildMessage', [$message]);

    $this->assertArrayHasKey('headers', $result);
    // Custom X-* headers should be included.
    $this->assertEquals('Drupal', $result['headers']['X-Mailer']);
    $this->assertEquals('custom-value', $result['headers']['X-Custom-Header']);
    // Non-standard but legitimate headers should also be included.
    $this->assertEquals('<mailto:unsub@example.com>', $result['headers']['List-Unsubscribe']);
    $this->assertEquals('bulk', $result['headers']['Precedence']);
    // Standard headers should not be present.
    $this->assertArrayNotHasKey('From', $result['headers']);
    $this->assertArrayNotHasKey('Content-Type', $result['headers']);
  }

  /**
   * Invokes a protected or private method.
   *
   * @param object $object
   *   The object instance.
   * @param string $methodName
   *   The method name.
   * @param array $parameters
   *   The parameters to pass.
   *
   * @return mixed
   *   The return value of the method.
   */
  protected function invokeMethod($object, $methodName, array $parameters = []) {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(TRUE);
    return $method->invokeArgs($object, $parameters);
  }

}
