<?php

namespace SubscriptionWebhooks\Laravel;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use SubscriptionWebhooks\Laravel\Core\WebhookProcessor;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * Handle Apple subscription webhook
     */
    public function handleAppleWebhook(
        Request $request, 
        WebhookProcessor $processor
    ): Response {
        try {
            // Process webhook
            $response = $processor->process($request->getContent());
            
            // Log successful webhook
            Log::info('Apple webhook received', [
                'event_type' => $response->eventType,
                'subscription_id' => $response->subscriptionId
            ]);
            
            // Dispatch Laravel event for further processing
            event(new SubscriptionWebhookReceived(
                'apple', 
                $response->toArray()
            ));
            
            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Apple Webhook Processing Error', [
                'message' => $e->getMessage(),
                'payload' => $request->getContent()
            ]);
            
            return response()->json(['status' => 'error'], 400);
        }
    }

    /**
     * Handle Google subscription webhook
     */
    public function handleGoogleWebhook(
        Request $request, 
        WebhookProcessor $processor
    ): Response {
        try {
            // Process webhook
            $response = $processor->process($request->getContent());
            
            // Log successful webhook
            Log::info('Google webhook received', [
                'event_type' => $response->eventType,
                'subscription_id' => $response->subscriptionId
            ]);
            
            // Dispatch Laravel event for further processing
            event(new SubscriptionWebhookReceived(
                'google', 
                $response->toArray()
            ));
            
            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Google Webhook Processing Error', [
                'message' => $e->getMessage(),
                'payload' => $request->getContent()
            ]);
            
            return response()->json(['status' => 'error'], 400);
        }
    }
}