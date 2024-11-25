<?php

namespace MarcoRieser\StatamicInstagram;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ImageProxy
{
    public function __invoke(string $hash): Application|Response|ResponseFactory
    {
        if (!($url = Cache::get(InstagramAPI::cacheKey('media_url', $hash)))) {
            abort(404);
        }

        $response = Http::get($url);

        return response($response->body(), $response->status())->header('Content-Type', $response->header('Content-Type'));
    }
}
