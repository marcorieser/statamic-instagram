<?php

namespace MarcoRieser\StatamicInstagram\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use MarcoRieser\StatamicInstagram\InstagramAPI;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Data\HasAugmentedData;

class Media implements Augmentable
{
    use HasAugmentedData;

    public function __construct(
        public int         $id,
        public ?string     $caption = null,
        public ?int        $comments_count = null,
        public ?bool       $is_shared_to_feed = null,
        public ?int        $like_count = null,
        public ?string     $media_product_type = null,
        public ?string     $media_type = null,
        public ?string     $media_url = null,
        public ?string     $permalink = null,
        public ?string     $thumbnail_url = null,
        public ?Carbon     $timestamp = null,
        public ?string     $username = null,
        public ?Collection $children = null,
    )
    {
        $this->cache();
    }

    public static function fromApiData(array $media): self
    {
        $id = Arr::get($media, 'id');
        $caption = Arr::get($media, 'caption');
        $comments_count = Arr::get($media, 'comments_count');
        $is_shared_to_feed = Arr::get($media, 'is_shared_to_feed');
        $like_count = Arr::get($media, 'like_count');
        $media_product_type = Arr::get($media, 'media_product_type');
        $media_type = Arr::get($media, 'media_type');
        $media_url = Arr::get($media, 'media_url');
        $permalink = Arr::get($media, 'permalink');
        $thumbnail_url = Arr::get($media, 'thumbnail_url');
        $timestamp = Arr::get($media, 'timestamp');
        $username = Arr::get($media, 'username');
        $children = Arr::get($media, 'children');

        if ($comments_count) {
            $comments_count = (int)$comments_count;
        }

        if ($is_shared_to_feed) {
            $is_shared_to_feed = (bool)$is_shared_to_feed;
        }

        if ($like_count) {
            $like_count = (int)$like_count;
        }

        if ($media_product_type) {
            $media_product_type = Str::lower($media_product_type);
        }

        if ($media_type) {
            $media_type = Str::lower($media_type);
        }

        if ($timestamp) {
            $timestamp = Carbon::parse($timestamp);
        }

        if ($children) {
            $children = collect($children)->map(fn(array $media) => Media::fromApiData($media));
        }

        return new self($id, $caption, $comments_count, $is_shared_to_feed, $like_count, $media_product_type, $media_type, $media_url, $permalink, $thumbnail_url, $timestamp, $username, $children);
    }

    public function values(): array
    {
        return collect([
            'id' => $this->id,
            'caption' => $this->caption,
            'comments_count' => $this->comments_count,
            'is_shared_to_feed' => $this->is_shared_to_feed,
            'like_count' => $this->like_count,
            'media_product_type' => $this->media_product_type,
            'media_type' => $this->media_type,
            'media_url' => $this->media_url,
            'permalink' => $this->permalink,
            'thumbnail_url' => $this->thumbnail_url,
            'timestamp' => $this->timestamp,
            'username' => $this->username,
            'children' => $this->children,
        ])
            ->filter()
            ->sortKeys()
            ->all();
    }

    protected function cache(): void
    {
        Cache::remember(
            InstagramAPI::cacheKey('media', $this->id),
            now()->addSeconds(config('statamic-instagram.cache.duration')),
            fn() => $this
        );
    }
}
