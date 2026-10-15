@extends('layouts.public')

@section('title', 'Acara — '.\App\Models\Setting::get('site_name', 'undilot'))
@section('own-heading', true)

@push('styles')
<style>
    .ev-page { max-width: 1120px; margin: 0 auto; padding: 40px 24px 16px; }
    .ev-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 28px; }
    .ev-head h1 { margin: 0 0 6px; font-size: 2.25rem; font-weight: 700; letter-spacing: -0.02em; color: var(--ui-text); }
    .ev-head p { max-width: 60ch; margin: 0; font-size: 1rem; color: var(--ui-muted); }
    .ev-search { display: flex; gap: 8px; flex: 0 1 420px; }
    .ev-search input { flex: 1; min-width: 0; height: 48px; padding: 0 14px; border: 1px solid var(--ui-border-strong); border-radius: var(--ui-r); background: var(--ui-surface); font-size: 1rem; }
    .ev-search input:focus { outline: none; border-color: var(--ui-blue); box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.18); }
    .ev-search button { min-height: 48px; padding: 0 20px; border: none; border-radius: var(--ui-r); background: var(--ui-blue); color: #fff; font-size: 1rem; font-weight: 600; cursor: pointer; }
    .ev-search button:hover { background: var(--ui-blue-hover); }
    .ev-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap: 20px; }
    .ev-card { display: flex; flex-direction: column; overflow: hidden; background: var(--ui-surface); border: 1px solid var(--ui-border); border-radius: var(--ui-r-lg); box-shadow: var(--ui-shadow-1); transition: box-shadow var(--ui-fast) var(--ui-ease); }
    .ev-card:hover { box-shadow: var(--ui-shadow-2); }
    .ev-banner { width: 100%; aspect-ratio: 797 / 235; object-fit: cover; }
    .ev-body { flex: 1; display: flex; flex-direction: column; gap: 8px; padding: 18px 20px 20px; }
    .ev-title { font-size: 1.125rem; font-weight: 700; line-height: 1.3; color: var(--ui-text); }
    .ev-meta { display: flex; align-items: baseline; gap: 8px; font-size: 1rem; color: var(--ui-muted); }
    .ev-draw { margin-top: auto; padding-top: 14px; border-top: 1px solid var(--ui-sep); font-size: 1rem; line-height: 1.5; color: var(--ui-text-2); }
    .ev-draw a { font-weight: 600; }
    .ev-section { margin: 44px 0 16px; font-size: 1.125rem; font-weight: 700; color: var(--ui-text); }
    .ev-empty { padding: 40px; border: 1px dashed var(--ui-border-strong); border-radius: var(--ui-r-lg); background: var(--ui-surface); text-align: center; font-size: 1rem; color: var(--ui-muted); }
    @media (max-width: 640px) {
        .ev-page { padding: 24px 16px 8px; }
        .ev-head h1 { font-size: 1.5rem; }
        .ev-search { flex: 1 1 100%; }
        .ev-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="ev-page">
    <div class="ev-head">
        <div>
            <h1>Acara</h1>
            <p>Pameran kraf dan cabutan lotnya. Syarikat dalam cabutan mengesahkan lot selepas log masuk.</p>
        </div>
        <form method="GET" class="ev-search" role="search">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari acara atau lokasi" aria-label="Cari acara">
            <button type="submit">Cari</button>
        </form>
    </div>

    @forelse($upcoming as $event)
        @if($loop->first)<div class="ev-grid">@endif
        @include('partials.public-event-card', ['event' => $event])
        @if($loop->last)</div>@endif
    @empty
        <div class="ev-empty">
            {{ request('search') ? 'Tiada acara akan datang yang sepadan.' : 'Tiada acara akan datang buat masa ini.' }}
        </div>
    @endforelse

    @if($past->isNotEmpty())
        <div class="ev-section">Telah berlangsung</div>
        <div class="ev-grid">
            @foreach($past as $event)
                @include('partials.public-event-card', ['event' => $event])
            @endforeach
        </div>
    @endif
</div>
@endsection
