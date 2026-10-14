{{--
    One event on the public list. Says when the voting window is, because that is
    what a participant acts on — never how many lots there are.
--}}
<article class="ev-card">
    @if($event->banner_url)
        <img class="ev-banner" src="{{ $event->banner_url }}" alt="" loading="lazy">
    @else
        <div class="ev-banner" aria-hidden="true"></div>
    @endif
    <div class="ev-body">
        <div class="ev-title">{{ $event->title }}</div>
        <div class="ev-meta">
            <i class="bi bi-calendar3"></i>
            {{ $event->start_date->format('d M Y') }}@unless($event->end_date->isSameDay($event->start_date)) – {{ $event->end_date->format('d M Y') }}@endunless
        </div>
        <div class="ev-meta"><i class="bi bi-geo-alt"></i> {{ $event->location }}</div>

        @if($event->polling_start_date && $event->polling_deadline)
            <div class="ev-draw">
                @if($event->polling_status === 'polling')
                    <strong>Cabutan lot dibuka</strong> sehingga {{ $event->polling_deadline->format('d M, h:i A') }}.
                    <a href="{{ route('login') }}">Log masuk untuk sahkan lot</a>
                @elseif($event->polling_status === 'draft')
                    Cabutan lot dibuka {{ $event->polling_start_date->format('d M Y, h:i A') }}.
                @elseif(in_array($event->polling_status, ['closed', 'completed'], true))
                    Cabutan lot telah ditutup.
                @endif
            </div>
        @endif
    </div>
</article>
