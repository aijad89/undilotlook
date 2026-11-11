@extends('layouts.frontend')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@include('partials.apple-styles')

@php
    $held = $company?->credentials ?? [];
    // A certificate on record, and how near its end is.
    $certState = function (?array $cert) {
        if (empty($cert['end'])) {
            return null;
        }
        $end = \Illuminate\Support\Carbon::parse($cert['end'])->endOfDay();

        return $end->isPast() ? ['ap-badge-red', 'Expired'] : ($end->lte(now()->addMonths(2)) ? ['ap-badge-orange', 'Expires soon'] : null);
    };
@endphp

<div class="ap-page ap-dash">
    <div class="ap-page-header">
        <div>
            <h2 class="ap-page-title">Welcome, {{ $user->name }}</h2>
            <p class="ap-page-subtitle">
                @if($company)
                    {{ $company->name }}@if($company->state) · {{ $company->state }}@endif
                @else
                    Your account has no company yet. Contact the Kraftangan office to have it added.
                @endif
            </p>
        </div>
    </div>

    {{-- What is waiting on them, most pressing first, each with its one next step. --}}
    <section class="ap-card ap-card-padded ap-todo" aria-labelledby="dash-todo">
        <h2 class="ap-section-title" id="dash-todo">What to do now</h2>
        @foreach($todo as [$tone, $text, $link, $go])
            @if($link)
                <a href="{{ $link }}" class="ap-todo-row ap-todo-{{ $tone }}">
                    <i class="bi {{ $tone === 'bad' ? 'bi-exclamation-octagon' : 'bi-exclamation-triangle' }}" aria-hidden="true"></i>
                    <span class="ap-todo-text">{{ $text }}</span>
                    <span class="ap-todo-go">{{ $go }} <i class="bi bi-chevron-right" aria-hidden="true"></i></span>
                </a>
            @else
                <p class="ap-todo-row ap-todo-{{ $tone }}">
                    <i class="bi {{ $tone === 'bad' ? 'bi-exclamation-octagon' : 'bi-exclamation-triangle' }}" aria-hidden="true"></i>
                    <span class="ap-todo-text">{{ $text }}</span>
                </p>
            @endif
        @endforeach
        @if($unreadNotificationCount > 0)
            <a href="{{ route('notifications.index') }}" class="ap-todo-row ap-todo-info">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <span class="ap-todo-text">You have {{ $unreadNotificationCount }} unread {{ \Illuminate\Support\Str::plural('notification', $unreadNotificationCount) }} from the office.</span>
                <span class="ap-todo-go">Read <i class="bi bi-chevron-right" aria-hidden="true"></i></span>
            </a>
        @endif
        @if(! $todo && ! $unreadNotificationCount)
            <p class="ap-todo-row ap-todo-ok">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <span class="ap-todo-text">Nothing is waiting on you right now.</span>
            </p>
        @endif
    </section>

    <div class="ap-dash-grid">
        <div class="ap-dash-main">
            <section>
                <h2 class="ap-section-title">Your lot draws</h2>
                @if($entries->isEmpty())
                    <div class="ap-card ap-empty">
                        <i class="bi bi-grid-3x3-gap ap-empty-icon" aria-hidden="true"></i>
                        <div class="ap-empty-title">You are not in a lot draw yet</div>
                        <p class="ap-empty-text mb-0">Ask to join an event that is open for registration. Once the office approves, the event appears here with its lot draw.</p>
                    </div>
                @endif
                <div class="ap-draw-grid">
                    @foreach($entries as $entry)
                        @php
                            $event = $entry->event;
                            $hasDrawn = isset($drawn[$event->id]);
                            $open = $event->polling_status === 'polling' && ! $event->polling_deadline?->isPast();
                        @endphp
                        <article class="ap-card ap-draw mb-3">
                            <div class="ap-draw-head">
                                <div>
                                    <h3 class="ap-draw-title">{{ $event->title }}</h3>
                                    <div class="ap-draw-meta">
                                        <span><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $event->location }}</span>
                                        <span><i class="bi bi-calendar3" aria-hidden="true"></i>{{ $event->start_date->format('d M Y') }}</span>
                                    </div>
                                </div>
                                @if($hasDrawn)
                                    <span class="ap-badge ap-badge-green">Lot confirmed</span>
                                @endif
                            </div>

                            @include('partials.draw-stepper', ['status' => $event->polling_status, 'labels' => \App\Models\Event::PARTICIPANT_STAGE_LABELS, 'stops' => \App\Models\Event::PARTICIPANT_STAGES])

                            <div class="ap-draw-next">
                                {{-- A drawn lot is named once, on its own link. --}}
                                @unless($hasDrawn && $entry->plot)
                                <span>
                                    @if($open)
                                        The draw is open until {{ $event->polling_deadline->format('d M Y, h:i A') }}.
                                    @elseif($event->polling_status === 'draft' && $event->polling_start_date)
                                        The draw opens {{ $event->polling_start_date->format('d M Y, h:i A') }}.
                                    @elseif($event->polling_status === 'draft')
                                        You are in the draw. The draw date has not been set yet.
                                    @elseif($entry->plot)
                                        Your lot: <strong>{{ $entry->plot->plot_code }}</strong>
                                    @else
                                        The draw has closed. The office will assign your lot.
                                    @endif
                                </span>
                                @endunless

                                @if($hasDrawn && $entry->plot)
                                    {{-- Says what the button is; the lot number itself stays on the button. --}}
                                    <span>Congratulations, you have your lot. Your lot number for this event is:</span>
                                    <a href="{{ route('zvote.index', $event) }}" class="ap-btn ap-btn-outline ap-draw-lot-link">
                                        <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>Lot <strong class="ms-1">{{ $entry->plot->plot_code }}</strong>
                                    </a>
                                @elseif($open)
                                    <a href="{{ route('zvote.index', $event) }}" class="ap-btn ap-btn-primary ap-btn-lg">
                                        <i class="bi bi-check-circle" aria-hidden="true"></i> Confirm Lot
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            @if($requests->isNotEmpty())
            <section>
                <h2 class="ap-section-title">Requests to join</h2>
                <div class="ap-draw-grid">
                @foreach($requests as $jr)
                    @php
                        $cancelled = $jr->event->isCalledOff();
                        [$badge, $label] = $cancelled ? ['ap-badge-red', 'Event cancelled'] : match ($jr->status) {
                            'draft' => ['ap-badge-gray', 'Draft, not sent'],
                            'pending' => ['ap-badge-orange', 'With the office'],
                            'rejected' => ['ap-badge-red', 'Not approved'],
                            'approved' => ['ap-badge-red', 'Taken off the draw list'],
                        };
                    @endphp
                    <article class="ap-card ap-draw mb-3">
                        <div class="ap-draw-head">
                            <div>
                                <h3 class="ap-draw-title">{{ $jr->event->title }}</h3>
                                <div class="ap-draw-meta">
                                    <span><i class="bi bi-clock" aria-hidden="true"></i>{{-- Rejecting moves updated_at, so a rejected request is dated by the review. --}}
                                    {{ match ($jr->status) { 'draft' => 'Saved', 'rejected', 'approved' => 'Answered', default => 'Sent' } }} {{ (in_array($jr->status, ['rejected', 'approved'], true) ? $jr->reviewed_at : $jr->updated_at)->format('d M Y') }}</span>
                                </div>
                            </div>
                            <span class="ap-badge {{ $badge }}">{{ $label }}</span>
                        </div>

                        <div class="ap-request-body">
                            @if($jr->reject_reason && $jr->status !== 'pending')
                                <p class="mb-2"><strong>Reason:</strong> {{ $jr->reject_reason }}</p>
                            @endif
                            @if($jr->answers)
                                @include('partials.join-answers', ['a' => $jr->answers])
                            @endif
                            @if($jr->payment_method)
                                <div>Payment: {{ \App\Models\EventJoinRequest::PAYMENT_METHODS[$jr->payment_method] }}@if($jr->waiver_category), {{ \App\Models\EventJoinRequest::WAIVER_CATEGORIES[$jr->waiver_category] ?? $jr->waiver_category }}@endif</div>
                            @endif
                            @if($jr->documents)
                                <ul>
                                    @foreach($jr->documents as $i => $doc)
                                        <li><a href="{{ route('join-requests.document', [$jr, $i]) }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark" aria-hidden="true"></i>{{ $doc['name'] }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        @if($jr->status !== 'pending' && $jr->event->acceptsJoinRequests())
                            <div class="ap-draw-next">
                                <a href="{{ route('events.show', $jr->event) }}" class="ap-btn ap-btn-primary">
                                    {{ match ($jr->status) { 'draft' => 'Finish and send', 'approved' => 'Ask to join again', default => 'Change and send again' } }}
                                </a>
                            </div>
                        @elseif($cancelled)
                            <div class="ap-draw-next">This event was cancelled.</div>
                        @elseif($jr->status !== 'pending')
                            {{-- Closed, not yet open again, or the draw already started: the same answer. --}}
                            <div class="ap-draw-next">Registration is not open, so this can't be sent{{ $jr->status === 'draft' ? '. This draft never reached the office.' : ' again.' }}</div>
                        @endif
                    </article>
                @endforeach
                </div>
            </section>
            @endif
        </div>

        <aside class="ap-dash-side">
            <section class="ap-card ap-card-padded">
                <h2 class="ap-section-title">Open for registration</h2>
                @forelse($openEvents as $event)
                    <div class="ap-open-row">
                        <div class="ap-open-title">{{ $event->title }}</div>
                        <div class="ap-open-meta">
                            <span><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $event->location }}</span>
                            <span><i class="bi bi-hourglass-split" aria-hidden="true"></i>Closes {{ $event->registration_close_at->format('d M Y') }}</span>
                        </div>
                        <a href="{{ route('events.show', $event) }}" class="ap-btn ap-btn-outline ap-btn-sm">View and join</a>
                    </div>
                @empty
                    <p class="ap-side-note mb-0">No other event is open for registration right now. New events appear on the <a href="{{ route('home') }}">events page</a>.</p>
                @endforelse
            </section>

            {{-- The answers each request starts from: changed by sending a request. --}}
            <section class="ap-card ap-card-padded">
                <h2 class="ap-section-title">Your company on record</h2>
                @if($held)
                    <dl class="ap-facts">
                        <dt>Contact person</dt>
                        <dd>{{ $held['contact_name'] ?? '—' }}@isset($held['contact_phone'])<br><span class="ap-tabular">{{ $held['contact_phone'] }}</span>@endisset</dd>
                        <dt>Recognition</dt>
                        <dd>{{ \App\Models\Company::RECOGNITIONS[$held['recognition'] ?? ''] ?? '—' }}</dd>
                        @foreach(['classification_cert' => 'Classification certificate', 'quality_cert' => 'Quality compliance'] as $key => $name)
                            @continue($key === 'quality_cert' && empty($held['quality']))
                            <dt>{{ $name }}</dt>
                            <dd>
                                @if(! empty($held[$key]['number']))
                                    <span class="ap-tabular">{{ $held[$key]['number'] }}</span>@if(! empty($held[$key]['end'])), valid until <span class="ap-tabular">{{ \Illuminate\Support\Carbon::parse($held[$key]['end'])->format('d M Y') }}</span>@endif
                                    @if($state = $certState($held[$key]))
                                        <span class="ap-badge {{ $state[0] }} ms-1">{{ $state[1] }}</span>
                                    @endif
                                @else
                                    {{ $key === 'quality_cert' ? 'Not applicable' : 'None' }}
                                @endif
                            </dd>
                        @endforeach
                        <dt>Product photos</dt>
                        <dd>{{ count($held['product_images'] ?? []) ? count($held['product_images']).' on file' : 'None yet' }}</dd>
                    </dl>
                    <p class="ap-side-note mb-0">Each request to join starts from these answers. To change them, change them on your next request.</p>
                @else
                    <p class="ap-side-note mb-0">Nothing on record yet. Your first request to join fills this in, and every later request starts from it.</p>
                @endif
            </section>
        </aside>
    </div>
</div>
@endsection
