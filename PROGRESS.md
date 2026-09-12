ALL TASKS COMPLETE

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
- [x] **3.1 The widget, Blade, text-first.** `Bpmore\SiteWeather\Widgets\SiteWeather extends Statamic\Widgets\Widget`, registered in the ServiceProvider `$widgets`, handle `site_weather`. Generate one with `php please make:widget --blade` in the host site to copy the Statamic 6 Blade widget shape, then delete it there. View `resources/views/widget.blade.php`: overall state as text label; band strip — each band a link to its `url` (plain text if null) reading "Label: state"; the worst band's headline; a visually-hidden `Forecast::summary()`; empty state "Nothing reporting yet." with one sentence on what could report. No colour-only meaning, no animation, no JavaScript. Tests: renders bands and headline with fakes; renders the empty state with none; the summary sentence is in the output. Add `['type' => 'site_weather', 'width' => 100]` to the host site's `config/statamic/cp.php` and confirm it renders on the dashboard.
- [x] **3.2 Icons and states.** Six distinct inline-SVG shapes, `aria-hidden="true"`, always beside the text label: sun, sun-behind-cloud, cloud, cloud-with-rain, cloud-with-lightning, and a dashed circle for unknown. Each state also gets a colour token, used only as reinforcement. Check contrast in both CP colour schemes. Keyboard focus visible on band links. Tests: each state's icon name appears in the rendered output.
- [x] **3.3 Audit the widget itself.** Run axe against the host site's dashboard with the widget populated by fakes and with the empty state; fix every finding; confirm nothing moves with `prefers-reduced-motion` unset (there is no animation to reduce). Record the axe result under Notes — this is the product line's own discipline applied to itself.

## Phase 4 — Ship
- [x] **4.1 README.** What it is, what it never does (compute), the honest empty state, and "Writing a contributor": the interface, the tag snippet for a service provider, the read-don't-compute rule, when to return `unknown`, and that the band's `url` should be the product's own dashboard. Screenshots of a full strip and the empty state. `CHANGELOG.md`, `LICENSE.md` (MIT — it is free).
- [x] **4.2 Scale check.** Site Weather does no work that scales with entries. Prove the failure modes that do exist: register 20 fake contributors including one that throws and one that is slow; confirm the dashboard renders, the bad band reads unknown, and total time is the sum of contributors — nothing added by the widget. Record timings under Notes.
- [x] **4.3 Listing copy and tag 1.0.** `LISTING.md` with marketplace copy (free; the funnel framing stays internal — the listing describes what the user sees). `git tag v1.0.0` locally (push is a human step).

## Phase 5 — The first real band
- [x] **5.1 Verify the first real contributor in the host site.** A11y Docs went first (A11y Report has no repo yet): `Bpmore\StatamicA11yDocs\Weather\DocumentsContributor` on branch `build/site-weather-band` in `../statamic-a11y-docs`. The dev-site tile shows the real Documents band beside the demo bands; the demo provider no longer fakes `documents`.

## Follow-ups outside this repo — not tasks for this loop
- **Lifecycle** (Freshness) — queued as a task in `../statamic-lifecycle/PROGRESS.md` Phase 7, after its utility dashboard exists to link to. **Plain** (Readability) — queued in `../statamic-plain/PROGRESS.md` Phase 3, after its utility screen. Each product's own loop does it; the task text names the A11y Docs example to follow and the demo band to drop from `../statamic-dev/app/Providers/SiteWeatherDemoProvider.php`.
- **A11y Report** (Accessibility), **Constellation** (Structure), **Drift** (Translations) have no repos yet; queue the same task when they are scaffolded. Wayfinding, Delegated and LMS are not bands in the spec.
- Publish this package so the path repositories in the paid products' `require-dev` can become a Packagist constraint.

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

**2026-09-11 — Phase 3.1 (widget).**
- The dashboard compiles each Blade widget's HTML as a **Vue template at runtime** (`DynamicHtmlRenderer` → `defineComponent({ template: html })`). That is what makes `<ui-widget>` work, and it has consequences: the content sits under `v-pre` so a `{{` in a contributor's headline is text, not an expression (tested); no `<style>` or `<script>` can be emitted; and styling comes from the CP's own stylesheet — `sr-only`, spacing and text colours are present, `list-none` is not (inline style instead). If a future Statamic build drops a utility class the styling degrades but the semantics do not.
- `ui-widget` renders its title as a `<span>`, not a heading; the tile's own structure is a visually-hidden summary `<p>` first, then visible overall, headline, and a `<ul>` strip. Nothing visible is `aria-hidden`: sighted screen-reader and magnifier users can read what they see, and the redundancy with the summary is accepted.
- No `make:widget --blade` scaffold was generated; the stub files in `vendor/statamic/cms/src/Console/Commands/stubs/` showed the shape directly.
- Host-site verification: the Chrome extension was not connected, so instead (a) `/cp/dashboard` was rendered as the CP user through the HTTP kernel and the widget pulled from the Inertia page props — 200, `Dashboard`, one widget, our HTML; (b) both the empty and a populated render were compiled with `@vue/compiler-dom` (from `../statamic-a11y-docs/node_modules`) — clean, no interpolation. **A look at it in a real browser is still owed**; 3.3 needs one for axe anyway. `../statamic-dev/config/statamic/cp.php` now lists the widget (that file is outside this repo).
- Empty state has the sentence but not yet the link to "what could report"; the link arrives with the README in 4.1.

**2026-09-11 — Phase 3.2 (icons and states).**
- Six hand-drawn 24px stroke icons in one Blade partial (`partials/state-icon.blade.php`), `aria-hidden` and `focusable="false"`, always beside the text; `State::icon()` names the shape and is what tests assert. Rendered with QuickLook and checked by eye before use.
- **Colour, one token per state, because the CP ships no `dark:` variant for these hues and its `color-scheme` is only ever `light` (so `light-dark()` is out).** Contrast computed from the CP's own oklch tokens against both cards (white; dark gray-850, Y=0.013): amber-600 3.19:1 / 5.21:1 (clear, fair), gray-500 4.84 / 3.44 (overcast, unknown), blue-500 3.76 / 4.42 (rain), red-600 4.76 / 3.49 (storm). All clear 3:1 both ways. amber-500 (2.15 on white) and gray-400 (2.60) were rejected; gray-600+ fails on dark.
- Focus on band links is not styled by this addon: the CP has a global `:focus-visible` rule (2px, blue-400, applies to any element not under a `focus-within` container) whose specificity beats its own stray `a:focus{outline:0}`. Links got `rounded-sm` so that ring hugs them. **Verify in a browser during 3.3** — this is inferred from the stylesheet, not seen.
- Composition checked with a preview page approximating the CP utilities, light and dark side by side (`scratchpad/preview.html.png`); Vue compile of the SVG-bearing markup is clean. Still not the real dashboard.

**2026-09-11 — Phase 3.3 (audit).** The Chrome extension was still not connected; the audit ran headless instead — Playwright 1.60 + axe-core 4.11 (from `../windrow/node_modules`) against the **real** dashboard at `statamic-dev.test`, logged in through a temporary local-only route that was removed afterwards (`routes/web.php` restored byte-for-byte; the URL now 404s).
- **Widget: zero axe violations and zero incomplete checks** in all four runs — populated and empty, light and dark — with WCAG 2.0/2.1/2.2 A+AA and best-practice rules. `color-contrast` passes on the real stylesheet; `link-name`, `list`, `listitem`, `target-size`, `aria-hidden-focus` pass. Nothing in the widget is animated or transitioned (computed styles checked, `prefers-reduced-motion` honoured trivially). No console errors or Vue warnings: the template compiles and renders as written.
- **Keyboard:** Tab reaches the first band link; `:focus-visible` is true and the CP's own rule draws a 2px solid blue-400 outline (offset −1px), confirming 3.2's inference. Screenshot in `docs/screenshots/focus-light.png`.
- One violation elsewhere on the page — `listitem`, a nested `<li>` in the CP navigation. Statamic's, not ours; not touched.
- Real dark card colour is `oklch(0.236 0.006 286.015)`, the lightness assumed in 3.2; contrast figures stand.
- Evidence kept in-repo: `docs/screenshots/` (widget light/dark, empty, focus — real dashboard renders, ready for the README) and `docs/audit/` (axe results JSON and the script, with a header saying what it needs).
- The dev site gained `app/Providers/SiteWeatherDemoProvider.php` (six sample bands via anonymous classes; `?weather-empty=1` shows the empty state). Kept on purpose — it is how the tile is seen populated while developing — and it is also a working example of the contract from a host app. Delete when real contributors land. Phase 3 is complete.

**2026-09-11 — Phase 4.1 (README, CHANGELOG, LICENSE).**
- README leads with the tile and the "never computes" rule, then installing, what you see, unknown, the empty state, the bands table, and "Writing a contributor" with a full example class, the tag snippet, and six numbered rules (read-don't-compute, unknown when unmeasured, dated measured readings, stable unique key, url = your dashboard, thresholds are yours). Screenshots are the real-dashboard captures from 3.3.
- The empty state now links to the README's contributor section — the "link to what could" from SPEC §8. Link text is a full sentence ("Any addon can contribute a band.") so it reads on its own. Re-captured and re-audited on the real dashboard in both schemes: zero violations, `link-in-text-block` passing, reachable by Tab. Temporary login route added and removed again; `routes/web.php` restored, URL 404s.
- `CHANGELOG.md` states the versioning promise: the contract (`WeatherContributor`, `Reading`, `State`, the tag) is the public API and breaking it is a major. `LICENSE.md` is MIT, matching `composer.json`.
- The README's `github.com/bpmore/statamic-site-weather` links assume the repo is published there (the `homepage` in `composer.json`). There is no remote yet.

**2026-09-11 — Phase 4.2 (scale check).** `tests/Feature/ScaleTest.php`, three tests, run through the real `/cp/dashboard` request under Testbench (`actingAs` a super user, widget read out of the Inertia props) and through the loader for timing.
- **Twenty contributors — 18 fakes across all six states, one that throws, one that sleeps 200 ms** — the dashboard returns 200 with one widget, 20 bands, the thrower reading unknown, the sleeper reading rain, overall storm.
- **Cost of the widget itself:** 1 band 0.16 ms; 20 bands 0.86 ms; **0.037 ms per extra band** (min of three warmed renders, Blade compiled). With the sleeper and thrower: 210.3 ms total, of which `usleep(200000)` alone measures 204 ms on this machine and the thrower's exception-plus-log 0.28 ms — the widget's own share stays under a millisecond; the remainder is sleep jitter.
- Budgets in the tests are deliberately loose for CI (overhead beyond the sleep < 100 ms; 20 bands < 50 ms) — they catch a regression of an order of magnitude, not noise. `SITE_WEATHER_TIMINGS=1 vendor/bin/pest --filter=Scale` prints the measured numbers.
- Conclusion, as SPEC §9 predicted: nothing here scales with entries. The one way the tile gets slow is a contributor that computes on request, which the contract forbids and the README repeats; a contributor that throws costs a quarter of a millisecond and one unknown band.
- New fixture: `SlowContributor(int $milliseconds)`.

**2026-09-11 — Phase 4.3 (listing, tag).**
- `LISTING.md` follows the a11y-docs shape: name, price (free), one-liner, card summary (98 chars), long description, what it does not do, features, requirements, suite positioning, categories (Widget primary, then Utility), keywords, screenshots. The funnel framing is absent from the copy, as the task asked; the listing describes what a person sees.
- `CHANGELOG.md` dated `1.0.0 - 2026-09-11`. Tagged `v1.0.0` locally on the final commit; **pushing the tag and the branch is a human step** — there is no remote, and `git push` is denied to this loop by design.
- Final state: 75 tests, 268 assertions, pint clean, `composer validate` clean, Statamic 6.32 resolved here and 6.31 in the host site.

**What is not done, deliberately (see "Follow-ups outside this repo"):** no real contributor exists yet. The first — A11y Report — is built in that repo. Until then the dev site shows the tile from `SiteWeatherDemoProvider`. When the first real one lands, the task to add here is: verify the real band in the host site, then delete the demo provider.

**2026-09-11 — Phase 5.1 (first real band, A11y Docs).**
- Nothing in this package changed; the work is in `../statamic-a11y-docs` (branch `build/site-weather-band`, commit `2d0caaf`) and it exercised the contract exactly as designed: one class, one string tag in `register()`, no dependency on this package (`suggest` + `require-dev` via a path repository until this is on Packagist).
- **What the host-site check found:** A11y Docs was installed there but `docs:install` never run, so its SQLite file did not exist and `reading()` threw. Site Weather did what 2.3 promised — the dashboard rendered, the band read unknown "Failed to report", the exception was logged — but a bare "Failed to report" with no link is a poor answer to "installed, not set up". The contributor now checks `DocumentDatabase::isInstalled()` first and says "Not set up yet: run php please docs:install", with the dashboard link. A lesson for every contributor that owns storage: **ask whether you are set up before you read**, because a caught exception is honest but unhelpful. Worth a line in the README's rules when the next contributor lands.
- The pre-existing failing test in A11y Docs (`PublishGateTest`, fails on its `main` untouched) is noted in that repo's `PROGRESS.md`, not fixed.

**2026-09-11 — after 5.1.** Lifecycle and Plain are both mid-build with their own task lists, and neither has a dashboard yet for a band to link to, so their bands were **queued, not built** — one task each, placed after the dashboard task in their own `PROGRESS.md` (commits `cd497ad`, `ed7334d` on their branches). README gained rule 7, "ask whether you are set up before you read", and a pointer to the real A11y Docs contributor.
