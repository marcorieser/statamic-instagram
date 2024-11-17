<?php

namespace Uchueli\StatamicInstagram\Tests;

use Uchueli\StatamicInstagram\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
