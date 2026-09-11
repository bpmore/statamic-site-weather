{{--
    The dashboard compiles this markup as a Vue template at runtime, which is
    what makes <ui-widget> work. v-pre on the content keeps Vue's hands off
    everything inside it: a headline containing "{{" stays text, and the
    markup is exactly what Blade produced.

    Accessibility (SPEC §5): every state is a word, never a colour alone; the
    whole tile is one sentence for a screen reader before it is anything
    else; nothing moves.
--}}
<ui-widget title="Site Weather">
    <div v-pre class="site-weather px-4 py-3 text-sm leading-normal">
        @if ($forecast->isEmpty())
            <p class="site-weather-empty text-gray-700 dark:text-gray-300">
                <strong class="font-semibold text-gray-900 dark:text-gray-100">Nothing reporting yet.</strong>
                Site Weather shows what other addons have already measured. A11y Report, A11y Docs, Lifecycle, Plain, Constellation and Drift each add a band when installed.
            </p>
        @else
            <p class="sr-only">{{ $forecast->summary() }}</p>

            <p class="site-weather-overall text-lg font-semibold text-gray-900 dark:text-gray-100">
                Overall: <span data-state="{{ $forecast->overall()->value }}">{{ $forecast->overall()->label() }}</span>
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
                            <a href="{{ $band->reading->url }}" class="underline text-gray-900 dark:text-gray-100">{{ $band->label }}: {{ $band->reading->state->label() }}</a>
                        @else
                            <span class="text-gray-700 dark:text-gray-300">{{ $band->label }}: {{ $band->reading->state->label() }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</ui-widget>
