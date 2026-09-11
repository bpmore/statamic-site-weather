<?php

declare(strict_types=1);

use Bpmore\SiteWeather\Band;
use Bpmore\SiteWeather\Contracts\WeatherContributor;
use Bpmore\SiteWeather\Contributors;
use Bpmore\SiteWeather\State;
use Bpmore\SiteWeather\Tests\Fixtures\FakeContributor;
use Bpmore\SiteWeather\Tests\Fixtures\NotAContributor;
use Bpmore\SiteWeather\Tests\Fixtures\RecordingLogger;
use Bpmore\SiteWeather\Tests\Fixtures\ThrowingContributor;
use Bpmore\SiteWeather\Tests\Fixtures\UnresolvableContributor;
use Psr\Log\LoggerInterface;

beforeEach(function () {
    $this->log = new RecordingLogger;
    $this->app->instance(LoggerInterface::class, $this->log);
});

/** Bind an instance under a name and tag it, the way a product's provider would tag a class. */
function tagInstance(string $abstract, object $instance): void
{
    app()->instance($abstract, $instance);
    app()->tag($abstract, WeatherContributor::TAG);
}

function contributors(): Contributors
{
    return app(Contributors::class);
}

it('is a singleton resolved from the container', function () {
    expect(contributors())->toBe(contributors());
});

it('exposes the tag name products register against', function () {
    expect(WeatherContributor::TAG)->toBe('site-weather.contributors');
});

it('finds nothing when nothing is tagged', function () {
    expect(contributors()->all())->toBe([])
        ->and(contributors()->bands())->toBe([])
        ->and($this->log->records)->toBe([]);
});

it('discovers a tagged contributor by class name', function () {
    app()->tag(ThrowingContributor::class, WeatherContributor::TAG);

    $all = contributors()->all();

    expect($all)->toHaveKey('throwing')
        ->and($all['throwing'])->toBeInstanceOf(ThrowingContributor::class);
});

it('reads every contributor into a band, in registration order', function () {
    tagInstance('fake.accessibility', FakeContributor::measured('accessibility', 'Accessibility', State::Storm, '412 open issues', '/cp/a11y-report'));
    tagInstance('fake.freshness', FakeContributor::measured('freshness', 'Freshness', State::Fair, '3% overdue'));
    tagInstance('fake.readability', FakeContributor::unknown('readability', 'Readability'));

    $bands = contributors()->bands();

    expect($bands)->toHaveCount(3)->each->toBeInstanceOf(Band::class)
        ->and(array_map(fn (Band $b) => $b->key, $bands))->toBe(['accessibility', 'freshness', 'readability'])
        ->and($bands[0]->label)->toBe('Accessibility')
        ->and($bands[0]->reading->state)->toBe(State::Storm)
        ->and($bands[0]->reading->headline)->toBe('412 open issues')
        ->and($bands[0]->reading->url)->toBe('/cp/a11y-report')
        ->and($bands[2]->reading->state)->toBe(State::Unknown)
        ->and($this->log->records)->toBe([]);
});

it('ignores a tagged class that does not implement the interface, and says so', function () {
    app()->tag(NotAContributor::class, WeatherContributor::TAG);
    tagInstance('fake.freshness', FakeContributor::measured('freshness', 'Freshness', State::Clear, 'Nothing overdue'));

    expect(array_keys(contributors()->all()))->toBe(['freshness'])
        ->and($this->log->messages('warning'))->toHaveCount(1)
        ->and($this->log->messages('warning')[0])->toContain(NotAContributor::class, 'does not implement');
});

it('keeps the first contributor for a key and logs the duplicate', function () {
    tagInstance('fake.first', FakeContributor::measured('accessibility', 'Accessibility', State::Rain, 'first'));
    tagInstance('fake.second', FakeContributor::measured('accessibility', 'Accessibility (again)', State::Clear, 'second'));

    $bands = contributors()->bands();

    expect($bands)->toHaveCount(1)
        ->and($bands[0]->reading->headline)->toBe('first')
        ->and($this->log->messages('warning'))->toHaveCount(1)
        ->and($this->log->messages('warning')[0])->toContain('band "accessibility"', 'already provided');
});

it('ignores a contributor with an empty key', function () {
    tagInstance('fake.blank', FakeContributor::unknown('  ', 'Nameless'));

    expect(contributors()->all())->toBe([])
        ->and($this->log->messages('warning')[0])->toContain('key() is empty');
});

it('turns a throwing reading into an unknown band without touching the others', function () {
    tagInstance('fake.accessibility', FakeContributor::measured('accessibility', 'Accessibility', State::Storm, '412 open issues'));
    app()->tag(ThrowingContributor::class, WeatherContributor::TAG);
    tagInstance('fake.freshness', FakeContributor::measured('freshness', 'Freshness', State::Fair, '3% overdue'));

    $bands = contributors()->bands();

    expect(array_map(fn (Band $b) => $b->key, $bands))->toBe(['accessibility', 'throwing', 'freshness'])
        ->and($bands[1]->label)->toBe('Throwing')
        ->and($bands[1]->reading->state)->toBe(State::Unknown)
        ->and($bands[1]->reading->headline)->toBe('Failed to report')
        ->and($bands[1]->reading->computedAt)->toBeNull()
        ->and($bands[0]->reading->state)->toBe(State::Storm)
        ->and($bands[2]->reading->state)->toBe(State::Fair);

    $errors = $this->log->messages('error');
    expect($errors)->toHaveCount(1)
        ->and($errors[0])->toContain('band "throwing"', ThrowingContributor::class, 'The stored results are corrupt.')
        ->and($this->log->records[0]['context']['exception'])->toBeInstanceOf(RuntimeException::class);
});

it('keeps what it found when a tagged service cannot be resolved, and logs the stop', function () {
    tagInstance('fake.accessibility', FakeContributor::measured('accessibility', 'Accessibility', State::Clear, 'No open issues'));
    app()->tag(UnresolvableContributor::class, WeatherContributor::TAG);
    tagInstance('fake.freshness', FakeContributor::measured('freshness', 'Freshness', State::Fair, '3% overdue'));

    expect(array_keys(contributors()->all()))->toBe(['accessibility']);

    $errors = $this->log->messages('error');
    expect($errors)->toHaveCount(1)
        ->and($errors[0])->toContain('stopped discovering', '1 found before it', 'Missing configuration.');
});

it('reads afresh on every call rather than caching', function () {
    tagInstance('fake.accessibility', FakeContributor::measured('accessibility', 'Accessibility', State::Clear, 'No open issues'));

    expect(contributors()->bands())->toHaveCount(1);

    tagInstance('fake.freshness', FakeContributor::measured('freshness', 'Freshness', State::Fair, '3% overdue'));

    expect(contributors()->bands())->toHaveCount(2);
});
