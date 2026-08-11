<?php

namespace Canopus\SmsApi;

use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sms.php', 'sms');

        $this->app->singleton('sms', fn ($app) => new SmsManager($app));
        $this->app->alias('sms', SmsManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sms.php' => config_path('sms.php'),
            ], 'sms-config');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['sms', SmsManager::class];
    }
}
