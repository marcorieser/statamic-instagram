<?php

namespace MarcoRieser\StatamicInstagram;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ItemNotFoundException;
use MarcoRieser\StatamicInstagram\Models\Account;
use MarcoRieser\StatamicInstagram\Models\Media;

class Instagram
{
    protected string $apiBaseUrl = 'https://graph.instagram.com/v21.0';

    protected ?string $handle = null;
    protected ?int $limit = null;
    protected ?Account $account = null;

    public function feed(): Collection
    {
        return Cache::remember(
            $this->getCacheKey($this->getAccount()->handle, 'feed', $this->getLimit()),
            now()->addSeconds(config('statamic-instagram.cache.duration')),
            function () {
                $response = Http::get("$this->apiBaseUrl/{$this->getUserId()}/media", [
                    'limit' => $this->getLimit(),
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
                    'access_token' => $this->getAccount()->accessToken,
                ]);

                if (!$response->successful()) {
                    throw new \RuntimeException('Could not fetch media list from API.');
                }

                return $response->collect('data')
                    ->map($this->completeChildMedia())
                    ->map(fn(array $media) => Media::fromApiData($media));
            }
        );
    }

    public static function getCacheKey(...$parts): string
    {
        return collect([
            config('statamic-instagram.cache.key_prefix'),
        ])->merge($parts)->implode('_');
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
            $this->getCacheKey($this->getAccount()->handle, "user_id"),
            now()->addSeconds(config('statamic-instagram.cache.duration')),
            function () {
                $response = Http::get($this->apiBaseUrl . '/me',
                    [
                        'fields' => 'user_id',
                        'access_token' => $this->getAccount()->accessToken,
                    ]);

                if (!$response->successful() || !($userId = $response->collect()->get('user_id'))) {
                    throw new \RuntimeException('Could not fetch user_id from API.');
                }

                return $userId;
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

    protected function fetchChildMedia(int $id): array
    {
        $response = Http::get("$this->apiBaseUrl/$id", [
            'fields' => collect([
                'id',
                'media_type',
                'media_url',
                'thumbnail_url',
                'permalink',
                'timestamp',
            ])->join(','),
            'access_token' => $this->getAccount()->accessToken,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Could not retrieve media with id $id.");
        }

        return $response->collect()->all();
    }

    public function media(int $id): ?Media
    {
        if ($media = Cache::get($this->getCacheKey('media', $id))) {
            return $media;
        }

        return collect([$this->fetchMedia($id)])
            ->map($this->completeChildMedia())
            ->map(fn(array $media) => Media::fromApiData($media))
            ->first();
    }

    protected function fetchMedia(int $id): array
    {
        $response = Http::get("$this->apiBaseUrl/$id", [
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
            'access_token' => $this->getAccount()->accessToken,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Could not retrieve media with id $id.");
        }

        return $response->collect()->all();
    }
}
