<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather\Tests;

use Bpmore\SiteWeather\ServiceProvider;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

/**
 * Boots a real Statamic through Orchestra Testbench.
 *
 * Site Weather stores nothing itself, but the Stache is still live in a booted
 * Statamic; PreventsSavingStacheItemsToDisk keeps one test's content out of the
 * next.
 */
abstract class TestCase extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;
}
