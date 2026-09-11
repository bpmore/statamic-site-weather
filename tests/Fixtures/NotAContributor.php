<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather\Tests\Fixtures;

/** Tagged by mistake: does not implement the interface. */
final class NotAContributor
{
    public function key(): string
    {
        return 'impostor';
    }
}
