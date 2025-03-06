<?php

namespace Tests\Laravel\Core;

use PHPUnit\Framework\TestCase;
use SubscriptionWebhooks\Laravel\Core\WebhookProcessor;
use SubscriptionWebhooks\Laravel\Core\AbstractWebhookHandler;
use SubscriptionWebhooks\Laravel\Core\WebhookResponse;
use Exception;

class WebhookProcessorTest extends TestCase
{
    private WebhookProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new WebhookProcessor();
    }

    public function testRegisterHandler(): void
    {
        /** @var AbstractWebhookHandler|\PHPUnit\Framework\MockObject\MockObject $handler */
        $handler = $this->createMock(AbstractWebhookHandler::class);
        $this->processor->registerHandler($handler);

        $this->assertCount(1, $this->getPrivateProperty($this->processor, 'handlers'));
    }

    public function testProcessWithValidHandler(): void
    {
        $payload = '{"notificationType": "INITIAL_BUY"}';

        /** @var AbstractWebhookHandler|\PHPUnit\Framework\MockObject\MockObject $handler */
        $handler = $this->createMock(AbstractWebhookHandler::class);
        $handler->method('validatePayload')
                ->with($payload)
                ->willReturn(true);
        $handler->method('processPayload')
                ->with($payload)
                ->willReturn(new WebhookResponse(
                    status: WebhookResponse::STATUS_SUCCESS,
                    eventType: 'initial_purchase'
                ));

        $this->processor->registerHandler($handler);

        $response = $this->processor->process($payload);

        $this->assertInstanceOf(WebhookResponse::class, $response);
        $this->assertEquals('initial_purchase', $response->eventType);
    }

    public function testProcessWithNoSuitableHandler(): void
    {
        $payload = '{"notificationType": "INITIAL_BUY"}';

        /** @var AbstractWebhookHandler|\PHPUnit\Framework\MockObject\MockObject $handler */
        $handler = $this->createMock(AbstractWebhookHandler::class);
        $handler->method('validatePayload')
                ->with($payload)
                ->willReturn(false);

        $this->processor->registerHandler($handler);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No suitable webhook handler found');
        $this->processor->process($payload);
    }

    private function getPrivateProperty(object $object, string $propertyName)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}