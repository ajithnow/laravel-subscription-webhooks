<?php

namespace SubscriptionWebhooks\Laravel\Core;

use Exception;

/**
 * Webhook Processor - Facade for handling webhooks
 */
class WebhookProcessor
{
    private array $handlers = [];

    /**
     * Register a webhook handler
     */
    public function registerHandler(AbstractWebhookHandler $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * Process incoming webhook
     * 
     * @throws Exception If no suitable handler is found
     */
    public function process(string $payload): WebhookResponse
    {
        foreach ($this->handlers as $handler) {
            if ($handler->validatePayload($payload)) {
                return $handler->processPayload($payload);
            }
        }

        throw new Exception('No suitable webhook handler found');
    }
}