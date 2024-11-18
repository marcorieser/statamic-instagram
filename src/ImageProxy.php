<?php

namespace MarcoRieser\StatamicInstagram;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ImageProxy
{
    public function __invoke(int $id): Application|Response|ResponseFactory
    {
        if (!($media = Cache::get(config('statamic-instagram.cache.key_prefix') . '_media_' . $id))) {
            abort(404);
        }

        $response = Http::get(Arr::get($media, 'thumbnail_url') ?? Arr::get($media, 'media_url'));

        return response($response->body(), $response->status())->header('Content-Type', $response->header('Content-Type'));
    }
}
