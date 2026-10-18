<?php

namespace MarcoRieser\StatamicInstagram\Tags;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use MarcoRieser\StatamicInstagram\InstagramAPI;
use MarcoRieser\StatamicInstagram\Models\Media;
use MarcoRieser\StatamicInstagram\Models\Profile;
use Statamic\Tags\Tags;

class Instagram extends Tags
{
    /**
     * The {{ instagram:profile handle="rickastley" }} tag.
     */
    public function profile(): ?Profile
    {
        try {
            $api = app(InstagramAPI::class);

            if ($handle = $this->params->get('handle')) {
                $api->setHandle($handle);
            }

            $this->refreshAccessToken($api);

            return $api->profile();
        } catch (\Exception $e) {
            if ($this->shouldLog()) {
                Log::error($e);

                return null;
            }

            throw $e;
        }
    }

    /**
     * The {{ instagram:feed limit="12" handle="rickastley" }} tag.
     */
    public function feed(): Collection
    {
        try {
            $api = app(InstagramAPI::class);

            if ($limit = $this->params->int('limit')) {
                $api->setLimit($limit);
            }

            if ($handle = $this->params->get('handle')) {
                $api->setHandle($handle);
            }

            $this->refreshAccessToken($api);

            return $api->feed();
        } catch (\Exception $e) {
            if ($this->shouldLog()) {
                Log::error($e);

                return collect();
            }

            throw $e;
        }
    }

    /**
     * The {{ instagram:media id="17916135464919603" handle="rickastley" }} tag.
     */
    public function media(): ?Media
    {
        try {
            $api = app(InstagramAPI::class);

            if ($handle = $this->params->get('handle')) {
                $api->setHandle($handle);
            }

            if (! ($id = $this->params->int('id'))) {
                throw new \InvalidArgumentException('The "id" parameter is required.');
            }

            $this->refreshAccessToken($api);

            return $api->media($id);
        } catch (\Exception $e) {
            if ($this->shouldLog()) {
                Log::error($e);

                return null;
            }

            throw $e;
        }
    }

    protected function refreshAccessToken(InstagramAPI $api): void
    {
        try {
            $api->refreshAccessToken();
        } catch (\Exception $e) {
            Log::warning($e->getMessage());
        }
    }

    protected function shouldLog(): bool
    {
        return app()->isProduction() || ! app()->hasDebugModeEnabled();
    }
}
