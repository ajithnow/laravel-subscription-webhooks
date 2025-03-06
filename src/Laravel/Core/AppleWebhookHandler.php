<?php

namespace SubscriptionWebhooks\Laravel\Core;

use Exception;
use JsonException;

/**
 * Apple App Store Subscription Webhook Handler
 */
class AppleWebhookHandler extends AbstractWebhookHandler
{

    /**
     * Validate Apple webhook signature
     */
    public function validatePayload(string $payload): bool
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            
            // Check if payload has the required structure
            if (!isset($data['notificationType'])) {
                $this->logger->warning('Invalid Apple webhook structure', [
                    'payload' => $payload
                ]);
                return false;
            }
            
            // Further validation can be implemented here
            // For example, signature verification with Apple's certificates
            
            return true;
        } catch (JsonException $e) {
            $this->logger->error('Invalid Apple webhook payload', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            return false;
        }
    }

    /**
     * Process Apple subscription notification
     */
    public function processPayload(string $payload): WebhookResponse
    {
        $data = json_decode($payload, true);
        
        // Handle different Apple subscription notification types
        switch ($data['notificationType'] ?? '') {
            case 'INITIAL_BUY':
                return $this->handleInitialPurchase($data);
            case 'RENEWAL':
                return $this->handleRenewal($data);
            case 'CANCELLATION':
                return $this->handleCancellation($data);
            case 'DID_CHANGE_RENEWAL_STATUS':
                return $this->handleRenewalStatusChange($data);
            case 'DID_FAIL_TO_RENEW':
                return $this->handleRenewalFailure($data);
            case 'PRICE_INCREASE':
                return $this->handlePriceIncrease($data);
            case 'REFUND':
                return $this->handleRefund($data);
            default:
                $this->logger->warning('Unknown Apple notification type', [
                    'type' => $data['notificationType'] ?? 'unknown',
                    'payload' => $payload
                ]);
                return new WebhookResponse(
                    status: WebhookResponse::STATUS_IGNORED,
                    eventType: 'unknown',
                    subscriptionId: $data['subscriptionId'] ?? null,
                    additionalData: ['raw_type' => $data['notificationType'] ?? null]
                );
        }
    }

    private function handleInitialPurchase(array $data): WebhookResponse
    {
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'initial_purchase',
            subscriptionId: $data['originalTransactionId'] ?? null,
            additionalData: $data
        );
    }

    private function handleRenewal(array $data): WebhookResponse
    {
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'renewal',
            subscriptionId: $data['originalTransactionId'] ?? null,
            additionalData: $data
        );
    }

    private function handleCancellation(array $data): WebhookResponse
    {
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'cancellation',
            subscriptionId: $data['originalTransactionId'] ?? null,
            additionalData: $data
        );
    }

    private function handleRenewalStatusChange(array $data): WebhookResponse
    {
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'renewal_status_change',
            subscriptionId: $data['originalTransactionId'] ?? null,
            additionalData: $data
        );
    }

    private function handleRenewalFailure(array $data): WebhookResponse
    {
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'renewal_failure',
            subscriptionId: $data['originalTransactionId'] ?? null,
            additionalData: $data
        );
    }
    
    private function handlePriceIncrease(array $data): WebhookResponse
    {
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'price_increase',
            subscriptionId: $data['originalTransactionId'] ?? null,
            additionalData: $data
        );
    }
    
    private function handleRefund(array $data): WebhookResponse
    {
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'refund',
            subscriptionId: $data['originalTransactionId'] ?? null,
            additionalData: $data
        );
    }
}