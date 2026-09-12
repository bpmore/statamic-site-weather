<?php

declare(strict_types=1);

use Bpmore\SiteWeather\Contracts\WeatherContributor;
use Bpmore\SiteWeather\State;
use Bpmore\SiteWeather\Tests\Fixtures\FakeContributor;
use Bpmore\SiteWeather\Tests\Fixtures\SlowContributor;
use Bpmore\SiteWeather\Tests\Fixtures\ThrowingContributor;
use Statamic\Facades\User;
use Statamic\Widgets\Loader;

/**
 * Site Weather does no work that scales with entries: it reads a handful of
 * stored values and renders them. What CAN go wrong is a contributor that
 * throws or one that computes on request, so that is what is measured here.
 */
function tagFakes(int $count): void
{
    $states = [State::Clear, State::Fair, State::Overcast, State::Rain, State::Storm, State::Unknown];

    for ($i = 1; $i <= $count; $i++) {
        $state = $states[$i % count($states)];
        app()->instance("scale.$i", $state->isMeasured()
            ? FakeContributor::measured("band-$i", "Band $i", $state, "Headline $i", "/cp/band-$i")
            : FakeContributor::unknown("band-$i", "Band $i"));
        app()->tag("scale.$i", WeatherContributor::TAG);
    }
}

/** Milliseconds for one widget render through the loader, as the dashboard does it. */
function renderMs(): float
{
    $start = hrtime(true);
    (string) app(Loader::class)->load('site_weather', ['type' => 'site_weather'])->html();

    return (hrtime(true) - $start) / 1e6;
}

function note(string $line): void
{
    if (getenv('SITE_WEATHER_TIMINGS')) {
        fwrite(STDERR, "\n  timing: $line");
    }
}

it('renders the real dashboard with twenty contributors, one throwing and one slow', function () {
    tagFakes(18);
    app()->tag(ThrowingContributor::class, WeatherContributor::TAG);
    app()->instance('scale.slow', new SlowContributor(200));
    app()->tag('scale.slow', WeatherContributor::TAG);

    config(['statamic.cp.widgets' => [['type' => 'site_weather', 'width' => 100]]]);
    $user = tap(User::make()->email('audit@example.com')->makeSuper())->save();

    $response = $this->actingAs($user)->get('/cp/dashboard');

    $response->assertOk();
    $widgets = $response->inertiaPage()['props']['widgets'];
    expect($widgets)->toHaveCount(1);

    $html = $widgets[0]['html'];
    expect(substr_count($html, '<li data-band='))->toBe(20)
        ->and($html)->toContain('data-band="throwing" data-state="unknown"')
        ->and($html)->toContain('Throwing: Unknown')
        ->and($html)->toContain('data-band="slow" data-state="rain"')
        ->and($html)->toContain('Overall: <span data-state="storm">Storm</span>');
});

it('adds nothing of its own on top of what contributors cost', function () {
    tagFakes(18);
    app()->tag(ThrowingContributor::class, WeatherContributor::TAG);
    app()->instance('scale.slow', new SlowContributor(200));
    app()->tag('scale.slow', WeatherContributor::TAG);

    renderMs(); // warm the compiled view; the dashboard's first-ever render pays this once
    $total = min(renderMs(), renderMs(), renderMs());
    $overhead = $total - 200;

    note(sprintf('20 contributors incl. 200 ms sleeper + thrower: %.1f ms total, %.1f ms beyond the sleep', $total, $overhead));

    // The 200 ms is the contributor's; everything else - discovery, twenty
    // readings, aggregation, Blade - is the widget's, and should be a few ms.
    expect($overhead)->toBeGreaterThanOrEqual(0)->toBeLessThan(100);
});

it('costs about the same for twenty bands as for one', function () {
    app()->instance('scale.one', FakeContributor::measured('one', 'One', State::Clear, 'Fine', '/cp/one'));
    app()->tag('scale.one', WeatherContributor::TAG);
    renderMs();
    $one = min(renderMs(), renderMs(), renderMs());

    tagFakes(19);
    renderMs();
    $twenty = min(renderMs(), renderMs(), renderMs());

    note(sprintf('1 band: %.2f ms; 20 bands: %.2f ms; per extra band: %.3f ms', $one, $twenty, ($twenty - $one) / 19));

    expect($twenty)->toBeLessThan(50)
        ->and($twenty - $one)->toBeLessThan(40);
});
