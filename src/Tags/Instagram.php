<?php

namespace MarcoRieser\StatamicInstagram\Tags;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use MarcoRieser\StatamicInstagram\Instagram as InstagramApi;
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
            $api = app(InstagramApi::class);

            if ($limit = $this->params->int('limit')) {
                $api->setLimit($limit);
            }

            if ($handle = $this->params->get('handle')) {
                $api->setHandle($handle);
            }

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
     * The {{ instagram:media id="17916135464919603" }} tag.
     */
    public function media(): ?Media
    {
        try {
            $api = app(InstagramApi::class);

            return $api->media($this->params->int('id'));
        } catch (\Exception $e) {
            if ($this->shouldLog()) {
                Log::error($e);
                return null;
            }

            throw $e;
        }
    }

    /**
     * The {{ instagram:proxy }} tag.
     */
    public function proxy(): ?string
    {
        if (!($id = $this->params->get('id') ?? $this->context->get('id'))) {
            return null;
        }

        /** @var Media $media */
        if (!($media = Cache::get(InstagramApi::getCacheKey('media', $id)))) {
            return null;
        }

        $path = parse_url($media->thumbnail_url ?? $media->media_url, PHP_URL_PATH);

        return route('statamic.statamic-instagram.proxy', ['id' => $id, 'extension' => pathinfo($path, PATHINFO_EXTENSION)]);
    }

    protected function shouldLog(): bool
    {
        return app()->isProduction() || !app()->hasDebugModeEnabled();
    }
}
