<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather\Tests\Fixtures;

use Bpmore\SiteWeather\Contracts\WeatherContributor;
use Bpmore\SiteWeather\Reading;
use RuntimeException;

/** A contributor whose reading() blows up, as a real one might. */
final class ThrowingContributor implements WeatherContributor
{
    public function key(): string
    {
        return 'throwing';
    }

    public function label(): string
    {
        return 'Throwing';
    }

    public function reading(): Reading
    {
        throw new RuntimeException('The stored results are corrupt.');
    }
}
