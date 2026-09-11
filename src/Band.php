<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather;

/**
 * One contributor's place on the tile: who it is and what it reported. The
 * reading is already fault-isolated - a contributor that threw is here with
 * an unknown reading, not missing.
 */
final readonly class Band
{
    public function __construct(
        public string $key,
        public string $label,
        public Reading $reading,
    ) {}
}
