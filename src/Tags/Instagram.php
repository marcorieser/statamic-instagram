<?php

namespace MarcoRieser\StatamicInstagram\Tags;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Tags\Tags;

class Instagram extends Tags
{
    protected string $apiBaseUrl = 'https://graph.instagram.com/v21.0';

    /**
     * The {{ instagram:feed limit="12" }} tag.
     */
    public function feed(): array
    {
        if (!$this->getAccessToken() || !$this->getUserId()) {
            return [];
        }

        return $this->fetchFeed();
    }

    protected function getAccessToken(): ?string
    {
        try {
            $accessToken = config('statamic-instagram.access_token');

            if (!$accessToken) {
                throw new \RuntimeException('Could not retrieve access token.');
            }

            return $accessToken;
        } catch (\Exception $exception) {
            \Log::alert('Instagram error: ' . $exception->getMessage());
            return null;
        }
    }

    protected function getUserId(): ?string
    {
        $cacheKey = config('statamic-instagram.cache.key_prefix') . '_user_id';

        try {
            return Cache::remember(
                $cacheKey,
                now()->addSeconds(config('statamic-instagram.cache.duration')),
                function () {
                    $response = Http::get($this->apiBaseUrl . '/me',
                        [
                            'fields' => 'user_id',
                            'access_token' => $this->getAccessToken(),
                        ]);

                    if (!$response->successful() || !($userId = $response->collect()->get('user_id'))) {
                        throw new \RuntimeException('Could not retrieve user_id.');
                    }

                    return $userId;
                }
            );
        } catch (\Exception $exception) {
            \Log::alert('Instagram error: ' . $exception->getMessage());
            return null;
        }
    }

    protected function fetchFeed(): array
    {
        $limit = $this->params->int('limit', 12);
        $cacheKey = config('statamic-instagram.cache.key_prefix') . '_feed_' . $limit;

        try {
            return Cache::remember(
                $cacheKey,
//                now()->addSeconds(config('statamic-instagram.cache.duration')),
                0,
                function () use ($limit) {
                    $response = Http::get("$this->apiBaseUrl/{$this->getUserId()}/media", [
                        'limit' => $limit,
                        'fields' => collect([
                            'id',
                            'caption',
                            'media_type',
                            'media_url',
                            'permalink',
                            'thumbnail_url',
                            'timestamp',
                            'children'
                        ])->join(','),
                        'access_token' => $this->getAccessToken(),
                    ]);

                    if (!$response->successful()) {
                        throw new \RuntimeException('Could not retrieve media list.');
                    }

                    return $response->collect('data')
                        ->map($this->sanitizeMedia())
                        ->map($this->cacheMedia())
                        ->all();
                }
            );
        } catch (\Exception $exception) {
            \Log::alert('Instagram error: ' . $exception->getMessage());
            return [];
        }
    }

    protected function sanitizeMedia(): \Closure
    {
        return function (array $media) {
            if (Arr::has($media, 'timestamp')) {
                $media['timestamp'] = Carbon::parse($media['timestamp']);
            }

            if (Arr::has($media, 'media_type')) {
                $media['media_type'] = Str::lower($media['media_type']);
            }

            if (Arr::has($media, 'children')) {
                $media['children'] = collect($media['children']['data'])
                    ->map($this->fetchChildMedia())
                    ->filter()
                    ->map($this->sanitizeMedia())
                    ->map($this->cacheMedia())
                    ->all();
            }

            return $media;
        };
    }

    protected function cacheMedia(): \Closure
    {
        return function (array $media) {
            $cacheKey = config('statamic-instagram.cache.key_prefix') . '_media_' . $media['id'];
            return Cache::remember(
                $cacheKey,
                now()->addSeconds(config('statamic-instagram.cache.duration')),
                function () use ($media) {
                    return $media;
                }
            );
        };
    }

    protected function fetchChildMedia(): \Closure
    {
        return function (array $child) {
            $id = $child['id'];
            try {
                $response = Http::get("$this->apiBaseUrl/$id", [
                    'fields' => collect([
                        'id',
                        'media_type',
                        'media_url',
                        'thumbnail_url',
                        'permalink',
                        'timestamp',
                    ])->join(','),
                    'access_token' => $this->getAccessToken(),
                ]);

                if (!$response->successful()) {
                    throw new \RuntimeException("Could not retrieve media with id $id.");
                }

                return $response->collect()->all();

            } catch (\Exception $exception) {
                \Log::alert('Instagram error: ' . $exception->getMessage());
                return [];
            }
        };
    }

    /**
     * The {{ instagram:proxy }} tag.
     */
    public function proxy(): ?string
    {
        if (!($id = $this->params->get('id') ?? $this->context->get('id'))) {
            return null;
        }

        if (!($media = Cache::get(config('statamic-instagram.cache.key_prefix') . '_media_' . $id))) {
            return null;
        }

        $path = parse_url(Arr::get($media, 'thumbnail_url') ?? Arr::get($media, 'media_url'), PHP_URL_PATH);

        return route('statamic.statamic-instagram.proxy', ['id' => $id, 'extension' => pathinfo($path, PATHINFO_EXTENSION)]);
    }
}
