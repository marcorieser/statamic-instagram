<?php

namespace MarcoRieser\StatamicInstagram\Tags;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use MarcoRieser\StatamicInstagram\InstagramAPI;
use MarcoRieser\StatamicInstagram\Models\Media;
use Statamic\Tags\Tags;

class Instagram extends Tags
{
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

            $api->refreshAccessToken();

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

            $api->refreshAccessToken();

            return $api->media($this->params->int('id'));
        } catch (\Exception $e) {
            if ($this->shouldLog()) {
                Log::error($e);
                return null;
            }

            throw $e;
        }
    }

    protected function shouldLog(): bool
    {
        return app()->isProduction() || !app()->hasDebugModeEnabled();
    }
}
