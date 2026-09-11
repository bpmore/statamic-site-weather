<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather;

use Statamic\Providers\AddonServiceProvider;

/**
 * Site Weather computes nothing. It reads what other addons have stored and
 * renders it, so there are no routes, no commands and no storage. The
 * widget arrives in Phase 3.
 */
class ServiceProvider extends AddonServiceProvider
{
    protected $viewNamespace = 'site-weather';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(Contributors::class);
    }
}
