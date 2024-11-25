<?php

namespace MarcoRieser\StatamicInstagram\Models;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class Account
{
    public function __construct(
        public string $handle,
        public string $accessToken,
    )
    {
    }

    public static function fromConfig(array $data): self
    {
        if (!($handle = Arr::get($data, 'handle'))) {
            throw new InvalidArgumentException("The account doesn't have a valid handle");
        }

        if (!($accessToken = Arr::get($data, 'access_token'))) {
            throw new InvalidArgumentException("The account doesn't have a valid access_token");
        }

        return new self($handle, $accessToken);
    }
}
