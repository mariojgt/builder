<?php

namespace Mariojgt\Builder;

use Illuminate\Support\ServiceProvider;
use Mariojgt\Builder\Commands\Install;
use Mariojgt\Builder\Commands\Republish;

class BuilderProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Load some commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Republish::class,
                Install::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->publish();
    }

    public function publish()
    {
        // Publish the resource
        $this->publishes([
            __DIR__.'/../Publish/Resource/' => resource_path('vendor/Builder/'),
        ]);

        // Publish the public folder
        $this->publishes([
            __DIR__.'/../Publish/Config/' => config_path('/'),
        ]);
    }
}
