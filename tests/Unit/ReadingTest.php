<?php

declare(strict_types=1);

use Bpmore\SiteWeather\Reading;
use Bpmore\SiteWeather\State;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

it('carries a state, headline, url and computed_at', function () {
    $at = new DateTimeImmutable('2026-09-11 10:00:00');

    $reading = new Reading(State::Storm, '412 open issues', '/cp/a11y-report', $at);

    expect($reading->state)->toBe(State::Storm)
        ->and($reading->headline)->toBe('412 open issues')
        ->and($reading->url)->toBe('/cp/a11y-report')
        ->and($reading->computedAt)->toBeInstanceOf(CarbonImmutable::class)
        ->and($reading->computedAt->toDateTimeString())->toBe('2026-09-11 10:00:00');
});

it('normalises any DateTimeInterface to CarbonImmutable', function () {
    $fromMutable = new Reading(State::Clear, 'All good', null, Carbon::parse('2026-01-01 12:00:00'));
    $fromNative = new Reading(State::Clear, 'All good', null, new DateTime('2026-01-01 12:00:00'));

    expect($fromMutable->computedAt)->toBeInstanceOf(CarbonImmutable::class)
        ->and($fromNative->computedAt)->toBeInstanceOf(CarbonImmutable::class)
        ->and($fromMutable->computedAt->equalTo($fromNative->computedAt))->toBeTrue();
});

it('refuses a measured state without a computed_at', function (State $state) {
    expect(fn () => new Reading($state, 'Looks fine'))
        ->toThrow(InvalidArgumentException::class, 'computed_at');
})->with([
    'clear' => State::Clear,
    'fair' => State::Fair,
    'overcast' => State::Overcast,
    'rain' => State::Rain,
    'storm' => State::Storm,
]);

it('allows Unknown without a computed_at', function () {
    $reading = new Reading(State::Unknown, 'Not measured yet');

    expect($reading->state)->toBe(State::Unknown)
        ->and($reading->computedAt)->toBeNull();
});

it('allows Unknown with a computed_at, for a dated failure', function () {
    $reading = new Reading(State::Unknown, 'Last scan failed', null, new DateTimeImmutable('2026-09-10'));

    expect($reading->computedAt?->toDateString())->toBe('2026-09-10');
});

it('builds an Unknown reading with sensible defaults', function () {
    $reading = Reading::unknown();

    expect($reading->state)->toBe(State::Unknown)
        ->and($reading->headline)->toBe('Not measured yet')
        ->and($reading->url)->toBeNull()
        ->and($reading->computedAt)->toBeNull();
});

it('lets an Unknown reading say more and link somewhere', function () {
    $reading = Reading::unknown('Run your first scan', '/cp/a11y-report/scan');

    expect($reading->headline)->toBe('Run your first scan')
        ->and($reading->url)->toBe('/cp/a11y-report/scan');
});

it('refuses a blank headline', function (string $headline) {
    expect(fn () => new Reading(State::Clear, $headline, null, new DateTimeImmutable))
        ->toThrow(InvalidArgumentException::class, 'headline');
})->with(['empty' => '', 'whitespace' => "  \t\n"]);

it('is immutable', function () {
    $reading = Reading::unknown();

    expect(fn () => $reading->headline = 'changed')->toThrow(Error::class);
});
