<?php

declare(strict_types=1);

use Bpmore\SiteWeather\Band;
use Bpmore\SiteWeather\Forecast;
use Bpmore\SiteWeather\Reading;
use Bpmore\SiteWeather\State;

function forecastBand(string $key, string $label, State $state, string $headline = 'Something'): Band
{
    $reading = $state->isMeasured()
        ? new Reading($state, $headline, null, new DateTimeImmutable('2026-09-11 09:00:00'))
        : Reading::unknown($headline === 'Something' ? 'Not measured yet' : $headline);

    return new Band($key, $label, $reading);
}

describe('with nothing reporting', function () {
    it('is empty, with no overall and no worst band', function () {
        $forecast = new Forecast([]);

        expect($forecast->isEmpty())->toBeTrue()
            ->and($forecast->overall())->toBeNull()
            ->and($forecast->worstBand())->toBeNull()
            ->and($forecast->unknownCount())->toBe(0)
            ->and($forecast->bands)->toBe([]);
    });

    it('never says sunny', function () {
        expect((new Forecast([]))->summary())->toBe('Nothing reporting yet.');
    });
});

describe('overall', function () {
    it('is clear only when every band is clear', function () {
        $forecast = new Forecast([
            forecastBand('accessibility', 'Accessibility', State::Clear, 'No open issues'),
            forecastBand('freshness', 'Freshness', State::Clear, 'Nothing overdue'),
        ]);

        expect($forecast->overall())->toBe(State::Clear)
            ->and($forecast->isEmpty())->toBeFalse();
    });

    it('is the worst band, not the average', function () {
        $forecast = new Forecast([
            forecastBand('accessibility', 'Accessibility', State::Clear),
            forecastBand('documents', 'Documents', State::Clear),
            forecastBand('freshness', 'Freshness', State::Storm, '61% overdue'),
            forecastBand('readability', 'Readability', State::Clear),
            forecastBand('structure', 'Structure', State::Clear),
        ]);

        expect($forecast->overall())->toBe(State::Storm);
    });

    it('is the worst measured band when some are unknown', function () {
        $forecast = new Forecast([
            forecastBand('accessibility', 'Accessibility', State::Unknown),
            forecastBand('freshness', 'Freshness', State::Fair),
            forecastBand('readability', 'Readability', State::Unknown),
        ]);

        expect($forecast->overall())->toBe(State::Fair)
            ->and($forecast->unknownCount())->toBe(2);
    });

    it('is unknown when every band is unknown', function () {
        $forecast = new Forecast([
            forecastBand('accessibility', 'Accessibility', State::Unknown),
            forecastBand('readability', 'Readability', State::Unknown),
        ]);

        expect($forecast->overall())->toBe(State::Unknown)
            ->and($forecast->isEmpty())->toBeFalse()
            ->and($forecast->unknownCount())->toBe(2);
    });
});

describe('worst band', function () {
    it('is the band in the overall state', function () {
        $forecast = new Forecast([
            forecastBand('accessibility', 'Accessibility', State::Fair),
            forecastBand('freshness', 'Freshness', State::Rain, '30% overdue'),
        ]);

        expect($forecast->worstBand()?->key)->toBe('freshness')
            ->and($forecast->worstBand()?->reading->headline)->toBe('30% overdue');
    });

    it('is the first registered when several share the worst state', function () {
        $forecast = new Forecast([
            forecastBand('accessibility', 'Accessibility', State::Storm, 'first'),
            forecastBand('freshness', 'Freshness', State::Storm, 'second'),
        ]);

        expect($forecast->worstBand()?->key)->toBe('accessibility');
    });

    it('is nothing when nothing has been measured', function () {
        $forecast = new Forecast([forecastBand('accessibility', 'Accessibility', State::Unknown)]);

        expect($forecast->worstBand())->toBeNull();
    });
});

describe('bands', function () {
    it('keeps registration order and splits measured from unknown', function () {
        $forecast = new Forecast([
            forecastBand('accessibility', 'Accessibility', State::Unknown),
            forecastBand('freshness', 'Freshness', State::Fair),
            forecastBand('readability', 'Readability', State::Unknown),
            forecastBand('structure', 'Structure', State::Clear),
        ]);

        $keys = fn (array $bands) => array_map(fn (Band $b) => $b->key, $bands);

        expect($keys($forecast->bands))->toBe(['accessibility', 'freshness', 'readability', 'structure'])
            ->and($keys($forecast->measuredBands()))->toBe(['freshness', 'structure'])
            ->and($keys($forecast->unknownBands()))->toBe(['accessibility', 'readability']);
    });

    it('accepts any iterable', function () {
        $bands = (function () {
            yield forecastBand('accessibility', 'Accessibility', State::Clear);
        })();

        expect((new Forecast($bands))->bands)->toHaveCount(1);
    });
});

describe('summary', function () {
    it('reads exactly as the spec has it', function () {
        $forecast = new Forecast([
            forecastBand('accessibility', 'Accessibility', State::Storm, '412 open issues'),
            forecastBand('freshness', 'Freshness', State::Fair, '3% overdue'),
        ]);

        expect($forecast->summary())->toBe('Overall: storm. Accessibility: storm, 412 open issues. Freshness: fair.');
    });

    it('names the unknown bands at the end', function () {
        $forecast = new Forecast([
            forecastBand('accessibility', 'Accessibility', State::Storm, '412 open issues'),
            forecastBand('documents', 'Documents', State::Unknown),
            forecastBand('freshness', 'Freshness', State::Fair, '3% overdue'),
            forecastBand('readability', 'Readability', State::Unknown, 'Failed to report'),
        ]);

        expect($forecast->summary())->toBe(
            'Overall: storm. Accessibility: storm, 412 open issues. Freshness: fair. Documents: unknown. Readability: unknown.'
        );
    });

    it('gives the headline to the worst band only, even on a clear day', function () {
        $forecast = new Forecast([
            forecastBand('accessibility', 'Accessibility', State::Clear, 'No open issues'),
            forecastBand('freshness', 'Freshness', State::Clear, 'Nothing overdue'),
        ]);

        expect($forecast->summary())->toBe('Overall: clear. Accessibility: clear, No open issues. Freshness: clear.');
    });

    it('says unknown overall when nothing has been measured', function () {
        $forecast = new Forecast([
            forecastBand('accessibility', 'Accessibility', State::Unknown),
            forecastBand('readability', 'Readability', State::Unknown),
        ]);

        expect($forecast->summary())->toBe('Overall: unknown. Accessibility: unknown. Readability: unknown.');
    });

    it('does not double a full stop a contributor added to its headline', function () {
        $forecast = new Forecast([forecastBand('accessibility', 'Accessibility', State::Rain, '12 open issues.')]);

        expect($forecast->summary())->toBe('Overall: rain. Accessibility: rain, 12 open issues.');
    });
});
