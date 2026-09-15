# Site Weather — Build Spec

**For:** handoff to Claude Code
**Date:** September 11, 2026
**Product:** `bpmore/statamic-site-weather` — one ambient health indicator for everything
**Price:** free · **Statamic:** `^6.0` · **PHP:** `^8.2`

---

## 1. Why the silliest thing on the list matters most

A single dashboard tile rendering the site's overall health as literal weather. Sunny,
overcast, drizzle, storm. Click through for detail.

It has the highest **Compounds** score of anything in either list, because it is a free shell
over data the paid products already generate. Someone installs it, sees permanent thunderstorms
in the accessibility band, clicks through, and lands on A11y Report's dashboard. A funnel
disguised as a joke.

**Build it after at least two paid products exist**, which they now do. With one data source
it's a gimmick; with four it's genuinely informative.

---

## 2. The rule that keeps it honest

**Site Weather never computes anything itself.** It has no scanner, no crawler, no checks. It
reads what other addons have already stored and renders it.

That constraint is what keeps it free, keeps it tiny, and keeps it from becoming a fourth
half-product. If a band needs data nobody is collecting, the band doesn't exist.

---

## 3. Bands

Each installed contributor supplies one band. Missing addon, missing band — never a fake or
zeroed one.

| Band | Source | Reads |
|---|---|---|
| Accessibility | A11y Report | open issues by impact, trend |
| Documents | A11y Docs | failing documents, proportion of library |
| Freshness | Lifecycle | overdue percentage |
| Readability | Plain | proportion above target grade |
| Structure | Constellation | orphan count |
| Translations | Drift | localizations behind |

---

## 4. The contributor interface

The thing to design carefully, because every future product will implement it.

```php
interface WeatherContributor
{
    public function key(): string;
    public function label(): string;
    public function reading(): Reading;   // state, headline, detail url, computed_at
}
```

`Reading` carries a state (`clear|fair|overcast|rain|storm|unknown`), a one-line headline in
plain words, a link, and when it was computed.

**Contributors register themselves.** Site Weather must not import any paid addon — it
discovers registered contributors via the container. That way it works with none of them
installed (showing an honest empty state) and needs no change when a fifth product ships.

**`unknown` is a first-class state.** A product installed but never scanned reports unknown,
not clear. Never imply health you haven't measured — the same discipline as the rest of the
line, applied to a weather icon.

---

## 5. Aggregation and presentation

Overall weather is the **worst** band, not the average. One storm is a storm. Averaging hides
exactly what the tile exists to surface.

The widget shows the overall state, a band strip, and a headline for the worst one. Click a
band, go to that product's dashboard.

**Accessibility of the weather itself** — this would be an embarrassing place to fail:

- Never colour alone. Each state has a distinct icon shape *and* a text label.
- The tile has a text summary readable by a screen reader: "Overall: storm. Accessibility:
  storm, 412 open issues. Freshness: fair."
- No animation. A raining tile is charming once and irritating forever, and it fails
  `prefers-reduced-motion` for no benefit.

---

## 6. Out of scope

No configuration of thresholds in v1 beyond what each source already exposes. No history or
trends — each product owns its own. No notifications. No front-end widget.

---

## 7. First session for Code

1. Scaffold; confirm `^6.0` / `^8.2`
2. **The `WeatherContributor` interface and registration**, with a fake contributor in tests.
   This is the whole architecture; get it right before any real source.
3. Aggregation (worst-wins) plus `unknown` handling, unit-tested
4. The widget, accessible and static, with a good empty state
5. One real contributor — A11y Report — implemented in *that* repo, not this one
6. Remaining contributors last, each in its own repo

Point 5 matters: contributors live in the products that own the data. Site Weather stays
ignorant of all of them.

---

## 8. Open questions

- **Where does the contributor interface live?** If it's in this package, every paid product
  depends on a free one. Recommend a tiny `bpmore/weather-contract` package both sides depend
  on, so neither owns the other.
- **Threshold tuning.** What makes accessibility "storm" rather than "rain"? Let each product
  decide for its own band — it knows its data better than a generic rule would.
- **Does the tile ever say "sunny" on a site with no data?** No. Empty state reads "nothing
  reporting yet," with a link to what could.

---

## 9. Decisions from the first session

Answers to §8, recorded so nothing below is built on a guess.

### Where the contract lives: here

`WeatherContributor`, `Reading` and `State` live in this package, under
`Bpmore\SiteWeather\`. There is no `bpmore/weather-contract` package in v1.

The concern in §8 was that every paid product would depend on a free one. It doesn't have
to. **Registration is a container tag**, and a tag is a string:

```php
// In a paid product's service provider. No import, no guard, no dependency.
$this->app->tag(A11yWeatherContributor::class, 'site-weather.contributors');
```

Tagging a class name never instantiates it. If Site Weather is not installed, nothing
resolves the tag and the contributor class is never autoloaded, so the missing interface is
never noticed. If it is installed, it resolves the tag lazily at render time, ignores anything
that doesn't implement the interface, and wraps each `reading()` in a try/catch.

Paid products list `bpmore/statamic-site-weather` under `suggest` for users, and under
`require-dev` so their own tests and static analysis see the interface.

Why not the separate package anyway: it is a second repo, Packagist listing and version line
for three files, and the run loop that builds this addon can't create it. If a third party
ever wants to contribute a band without installing the widget, extract then. All first-party
products are owned by one person, so a namespace move is a coordinated afternoon, not a
migration.

### Thresholds: each product decides

Unchanged from §8. The contract carries a `State`; how a product gets there is its business.
Site Weather has no threshold configuration.

### `unknown` in the aggregate

Overall weather is the worst **measured** band. `unknown` is never ranked against a measured
state — it is neither better nor worse than `clear`, it is *absent*.

- Any measured band → overall is the worst of them, and the summary appends each unknown
  band by name ("Readability: unknown.") so the gap is visible.
- Every band unknown → overall is `unknown`.
- No bands at all → the **empty state**, which is distinct from unknown: "Nothing reporting
  yet," with a link to what could. It never reads "sunny."

A `Reading` in a measured state must carry a `computed_at`; the constructor refuses otherwise.
That is the "never imply health you haven't measured" rule made structural.

### Rendering: Blade, static

Statamic 6 widgets can be Vue or Blade. This one is Blade: a static tile with links needs no
component, no build step and no JavaScript, and a widget that ships without a JS build cannot
break when the control panel's asset pipeline changes. Every state has a distinct icon shape
*and* a text label; the icon is `aria-hidden`; there is a visually-hidden full-sentence
summary; there is no animation.

### What could go wrong on a 40,000-entry site

Nothing in this package scales with entry count — it reads six stored values and renders
them. The two things that *can* go wrong are both in contributors:

1. **A contributor computes on request.** The contract forbids it in words (docblock) and the
   README repeats it; Site Weather cannot enforce it. A slow contributor makes the dashboard
   slow for every control-panel user on every load. Phase 4.2 measures this so the number
   is known.
2. **A contributor throws.** Caught per contributor; that band reads `unknown` with the
   headline "Failed to report," the exception is logged, and the other bands render. The
   dashboard never breaks because of a band.

No caching in v1: contributors are meant to be cheap, and a cache layer would put a second
"as of" time next to each band's own `computed_at`.

### Bands as built (2026-09-15)

Drift shipped as **Fallow**, and its scope changed on the way: it reports content
model drift — which blueprint fields real content fills, which are dead, which
entry keys no field claims — not translations. Its band is therefore
**Content model**, not Translations, and the §3 table is out of date on that row.
Fallow also stores nothing, so its band reads an audit the site saves on a
schedule (`fallow.report_path`) and is *unknown* until one exists: the first
contributor built around a saved artefact rather than a database.

Constellation's band is **Structure**, read from the newest complete run per
site, scaled by the share of pages that are orphans.

Six bands are real. Nothing on the tile is faked any more.
