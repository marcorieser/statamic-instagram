<?php

namespace MarcoRieser\StatamicInstagram;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use MarcoRieser\StatamicInstagram\Instagram as InstagramApi;
use MarcoRieser\StatamicInstagram\Models\Media;

class ImageProxy
{
    public function __invoke(int $id): Application|Response|ResponseFactory
    {
        /** @var Media $media */
        if (!($media = Cache::get(InstagramApi::getCacheKey('media', $id)))) {
            abort(404);
        }

        $response = Http::get($media->thumbnail_url ?? $media->media_url);

        return response($response->body(), $response->status())->header('Content-Type', $response->header('Content-Type'));
    }
}
