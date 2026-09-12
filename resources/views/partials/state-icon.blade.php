{{--
    One inline SVG per state. Decorative: the text label beside it carries the
    meaning, so it is aria-hidden and unfocusable. Colour is reinforcement
    only, and each token clears 3:1 on both the light (white) and dark
    (gray-850) card - the CP ships no dark: variants for these hues, so one
    colour has to serve both. Contrast figures are in PROGRESS.md.

    @param  \Bpmore\SiteWeather\State  $state
    @param  string  $class  Size and layout classes, e.g. "size-4".
--}}
@php
    $colour = match ($state) {
        \Bpmore\SiteWeather\State::Clear, \Bpmore\SiteWeather\State::Fair => 'text-amber-600',
        \Bpmore\SiteWeather\State::Rain => 'text-blue-500',
        \Bpmore\SiteWeather\State::Storm => 'text-red-600',
        \Bpmore\SiteWeather\State::Overcast, \Bpmore\SiteWeather\State::Unknown => 'text-gray-500',
    };
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" data-icon="{{ $state->icon() }}" class="{{ $class ?? 'size-4' }} shrink-0 {{ $colour }}">
@switch($state)
    @case(\Bpmore\SiteWeather\State::Clear)
    <circle cx="12" cy="12" r="4"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4l1.4-1.4M17 7l1.4-1.4"/>
    @break
    @case(\Bpmore\SiteWeather\State::Fair)
    <path d="M10 7A3 3 0 1 0 7 10"/><path d="M7 2.8V1.5M2.8 7H1.5M4.03 4.03 3.1 3.1M9.97 4.03l.93-.93M4.03 9.97l-.93.93M11.2 7h1.3"/><path d="M8.5 20H18a3.1 3.1 0 0 0 .2-6A4.5 4.5 0 0 0 9.6 13.6 3.25 3.25 0 0 0 8.5 20Z"/>
    @break
    @case(\Bpmore\SiteWeather\State::Overcast)
    <path d="M7 19h10.5a4 4 0 0 0 .2-8A6 6 0 0 0 6.5 10.5 4.25 4.25 0 0 0 7 19Z"/>
    @break
    @case(\Bpmore\SiteWeather\State::Rain)
    <path d="M7 16h10.5a3.5 3.5 0 0 0 .2-7A6 6 0 0 0 6.6 8.6 3.7 3.7 0 0 0 7 16Z"/><path d="M8 19l-1 2.5M12 19l-1 2.5M16 19l-1 2.5"/>
    @break
    @case(\Bpmore\SiteWeather\State::Storm)
    <path d="M7 15h10.5a3.5 3.5 0 0 0 .2-7A6 6 0 0 0 6.6 7.6 3.7 3.7 0 0 0 7 15Z"/><path d="M13 15l-2 3.5h3l-2 3.5"/>
    @break
    @case(\Bpmore\SiteWeather\State::Unknown)
    <circle cx="12" cy="12" r="8.5" stroke-dasharray="2.67 2.67"/>
    @break
@endswitch
</svg>
