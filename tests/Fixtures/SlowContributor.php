<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather\Tests\Fixtures;

use Bpmore\SiteWeather\Contracts\WeatherContributor;
use Bpmore\SiteWeather\Reading;
use Bpmore\SiteWeather\State;
use DateTimeImmutable;

/**
 * A contributor that breaks the contract's first rule and does work on
 * request. Exists so the cost of that can be measured, not excused.
 */
final class SlowContributor implements WeatherContributor
{
    public function __construct(private int $milliseconds) {}

    public function key(): string
    {
        return 'slow';
    }

    public function label(): string
    {
        return 'Slow';
    }

    public function reading(): Reading
    {
        usleep($this->milliseconds * 1000);

        return new Reading(State::Rain, "Took {$this->milliseconds} ms", null, new DateTimeImmutable);
    }
}
