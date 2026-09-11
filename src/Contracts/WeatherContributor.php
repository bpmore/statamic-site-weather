<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather\Contracts;

use Bpmore\SiteWeather\Reading;

/**
 * One band on the tile. Implemented by the product that owns the data, never
 * by Site Weather.
 *
 * Register it from a service provider by tagging the class name - a string,
 * so the product needs no import of and no dependency on this package:
 *
 *     $this->app->tag(A11yWeatherContributor::class, 'site-weather.contributors');
 *
 * If Site Weather is not installed nothing resolves the tag, the class is
 * never autoloaded, and the missing interface is never noticed.
 *
 * The contract:
 *
 * - reading() READS. It returns what the product has already stored - the
 *   last scan's result, a cached count - and never scans, crawls or computes.
 *   It runs on every dashboard load for every control-panel user, and Site
 *   Weather cannot enforce this; a slow contributor makes the dashboard slow.
 * - When nothing has been measured, return Reading::unknown(). Never report
 *   clear for a site you have not looked at.
 * - key() is stable and unique across products: a short slug such as
 *   "accessibility". The first contributor registered for a key wins.
 * - Anything thrown from reading() is caught and logged, and the band reads
 *   unknown with the headline "Failed to report". The dashboard never breaks
 *   because of a band.
 */
interface WeatherContributor
{
    public const TAG = 'site-weather.contributors';

    /** A short, stable slug that identifies the band: "accessibility". */
    public function key(): string;

    /** The band's name as people see it: "Accessibility". */
    public function label(): string;

    /** The stored result, read - not computed - now. */
    public function reading(): Reading;
}
