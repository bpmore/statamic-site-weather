# Changelog

What changed in each release, and what you have to do about it.

Versions are `MAJOR.MINOR.PATCH`. The contributor contract —
`WeatherContributor`, `Reading`, `State` and the `site-weather.contributors`
tag — is the public API; a breaking change to any of it is a major version.

## Unreleased

First release.

- The `WeatherContributor` contract, discovered through the
  `site-weather.contributors` container tag.
- `Reading` (state, headline, url, computed_at) and the six-state `State` enum.
- Worst-wins aggregation; unknown is a first-class state; an empty tile never
  says sunny.
- The dashboard widget: Blade, static, with a distinct icon shape and a word
  for every state and a one-sentence screen-reader summary. Audited with axe on
  the real dashboard in both colour schemes: no violations.
