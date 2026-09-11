<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather;

/**
 * The whole tile, worked out from its bands.
 *
 * Overall weather is the worst measured band, not the average: one storm is
 * a storm, and averaging hides exactly what the tile exists to surface
 * (SPEC §5). Unknown bands are counted and named, never ranked. No bands at
 * all is the empty state, which is distinct from unknown: nothing is
 * reporting, as opposed to something reporting that it has not measured.
 */
final readonly class Forecast
{
    /** @var list<Band> */
    public array $bands;

    /** @param  iterable<Band>  $bands  In registration order. */
    public function __construct(iterable $bands)
    {
        $this->bands = is_array($bands) ? array_values($bands) : iterator_to_array($bands, false);
    }

    public static function from(Contributors $contributors): self
    {
        return new self($contributors->bands());
    }

    public function isEmpty(): bool
    {
        return $this->bands === [];
    }

    /**
     * Null when nothing is reporting; Unknown when bands exist but none has
     * measured anything; otherwise the worst measured state.
     */
    public function overall(): ?State
    {
        if ($this->isEmpty()) {
            return null;
        }

        return State::worst(array_map(fn (Band $band) => $band->reading->state, $this->bands))
            ?? State::Unknown;
    }

    /**
     * The first band in the worst measured state - the one whose headline
     * the tile shows. Null when nothing is measured.
     */
    public function worstBand(): ?Band
    {
        $overall = $this->overall();

        if ($overall === null || ! $overall->isMeasured()) {
            return null;
        }

        foreach ($this->bands as $band) {
            if ($band->reading->state === $overall) {
                return $band;
            }
        }

        return null;
    }

    /** @return list<Band> */
    public function measuredBands(): array
    {
        return array_values(array_filter($this->bands, fn (Band $band) => $band->reading->state->isMeasured()));
    }

    /** @return list<Band> */
    public function unknownBands(): array
    {
        return array_values(array_filter($this->bands, fn (Band $band) => ! $band->reading->state->isMeasured()));
    }

    public function unknownCount(): int
    {
        return count($this->unknownBands());
    }

    /**
     * The tile in one breath, for a screen reader:
     *
     *   Overall: storm. Accessibility: storm, 412 open issues. Freshness: fair. Readability: unknown.
     *
     * Measured bands first, the worst one carrying its headline; unknown bands
     * named at the end so the gaps are heard, not hidden.
     */
    public function summary(): string
    {
        if ($this->isEmpty()) {
            return 'Nothing reporting yet.';
        }

        $worst = $this->worstBand();

        $parts = ['Overall: '.self::word($this->overall()).'.'];

        foreach ([...$this->measuredBands(), ...$this->unknownBands()] as $band) {
            $part = $band->label.': '.self::word($band->reading->state);

            if ($band === $worst) {
                $part .= ', '.rtrim($band->reading->headline, '.');
            }

            $parts[] = $part.'.';
        }

        return implode(' ', $parts);
    }

    /** The state as it reads mid-sentence: "storm", not "Storm". */
    private static function word(State $state): string
    {
        return mb_strtolower($state->label());
    }
}
