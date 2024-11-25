<?php

namespace MarcoRieser\StatamicInstagram\Modifiers;

use Statamic\Modifiers\Modifier;

class ImageProxy extends Modifier
{
    protected static $handle = 'ig_proxy';

    public function index($value): string
    {
        $extension = pathinfo(parse_url($value, PHP_URL_PATH), PATHINFO_EXTENSION);
        return route('statamic.statamic-instagram.proxy', ['hash' => md5($value), 'extension' => $extension]);
    }
}
