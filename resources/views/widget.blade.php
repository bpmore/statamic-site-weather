{{--
    The dashboard compiles this markup as a Vue template at runtime, which is
    what makes <ui-widget> work. v-pre on the content keeps Vue's hands off
    everything inside it: a headline containing "{{" stays text, and the
    markup is exactly what Blade produced.

    Accessibility (SPEC §5): every state is a word beside a distinct shape,
    never a colour alone; the whole tile is one sentence for a screen reader
    before it is anything else; nothing moves. Focus on the band links is
    drawn by the control panel's own :focus-visible rule.
--}}
<ui-widget title="Site Weather">
    <div v-pre class="site-weather px-4 py-3 text-sm leading-normal">
        @if ($forecast->isEmpty())
            <p class="site-weather-empty text-gray-700 dark:text-gray-300">
                <strong class="font-semibold text-gray-900 dark:text-gray-100">Nothing reporting yet.</strong>
                Site Weather shows what other addons have already measured. A11y Report, A11y Docs, Lifecycle, Plain, Constellation and Drift each add a band when installed.
            </p>
        @else
            @php($overall = $forecast->overall())

            <p class="sr-only">{{ $forecast->summary() }}</p>

            <p class="site-weather-overall flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                @include('site-weather::partials.state-icon', ['state' => $overall, 'class' => 'size-8'])
                <span>Overall: <span data-state="{{ $overall->value }}">{{ $overall->label() }}</span></span>
            </p>

            @if ($worst = $forecast->worstBand())
                <p class="site-weather-headline text-gray-700 dark:text-gray-300">{{ $worst->label }}: {{ $worst->reading->headline }}</p>
            @else
                <p class="site-weather-headline text-gray-700 dark:text-gray-300">Nothing measured yet.</p>
            @endif

            <ul class="site-weather-bands mt-3 flex flex-wrap gap-x-4 gap-y-1" style="list-style: none; margin-left: 0; padding-left: 0;">
                @foreach ($forecast->bands as $band)
                    <li data-band="{{ $band->key }}" data-state="{{ $band->reading->state->value }}">
                        @if ($band->reading->url)
                            <a href="{{ $band->reading->url }}" class="inline-flex items-center gap-1 rounded-sm underline text-gray-900 dark:text-gray-100">
                                @include('site-weather::partials.state-icon', ['state' => $band->reading->state, 'class' => 'size-4'])
                                <span>{{ $band->label }}: {{ $band->reading->state->label() }}</span>
                            </a>
                        @else
                            <span class="inline-flex items-center gap-1 text-gray-700 dark:text-gray-300">
                                @include('site-weather::partials.state-icon', ['state' => $band->reading->state, 'class' => 'size-4'])
                                <span>{{ $band->label }}: {{ $band->reading->state->label() }}</span>
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</ui-widget>
