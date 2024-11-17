<?php

namespace MarcoRieser\StatamicInstagram\Tags;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Statamic\Tags\Tags;

class InstagramFeed extends Tags
{
    protected string $apiBaseUrl = 'https://graph.instagram.com/v21.0';

    /**
     * The {{ instagram_feed }} tag.
     */
    public function index(): array
    {
        $limit = $this->params->int('limit', 12);
        $cacheKey = config('statamic-instagram.cache.key_prefix') . '_feed_' . $limit;

        if (!($accessToken = $this->getAccessToken())) {
            return [];
        }

        if (!($userId = $this->getUserId())) {
            return [];
        }

        try {
            return Cache::remember(
                $cacheKey,
                now()->addSeconds(config('statamic-instagram.cache.duration')),
                function () use ($accessToken, $limit, $userId) {
                    $response = Http::get("$this->apiBaseUrl/{$userId}/media", [
                        'limit' => $limit,
                        'fields' => collect([
                            'id',
                            'caption',
                            'media_type',
                            'media_url',
                            'permalink',
                            'thumbnail_url',
                            'timestamp',
                        ])->join(','),
                        'access_token' => $accessToken,
                    ]);

                    if (!$response->successful()) {
                        throw new \RuntimeException('Could not retrieve media.');
                    }

                    return $response->collect('data')->all();
                }
            );
        } catch (\Exception $exception) {
            \Log::alert('Instagram error: ' . $exception->getMessage());
            return [];
        }
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
}
