<?php

namespace SubscriptionWebhooks\Laravel\Core;

/**
 * Webhook Response Object
 */
class WebhookResponse
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_IGNORED = 'ignored';

    public function __construct(
        public string $status,
        public string $eventType,
        public ?string $subscriptionId = null,
        public ?array $additionalData = null
    ) {}

    /**
     * Convert response to array for easier handling
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'eventType' => $this->eventType,
            'subscriptionId' => $this->subscriptionId,
            'additionalData' => $this->additionalData
        ];
    }
}