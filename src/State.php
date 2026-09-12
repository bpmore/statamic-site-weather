<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather;

use LogicException;

/**
 * The six states a band, or the whole site, can be in.
 *
 * Five are measured and ordered: Clear is best, Storm is worst. Unknown is
 * not on that scale at all. It means nothing has been measured, and it is
 * never ranked against a state that has been (SPEC §9) - neither better nor
 * worse than Clear, just absent.
 */
enum State: string
{
    case Clear = 'clear';
    case Fair = 'fair';
    case Overcast = 'overcast';
    case Rain = 'rain';
    case Storm = 'storm';
    case Unknown = 'unknown';

    /** Plain words, always shown beside the icon - never colour alone. */
    public function label(): string
    {
        return match ($this) {
            self::Clear => 'Clear',
            self::Fair => 'Fair',
            self::Overcast => 'Overcast',
            self::Rain => 'Rain',
            self::Storm => 'Storm',
            self::Unknown => 'Unknown',
        };
    }

    /**
     * The shape that stands for this state, always drawn beside the label -
     * six silhouettes a person can tell apart with no colour at all.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Clear => 'sun',
            self::Fair => 'sun-behind-cloud',
            self::Overcast => 'cloud',
            self::Rain => 'cloud-rain',
            self::Storm => 'cloud-lightning',
            self::Unknown => 'dashed-circle',
        };
    }

    public function isMeasured(): bool
    {
        return $this !== self::Unknown;
    }

    /**
     * Position on the measured scale, 0 (Clear) to 4 (Storm).
     *
     * @throws LogicException for Unknown, which has no position on it. Check
     *                        isMeasured() first; ranking the unmeasured is the
     *                        mistake this whole product exists to avoid.
     */
    public function severity(): int
    {
        return match ($this) {
            self::Clear => 0,
            self::Fair => 1,
            self::Overcast => 2,
            self::Rain => 3,
            self::Storm => 4,
            self::Unknown => throw new LogicException('Unknown has no severity: nothing has been measured.'),
        };
    }

    /** False whenever either side is Unknown: absence is not a rank. */
    public function isWorseThan(State $other): bool
    {
        if (! $this->isMeasured() || ! $other->isMeasured()) {
            return false;
        }

        return $this->severity() > $other->severity();
    }

    /**
     * The worst of the measured states given, or null when none was measured.
     * Unknown states are skipped, not counted - the caller surfaces those
     * separately.
     *
     * @param  iterable<State>  $states
     */
    public static function worst(iterable $states): ?State
    {
        $worst = null;

        foreach ($states as $state) {
            if (! $state->isMeasured()) {
                continue;
            }

            if ($worst === null || $state->isWorseThan($worst)) {
                $worst = $state;
            }
        }

        return $worst;
    }
}
