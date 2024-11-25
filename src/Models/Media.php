<?php

namespace MarcoRieser\StatamicInstagram\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use MarcoRieser\StatamicInstagram\Instagram;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Data\HasAugmentedData;

class Media implements Augmentable
{
    use HasAugmentedData;

    public function __construct(
        public int         $id,
        public ?string     $caption = null,
        public ?string     $media_type = null,
        public ?string     $media_url = null,
        public ?string     $permalink = null,
        public ?string     $thumbnail_url = null,
        public ?Carbon     $timestamp = null,
        public ?Collection $children = null,
    )
    {
        $this->cache();
    }

    public static function fromApiData(array $media): self
    {
        $id = Arr::get($media, 'id');
        $caption = Arr::get($media, 'caption');
        $media_type = Arr::get($media, 'media_type');
        $media_url = Arr::get($media, 'media_url');
        $permalink = Arr::get($media, 'permalink');
        $thumbnail_url = Arr::get($media, 'thumbnail_url');
        $timestamp = Arr::get($media, 'timestamp');
        $children = Arr::get($media, 'children');

        if ($timestamp) {
            $timestamp = Carbon::parse($timestamp);
        }

        if ($media_type) {
            $media_type = Str::lower($media_type);
        }

        if ($children) {
            $children = collect($children)->map(fn(array $media) => Media::fromApiData($media));
        }

        return new self($id, $caption, $media_type, $media_url, $permalink, $thumbnail_url, $timestamp, $children);
    }

    public function values(): array
    {
        return collect([
            'id' => $this->id,
            'caption' => $this->caption,
            'media_type' => $this->media_type,
            'media_url' => $this->media_url,
            'permalink' => $this->permalink,
            'thumbnail_url' => $this->thumbnail_url,
            'timestamp' => $this->timestamp,
            'children' => $this->children,
        ])
            ->filter()
            ->all();
    }

    protected function cache(): void
    {
        Cache::remember(
            Instagram::cacheKey('media', $this->id),
            now()->addSeconds(config('statamic-instagram.cache.duration')),
            fn() => $this
        );

        if ($this->thumbnail_url) {
            Cache::remember(
                Instagram::cacheKey('media_url', md5($this->thumbnail_url)),
                now()->addSeconds(config('statamic-instagram.cache.duration')),
                fn() => $this->thumbnail_url
            );
        }

        if ($this->media_url && $this->media_type !== 'video') {
            Cache::remember(
                Instagram::cacheKey('media_url', md5($this->media_url)),
                now()->addSeconds(config('statamic-instagram.cache.duration')),
                fn() => $this->media_url
            );
        }
    }
}
