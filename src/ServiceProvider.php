<?php

namespace MarcoRieser\StatamicInstagram;

use MarcoRieser\StatamicInstagram\Models\Media;
use MarcoRieser\StatamicInstagram\Models\Profile;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    public function register(): void
    {
        $this->registerSerializableClasses([
            Media::class,
            Profile::class,
        ]);
    }

    public function bootAddon(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/statamic-instagram.php', 'statamic-instagram');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/statamic-instagram.php' => config_path('statamic-instagram.php'),
            ], 'statamic-instagram-config');
        }
    }
}
