<?php

namespace MarcoRieser\StatamicInstagram\Models;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class Account
{
    public function __construct(
        public string $handle,
        public string $accessToken,
        public string $login = 'instagram',
        public ?string $userId = null,
    ) {}

    public function usesUnifiedLogin(): bool
    {
        return $this->login === 'unified';
    }

    public static function fromConfig(array $data): self
    {
        if (! ($handle = Arr::get($data, 'handle'))) {
            throw new InvalidArgumentException("The account doesn't have a valid handle");
        }

        if (! ($accessToken = Arr::get($data, 'access_token'))) {
            throw new InvalidArgumentException("The account doesn't have a valid access_token");
        }

        $login = Arr::get($data, 'login', 'instagram');

        if (! in_array($login, ['instagram', 'unified'], true)) {
            throw new InvalidArgumentException("The account has an invalid login \"$login\". Use \"instagram\" or \"unified\".");
        }

        $userId = Arr::get($data, 'user_id');

        return new self($handle, $accessToken, $login, $userId ? (string) $userId : null);
    }
}
