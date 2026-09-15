# Marketplace listing copy

Copy for the Statamic marketplace listing. The listing describes what a person
sees on their dashboard; why the addon exists in the line-up stays out of it.

---

## Name

**Site Weather**

> **Site Weather: Your Site's Health as a Dashboard Tile**

Not "A11y Weather". It is the one addon in the line that is not about
accessibility in particular — accessibility is one band of six — and naming it
that way would make the other five bands look like afterthoughts.

## Price

Free.

## One-liner

One dashboard tile that shows your site's health as weather.

## Summary (marketplace card)

Sunny, overcast, rain or storm — one tile that reads the health your other
addons already measure, and never guesses.

98 characters.

Not "monitors your site": it monitors nothing, and someone who installs it
expecting uptime or infrastructure checks should not have been led there.

## Long description

Every addon that measures something about your site — open accessibility
issues, stale entries, unreadable documents, orphaned pages, translations that
have fallen behind — has its own dashboard, and you have to go and look. Site
Weather puts the answer on the one page everyone already opens.

Clear. Fair. Overcast. Rain. Storm.

One tile, one glance. The overall weather is the **worst** of what is reporting,
never the average, because one storm is a storm and averaging would hide
exactly the thing you need to see. Under it, a strip of bands — one per addon
that reports — each a link straight to that addon's own dashboard for the
detail.

**Site Weather computes nothing.** It has no scanner, no crawler and no
checks. It reads what your other addons have already stored and renders it.
That is what makes it free, keeps it tiny, and means installing it changes
nothing about how your site runs.

**It never guesses.** An addon that is installed but has never run reports
*unknown*, not clear — the tile will not imply health nobody has measured. And
with nothing reporting at all, it says "Nothing reporting yet." It does not say
sunny.

**Built to be looked at by everyone.** Each state has its own icon shape and
its own word, never a colour alone. A screen reader gets the whole tile as one
sentence: *"Overall: storm. Accessibility: storm, 412 open issues. Freshness:
fair."* Nothing animates. The tile is audited with axe on the real dashboard,
light and dark, with no violations.

### Bands

| Band | Reported by |
|---|---|
| Accessibility | A11y Report |
| Documents | A11y Docs |
| Freshness | Lifecycle |
| Readability | Plain |
| Structure | Constellation |
| Content model | Fallow |

Missing addon, missing band — never a fake or zeroed one. **Any addon can
contribute a band**: one small class and a one-line tag in a service provider,
with no dependency on Site Weather itself. The README shows how.

### What it does not do

No thresholds to tune — each addon decides what counts as a storm for its own
data. No history or trends — each addon owns its own. No notifications. No
front-end tag. Nothing that makes it a fifth half-product.

## Features list

- One dashboard widget, one line of config, nothing else to set up
- Overall state is the worst band, never the average
- A band per reporting addon, linking to that addon's dashboard
- *Unknown* is a first-class state; an empty tile never says sunny
- Distinct icon shape and text label for every state — never colour alone
- One-sentence screen-reader summary of the whole tile
- Static: no animation, no JavaScript, no build step
- Audited with axe on the real dashboard, light and dark, no violations
- A contributor contract any addon can implement without depending on this one
- Computes nothing, stores nothing, adds under a millisecond to a dashboard load

## Requirements

Statamic 6, PHP 8.2+. No database.

## Suite positioning

The tile over the line. Free, so it sits alongside A11y Gate as the no-cost
entry point — but where Gate is about accessibility only, this shows the whole
site.

- **A11y Gate** — free. Blocks entries with accessibility problems.
- **Site Weather** — free. One tile for the health of everything.
- **A11y Report**, **A11y Docs**, **Lifecycle**, **Plain**, **Constellation**,
  **Drift** — each reports a band.

With one of the paid addons installed it is a useful tile; with several it is
the page you open first.

## Categories

In order; the marketplace treats the first as primary.

| | |
|---|---|
| **Widget** | Primary. It is a dashboard widget and nothing else; someone browsing Widget is exactly who this is for. |
| **Utility** | Where the rest of the line lives, so browsing Utility shows the family together. |

Deliberately not **Analytics** — on a CMS marketplace that means traffic, and
this measures nothing — and not **CLI**, **Fieldtype** or **Tag**, none of
which it registers.

## Keywords

dashboard, widget, health, status, overview, accessibility, a11y, weather

## Screenshots

From `docs/screenshots/`, real dashboard renders:

1. `widget-light.png` — the tile with six bands, overall storm
2. `widget-dark.png` — the same in the dark scheme
3. `empty-light.png` — nothing reporting yet
4. `focus-light.png` — keyboard focus on a band link
