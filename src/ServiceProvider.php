<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather;

use Statamic\Providers\AddonServiceProvider;

/**
 * Site Weather computes nothing. It reads what other addons have stored and
 * renders it, so this provider has nothing to register yet: no routes, no
 * commands, no storage. The widget arrives in Phase 3.
 */
class ServiceProvider extends AddonServiceProvider
{
    protected $viewNamespace = 'site-weather';
}
