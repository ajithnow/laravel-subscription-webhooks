<?php

namespace Tests\SubscriptionWebhooks\Laravel\Core;

use PHPUnit\Framework\TestCase;
use SubscriptionWebhooks\Laravel\Core\AppleWebhookHandler;
use SubscriptionWebhooks\Laravel\Core\WebhookResponse;
use JsonException;
use Psr\Log\LoggerInterface;
use Mockery;

class AppleWebhookHandlerTest extends TestCase
{
    private AppleWebhookHandler $handler;
    private LoggerInterface $mockLogger;

    public function setUp(): void
    {
        parent::setUp();

        // Create the handler (partial mock if needed)
        $this->handler = Mockery::mock(AppleWebhookHandler::class)
            ->makePartial();

        // Mock verifyAppleSignature to return true for tests
        $this->handler->shouldReceive('verifyAppleSignature')
            ->andReturn(true);

        // Set up logger properly using the setter
        $logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('warning')->andReturn(null);
        $logger->shouldReceive('error')->andReturn(null);
        $this->handler->setLogger($logger);
    }

    public function testValidatePayloadWithValidData()
    {
        // Create a valid signed payload
        $validPayload = json_encode([
            'signedPayload' => 'header.eyJub3RpZmljYXRpb25UeXBlIjoiSU5JVElBTF9CVVkifQ.signature'
        ]);

        $result = $this->handler->validatePayload($validPayload);

        $this->assertTrue($result);
        $this->assertEquals(
            ['notificationType' => 'INITIAL_BUY'],
            $this->handler->getDecodedData()
        );
    }
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test invalid payload validation (missing signedPayload)
     */
    public function testValidatePayloadWithInvalidData(): void
    {
        $payload = json_encode([
            'originalTransactionId' => '12345',
        ]);

        $this->assertFalse($this->handler->validatePayload($payload));
    }

    /**
     * Test invalid JSON payload
     */
    public function testValidatePayloadWithInvalidJson(): void
    {
        $payload = 'invalid_json';

        $this->assertFalse($this->handler->validatePayload($payload));
    }

    /**
     * Test processing INITIAL_BUY notification
     */
    public function testProcessPayloadInitialBuy(): void
    {
        // Create a mock handler for processPayload tests
        $mockHandler = Mockery::mock(AppleWebhookHandler::class)->makePartial();
        $mockHandler->shouldReceive('logUnknownEvent')->andReturnUsing(function ($type, $payload) {
            return new WebhookResponse(
                status: WebhookResponse::STATUS_IGNORED,
                eventType: 'unknown',
                subscriptionId: json_decode($payload, true)['originalTransactionId'] ?? null,
                additionalData: ['raw_type' => $type]
            );
        });

        $payload = json_encode([
            'notificationType' => 'INITIAL_BUY',
            'originalTransactionId' => '12345',
        ]);

        $response = $mockHandler->processPayload($payload);

        $this->assertInstanceOf(WebhookResponse::class, $response);
        $this->assertEquals('initial_purchase', $response->eventType);
        $this->assertEquals('12345', $response->subscriptionId);
    }

    /**
     * Test processing RENEWAL notification
     */
    public function testProcessPayloadRenewal(): void
    {
        // Create a mock handler for processPayload tests
        $mockHandler = Mockery::mock(AppleWebhookHandler::class)->makePartial();
        $mockHandler->shouldReceive('logUnknownEvent')->andReturnUsing(function ($type, $payload) {
            return new WebhookResponse(
                status: WebhookResponse::STATUS_IGNORED,
                eventType: 'unknown',
                subscriptionId: json_decode($payload, true)['originalTransactionId'] ?? null,
                additionalData: ['raw_type' => $type]
            );
        });

        $payload = json_encode([
            'notificationType' => 'RENEWAL',
            'originalTransactionId' => '12345',
        ]);

        $response = $mockHandler->processPayload($payload);

        $this->assertInstanceOf(WebhookResponse::class, $response);
        $this->assertEquals('renewal', $response->eventType);
        $this->assertEquals('12345', $response->subscriptionId);
    }

    /**
     * Test processing unknown notification type
     */
    public function testProcessPayloadUnknownType(): void
    {
        // Create a mock handler for processPayload tests
        $mockHandler = Mockery::mock(AppleWebhookHandler::class)->makePartial();
        $mockHandler->shouldReceive('logUnknownEvent')->andReturnUsing(function ($type, $payload) {
            return new WebhookResponse(
                status: WebhookResponse::STATUS_IGNORED,
                eventType: 'unknown',
                subscriptionId: json_decode($payload, true)['originalTransactionId'] ?? null,
                additionalData: ['raw_type' => $type]
            );
        });

        $payload = json_encode([
            'notificationType' => 'UNKNOWN_TYPE',
            'originalTransactionId' => '12345',
        ]);

        $response = $mockHandler->processPayload($payload);

        $this->assertInstanceOf(WebhookResponse::class, $response);
        $this->assertEquals('unknown', $response->eventType);
        $this->assertEquals('12345', $response->subscriptionId);
    }

    /**
     * Test processing invalid JSON payload
     */
    public function testProcessPayloadInvalidJson(): void
    {
        $payload = 'invalid_json';

        $this->expectException(JsonException::class);
        $this->handler->processPayload($payload);
    }
}
