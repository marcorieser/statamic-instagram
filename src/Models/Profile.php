<?php

namespace MarcoRieser\StatamicInstagram\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use MarcoRieser\StatamicInstagram\InstagramAPI;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Data\HasAugmentedData;

class Profile implements Augmentable
{
    use HasAugmentedData;

    public function __construct(
        public int     $id,
        public ?int    $followers_count,
        public ?int    $follows_count,
        public ?int    $media_count,
        public ?string $biography,
        public ?string $name,
        public ?string $profile_picture_url,
        public ?string $username,
        public ?string $website,
    )
    {
        $this->cache();
    }

    public static function fromApiData(array $profile): self
    {
        $id = Arr::get($profile, 'id');
        $followers_count = Arr::get($profile, 'followers_count');
        $follows_count = Arr::get($profile, 'follows_count');
        $media_count = Arr::get($profile, 'media_count');
        $biography = Arr::get($profile, 'biography');
        $name = Arr::get($profile, 'name');
        $profile_picture_url = Arr::get($profile, 'profile_picture_url');
        $username = Arr::get($profile, 'username');
        $website = Arr::get($profile, 'website');

        if ($followers_count !== null) {
            $followers_count = (int)$followers_count;
        }

        if ($follows_count !== null) {
            $follows_count = (int)$follows_count;
        }

        if ($media_count !== null) {
            $media_count = (int)$media_count;
        }

        return new self($id, $followers_count, $follows_count, $media_count, $biography, $name, $profile_picture_url, $username, $website);
    }

    public function values(): array
    {
        return collect([
            'id' => $this->id,
            'followers_count' => $this->followers_count,
            'follows_count' => $this->follows_count,
            'media_count' => $this->media_count,
            'biography' => $this->biography,
            'name' => $this->name,
            'profile_picture_url' => $this->profile_picture_url,
            'username' => $this->username,
            'website' => $this->website,
        ])
            ->filter(fn($value) => $value !== null)
            ->sortKeys()
            ->all();
    }

    protected function cache(): void
    {
        Cache::remember(
            InstagramAPI::cacheKey('profile', $this->id),
            now()->addSeconds(config('statamic-instagram.cache.duration')),
            fn() => $this
        );
    }
}
