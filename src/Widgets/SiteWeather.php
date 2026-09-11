<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather\Widgets;

use Bpmore\SiteWeather\Contributors;
use Bpmore\SiteWeather\Forecast;
use Illuminate\Contracts\View\View;
use Statamic\Widgets\Widget;

/**
 * The tile. Handle "site_weather"; put it on the dashboard with
 *
 *     'widgets' => [['type' => 'site_weather', 'width' => 100]],
 *
 * in config/statamic/cp.php. Blade, not Vue: a static tile with links needs
 * no component and no build step, so it cannot break when the control
 * panel's asset pipeline changes.
 */
class SiteWeather extends Widget
{
    public function __construct(private Contributors $contributors) {}

    public function html(): View
    {
        return view('site-weather::widget', [
            'forecast' => Forecast::from($this->contributors),
        ]);
    }
}
