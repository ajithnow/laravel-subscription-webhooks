<?php

namespace SubscriptionWebhooks\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use SubscriptionWebhooks\Laravel\Core\WebhookProcessor;
use SubscriptionWebhooks\Laravel\Core\AppleWebhookHandler;
use SubscriptionWebhooks\Laravel\Core\GoogleWebhookHandler;
use Psr\Log\LoggerInterface;

class SubscriptionWebhookServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/subscription-webhooks.php', 'subscription-webhooks'
        );

        // Bind WebhookProcessor as a singleton
        $this->app->singleton(WebhookProcessor::class, function ($app) {
            $processor = new WebhookProcessor();
            
            // Get logger instance
            $logger = $app->make(LoggerInterface::class);
            
            // Register default handlers
            $processor->registerHandler(new AppleWebhookHandler($logger));
            $processor->registerHandler(new GoogleWebhookHandler($logger));
            
            return $processor;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../../config/subscription-webhooks.php' => config_path('subscription-webhooks.php'),
        ], 'config');

        // Register routes if enabled in configuration
        if (config('subscription-webhooks.routes.enabled', false)) {
            $this->registerRoutes();
        }
    }

    /**
     * Register webhook routes
     */
    protected function registerRoutes(): void
    {
        Route::middleware(config('subscription-webhooks.routes.middleware', ['api']))
            ->prefix(config('subscription-webhooks.routes.prefix', 'webhooks/subscriptions'))
            ->group(function () {
                Route::post('apple', [WebhookController::class, 'handleAppleWebhook'])
                    ->name('webhooks.apple-subscriptions');
                
                Route::post('google', [WebhookController::class, 'handleGoogleWebhook'])
                    ->name('webhooks.google-subscriptions');
            });
    }
}