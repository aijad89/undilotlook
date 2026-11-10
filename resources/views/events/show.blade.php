@extends('layouts.public')

@section('title', $event->title.' — '.\App\Models\Setting::get('site_name', 'undilot'))
@section('page-title', 'Event')
@section('own-heading', true)

@push('styles')
@include('partials.public-event-styles')
<style>
    .evd-back { display: inline-flex; align-items: center; gap: 6px; min-height: 40px; margin-bottom: 16px; color: var(--ui-text-2); font-size: 1rem; font-weight: 600; text-decoration: none; }
    .evd-back:hover { color: var(--ui-blue-text); }
    .evd-banner { display: block; width: 100%; max-height: 360px; margin-bottom: 28px; object-fit: cover; border-radius: var(--ui-r-lg); }
    .evd-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(300px, 380px); gap: 32px; align-items: start; }
    .evd-head { display: flex; gap: 18px; align-items: flex-start; margin-bottom: 20px; }
    .evd-head h1 { margin: 0 0 10px; font-size: 2.25rem; font-weight: 700; line-height: 1.15; letter-spacing: -0.02em; color: var(--ui-text); }
    .evd-head .ev-date { width: 68px; }
    .evd-head .ev-date-day { font-size: 1.75rem; }
    .evd-about { max-width: 72ch; margin: 0; font-size: 1rem; line-height: 1.7; color: var(--ui-text-2); white-space: pre-line; }
    .evd-card { padding: 22px; background: var(--ui-surface); border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); box-shadow: var(--ui-shadow-1); }
    .evd-fact { display: flex; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--ui-sep); }
    .evd-fact:first-child { padding-top: 0; }
    .evd-fact:last-of-type { border-bottom: none; }
    .evd-fact > i { margin-top: 2px; font-size: 1.125rem; color: var(--ui-muted); }
    .evd-fact-label { margin-bottom: 2px; font-size: 0.875rem; font-weight: 600; color: var(--ui-muted); }
    .evd-fact-value { font-size: 1rem; color: var(--ui-text); }
    .evd-cta { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; min-height: 48px; margin-top: 14px; border-radius: var(--ui-r); background: var(--ui-blue); color: #fff; font-size: 1rem; font-weight: 600; text-decoration: none; }
    .evd-cta:hover { background: var(--ui-blue-hover); color: #fff; }
    .evd-note { margin: 12px 0 0; font-size: 0.9375rem; line-height: 1.5; color: var(--ui-muted); }
    .evd-msg { margin: 14px 0 0; padding: 10px 12px; border-radius: var(--ui-r); border: 1px solid; font-size: 0.9375rem; line-height: 1.5; }
    .evd-msg-ok { background: var(--ui-ok-tint); color: var(--ui-ok-text); border-color: var(--ui-ok-border); }
    .evd-msg-bad { background: var(--ui-bad-tint); color: var(--ui-bad-text); border-color: var(--ui-bad-border); }
    .evd-msg-wait { background: var(--ui-warn-tint); color: var(--ui-warn-text); border-color: var(--ui-warn-border); }
    .evd-join-card { margin-top: 28px; max-width: 72ch; }
    .evd-join-title { margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--ui-text); }
    .evd-join { display: flex; flex-direction: column; gap: 6px; margin-top: 14px; }
    .evd-join input[type=file] { margin-bottom: 8px; font-size: 0.9375rem; }
    .evd-cta-btn { border: 0; cursor: pointer; }
    .evd-cta-quiet { background: var(--ui-surface); color: var(--ui-text-2); border: 1px solid var(--ui-border); }
    .evd-cta-quiet:hover { background: var(--ui-row-hover); color: var(--ui-text); }
    .evd-question { margin: 16px 0 0; font-size: 1rem; font-weight: 600; color: var(--ui-text); }
    .evd-step { margin: 0 0 8px; padding: 0; border: 0; }
    .evd-step legend.evd-question { margin-bottom: 8px; }
    .evd-pay { display: flex; flex-wrap: wrap; gap: 8px; }
    .evd-pay-option { display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 0 14px; border: 1px solid var(--ui-border); border-radius: var(--ui-r); font-size: 1rem; color: var(--ui-text); cursor: pointer; }
    .evd-pay-option:has(input:checked) { border-color: var(--ui-blue); background: var(--ui-blue-tint); color: var(--ui-blue-text); }
    .evd-optional { font-weight: 400; color: var(--ui-muted); }
    .evd-step-count { margin: 0 0 6px; font-size: 0.875rem; font-weight: 600; color: var(--ui-muted); }
    .evd-step legend.evd-join-title { margin-bottom: 12px; }
    .evd-join[data-step] { margin-top: 0; }
    .evd-wizard-nav { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    /* The steps and buttons set their own display; hidden has to win. */
    .evd-join-card [hidden], [data-join-submit][hidden] { display: none !important; }
    .evd-cta:disabled { opacity: 0.5; cursor: not-allowed; }
    @media (max-width: 860px) { .evd-grid { grid-template-columns: 1fr; gap: 24px; } }
    @media (max-width: 640px) {
        .ev-page { padding-top: 16px; }
        .evd-head h1 { font-size: 1.5rem; }
        .evd-head .ev-date { width: 56px; }
        .evd-head .ev-date-day { font-size: 1.5rem; }
    }
</style>
@endpush

@php
    // Whether this participant may still ask to join; the steps are in the form.
    $canJoin = $company && ! $onList && $joinRequest?->status !== 'pending' && $event->acceptsJoinRequests();
    $drawOpen =$event->polling_status === 'polling' && $event->polling_deadline && ! $event->polling_deadline->isPast();
@endphp

@section('content')
<div class="ev-page">
    <a href="{{ route('home') }}" class="evd-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> All events</a>

    @if($event->banner_url)
        <img class="evd-banner" src="{{ $event->banner_url }}" alt="">
    @endif

    <div class="evd-grid">
        <div>
            <div class="evd-head">
                <div class="ev-date" aria-hidden="true">
                    <span class="ev-date-month">{{ $event->start_date->format('M') }}</span>
                    <span class="ev-date-day">{{ $event->start_date->format('d') }}</span>
                </div>
                <div style="min-width:0;">
                    <h1>{{ $event->title }}</h1>
                    @include('partials.public-event-stage', ['event' => $event])
                </div>
            </div>

            @if($event->description)
                <p class="evd-about">{{ $event->description }}</p>
            @else
                <p class="evd-about" style="color:var(--ui-muted);">The office has not added a description for this event.</p>
            @endif

            {{-- Joining happens under the description, one question at a time
                 with Previous / Next. One form, because a picked file cannot
                 survive a page load; the script only shows one step at a time.
                 Without it every step shows at once and the form still sends.
                 The Join button is in the side card and shows on the last step.
                 A new question is one more [data-step] block. --}}
            @if($canJoin)
                <form method="POST" action="{{ route('events.join', $event) }}" enctype="multipart/form-data"
                      class="evd-card evd-join-card" id="join-form"
                      data-start="{{ $errors->hasAny(['payment_method', 'receipt', 'documents', 'documents.*']) ? 2 : 1 }}">
                    @csrf
                    <p class="evd-step-count" data-step-count hidden></p>

                    <div class="evd-join" data-step="1">
                        <fieldset class="evd-step">
                            <legend class="evd-join-title" id="join-heading">Do you want to join this event?</legend>
                            <div class="evd-pay">
                                <label class="evd-pay-option"><input type="radio" name="wants_to_join" value="yes"> Yes</label>
                                <label class="evd-pay-option"><input type="radio" name="wants_to_join" value="no"> No</label>
                            </div>
                        </fieldset>
                        <p class="evd-note" data-said-no hidden>No problem. You can change your mind until {{ $event->registration_close_at->format('d M Y, h:i A') }}.</p>
                    </div>

                    <div class="evd-join" data-step="2">
                        <fieldset class="evd-step">
                            <legend class="evd-question">How did you pay?</legend>
                            <div class="evd-pay">
                                @foreach(\App\Models\EventJoinRequest::PAYMENT_METHODS as $key => $label)
                                    <label class="evd-pay-option">
                                        <input type="radio" name="payment_method" value="{{ $key }}" @checked(old('payment_method') === $key) required>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('payment_method')<p class="evd-msg evd-msg-bad">{{ $message }}</p>@enderror
                        </fieldset>

                        <label class="evd-question" for="receipt">Payment receipt</label>
                        <input type="file" id="receipt" name="receipt" accept=".pdf,.jpg,.jpeg,.png" required>
                        @error('receipt')<p class="evd-msg evd-msg-bad">{{ $message }}</p>@enderror

                        <label class="evd-question" for="documents">Supporting documents <span class="evd-optional">(optional)</span></label>
                        <input type="file" id="documents" name="documents[]" accept=".pdf,.jpg,.jpeg,.png" multiple>
                        @error('documents')<p class="evd-msg evd-msg-bad">{{ $message }}</p>@enderror
                        @foreach($errors->get('documents.*') as $messages)
                            <p class="evd-msg evd-msg-bad">{{ $messages[0] }}</p>
                        @endforeach
                        <p class="evd-note" style="margin-top:0;">PDF or image, up to 5 MB each. Up to 10 supporting documents; hold Ctrl to pick several.</p>
                    </div>

                    <div class="evd-wizard-nav" data-nav hidden>
                        <button type="button" class="evd-cta evd-cta-btn evd-cta-quiet" data-prev>Previous</button>
                        <button type="button" class="evd-cta evd-cta-btn" data-next>Next</button>
                    </div>
                </form>

                <script>
                // After the page has loaded: the Join button is in the side card, further down.
                document.addEventListener('DOMContentLoaded', function () {
                    var form = document.getElementById('join-form');
                    var steps = form.querySelectorAll('[data-step]');
                    var count = form.querySelector('[data-step-count]');
                    var prev = form.querySelector('[data-prev]');
                    var next = form.querySelector('[data-next]');
                    var submit = document.querySelector('[data-join-submit]');
                    var saidNo = form.querySelector('[data-said-no]');
                    var current = Number(form.dataset.start) || 1;

                    // Step 1 answered No: nowhere to go next.
                    function blocked() {
                        var answer = form.querySelector('input[name=wants_to_join]:checked');
                        return current === 1 && (!answer || answer.value !== 'yes');
                    }

                    function show(n) {
                        current = n;
                        steps.forEach(function (s) { s.hidden = Number(s.dataset.step) !== n; });
                        count.textContent = 'Step ' + n + ' of ' + steps.length;
                        prev.disabled = n === 1;
                        next.hidden = n === steps.length;
                        next.disabled = blocked();
                        if (submit) submit.hidden = n !== steps.length;
                    }

                    // Next only past a step whose own fields are filled in.
                    next.addEventListener('click', function () {
                        var fields = form.querySelectorAll('[data-step="' + current + '"] input');
                        for (var i = 0; i < fields.length; i++) {
                            if (!fields[i].reportValidity()) return;
                        }
                        show(current + 1);
                        form.scrollIntoView({ block: 'start', behavior: 'smooth' });
                    });
                    prev.addEventListener('click', function () { show(current - 1); });

                    form.addEventListener('change', function (e) {
                        if (e.target.name !== 'wants_to_join') return;
                        saidNo.hidden = e.target.value !== 'no';
                        next.disabled = blocked();
                    });

                    if (current > 1) form.querySelector('input[name=wants_to_join][value=yes]').checked = true;
                    count.hidden = false;
                    form.querySelector('[data-nav]').hidden = false;
                    show(current);
                });
                </script>
            @endif
        </div>

        <aside class="evd-card" aria-label="Event details">
            <div class="evd-fact">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <div>
                    <div class="evd-fact-label">When</div>
                    <div class="evd-fact-value">
                        {{ $event->start_date->format('d M Y') }}@unless($event->end_date->isSameDay($event->start_date)) – {{ $event->end_date->format('d M Y') }}@endunless
                    </div>
                </div>
            </div>
            <div class="evd-fact">
                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                <div>
                    <div class="evd-fact-label">Where</div>
                    <div class="evd-fact-value">{{ $event->location }}@if($event->state), {{ $event->state }}@endif</div>
                </div>
            </div>
            @if($event->polling_start_date && $event->polling_deadline)
                <div class="evd-fact">
                    <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
                    <div>
                        <div class="evd-fact-label">Lot draw</div>
                        <div class="evd-fact-value">
                            {{ $event->polling_start_date->format('d M Y, h:i A') }}<br>
                            to {{ $event->polling_deadline->format('d M Y, h:i A') }}
                        </div>
                    </div>
                </div>
            @endif

            @if($event->registration_open_at && $event->registration_close_at)
                <div class="evd-fact">
                    <i class="bi bi-person-plus" aria-hidden="true"></i>
                    <div>
                        <div class="evd-fact-label">Registration</div>
                        <div class="evd-fact-value">
                            {{ $event->registration_open_at->format('d M Y, h:i A') }}<br>
                            to {{ $event->registration_close_at->format('d M Y, h:i A') }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- After a send, the pending box below already says it. --}}
            @if(session('success') && $joinRequest?->status !== 'pending')
                <p class="evd-msg evd-msg-ok" role="status">{{ session('success') }}</p>
            @endif
            @if(session('error'))
                <p class="evd-msg evd-msg-bad" role="alert">{{ session('error') }}</p>
            @endif

            @if($onList)
                <p class="evd-msg evd-msg-ok">Your company is in this event's draw.</p>
                <a href="{{ route('dashboard') }}" class="evd-cta"><i class="bi bi-check-circle" aria-hidden="true"></i> Go to my dashboard</a>
            @elseif($joinRequest?->status === 'pending')
                <p class="evd-msg evd-msg-wait">Your request to join was sent {{ $joinRequest->updated_at->format('d M Y') }}. The office is reviewing your documents.</p>
            @elseif($event->acceptsJoinRequests())
                @if($joinRequest?->status === 'rejected')
                    <p class="evd-msg evd-msg-bad"><strong>The office did not accept your request:</strong> {{ $joinRequest->reject_reason }} You may send it again.</p>
                @endif

                @guest('web')
                    <a href="{{ route('login') }}" class="evd-cta"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Sign in to join</a>
                @else
                    @if($canJoin)
                        {{-- Sends the form under the description; shown on its last step. --}}
                        <button type="submit" form="join-form" class="evd-cta evd-cta-btn" data-join-submit><i class="bi bi-person-plus" aria-hidden="true"></i> Join this event</button>
                    @endif
                @endguest
            @elseif($drawOpen)
                @auth('web')
                    <a href="{{ route('dashboard') }}" class="evd-cta"><i class="bi bi-check-circle" aria-hidden="true"></i> Go to my dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="evd-cta"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Sign in to confirm your lot</a>
                @endauth
            @endif
            <p class="evd-note">Your login is created by the office. Sign in to join an event; the office reviews your documents before adding you to the draw.</p>
        </aside>
    </div>
</div>
@endsection
