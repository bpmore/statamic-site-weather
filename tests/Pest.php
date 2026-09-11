<?php

declare(strict_types=1);

use Bpmore\SiteWeather\Tests\TestCase;

// Only tests that need a booted Statamic live in Feature. The contract,
// aggregation and value objects are framework-free and go in Unit.
uses(TestCase::class)->in('Feature');
