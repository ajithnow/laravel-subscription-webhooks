<?php

namespace SubscriptionWebhooks\Laravel\Core;

use Exception;
use JsonException;
use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;

/**
 * Handles Apple App Store subscription webhooks.
 */
class AppleWebhookHandler extends AbstractWebhookHandler
{
    /** @var array|null Decoded JWT payload data */
    protected ?array $decodedData = null;

    /**
     * Validates Apple webhook payload and extracts JWT payload.
     */
    public function validatePayload(string $payload): bool
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            if (empty($data['signedPayload'])) {
                return $this->logWarning('Missing signedPayload in Apple webhook', $payload);
            }

            $decodedPayload = $this->decodeJwtPayload($data['signedPayload']);
            if (!$decodedPayload) {
                return false;
            }

            if (!$this->verifyAppleSignature($data['signedPayload'])) {
                $this->logger->warning('Apple webhook signature verification failed', [
                    'signedPayload' => $data['signedPayload']
                ]);
                return false;
            }

            $this->decodedData = $decodedPayload;

            return isset($decodedPayload['notificationType']);
        } catch (JsonException $e) {
            return $this->logError('Invalid Apple webhook payload', $e, $payload);
        } catch (Exception $e) {
            return $this->logError('Error processing Apple webhook', $e, $payload);
        }
    }

    /**
     * Processes the Apple subscription notification.
     */
    public function processPayload(string $payload): WebhookResponse
    {
        $data = json_decode($payload, true);
        $notificationType = $data['notificationType'] ?? 'unknown';

        return match ($notificationType) {
            'INITIAL_BUY' => $this->handleSubscriptionEvent('initial_purchase', $data),
            'RENEWAL' => $this->handleSubscriptionEvent('renewal', $data),
            'CANCELLATION' => $this->handleSubscriptionEvent('cancellation', $data),
            'DID_CHANGE_RENEWAL_STATUS' => $this->handleSubscriptionEvent('renewal_status_change', $data),
            'DID_FAIL_TO_RENEW' => $this->handleSubscriptionEvent('renewal_failure', $data),
            'PRICE_INCREASE' => $this->handleSubscriptionEvent('price_increase', $data),
            'REFUND' => $this->handleSubscriptionEvent('refund', $data),
            default => $this->logUnknownEvent($notificationType, $payload),
        };
    }

    /**
     * Verifies the JWT signature of the Apple webhook payload.
     */
    public function verifyAppleSignature(string $signedPayload): bool
    {
        if (!config('subscription-webhooks.platforms.apple.verify_signature', true)) {
            return true;
        }

        try {
            $header = $this->decodeJwtHeader($signedPayload);
            if (!$header || empty($header['kid'])) {
                return false;
            }

            $publicKey = $this->getApplePublicKey($header['kid']);
            if (!$publicKey) {
                return false;
            }

            JWT::decode($signedPayload, new Key($publicKey, 'RS256'));
            return true;
        } catch (Exception $e) {
            return $this->logError('Apple JWT verification failed', $e);
        }
    }

    /**
     * Retrieves Apple's public key for verifying JWT signatures.
     */
    protected function getApplePublicKey(string $kid): ?Key
    {
        try {
            $response = Http::get('https://appleid.apple.com/auth/keys');
            if (!$response->successful()) {
                 $this->logError('Failed to fetch Apple public keys', null, $response->body());
                 return null;
            }

            foreach ($response->json()['keys'] ?? [] as $key) {
                if ($key['kid'] === $kid) {
                    return JWK::parseKeySet(['keys' => [$key]])[$kid] ?? null;
                }
            }

            $this->logWarning('No matching Apple public key found', ['kid' => $kid]);
            return null;
        } catch (Exception $e) {
            $this->logError('Error fetching Apple public key', $e);
            return null;
        }
    }

    /**
     * Decodes the payload section of a JWT.
     */
    private function decodeJwtPayload(string $signedPayload): ?array
    {
        $jwtParts = explode('.', $signedPayload);
        if (count($jwtParts) !== 3) {
            $this->logWarning('Invalid JWT format in Apple webhook', ['signedPayload' => $signedPayload]);
            return null;
        }

        return json_decode(base64_decode(strtr($jwtParts[1], '-_', '+/')), true) ?: null;
    }

    /**
     * Decodes the header section of a JWT.
     */
    private function decodeJwtHeader(string $signedPayload): ?array
    {
        $jwtParts = explode('.', $signedPayload);
        return json_decode(base64_decode(strtr($jwtParts[0], '-_', '+/')), true) ?: null;
    }

    /**
     * Handles different subscription events in a uniform way.
     */
    private function handleSubscriptionEvent(string $eventType, array $data): WebhookResponse
    {
        return new WebhookResponse(
            status: WebhookResponse::STATUS_SUCCESS,
            eventType: $eventType,
            subscriptionId: $data['originalTransactionId'] ?? null,
            additionalData: $data
        );
    }

    /**
     * Logs unknown webhook events.
     */
    private function logUnknownEvent(string $type, string $payload): WebhookResponse
    {
        $this->logger->warning('Unknown Apple notification type', ['type' => $type, 'payload' => $payload]);
        return new WebhookResponse(
            status: WebhookResponse::STATUS_IGNORED,
            eventType: 'unknown',
            subscriptionId: null,
            additionalData: ['raw_type' => $type]
        );
    }

    /**
     * Logs warnings and returns false.
     */
    private function logWarning(string $message, mixed $context = null): bool
    {
        $this->logger->warning($message, is_array($context) ? $context : ['context' => $context]);
        return false;
    }

    /**
     * Logs errors and returns false.
     */
    private function logError(string $message, ?Exception $exception = null, mixed $context = null): bool
    {
        $this->logger->error($message, array_filter([
            'error' => $exception?->getMessage(),
            'context' => $context
        ]));
        return false;
    }
}
