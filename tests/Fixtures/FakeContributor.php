<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather\Tests\Fixtures;

use Bpmore\SiteWeather\Contracts\WeatherContributor;
use Bpmore\SiteWeather\Reading;
use Bpmore\SiteWeather\State;
use DateTimeImmutable;

/** A contributor that reports whatever it was built with. */
final class FakeContributor implements WeatherContributor
{
    public function __construct(
        private string $key,
        private string $label,
        private Reading $reading,
    ) {}

    public static function measured(string $key, string $label, State $state, string $headline, ?string $url = null): self
    {
        return new self($key, $label, new Reading($state, $headline, $url, new DateTimeImmutable('2026-09-11 09:00:00')));
    }

    public static function unknown(string $key, string $label, string $headline = 'Not measured yet'): self
    {
        return new self($key, $label, Reading::unknown($headline));
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function reading(): Reading
    {
        return $this->reading;
    }
}
