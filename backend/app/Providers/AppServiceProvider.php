<?php

namespace App\Providers;

use App\Services\Messaging\LogGateway;
use App\Services\Messaging\MessageGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Remplacer LogGateway par une passerelle réelle (SMS / WhatsApp API) quand elle sera contractée.
        $this->app->bind(MessageGateway::class, LogGateway::class);
    }

    public function boot(): void
    {
        //
    }
}
