{{--
    One event on the public list, as a card: poster left, details right, and
    what a visitor can do at its bottom right. The whole card opens the event's page; only
    an open draw gets a button of its own. Never says how many lots there are.
--}}
@php
    $stage = $event->publicStage();
@endphp
<article class="ev-row">
    <div class="ev-poster {{ $event->banner_url ? 'has-banner' : '' }}">
        <div class="ev-poster-frame">
            @if($event->banner_url)
                <img src="{{ $event->banner_url }}" alt="" loading="lazy">
            @endif
            <div class="ev-date" aria-hidden="true"><span class="ev-date-month">{{ $event->start_date->format('M') }}</span><span class="ev-date-day">{{ $event->start_date->format('d') }}</span></div>
        </div>
    </div>

    <div class="ev-info">
        <h2 class="ev-title"><a href="{{ route('events.show', $event) }}" class="ev-link">{{ $event->title }}</a></h2>
        <div class="ev-meta">
            <i class="bi bi-calendar3" aria-hidden="true"></i>
            <span>{{ $event->start_date->format('d M Y') }}@unless($event->end_date->isSameDay($event->start_date)) – {{ $event->end_date->format('d M Y') }}@endunless</span>
        </div>
        <div class="ev-meta">
            <i class="bi bi-geo-alt" aria-hidden="true"></i>
            <span>{{ $event->location }}@if($event->state), {{ $event->state }}@endif</span>
        </div>
        @if($event->description)
            <p class="ev-desc">{{ $event->description }}</p>
        @endif

        <div class="ev-foot">
            @if($stage === 'open')
                <p class="ev-foot-note">Lot draw open until {{ $event->polling_deadline->format('d M, h:i A') }}</p>
            @elseif($stage === 'soon' && $event->polling_start_date)
                <p class="ev-foot-note">Lot draw opens {{ $event->polling_start_date->format('d M, h:i A') }}</p>
            @endif

            @switch($stage)
                @case('open')
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="ev-cta ev-cta-primary">
                        Confirm your lot <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                    @break
                @case('soon')
                    <span class="ev-cta ev-cta-neutral">Coming soon</span>
                    @break
                @case('now')
                    <span class="ev-cta ev-cta-info">Happening now</span>
                    @break
                @case('closed')
                    <span class="ev-cta ev-cta-neutral">Lot draw closed</span>
                    @break
                @default
                    <span class="ev-cta ev-cta-neutral">Ended</span>
            @endswitch
        </div>
    </div>
</article>
