# Laravel Subscription Webhooks

A Laravel package for handling webhooks from Apple App Store and Google Play Store subscription notifications.

## Features

* Automatic route registration for subscription webhooks
* Event-driven webhook processing
* Support for all Apple App Store Server Notification v2 event types
* Support for all Google Play Billing subscription events
* Configurable middleware and routes
* PSR-3 compliant logging
* Extensible webhook handler architecture

## Installation

Install the package via Composer:

```bash
composer require your-vendor/laravel-subscription-webhooks
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="SubscriptionWebhooks\Laravel\SubscriptionWebhookServiceProvider" --tag="config"
```

## Configuration

Edit `config/subscription-webhooks.php` to configure webhook handling:

```php
return [
    'routes' => [
        // Enable automatic webhook routes
        'enabled' => true,
        
        // Customize middleware
        'middleware' => ['api'],
        
        // Customize route prefix
        'prefix' => 'webhooks/subscriptions',
    ],
    
    'platforms' => [
        'apple' => [
            // Configure Apple webhook settings
            'verify_signature' => true,
            'public_key_path' => storage_path('apple/public_key.pem'),
        ],
        'google' => [
            // Configure Google webhook settings
            'verify_signature' => true,
            'service_account_path' => storage_path('google/service_account.json'),
        ],
    ],
    
    'processing' => [
        // Queue webhook processing
        'queue' => false,
        
        // Default queue connection
        'queue_connection' => 'default',
    ],
];
```

## Usage

### Automatic Route Registration

When `routes.enabled` is set to `true` in the config, the package will automatically register the following routes:
* **POST** `/webhooks/subscriptions/apple` - For Apple App Store subscription webhooks
* **POST** `/webhooks/subscriptions/google` - For Google Play Store subscription webhooks

### Listening to Webhook Events

In your `EventServiceProvider` or any service provider:

```php
use SubscriptionWebhooks\Laravel\Events\SubscriptionWebhookReceived;

public function boot()
{
    Event::listen(SubscriptionWebhookReceived::class, function ($event) {
        // $event->platform will be 'apple' or 'google'
        // $event->webhookData contains the webhook data
        
        if ($event->platform === 'apple') {
            // Handle Apple subscription notification
            $notificationType = $event->webhookData['notificationType'];
            $subtype = $event->webhookData['subtype'] ?? null;
            
            // Process notification based on type
        } elseif ($event->platform === 'google') {
            // Handle Google subscription notification
            $notificationType = $event->webhookData['notificationType'];
            
            // Process notification based on type
        }
    });
}
```

### Specific Event Listeners

For more targeted event handling, you can listen to platform-specific events:

```php
use SubscriptionWebhooks\Laravel\Events\Apple\SubscriptionRenewed;
use SubscriptionWebhooks\Laravel\Events\Google\SubscriptionPurchased;

// Apple-specific event
Event::listen(SubscriptionRenewed::class, function ($event) {
    // Handle subscription renewal
    $subscriptionId = $event->getSubscriptionId();
    // Update your database
});

// Google-specific event
Event::listen(SubscriptionPurchased::class, function ($event) {
    // Handle new subscription purchase
    $subscriptionId = $event->getSubscriptionId();
    // Update your database
});
```

## Advanced Usage

### Custom Webhook Processing

You can implement your own webhook processor by extending the base handler:

```php
use SubscriptionWebhooks\Laravel\Handlers\WebhookHandler;

class CustomAppleWebhookHandler extends WebhookHandler
{
    public function process(array $payload): void
    {
        // Custom processing logic here
        
        // You can still fire the built-in events if needed
        $this->fireEvents($payload);
        
        // Or implement completely custom logic
    }
}
```

Then register your custom handler in a service provider:

```php
$this->app->bind(
    SubscriptionWebhooks\Laravel\Handlers\AppleWebhookHandler::class,
    CustomAppleWebhookHandler::class
);
```

### Manual Webhook Processing

If you prefer to handle the webhooks through your own controllers:

```php
use SubscriptionWebhooks\Laravel\Processors\AppleWebhookProcessor;

class CustomWebhookController extends Controller
{
    public function handleAppleWebhook(Request $request, AppleWebhookProcessor $processor)
    {
        return $processor->handle($request);
    }
}
```

## Logging

By default, all webhook processing is logged. You can configure the logging behavior in your Laravel logging configuration.

## Testing

The package includes helpers for testing webhook handling in your application:

```php
use SubscriptionWebhooks\Laravel\Testing\FakeAppleWebhook;

// In a test case
public function testSubscriptionRenewal()
{
    // Create a fake Apple webhook payload
    $fake = FakeAppleWebhook::renewalEvent()
        ->forSubscription('sub_123456')
        ->create();
        
    // Dispatch the fake webhook
    $fake->dispatch();
    
    // Assert the expected behavior
    Event::assertDispatched(SubscriptionRenewed::class);
    
    // Or assert against your database
    $this->assertDatabaseHas('subscriptions', [
        'subscription_id' => 'sub_123456',
        'status' => 'active',
    ]);
}
```

## Security

- Always ensure your webhook endpoints are properly secured.
- Use HTTPS for all webhook URLs in production.
- Keep your signature verification enabled in production environments.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).