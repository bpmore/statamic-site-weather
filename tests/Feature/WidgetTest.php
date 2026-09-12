<?php

declare(strict_types=1);

use Bpmore\SiteWeather\Contracts\WeatherContributor;
use Bpmore\SiteWeather\State;
use Bpmore\SiteWeather\Tests\Fixtures\FakeContributor;
use Bpmore\SiteWeather\Tests\Fixtures\ThrowingContributor;
use Bpmore\SiteWeather\Widgets\SiteWeather;
use Statamic\Widgets\Loader;

function tagFake(string $abstract, FakeContributor $contributor): void
{
    app()->instance($abstract, $contributor);
    app()->tag($abstract, WeatherContributor::TAG);
}

/** Render the widget the way the dashboard does: through the loader, cast to string. */
function renderWidget(): string
{
    return (string) app(Loader::class)->load('site_weather', ['type' => 'site_weather'])->html();
}

it('is registered under the handle site_weather', function () {
    expect(SiteWeather::handle())->toBe('site_weather')
        ->and(app('statamic.widgets')->get('site_weather'))->toBe(SiteWeather::class);
});

it('wraps everything in a ui-widget and keeps Vue out of the content', function () {
    $html = renderWidget();

    expect($html)->toContain('<ui-widget title="Site Weather">')
        ->and($html)->toMatch('/<div v-pre class="site-weather /');
});

it('shows the empty state, and never says sunny, when nothing reports', function () {
    $html = renderWidget();

    expect($html)->toContain('Nothing reporting yet.')
        ->and($html)->toContain('each add a band when installed')
        ->and($html)->toContain('<a href="https://github.com/bpmore/statamic-site-weather#writing-a-contributor"')
        ->and($html)->not->toContain('Overall:')
        ->and($html)->not->toContain('Clear')
        ->and($html)->not->toContain('<ul');
});

it('shows the overall state, the worst headline, and a band strip', function () {
    tagFake('fake.accessibility', FakeContributor::measured('accessibility', 'Accessibility', State::Storm, '412 open issues', '/cp/a11y-report'));
    tagFake('fake.freshness', FakeContributor::measured('freshness', 'Freshness', State::Fair, '3% overdue', '/cp/lifecycle'));

    $html = renderWidget();

    expect($html)->toContain('Overall: <span data-state="storm">Storm</span>')
        ->and($html)->toContain('Accessibility: 412 open issues')
        ->and($html)->toMatch('~<a href="/cp/a11y-report" class="[^"]*underline[^"]*">\s*<svg[^>]*data-icon="cloud-lightning"[^>]*>.*?</svg>\s*<span>Accessibility: Storm</span>\s*</a>~s')
        ->and($html)->toMatch('~<a href="/cp/lifecycle" class="[^"]*underline[^"]*">\s*<svg[^>]*data-icon="sun-behind-cloud"[^>]*>.*?</svg>\s*<span>Freshness: Fair</span>\s*</a>~s')
        ->and($html)->toContain('data-band="accessibility" data-state="storm"')
        ->and($html)->toContain('data-band="freshness" data-state="fair"')
        ->and($html)->not->toContain('Nothing reporting yet');
});

it('puts the screen-reader summary first, visually hidden', function () {
    tagFake('fake.accessibility', FakeContributor::measured('accessibility', 'Accessibility', State::Storm, '412 open issues'));
    tagFake('fake.freshness', FakeContributor::measured('freshness', 'Freshness', State::Fair, '3% overdue'));

    $html = renderWidget();

    expect($html)->toContain('<p class="sr-only">Overall: storm. Accessibility: storm, 412 open issues. Freshness: fair.</p>')
        ->and(strpos($html, 'class="sr-only"'))->toBeLessThan(strpos($html, 'site-weather-overall'));
});

it('renders a band without a url as plain text, not a link', function () {
    tagFake('fake.freshness', FakeContributor::measured('freshness', 'Freshness', State::Clear, 'Nothing overdue'));

    $html = renderWidget();

    expect($html)->toMatch('~<span class="[^"]*text-gray-700[^"]*">\s*<svg[^>]*data-icon="sun"[^>]*>.*?</svg>\s*<span>Freshness: Clear</span>\s*</span>~s')
        ->and($html)->not->toContain('<a href');
});

it('reads unknown overall with no headline when nothing has been measured', function () {
    tagFake('fake.readability', FakeContributor::unknown('readability', 'Readability'));

    $html = renderWidget();

    expect($html)->toContain('Overall: <span data-state="unknown">Unknown</span>')
        ->and($html)->toContain('Nothing measured yet.')
        ->and($html)->toContain('Readability: Unknown')
        ->and($html)->toContain('<p class="sr-only">Overall: unknown. Readability: unknown.</p>');
});

it('still renders when a contributor throws', function () {
    tagFake('fake.accessibility', FakeContributor::measured('accessibility', 'Accessibility', State::Clear, 'No open issues'));
    app()->tag(ThrowingContributor::class, WeatherContributor::TAG);

    $html = renderWidget();

    expect($html)->toContain('Overall: <span data-state="clear">Clear</span>')
        ->and($html)->toContain('Throwing: Unknown')
        ->and($html)->toContain('Overall: clear. Accessibility: clear, No open issues. Throwing: unknown.');
});

it('escapes what contributors say', function () {
    tagFake('fake.evil', FakeContributor::measured('evil', '<b>Evil</b>', State::Rain, '{{ 1 + 1 }} <script>alert(1)</script>', '/cp/x" onmouseover="alert(1)'));

    $html = renderWidget();

    expect($html)->not->toContain('<b>Evil</b>')
        ->and($html)->not->toContain('<script>')
        ->and($html)->not->toContain('onmouseover="alert')
        ->and($html)->toContain('&lt;b&gt;Evil&lt;/b&gt;')
        ->and($html)->toContain('{{ 1 + 1 }} &lt;script&gt;');
});

it('has no javascript and no animation', function () {
    tagFake('fake.accessibility', FakeContributor::measured('accessibility', 'Accessibility', State::Storm, '412 open issues'));

    $html = renderWidget();

    expect($html)->not->toContain('<script')
        ->and($html)->not->toContain('animate')
        ->and($html)->not->toContain('transition')
        ->and($html)->not->toMatch('/\son[a-z]+=/i');
});

it('draws each state as its own shape beside the label', function (State $state, string $icon, string $colour) {
    $contributor = $state->isMeasured()
        ? FakeContributor::measured('band', 'Band', $state, 'Headline', '/cp/band')
        : FakeContributor::unknown('band', 'Band');
    tagFake('fake.band', $contributor);

    $html = renderWidget();

    // The overall icon (size-8) and the band's icon (size-4) both carry the shape and the colour.
    expect($html)->toMatch('~<svg[^>]*data-icon="'.$icon.'"[^>]*class="size-8 shrink-0 '.$colour.'"~')
        ->and($html)->toMatch('~<svg[^>]*data-icon="'.$icon.'"[^>]*class="size-4 shrink-0 '.$colour.'"[^>]*>.*?</svg>\\s*<span>Band: '.$state->label().'</span>~s');
})->with([
    'clear' => [State::Clear, 'sun', 'text-amber-600'],
    'fair' => [State::Fair, 'sun-behind-cloud', 'text-amber-600'],
    'overcast' => [State::Overcast, 'cloud', 'text-gray-500'],
    'rain' => [State::Rain, 'cloud-rain', 'text-blue-500'],
    'storm' => [State::Storm, 'cloud-lightning', 'text-red-600'],
    'unknown' => [State::Unknown, 'dashed-circle', 'text-gray-500'],
]);

it('keeps every icon decorative and unfocusable, one per label', function () {
    tagFake('fake.accessibility', FakeContributor::measured('accessibility', 'Accessibility', State::Storm, '412 open issues', '/cp/a11y-report'));
    tagFake('fake.freshness', FakeContributor::measured('freshness', 'Freshness', State::Fair, '3% overdue'));
    tagFake('fake.readability', FakeContributor::unknown('readability', 'Readability'));

    $html = renderWidget();

    preg_match_all('~<svg\\b[^>]*>~', $html, $svgs);

    expect($svgs[0])->toHaveCount(4) // one overall, three bands
        ->each->toContain('aria-hidden="true"', 'focusable="false"', 'stroke="currentColor"');

    expect($html)->not->toContain('<title')
        ->and($html)->not->toContain('role="img"');
});
