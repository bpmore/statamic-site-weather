<?php

declare(strict_types=1);

use Bpmore\SiteWeather\State;

it('is backed by the six lowercase state names', function () {
    expect(array_map(fn (State $s) => $s->value, State::cases()))
        ->toBe(['clear', 'fair', 'overcast', 'rain', 'storm', 'unknown']);
});

it('labels every state in plain words', function () {
    expect(State::Clear->label())->toBe('Clear')
        ->and(State::Fair->label())->toBe('Fair')
        ->and(State::Overcast->label())->toBe('Overcast')
        ->and(State::Rain->label())->toBe('Rain')
        ->and(State::Storm->label())->toBe('Storm')
        ->and(State::Unknown->label())->toBe('Unknown');
});

it('treats every state but Unknown as measured', function () {
    foreach (State::cases() as $state) {
        expect($state->isMeasured())->toBe($state !== State::Unknown, $state->name);
    }
});

it('orders the measured states from Clear to Storm', function () {
    $scale = [State::Clear, State::Fair, State::Overcast, State::Rain, State::Storm];

    foreach ($scale as $i => $state) {
        expect($state->severity())->toBe($i);

        foreach ($scale as $j => $other) {
            expect($state->isWorseThan($other))->toBe($i > $j, "{$state->name} vs {$other->name}");
        }
    }
});

it('refuses to give Unknown a severity', function () {
    expect(fn () => State::Unknown->severity())->toThrow(LogicException::class);
});

it('never ranks Unknown against anything, in either direction', function () {
    foreach (State::cases() as $state) {
        expect(State::Unknown->isWorseThan($state))->toBeFalse("Unknown vs {$state->name}")
            ->and($state->isWorseThan(State::Unknown))->toBeFalse("{$state->name} vs Unknown");
    }
});

it('picks the worst of the measured states', function () {
    expect(State::worst([State::Clear, State::Storm, State::Fair]))->toBe(State::Storm)
        ->and(State::worst([State::Fair, State::Clear]))->toBe(State::Fair)
        ->and(State::worst([State::Clear]))->toBe(State::Clear)
        ->and(State::worst([State::Rain, State::Rain]))->toBe(State::Rain);
});

it('skips Unknown when picking the worst', function () {
    expect(State::worst([State::Unknown, State::Clear, State::Unknown]))->toBe(State::Clear)
        ->and(State::worst([State::Overcast, State::Unknown]))->toBe(State::Overcast);
});

it('has no worst when nothing was measured', function () {
    expect(State::worst([]))->toBeNull()
        ->and(State::worst([State::Unknown]))->toBeNull()
        ->and(State::worst([State::Unknown, State::Unknown]))->toBeNull();
});

it('accepts any iterable, not just arrays', function () {
    $states = (function () {
        yield State::Fair;
        yield State::Unknown;
        yield State::Rain;
    })();

    expect(State::worst($states))->toBe(State::Rain);
});
