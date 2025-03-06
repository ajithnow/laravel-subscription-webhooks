<?php

namespace SubscriptionWebhooks\Laravel;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionWebhookReceived
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $platform,
        public array $webhookData
    ) {}
}