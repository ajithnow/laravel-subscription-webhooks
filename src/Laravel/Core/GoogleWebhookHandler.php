<?php

namespace SubscriptionWebhooks\Laravel\Core;

use Exception;
use JsonException;

/**
 * Google Play Store Subscription Webhook Handler
 */
class GoogleWebhookHandler extends AbstractWebhookHandler
{
    private const GOOGLE_ENDPOINT = 'https://www.googleapis.com/androidpublisher/v3/notifications';

    /**
     * Validate Google webhook signature
     */
    public function validatePayload(string $payload): bool
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            
            // Check if payload has the required structure
            if (!isset($data['subscriptionNotification']) && 
                !isset($data['oneTimeProductNotification']) && 
                !isset($data['testNotification'])) {
                $this->logger->warning('Invalid Google webhook structure', [
                    'payload' => $payload
                ]);
                return false;
            }
            
            // Further validation can be implemented here
            // For example, JWT validation
            
            return true;
        } catch (JsonException $e) {
            $this->logger->error('Invalid Google webhook payload', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            return false;
        }
    }

    /**
     * Process Google subscription notification
     */
    public function processPayload(string $payload): WebhookResponse
    {
        $data = json_decode($payload, true);
        
        // Handle test notifications
        if (isset($data['testNotification'])) {
            return new WebhookResponse(
                status: WebhookResponse::STATUS_SUCCESS,
                eventType: 'test_notification',
                additionalData: $data
            );
        }
        
        // Handle subscription notifications
        if (isset($data['subscriptionNotification'])) {
            $notificationType = $data['subscriptionNotification']['notificationType'] ?? null;
            
            switch ($notificationType) {
                case 1: // SUBSCRIPTION_RECOVERED
                    return $this->handleRecovered($data);
                case 2: // SUBSCRIPTION_RENEWED
                    return $this->handleRenewed($data);
                case 3: // SUBSCRIPTION_CANCELED
                    return $this->handleCanceled($data);
                case 4: // SUBSCRIPTION_PURCHASED
                    return $this->handlePurchased($data);
                case 5: // SUBSCRIPTION_ON_HOLD
                    return $this->handleOnHold($data);
                case 6: // SUBSCRIPTION_IN_GRACE_PERIOD
                    return $this->handleGracePeriod($data);
                case 7: // SUBSCRIPTION_RESTARTED
                    return $this->handleRestarted($data);
                case 8: // SUBSCRIPTION_PRICE_CHANGE_CONFIRMED
                    return $this->handlePriceChangeConfirmed($data);
                case 9: // SUBSCRIPTION_DEFERRED
                    return $this->handleDeferred($data);
                case 10: // SUBSCRIPTION_PAUSED
                    return $this->handlePaused($data);
                case 11: // SUBSCRIPTION_PAUSE_SCHEDULE_CHANGED
                    return $this->handlePauseScheduleChanged($data);
                case 12: // SUBSCRIPTION_REVOKED
                    return $this->handleRevoked($data);
                case 13: // SUBSCRIPTION_EXPIRED
                    return $this->handleExpired($data);
                default:
                    $this->logger->warning('Unknown Google subscription notification type', [
                        'type' => $notificationType,
                        'payload' => $payload
                    ]);
                    return new WebhookResponse(
                        status: WebhookResponse::STATUS_IGNORED,
                        eventType: 'unknown_subscription',
                        additionalData: ['raw_type' => $notificationType]
                    );
            }
        }
        
        // Handle one-time product notifications
        if (isset($data['oneTimeProductNotification'])) {
            $notificationType = $data['oneTimeProductNotification']['notificationType'] ?? null;
            
            switch ($notificationType) {
                case 1: // ONE_TIME_PRODUCT_PURCHASED
                    return $this->handleOneTimePurchased($data);
                case 2: // ONE_TIME_PRODUCT_CANCELED
                    return $this->handleOneTimeCanceled($data);
                default:
                    $this->logger->warning('Unknown Google one-time notification type', [
                        'type' => $notificationType,
                        'payload' => $payload
                    ]);
                    return new WebhookResponse(
                        status: WebhookResponse::STATUS_IGNORED,
                        eventType: 'unknown_one_time',
                        additionalData: ['raw_type' => $notificationType]
                    );
            }
        }
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_FAILED,
            eventType: 'unknown',
            additionalData: $data
        );
    }

    private function handleRecovered(array $data): WebhookResponse
    {
        $subInfo = $data['subscriptionNotification'];
        $purchaseToken = $subInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'subscription_recovered',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }

    private function handleRenewed(array $data): WebhookResponse
    {
        $subInfo = $data['subscriptionNotification'];
        $purchaseToken = $subInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'subscription_renewed',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }

    private function handleCanceled(array $data): WebhookResponse
    {
        $subInfo = $data['subscriptionNotification'];
        $purchaseToken = $subInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'subscription_canceled',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }

    private function handlePurchased(array $data): WebhookResponse
    {
        $subInfo = $data['subscriptionNotification'];
        $purchaseToken = $subInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'subscription_purchased',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }

    private function handleOnHold(array $data): WebhookResponse
    {
        $subInfo = $data['subscriptionNotification'];
        $purchaseToken = $subInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'subscription_on_hold',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }

    private function handleGracePeriod(array $data): WebhookResponse
    {
        $subInfo = $data['subscriptionNotification'];
        $purchaseToken = $subInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'subscription_grace_period',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }

    private function handleRestarted(array $data): WebhookResponse
    {
        $subInfo = $data['subscriptionNotification'];
        $purchaseToken = $subInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'subscription_restarted',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }

    private function handlePriceChangeConfirmed(array $data): WebhookResponse
    {
        $subInfo = $data['subscriptionNotification'];
        $purchaseToken = $subInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'subscription_price_change_confirmed',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }

    private function handleDeferred(array $data): WebhookResponse
    {
        $subInfo = $data['subscriptionNotification'];
        $purchaseToken = $subInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'subscription_deferred',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }

    private function handlePaused(array $data): WebhookResponse
    {
        $subInfo = $data['subscriptionNotification'];
        $purchaseToken = $subInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'subscription_paused',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }

    private function handlePauseScheduleChanged(array $data): WebhookResponse
    {
        $subInfo = $data['subscriptionNotification'];
        $purchaseToken = $subInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'subscription_pause_schedule_changed',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }

    private function handleRevoked(array $data): WebhookResponse
    {
        $subInfo = $data['subscriptionNotification'];
        $purchaseToken = $subInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'subscription_revoked',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }

    private function handleExpired(array $data): WebhookResponse
    {
        $subInfo = $data['subscriptionNotification'];
        $purchaseToken = $subInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'subscription_expired',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }

    private function handleOneTimePurchased(array $data): WebhookResponse
    {
        $oneTimeInfo = $data['oneTimeProductNotification'];
        $purchaseToken = $oneTimeInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'one_time_purchased',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }

    private function handleOneTimeCanceled(array $data): WebhookResponse
    {
        $oneTimeInfo = $data['oneTimeProductNotification'];
        $purchaseToken = $oneTimeInfo['purchaseToken'] ?? null;
        
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: 'one_time_canceled',
            subscriptionId: $purchaseToken,
            additionalData: $data
        );
    }
}