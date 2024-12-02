<?php

namespace MarcoRieser\StatamicInstagram;

use Edalzell\Forma\Forma;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/statamic-instagram.php', 'statamic-instagram');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/statamic-instagram.php' => config_path('statamic-instagram.php'),
            ], 'statamic-instagram-config');
        }
    }
}
