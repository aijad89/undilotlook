@extends('layouts.public')

@section('title', 'Upcoming Events — '.\App\Models\Setting::get('site_name', 'undilot'))
@section('page-title', 'Upcoming Events')
@section('own-heading', true)

@push('styles')
@include('partials.public-event-styles')
<style>
    .ev-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 20px; }
    .ev-head h1 { margin: 0 0 6px; font-size: 2.25rem; font-weight: 700; letter-spacing: -0.02em; color: var(--ui-text); }
    .ev-head p { max-width: 60ch; margin: 0; font-size: 1rem; color: var(--ui-muted); }
    .ev-search { display: flex; gap: 8px; flex: 0 1 420px; }
    .ev-search input { flex: 1; min-width: 0; height: 48px; padding: 0 14px; border: 1px solid var(--ui-border-strong); border-radius: var(--ui-r); background: var(--ui-surface); font-size: 1rem; }
    .ev-search input:focus { outline: none; border-color: var(--ui-blue); box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.18); }
    .ev-search button { min-height: 48px; padding: 0 20px; border: none; border-radius: var(--ui-r); background: var(--ui-blue); color: #fff; font-size: 1rem; font-weight: 600; cursor: pointer; }
    .ev-search button:hover { background: var(--ui-blue-hover); }
    /* Melon Ticket's list: 12px between cards, 30px inside, 40px from poster to text. */
    .ev-list { display: flex; flex-direction: column; gap: 12px; }
    .ev-row { position: relative; display: grid; grid-template-columns: 220px minmax(0, 1fr); column-gap: 40px; padding: 30px; background: var(--ui-surface); border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); box-shadow: var(--ui-shadow-1); transition: box-shadow var(--ui-fast) var(--ui-ease), border-color var(--ui-fast) var(--ui-ease); }
    .ev-row:hover { box-shadow: var(--ui-shadow-2); border-color: var(--ui-border-hover); }
    .ev-row:focus-within { outline: 3px solid var(--ui-focus); outline-offset: 2px; }

    /* Poster: the banner with the date on its corner, or the date alone on a plain panel. */
    .ev-poster { position: relative; min-width: 0; display: grid; align-content: start; }
    .ev-poster:not(.has-banner) { place-items: center; align-content: center; min-height: 140px; border-radius: var(--ui-r); background: var(--ui-surface-2); }
    .ev-poster img { display: block; width: 100%; aspect-ratio: 797 / 235; object-fit: cover; border-radius: var(--ui-r); }
    .ev-poster-frame { position: relative; }
    .ev-poster .ev-date { width: 84px; }
    .ev-poster .ev-date-day { padding: 10px 0 12px; font-size: 2.25rem; }
    .ev-poster.has-banner .ev-date { position: absolute; top: 8px; left: 8px; width: 52px; box-shadow: var(--ui-shadow-2); }
    .ev-poster.has-banner .ev-date-day { padding: 5px 0 6px; font-size: 1.25rem; }

    .ev-info { min-width: 0; display: flex; flex-direction: column; gap: 6px; }
    .ev-title { margin: 0 0 4px; font-size: 1.25rem; font-weight: 700; line-height: 1.3; }
    /* The title's link covers the whole card, so anywhere on it opens the event. */
    .ev-link { color: var(--ui-text); text-decoration: none; }
    .ev-link:focus { outline: none; }
    .ev-link::after { content: ''; position: absolute; inset: 0; }
    .ev-desc { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin: 6px 0 0; font-size: 1rem; line-height: 1.5; color: var(--ui-text-2); }
    .ev-foot { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 12px 16px; margin-top: auto; padding-top: 16px; }
    .ev-foot-note { margin: 0; font-size: 0.9375rem; color: var(--ui-muted); }

    /* What a visitor can do, by status, 240 × 56 at the card's bottom right. Only an open draw is a real button. */
    .ev-cta { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 240px; height: 56px; margin-left: auto; padding: 0 16px; border: 1px solid transparent; border-radius: var(--ui-r); font-size: 1rem; font-weight: 600; text-align: center; text-decoration: none; }
    .ev-cta-primary { position: relative; z-index: 1; background: var(--ui-blue); color: #fff; }
    .ev-cta-primary:hover { background: var(--ui-blue-hover); color: #fff; }
    .ev-cta-gold { border-color: var(--ui-gold); background: var(--ui-gold-tint); color: var(--ui-gold-text); }
    .ev-cta-info { background: var(--ui-info-tint); color: var(--ui-info-text); }
    .ev-cta-neutral { background: var(--ui-neutral-tint); color: var(--ui-neutral-text); }

    .ev-section { margin: 56px 0 20px; font-size: 1.375rem; font-weight: 700; color: var(--ui-text); }
    /* Past events are a record, not an invitation: a smaller poster and title. */
    .ev-past .ev-row { grid-template-columns: 160px minmax(0, 1fr); }
    .ev-past .ev-poster:not(.has-banner) { min-height: 100px; }
    .ev-past .ev-poster:not(.has-banner) .ev-date { width: 56px; }
    .ev-past .ev-poster:not(.has-banner) .ev-date-day { padding: 7px 0 8px; font-size: 1.5rem; }
    .ev-past .ev-title { font-size: 1.125rem; }
    .ev-more { display: flex; justify-content: center; margin-top: 20px; }
    .ev-more a { display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 0 24px; border: 1px solid var(--ui-border-strong); border-radius: var(--ui-r); background: var(--ui-surface); color: var(--ui-text); font-size: 1rem; font-weight: 600; text-decoration: none; }
    .ev-more a:hover { border-color: var(--ui-blue); color: var(--ui-blue-text); }
    .ev-empty { padding: 40px; border: 1px dashed var(--ui-border-strong); border-radius: var(--ui-r-lg); background: var(--ui-surface); text-align: center; font-size: 1rem; color: var(--ui-muted); }
    @media (prefers-reduced-motion: reduce) { .ev-row { transition: none; } }
    @media (max-width: 640px) {
        .ev-page { padding-top: 24px; }
        .ev-head h1 { font-size: 1.5rem; }
        .ev-search { flex: 1 1 100%; }
        .ev-row, .ev-past .ev-row { grid-template-columns: 1fr; row-gap: 14px; padding: 16px; }
        .ev-poster:not(.has-banner), .ev-past .ev-poster:not(.has-banner) { place-items: start; align-content: start; min-height: 0; background: none; }
        .ev-poster .ev-date { width: 56px; }
        .ev-poster .ev-date-day { padding: 7px 0 8px; font-size: 1.5rem; }
        .ev-cta { width: 100%; margin-left: 0; }
    }
</style>
@endpush

@section('content')
<div class="ev-page">
    <div class="ev-head">
        <div>
            <h1>Upcoming Events</h1>
            <p>Craft fairs and their lot draws. Companies in a draw confirm their lot after signing in.</p>
        </div>
        <form method="GET" class="ev-search" role="search">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search events or locations" aria-label="Search events">
            <button type="submit">Search</button>
        </form>
    </div>

    @forelse($upcoming as $event)
        @if($loop->first)<div class="ev-list">@endif
        @include('partials.public-event-card', ['event' => $event])
        @if($loop->last)</div>@endif
    @empty
        <div class="ev-empty">
            {{ request('search') ? 'No upcoming events match your search.' : 'No upcoming events right now.' }}
        </div>
    @endforelse

    @if($past->isNotEmpty())
        <h2 class="ev-section" id="past">Past events</h2>
        <div class="ev-list ev-past">
            @foreach($past->take($pastShown) as $event)
                @include('partials.public-event-card', ['event' => $event])
            @endforeach
        </div>
        @if($past->count() > $pastShown)
            <div class="ev-more">
                <a href="{{ request()->fullUrlWithQuery(['past' => $pastShown + $pastStep]) }}#past">
                    More <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </a>
            </div>
        @endif
    @endif
</div>
@endsection
