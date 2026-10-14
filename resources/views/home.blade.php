@extends('layouts.public')

@section('title', 'Acara — '.\App\Models\Setting::get('site_name', 'undilot'))
@section('own-heading', true)

@push('styles')
<style>
    .ev-page { max-width: 1080px; margin: 0 auto; padding: 32px 20px 48px; }
    .ev-head { display: flex; justify-content: space-between; align-items: end; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
    .ev-head h1 { font-size: 1.6rem; font-weight: 700; margin: 0; letter-spacing: -0.02em; }
    .ev-head p { color: #6E6E73; margin: 4px 0 0; }
    .ev-search { display: flex; gap: 8px; }
    .ev-search input { border: 1px solid rgba(60,60,67,0.18); border-radius: 10px; padding: 8px 12px; min-width: 220px; }
    .ev-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
    .ev-card { background: #fff; border: 1px solid rgba(60,60,67,0.1); border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; }
    .ev-banner { aspect-ratio: 797 / 235; background: linear-gradient(135deg, #E0EBFF, #F2F2F7); object-fit: cover; width: 100%; }
    .ev-body { padding: 14px 16px 16px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
    .ev-title { font-weight: 650; font-size: 1.02rem; color: #1C1C1E; }
    .ev-meta { font-size: 0.84rem; color: #6E6E73; display: flex; gap: 6px; align-items: baseline; }
    .ev-draw { margin-top: auto; padding-top: 8px; font-size: 0.82rem; color: #1C1C1E; border-top: 1px solid rgba(60,60,67,0.08); }
    .ev-section { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; color: #6E6E73; margin: 32px 0 12px; }
    .ev-empty { background: #fff; border: 1px dashed rgba(60,60,67,0.2); border-radius: 14px; padding: 32px; text-align: center; color: #6E6E73; }
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
            <button type="submit" class="pub-btn-primary" style="border:none;">Cari</button>
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
