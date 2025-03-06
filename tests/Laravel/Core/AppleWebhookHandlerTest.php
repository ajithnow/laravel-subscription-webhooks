<?php

namespace Tests\SubscriptionWebhooks\Laravel\Core;

use PHPUnit\Framework\TestCase;
use SubscriptionWebhooks\Laravel\Core\AppleWebhookHandler;
use SubscriptionWebhooks\Laravel\Core\WebhookResponse;
use JsonException;

class AppleWebhookHandlerTest extends TestCase
{
    private AppleWebhookHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new AppleWebhookHandler();
    }

    /**
     * Test valid payload validation
     */
    public function testValidatePayloadWithValidData(): void
    {
        $payload = json_encode([
            'notificationType' => 'INITIAL_BUY',
            'originalTransactionId' => '12345',
        ]);

        $this->assertTrue($this->handler->validatePayload($payload));
    }

    /**
     * Test invalid payload validation (missing notificationType)
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
        $payload = json_encode([
            'notificationType' => 'INITIAL_BUY',
            'originalTransactionId' => '12345',
        ]);

        $response = $this->handler->processPayload($payload);

        $this->assertInstanceOf(WebhookResponse::class, $response);
        $this->assertEquals('initial_purchase', $response->eventType);
        $this->assertEquals('12345', $response->subscriptionId);
    }

    /**
     * Test processing RENEWAL notification
     */
    public function testProcessPayloadRenewal(): void
    {
        $payload = json_encode([
            'notificationType' => 'RENEWAL',
            'originalTransactionId' => '12345',
        ]);

        $response = $this->handler->processPayload($payload);

        $this->assertInstanceOf(WebhookResponse::class, $response);
        $this->assertEquals('renewal', $response->eventType);
        $this->assertEquals('12345', $response->subscriptionId);
    }

    /**
     * Test processing unknown notification type
     */
    public function testProcessPayloadUnknownType(): void
    {
        $payload = json_encode([
            'notificationType' => 'UNKNOWN_TYPE',
            'originalTransactionId' => '12345',
        ]);

        $response = $this->handler->processPayload($payload);

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