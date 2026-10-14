@extends('layouts.admin')

@section('title', 'Dropdown Lists')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-header mb-4">
        <h2>Dropdown Lists</h2>
        <p class="mac-muted mb-0">
            The short lists the forms pick from. Each is stored as one settings row,
            so adding a value takes effect immediately and without a deploy.
        </p>
    </div>

    <div class="mac-card mac-card-padded mb-3">
        <h3 class="mac-card-title">Administrative Zones</h3>
        <p class="mac-muted mac-text-sm">
            A zone is an office, not a place — one covers Selangor, Kuala Lumpur and
            Putrajaya; Kedah and Langkawi are two. <strong>This list decides who sees
            what:</strong> a state admin reaches a company when the two strings match
            exactly. That is why a zone is renamed rather than retyped — renaming
            carries every admin, company and event filed under it across with the name.
        </p>

        <div class="table-responsive mb-3">
            <table class="mac-table">
                <thead>
                    <tr>
                        <th>Zone</th>
                        <th class="text-end">Admins</th>
                        <th class="text-end">Companies</th>
                        <th class="text-end">Events</th>
                        @if($canEditZones)<th class="text-end"></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($zones as $zone)
                        <tr>
                            <td>{{ $zone }}</td>
                            <td class="text-end">{{ $zoneUsage[$zone]['admins'] }}</td>
                            <td class="text-end">{{ $zoneUsage[$zone]['companies'] }}</td>
                            <td class="text-end">{{ $zoneUsage[$zone]['events'] }}</td>
                            @if($canEditZones)
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.settings.zones.destroy') }}"
                                          class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="zone" value="{{ $zone }}">
                                        <button type="submit" class="mac-action-btn mac-action-btn-danger"
                                                title="Remove zone"
                                                @disabled(array_sum($zoneUsage[$zone]) > 0)
                                                data-confirm="Remove the zone &quot;{{ $zone }}&quot;?">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($canEditZones)
            <div class="row g-3">
                <div class="col-md-5">
                    <form method="POST" action="{{ route('admin.settings.zones.store') }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="zone" class="mac-form-control" placeholder="New zone"
                               aria-label="New zone" required>
                        <button type="submit" class="mac-btn-secondary">Add</button>
                    </form>
                </div>

                <div class="col-md-7">
                    <form method="POST" action="{{ route('admin.settings.zones.rename') }}" class="d-flex gap-2">
                        @csrf
                        @method('PUT')
                        <select name="from" class="mac-select" aria-label="Zone to rename" required>
                            @include('partials.zone-options', ['placeholder' => 'Rename…'])
                        </select>
                        <input type="text" name="to" class="mac-form-control" placeholder="New name"
                               aria-label="New name" required>
                        <button type="submit" class="mac-btn-secondary"
                                data-confirm="Rename the zone and move every row filed under it?">
                            Rename
                        </button>
                    </form>
                </div>
            </div>
        @else
            <p class="mac-muted mac-text-sm mb-0">
                Zones decide who sees what, so only a super admin changes them.
            </p>
        @endif
    </div>

    @include('admin.settings.partials.managed-list', [
        'title' => 'Booth Sizes',
        'list' => 'plot_sizes',
        'values' => $plotSizes,
        'help' => 'What a draw entry\'s booth size may be set to, and what a plan is typed
                   against. Offered, not enforced: a size already in a plan or on a
                   registration stays visible beside these even if it is removed here.',
    ])

    @include('admin.settings.partials.managed-list', [
        'title' => 'Entrepreneur Categories',
        'list' => 'entrepreneur_categories',
        'values' => $entrepreneurCategories,
        'help' => 'What kind of body a company registers as. Two of these — government
                   agency and NGO — are treated differently by the draw, and THAT pair is
                   named in code rather than here: renaming one of them in this box does
                   not change which bodies the draw reserves a lot for.',
    ])
</div>
@endsection
