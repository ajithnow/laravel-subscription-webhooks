<?php

namespace SubscriptionWebhooks\Laravel\Core;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Base abstract class for handling subscription webhooks
 */
abstract class AbstractWebhookHandler
{
    protected LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Validate the incoming webhook payload
     * 
     * @param string $payload Raw webhook payload
     * @return bool Whether the payload is valid
     */
    abstract public function validatePayload(string $payload): bool;

    /**
     * Process the webhook payload
     * 
     * @param string $payload Raw webhook payload
     * @return WebhookResponse Processing result
     * @throws Exception If payload processing fails
     */
    abstract public function processPayload(string $payload): WebhookResponse;
}