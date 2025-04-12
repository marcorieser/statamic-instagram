<?php

namespace MarcoRieser\StatamicInstagram;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ItemNotFoundException;
use MarcoRieser\StatamicInstagram\Models\Account;
use MarcoRieser\StatamicInstagram\Models\Media;
use MarcoRieser\StatamicInstagram\Models\Profile;

class InstagramAPI
{
    protected string $apiBaseUrl = 'https://graph.instagram.com';
    protected string $businessApiBaseUrl = 'https://graph.instagram.com/v21.0';

    protected ?string $handle = null;
    protected ?int $limit = null;
    protected ?Account $account = null;

    public static function cacheKey(...$parts): string
    {
        return collect([
            config('statamic-instagram.cache.key_prefix'),
        ])->merge($parts)->implode('_');
    }

    public function profile(): Profile
    {
        return Cache::remember(
            $this->cacheKey($this->getAccount()->handle, 'profile'),
            now()->addSeconds(config('statamic-instagram.cache.duration')),
            function () {
                $response = Http::get("$this->businessApiBaseUrl/{$this->getUserId()}", [
                    'fields' => collect([
                        'biography',
                        'followers_count',
                        'follows_count',
                        'id',
                        'media_count',
                        'name',
                        'profile_picture_url',
                        'username',
                        'website',
                    ])->join(','),
                    'access_token' => $this->getAccount()->accessToken,
                ]);

                if (!$response->successful()) {
                    throw new \RuntimeException($this->formatException('Could not fetch media list from API.', $response));
                }

                return Profile::fromApiData($response->json());
            }
        );
    }

    public function feed(): Collection
    {
        return Cache::remember(
            $this->cacheKey($this->getAccount()->handle, 'feed', $this->getLimit()),
            now()->addSeconds(config('statamic-instagram.cache.duration')),
            function () {
                $response = Http::get("$this->businessApiBaseUrl/{$this->getUserId()}/media", [
                    'limit' => $this->getLimit(),
                    'fields' => collect([
                        'id',
                        'caption',
                        'comments_count',
                        'is_shared_to_feed',
                        'like_count',
                        'media_product_type',
                        'media_type',
                        'media_url',
                        'permalink',
                        'thumbnail_url',
                        'timestamp',
                        'username',
                        'children'
                    ])->join(','),
                    'access_token' => $this->getAccount()->accessToken,
                ]);

                if (!$response->successful()) {
                    throw new \RuntimeException($this->formatException('Could not fetch media list from API.', $response));
                }

                return $response->collect('data')
                    ->map($this->completeChildMedia())
                    ->map(fn(array $media) => Media::fromApiData($media));
            }
        );
    }

    public function getAccount(): Account
    {
        if (!$this->account) {
            $accounts = collect(config('statamic-instagram.accounts'));

            if (!$accounts->count()) {
                throw new ItemNotFoundException('No instagram accounts specified in config.');
            }

            $handle = $this->getHandle();

            if (!($account = $accounts->firstWhere('handle', $handle))) {
                throw new ItemNotFoundException("No Instagram account with the handle \"$handle\" specified in config.");
            }

            $this->account = Account::fromConfig($account);
        }

        return $this->account;
    }

    public function getLimit(): int
    {
        if (!$this->limit) {
            if (!($limit = config('statamic-instagram.limit'))) {
                throw new ItemNotFoundException('Default Instagram limit not specified in config.');
            }

            $this->limit = (int)$limit;
        }

        return $this->limit;
    }

    public function setLimit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function getUserId(): string
    {
        return Cache::remember(
            $this->cacheKey($this->getAccount()->handle, "user_id"),
            now()->addSeconds(config('statamic-instagram.cache.duration')),
            function () {
                $response = Http::get($this->businessApiBaseUrl . '/me',
                    [
                        'fields' => 'user_id',
                        'access_token' => $this->getAccount()->accessToken,
                    ]);

                if (!$response->successful() || !($userId = $response->collect()->get('user_id'))) {
                    throw new \RuntimeException($this->formatException('Could not fetch user_id from API.', $response));
                }

                return $userId;
            }
        );
    }

    public function getHandle(): string
    {
        if (!$this->handle) {
            $accounts = collect(config('statamic-instagram.accounts'));

            if (!$accounts->count()) {
                throw new ItemNotFoundException('No instagram accounts specified in config.');
            }

            $this->handle = Arr::get($accounts->first(), 'handle');
        }

        return $this->handle;
    }

    public function setHandle(string $handle): static
    {
        $this->handle = $handle;

        return $this;
    }

    public function media(int $id): ?Media
    {
        if ($media = Cache::get($this->cacheKey('media', $id))) {
            return $media;
        }

        return collect([$this->fetchMedia($id)])
            ->map($this->completeChildMedia())
            ->map(fn(array $media) => Media::fromApiData($media))
            ->first();
    }

    public function refreshAccessToken(): bool
    {
        $durationMinOneDayMaxTwoMonths = min(
            now()->addMonths(2)->subDay(),
            max(
                now()->addSeconds(config('statamic-instagram.cache.duration')),
                now()->addDay()
            ),
        );

        return Cache::remember(
            $this->cacheKey($this->getAccount()->handle, 'token_refreshed'),
            $durationMinOneDayMaxTwoMonths,
            function () {
                $response = Http::get("$this->apiBaseUrl/refresh_access_token", [
                    'grant_type' => 'ig_refresh_token',
                    'access_token' => $this->getAccount()->accessToken,
                ]);

                if (!$response->successful()) {
                    throw new \RuntimeException($this->formatException("Could not refresh access token for {$this->getAccount()->handle} account.", $response));
                }

                return true;
            }
        );
    }

    protected function completeChildMedia(): \Closure
    {
        return function (array $media) {
            if (!config('statamic-instagram.include_child_posts')) {
                $media['children'] = null;

                return $media;
            }

            if (Arr::has($media, 'children')) {
                $media['children'] = collect($media['children']['data'])
                    ->map(fn(array $child) => $this->fetchChildMedia(Arr::get($child, 'id')))
                    ->filter()
                    ->map($this->completeChildMedia())
                    ->all();
            }

            return $media;
        };
    }

    protected function fetchChildMedia(int $id): array
    {
        $response = Http::get("$this->businessApiBaseUrl/$id", [
            'fields' => collect([
                'id',
                'comments_count',
                'like_count',
                'media_product_type',
                'media_type',
                'media_url',
                'permalink',
                'thumbnail_url',
                'timestamp',
                'username'
            ])->join(','),
            'access_token' => $this->getAccount()->accessToken,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException($this->formatException("Could not retrieve media with id $id.", $response));
        }

        return $response->collect()->all();
    }

    protected function fetchMedia(int $id): array
    {
        $response = Http::get("$this->businessApiBaseUrl/$id", [
            'fields' => collect([
                'id',
                'caption',
                'comments_count',
                'is_shared_to_feed',
                'like_count',
                'media_product_type',
                'media_type',
                'media_url',
                'permalink',
                'thumbnail_url',
                'timestamp',
                'username',
                'children'
            ])->join(','),
            'access_token' => $this->getAccount()->accessToken,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException($this->formatException("Could not retrieve media with id $id.", $response));
        }

        return $response->collect()->all();
    }

    protected function formatException(string $message, Response $response): string
    {
        if (!($error = $response->json('error'))) {
            return $message;
        }


        return $message . ' ' . json_encode($error);
    }
}
