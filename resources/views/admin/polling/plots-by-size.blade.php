@extends('layouts.admin')

@section('title', $plotSize->size.' Plots')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.polling.index') }}">Lot Draw</a> <span class="sep" aria-hidden="true">›</span> <a href="{{ route('admin.polling.show', $event) }}">{{ $event->title }}</a> <span class="sep" aria-hidden="true">›</span> {{ $plotSize->location }} {{ $plotSize->size }}
    </div>

    <div class="mac-header mb-4">
        <h2>{{ $plotSize->location }} — {{ $plotSize->size }} plots</h2>
        <p class="mac-muted mb-0">
            {{ $plots->count() }} plots, {{ $plots->where('is_allocated', false)->count() }} still free.
        </p>
    </div>

    <div class="mac-card">
        <div class="table-responsive">
            <table class="mac-table">
                <thead>
                    <tr>
                        <th>Plot</th>
                        <th>Craft code</th>
                        <th>Held by</th>
                        <th class="text-center">How</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plots as $plot)
                        <tr>
                            <td class="fw-semibold">{{ $plot->plot_code }}</td>
                            <td>{{ $plot->bidang?->code ?? 'Open pool' }}</td>
                            <td>
                                @if(! $plot->is_allocated)
                                    <span class="mac-muted">Free</span>
                                @elseif($plot->company)
                                    {{ $plot->company->name }}
                                @else
                                    {{-- Redacted: another zone's company. --}}
                                    <span class="mac-muted">Held (another zone)</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($plot->is_allocated)
                                    <span class="mac-badge {{ $plot->allocation_type === 'preset' ? 'mac-badge-blue' : 'mac-badge-green' }}">
                                        {{ $plot->allocation_type === 'preset' ? 'Preset' : 'Drawn' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="mac-empty">No plots built for this size yet.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
