<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather\Tests\Fixtures;

use Bpmore\SiteWeather\Contracts\WeatherContributor;
use Bpmore\SiteWeather\Reading;
use RuntimeException;

/** A contributor the container cannot even construct. */
final class UnresolvableContributor implements WeatherContributor
{
    public function __construct()
    {
        throw new RuntimeException('Missing configuration.');
    }

    public function key(): string
    {
        return 'unresolvable';
    }

    public function label(): string
    {
        return 'Unresolvable';
    }

    public function reading(): Reading
    {
        return Reading::unknown();
    }
}
