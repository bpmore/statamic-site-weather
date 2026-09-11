# PROGRESS — Site Weather

**Spec:** `SPEC.md` — hand-written, committed. §9 records the decisions made in the first session.
**Package:** `bpmore/statamic-site-weather` · free · Statamic `^6.0` · PHP `^8.2`
**Rule:** do the next unfinished task only, then update this file, verify the build, and commit.

## Phase 0 — Write the spec
- [x] Read the intended scope, price and positioning — `SPEC.md` was hand-written from the opportunities list (the list itself is not in this repo; see Notes)
- [x] Survey the Statamic marketplace — findings under Notes; nothing does this
- [x] Identify and answer the one architecture-changing question — where the contract lives; answered in `SPEC.md` §9
- [x] `SPEC.md` — what it is, what it is not, data model, hard problems, scale
- [x] Rewrite the phases below as real tasks derived from the spec
- [x] Commit `SPEC.md` and the rewritten `PROGRESS.md`, then stop

## Phase 1 — Foundations
- [x] **1.1 Scaffold the addon.** `composer.json` (`name: bpmore/statamic-site-weather`, `type: statamic-addon`, `statamic/cms: ^6.0`, `php: ^8.2`, PSR-4 `Bpmore\SiteWeather\` → `src/`, `extra.statamic` and `extra.laravel.providers`), `src/ServiceProvider.php` extending `Statamic\Providers\AddonServiceProvider`, Pest with `tests/TestCase.php` extending `Statamic\Testing\AddonTestCase`, `phpunit.xml`, `pint.json`, `.gitignore` (`vendor/`, `.phpunit.cache/`, `build-log.jsonl`), `.github/workflows/tests.yml` running pest and pint. Use `../statamic-a11y-docs` as the reference for the Statamic 6 shape (no `core/`, no database, no `package.json` — this addon has no JS build). One smoke test: the service provider boots. Then wire the host site: add a `path` repository for `../statamic-site-weather` to `statamic-dev/composer.json` (there is none yet — only `a11y-docs`), `composer require bpmore/statamic-site-weather:@dev` there, confirm `php please addons:discover` lists it. Remove the "Current state" paragraph from `CLAUDE.md`. **Done when:** `vendor/bin/pest` and `vendor/bin/pint --test` pass here, `composer show statamic/cms` shows 6.x here *and* in the host site, host site boots.
- [x] **1.2 Confirm constraints.** `composer.json` says `statamic/cms: ^6.0` and `php: ^8.2` — not `^5.0`, not `^8.4` — and `composer.lock` resolved `statamic/cms` to 6.x. Record the resolved versions under Notes. (Verification only; fold into the 1.1 commit if done in the same run.)

*No migrations or models: Site Weather stores nothing (SPEC §2).*

## Phase 2 — Core: the contract and aggregation
- [x] **2.1 `State` enum.** `Bpmore\SiteWeather\State`: `Clear | Fair | Overcast | Rain | Storm | Unknown` (string-backed, values `clear|fair|overcast|rain|storm|unknown`). `label(): string` (plain words), `severity(): int` for the five measured states, `isMeasured(): bool` (false only for Unknown), `isWorseThan(State): bool`, and `static worst(iterable<State>): ?State` over measured states only. Unit tests for the ordering and that `Unknown` never wins or loses a comparison.
- [x] **2.2 `Reading` value object.** `Bpmore\SiteWeather\Reading` — readonly: `State $state`, `string $headline`, `?string $url`, `?CarbonImmutable $computedAt`. Named constructor `Reading::unknown(string $headline = 'Not measured yet', ?string $url = null)` with `computedAt = null`. Invariant: a measured state requires a non-null `computedAt` (throw on construction otherwise) — never imply health you haven't dated. Tests.
- [x] **2.3 `WeatherContributor` interface and discovery.** `Bpmore\SiteWeather\Contracts\WeatherContributor` (`key()`, `label()`, `reading()`), with a docblock stating the contract: `reading()` reads stored results and never computes; return `Reading::unknown()` when nothing has been measured. Public tag name `site-weather.contributors` (constant on the interface). `Bpmore\SiteWeather\Contributors` (singleton) resolves `app()->tagged('site-weather.contributors')` lazily, skips and logs anything not implementing the interface, dedupes by `key()` (first wins, logged), and wraps each `reading()` in try/catch → `Reading::unknown('Failed to report')` plus a logged error. `tests/Fixtures/FakeContributor` (configurable key/label/reading) and `ThrowingContributor`. Tests: discovers a tagged contributor; ignores a tagged non-implementer; throwing contributor yields unknown and does not break the others; none tagged → empty.
- [x] **2.4 `Forecast` aggregator.** `Bpmore\SiteWeather\Forecast::from(Contributors)` → `Band[]` (key, label, reading), `overall(): ?State`, `unknownCount(): int`, `worstBand(): ?Band`, `isEmpty(): bool`, `summary(): string` producing exactly the screen-reader sentence from SPEC §5 (`Overall: storm. Accessibility: storm, 412 open issues. Freshness: fair.`) with unknown bands appended as `Readability: unknown.` Rules (SPEC §9): overall = worst *measured* band; all bands unknown → `Unknown`; no bands → empty (not unknown). Tests for each rule and for the summary string.

## Phase 3 — Control panel
- [ ] **3.1 The widget, Blade, text-first.** `Bpmore\SiteWeather\Widgets\SiteWeather extends Statamic\Widgets\Widget`, registered in the ServiceProvider `$widgets`, handle `site_weather`. Generate one with `php please make:widget --blade` in the host site to copy the Statamic 6 Blade widget shape, then delete it there. View `resources/views/widget.blade.php`: overall state as text label; band strip — each band a link to its `url` (plain text if null) reading "Label: state"; the worst band's headline; a visually-hidden `Forecast::summary()`; empty state "Nothing reporting yet." with one sentence on what could report. No colour-only meaning, no animation, no JavaScript. Tests: renders bands and headline with fakes; renders the empty state with none; the summary sentence is in the output. Add `['type' => 'site_weather', 'width' => 100]` to the host site's `config/statamic/cp.php` and confirm it renders on the dashboard.
- [ ] **3.2 Icons and states.** Six distinct inline-SVG shapes, `aria-hidden="true"`, always beside the text label: sun, sun-behind-cloud, cloud, cloud-with-rain, cloud-with-lightning, and a dashed circle for unknown. Each state also gets a colour token, used only as reinforcement. Check contrast in both CP colour schemes. Keyboard focus visible on band links. Tests: each state's icon name appears in the rendered output.
- [ ] **3.3 Audit the widget itself.** Run axe against the host site's dashboard with the widget populated by fakes and with the empty state; fix every finding; confirm nothing moves with `prefers-reduced-motion` unset (there is no animation to reduce). Record the axe result under Notes — this is the product line's own discipline applied to itself.

## Phase 4 — Ship
- [ ] **4.1 README.** What it is, what it never does (compute), the honest empty state, and "Writing a contributor": the interface, the tag snippet for a service provider, the read-don't-compute rule, when to return `unknown`, and that the band's `url` should be the product's own dashboard. Screenshots of a full strip and the empty state. `CHANGELOG.md`, `LICENSE.md` (MIT — it is free).
- [ ] **4.2 Scale check.** Site Weather does no work that scales with entries. Prove the failure modes that do exist: register 20 fake contributors including one that throws and one that is slow; confirm the dashboard renders, the bad band reads unknown, and total time is the sum of contributors — nothing added by the widget. Record timings under Notes.
- [ ] **4.3 Listing copy and tag 1.0.** `LISTING.md` with marketplace copy (free; the funnel framing stays internal — the listing describes what the user sees). `git tag v1.0.0` locally (push is a human step).

## Follow-ups outside this repo — not tasks for this loop
- First real contributor: **A11y Report**, implemented in *that* repo (SPEC §7.5). When it exists, add a task here: verify the real band in the host site.
- Then A11y Docs, Lifecycle, Plain, Constellation, Drift — each in its own repo, each mapping its own severities to a `State`.

## House rules that apply to every product in this line
- Shared vocabulary: weather states are the *presentation* vocabulary; each contributor maps its own severity names to a state and keeps its own finding shape
- Anything that changes a live site is opt-in, off by default, and has a dry-run — n/a here, nothing is changed
- Anything that scans at scale is queued, resumable and concurrency-limited — n/a here, nothing scans
- Anything that reports never overclaims — `unknown` is a first-class state; an empty tile never reads "sunny"
- Anything the product generates for a human to read is itself accessible — Phase 3.3 audits the widget

## Notes
<!-- Record surprises, decisions and blockers here. If a task is wrong or blocked, write why and stop. -->

**2026-09-11 — Phase 0.**
- `statamic-addon-opportunities.md` / `side-quests.md` are not in this repo or anywhere under `~/Herd`; `SPEC.md` was hand-written from them and is treated as the source. Not a blocker.
- Marketplace survey: no addon aggregates content-quality signals into one indicator. Nearest: `spatie/health` (infrastructure checks — disk, DB, queue — one widget per check, configured in `cp.php`; a complement, not an overlap); the Oh Dear integration (uptime/SSL widget, abandoned, Statamic v3 only); Statamic Logbook (log/audit widgets with "24h health cards"). Nothing uses a weather metaphor. Statamic's own widget docs use a `LocalWeather` example widget, which is a coincidence worth avoiding confusion with in the README.
- Architecture decision (SPEC §9): the contract lives in this package; registration is a container tag, so paid products carry no dependency on this package. A separate `bpmore/weather-contract` package was considered and rejected for v1.
- Widget decision: Blade widget (Statamic 6 `make:widget --blade`), not Vue — static tile, no JS build. `npm` steps in `CLAUDE.md` do not apply to v1.
- Host site (`../statamic-dev`) is on `statamic/cms` v6.31.0 and has **no** path repository for this addon yet, despite `CLAUDE.md` — only `a11y-docs`. Task 1.1 adds it.
- `run-build.sh` writes `build-log.jsonl` in this repo; task 1.1's `.gitignore` excludes it.

**2026-09-11 — Phase 1.1 / 1.2 (scaffold).**
- Resolved here: `statamic/cms` v6.32.0, `laravel/framework` 13.31.0, `orchestra/testbench` 11.2.0, `pestphp/pest` 3.8.7, `laravel/pint` 1.32.1, on PHP 8.4.23. Constraints are `statamic/cms: ^6.0`, `php: ^8.2`. The host site is on `statamic/cms` v6.31.0 — its lock file, not a constraint difference.
- `AddonTestCase` builds the addon manifest from `composer.json` itself and carries id, slug, namespace and provider — not `extra.statamic.name` — so `Addon::get(...)->name()` returns the package id under test. The smoke test asserts id, slug and namespace instead.
- `PreventsSavingStacheItemsToDisk` needs `tests/__fixtures__/dev-null/` to already exist (it recreates the leaf, not the parent). A tracked `.gitkeep` provides it; `.gitignore` excludes everything else in that directory.
- Host site wiring: added the `bpmore-site-weather` path repository (symlinked) and `bpmore/statamic-site-weather: @dev` to `../statamic-dev/composer.json`; `php please addons:discover` lists the addon; `php please about` boots; `/` is 200 and `/cp` redirects to login. No scaffold was generated with `make:addon` — the sibling `a11y-docs` layout was used directly, as planned.
- CI mirrors `../statamic-lifecycle`: pest on PHP 8.2–8.4 with `composer update` per version, a Statamic-6 resolution guard, and pint on 8.4. It has not run yet — there is no remote.

**2026-09-11 — Phase 2.1 (`State`).**
- `severity()` throws `LogicException` for `Unknown` rather than returning null or a sentinel: the unmeasured cannot be ranked, and a caller that forgot to check `isMeasured()` should find out loudly, not sort unknown below clear. `isWorseThan()` and `worst()` do the check themselves, so nothing in the aggregator needs to.
- Unit tests live in `tests/Unit/` with no booted Statamic; `Pest.php` binds `TestCase` only to `Feature/`.

**2026-09-11 — Phase 2.2 (`Reading`).**
- The constructor accepts any `DateTimeInterface` for `computedAt` and stores a `CarbonImmutable`, so contributors can pass `now()` or a mutable Carbon without friction; the property is still the immutable type the task named.
- The invariant is one-directional: measured requires a date, Unknown may carry one ("Last scan failed" at a known time is honest and useful). A blank headline is also refused - it is a contributor bug, and in 2.3 it will surface as a logged "Failed to report" rather than an empty line on the tile.

**2026-09-11 — Phase 2.3 (contract and discovery).**
- `Band` (key, label, reading) landed here rather than in 2.4: it is the natural output of "contributor + fault-isolated reading", so `Contributors::bands()` returns `list<Band>` and 2.4 consumes that. `Forecast` still owns overall/summary.
- `Container::tagged()` resolves each service inside a generator, so a contributor whose **constructor** throws ends discovery — a generator cannot resume past a throw. `all()` keeps what it collected, logs an error naming the tag and the count found before the stop, and returns. Anything tagged *after* the broken one is invisible until it is fixed. A `reading()` that throws is the common failure and is fully isolated per band. Both are tested.
- Contributors is a singleton with `Container` and `Psr\Log\LoggerInterface` injected; tests bind a `RecordingLogger` fixture so log messages are asserted exactly rather than via facade spies. Extra fixtures beyond the two planned: `NotAContributor`, `UnresolvableContributor`, `RecordingLogger`.
- Nothing is cached, and there is a test that says so (SPEC §9).

**2026-09-11 — Phase 2.4 (`Forecast`).**
- The summary gives the headline to the **worst band only** — that is what the spec's example sentence does (`Accessibility: storm, 412 open issues. Freshness: fair.`) and it mirrors the tile, which shows one headline. Measured bands come first in registration order, then unknown bands by name, so the gaps are heard last rather than lost. A trailing full stop on a headline is trimmed so the sentence never reads "issues..".
- `worstBand()` is the first registered band in the overall state; null when overall is unknown or empty. The widget needs to handle both nulls (3.1).
- `measuredBands()` / `unknownBands()` were added beyond the task list: `summary()` is built on them and the widget's strip will need the same split.
- Phase 2 is complete: 54 tests, 184 assertions, framework-free where possible.
