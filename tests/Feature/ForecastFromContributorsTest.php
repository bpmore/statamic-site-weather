<?php

declare(strict_types=1);

use Bpmore\SiteWeather\Contracts\WeatherContributor;
use Bpmore\SiteWeather\Contributors;
use Bpmore\SiteWeather\Forecast;
use Bpmore\SiteWeather\State;
use Bpmore\SiteWeather\Tests\Fixtures\FakeContributor;
use Bpmore\SiteWeather\Tests\Fixtures\ThrowingContributor;

it('builds the forecast from whatever is tagged, failures included', function () {
    app()->instance('fake.accessibility', FakeContributor::measured('accessibility', 'Accessibility', State::Storm, '412 open issues', '/cp/a11y-report'));
    app()->tag(['fake.accessibility', ThrowingContributor::class], WeatherContributor::TAG);

    $forecast = Forecast::from(app(Contributors::class));

    expect($forecast->overall())->toBe(State::Storm)
        ->and($forecast->worstBand()?->key)->toBe('accessibility')
        ->and($forecast->unknownCount())->toBe(1)
        ->and($forecast->summary())->toBe('Overall: storm. Accessibility: storm, 412 open issues. Throwing: unknown.');
});

it('is empty when nothing is tagged', function () {
    $forecast = Forecast::from(app(Contributors::class));

    expect($forecast->isEmpty())->toBeTrue()
        ->and($forecast->summary())->toBe('Nothing reporting yet.');
});
