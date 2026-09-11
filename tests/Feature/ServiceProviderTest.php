<?php

declare(strict_types=1);

use Bpmore\SiteWeather\ServiceProvider;
use Statamic\Facades\Addon;

// AddonTestCase builds the addon manifest from composer.json itself, carrying
// id, slug, namespace and provider - not extra.statamic.name - so the display
// name is not something this harness can assert.
it('boots as a registered Statamic addon', function () {
    expect(app()->getProvider(ServiceProvider::class))->toBeInstanceOf(ServiceProvider::class);

    $addon = Addon::get('bpmore/statamic-site-weather');

    expect($addon)->not->toBeNull()
        ->and($addon->id())->toBe('bpmore/statamic-site-weather')
        ->and($addon->slug())->toBe('site-weather')
        ->and($addon->namespace())->toBe('Bpmore\SiteWeather');
});
