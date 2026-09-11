<?php

declare(strict_types=1);

namespace Bpmore\SiteWeather;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * What one contributor reports: a state, a headline in plain words, where to
 * go for detail, and when the underlying measurement was taken.
 *
 * A measured state without a date is refused. "Never imply health you haven't
 * measured" (SPEC §4) is a rule about time as much as about state: a reading
 * is a claim about the site at a moment, and a claim with no moment is not
 * one Site Weather will show.
 */
final readonly class Reading
{
    public ?CarbonImmutable $computedAt;

    /**
     * @param  string  $headline  One line, plain words: "412 open issues", "Nothing overdue".
     * @param  ?string  $url  Where a click goes - the contributing product's own dashboard.
     * @param  ?DateTimeInterface  $computedAt  Required for every measured state.
     */
    public function __construct(
        public State $state,
        public string $headline,
        public ?string $url = null,
        ?DateTimeInterface $computedAt = null,
    ) {
        if (trim($headline) === '') {
            throw new InvalidArgumentException('A reading needs a headline: one line, in plain words.');
        }

        if ($state->isMeasured() && $computedAt === null) {
            throw new InvalidArgumentException(
                "A {$state->value} reading needs a computed_at: a measured state without a date implies health that was never measured."
            );
        }

        $this->computedAt = $computedAt === null ? null : CarbonImmutable::instance($computedAt);
    }

    /**
     * Nothing has been measured. The default headline says so; a contributor
     * that knows more ("Last scan failed") should say that instead.
     */
    public static function unknown(string $headline = 'Not measured yet', ?string $url = null): self
    {
        return new self(State::Unknown, $headline, $url);
    }
}
