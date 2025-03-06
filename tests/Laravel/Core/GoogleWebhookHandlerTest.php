<?php

namespace Tests\SubscriptionWebhooks\Laravel\Core;

use PHPUnit\Framework\TestCase;
use SubscriptionWebhooks\Laravel\Core\GoogleWebhookHandler;
use SubscriptionWebhooks\Laravel\Core\WebhookResponse;
use JsonException;

class GoogleWebhookHandlerTest extends TestCase
{
    private GoogleWebhookHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new GoogleWebhookHandler();
    }

    /**
     * Test valid payload validation
     */
    public function testValidatePayloadWithValidData(): void
    {
        $payload = json_encode([
            'subscriptionNotification' => [
                'notificationType' => 1,
                'purchaseToken' => 'test_token',
            ],
        ]);

        $this->assertTrue($this->handler->validatePayload($payload));
    }

    /**
     * Test invalid payload validation (missing required fields)
     */
    public function testValidatePayloadWithInvalidData(): void
    {
        $payload = json_encode([
            'invalidKey' => 'invalidValue',
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
     * Test processing SUBSCRIPTION_RECOVERED notification
     */
    public function testProcessPayloadSubscriptionRecovered(): void
    {
        $payload = json_encode([
            'subscriptionNotification' => [
                'notificationType' => 1,
                'purchaseToken' => 'test_token',
            ],
        ]);

        $response = $this->handler->processPayload($payload);

        $this->assertInstanceOf(WebhookResponse::class, $response);
        $this->assertEquals('subscription_recovered', $response->eventType);
        $this->assertEquals('test_token', $response->subscriptionId);
    }

    /**
     * Test processing SUBSCRIPTION_RENEWED notification
     */
    public function testProcessPayloadSubscriptionRenewed(): void
    {
        $payload = json_encode([
            'subscriptionNotification' => [
                'notificationType' => 2,
                'purchaseToken' => 'test_token',
            ],
        ]);

        $response = $this->handler->processPayload($payload);

        $this->assertInstanceOf(WebhookResponse::class, $response);
        $this->assertEquals('subscription_renewed', $response->eventType);
        $this->assertEquals('test_token', $response->subscriptionId);
    }

    /**
     * Test processing ONE_TIME_PRODUCT_PURCHASED notification
     */
    public function testProcessPayloadOneTimePurchased(): void
    {
        $payload = json_encode([
            'oneTimeProductNotification' => [
                'notificationType' => 1,
                'purchaseToken' => 'test_token',
            ],
        ]);

        $response = $this->handler->processPayload($payload);

        $this->assertInstanceOf(WebhookResponse::class, $response);
        $this->assertEquals('one_time_purchased', $response->eventType);
        $this->assertEquals('test_token', $response->subscriptionId);
    }

    /**
     * Test processing unknown subscription notification type
     */
    public function testProcessPayloadUnknownSubscriptionType(): void
    {
        $payload = json_encode([
            'subscriptionNotification' => [
                'notificationType' => 999, // Unknown type
                'purchaseToken' => 'test_token',
            ],
        ]);

        $response = $this->handler->processPayload($payload);

        $this->assertInstanceOf(WebhookResponse::class, $response);
        $this->assertEquals('unknown_subscription', $response->eventType);
    }

    /**
     * Test processing test notification
     */
    public function testProcessPayloadTestNotification(): void
    {
        $payload = json_encode([
            'testNotification' => [
                'message' => 'This is a test notification',
            ],
        ]);

        $response = $this->handler->processPayload($payload);

        $this->assertInstanceOf(WebhookResponse::class, $response);
        $this->assertEquals('test_notification', $response->eventType);
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