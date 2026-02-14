<?php

namespace JordJD\ArtisanMenu\Providers;

use JordJD\ArtisanMenu\Console\Commands\ArtisanMenu;
use Illuminate\Support\ServiceProvider;

class ArtisanMenuServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ArtisanMenu::class,
            ]);
        }
    }
}