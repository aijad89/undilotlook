@extends('layouts.admin')
@section('title', 'Footer Menus')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">

    <div class="mac-header mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>Footer Menus</h2>
            <div class="mac-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="sep" aria-hidden="true">›</span>
                Footer Menus
            </div>
            <p class="mac-muted mb-0">The entries in the footer of the public site and the participant portal, up to 15.</p>
        </div>
        @if($menus->count() < 15)
            <a href="{{ route('admin.footer-menus.create') }}" class="mac-btn-primary">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                    <path d="M8 2v12M2 8h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                New Menu Item
            </a>
        @endif
    </div>

    @include('partials.admin-alerts')

    @if($menus->count() >= 15)
        <div class="alert alert-info mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Maximum 15 footer menu items reached (5+4+3+2+1 pyramid layout).
        </div>
    @endif

    <div class="mac-card">
        <div class="mac-card-header d-flex justify-content-between align-items-center">
            <div class="mac-card-title">
                <i class="bi bi-layout-text-sidebar"></i>
                Menu Items
            </div>
            <div class="mac-muted mt-1" style="font-size:0.8125rem;">Drag rows to reorder</div>
        </div>
        <div class="table-responsive">
            <table class="mac-table" id="footerMenuTable">
                <thead>
                    <tr>
                        <th style="width:36px"><span class="visually-hidden">Drag to reorder</span></th>
                        <th style="width:40px">#</th>
                        <th>Title</th>
                        <th style="width:100px">Status</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody id="sortableBody">
                    @forelse($menus as $menu)
                        <tr data-id="{{ $menu->id }}" style="cursor:grab;">
                            <td class="mac-muted" style="font-size:1rem;">
                                <i class="bi bi-grip-vertical"></i>
                            </td>
                            <td class="mac-muted">{{ $loop->iteration }}</td>
                            <td>
                                <div style="font-weight:500;">{{ $menu->title }}</div>
                                <div class="mac-muted" style="font-size:0.8125rem;margin-top:2px;">
                                    {{ Str::limit(strip_tags($menu->content), 80) }}
                                </div>
                            </td>
                            <td>
                                @if($menu->is_active)
                                    <span class="mac-status mac-status-green"><span class="mac-status-dot"></span>Active</span>
                                @else
                                    <span class="mac-status mac-status-gray"><span class="mac-status-dot"></span>Hidden</span>
                                @endif
                            </td>
                            <td style="text-align:right; white-space:nowrap;">
                                <a href="{{ route('admin.footer-menus.edit', $menu) }}" class="mac-action-btn">Edit</a>
                                <form action="{{ route('admin.footer-menus.destroy', $menu) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="mac-action-btn-danger"
                                            data-confirm="Delete « {{ $menu->title }} »?">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="mac-empty py-5">
                                    <div class="mac-empty-icon"><i class="bi bi-layout-text-sidebar"></i></div>
                                    <div class="mac-empty-text">No footer menu items yet</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pyramid preview --}}
    @if($menus->count() > 0)
        <div class="mac-card mac-card-padded mt-3">
            <h3 class="mac-card-title"><i class="bi bi-eye" aria-hidden="true"></i> Footer Preview</h3>
            @php
                $items = $menus->where('is_active', true)->values();
                $rows = [];
                $i = 0;
                $pattern = [5, 4];
                $pIdx = 0;
                while ($i < $items->count()) {
                    $size = $pattern[$pIdx % 2];
                    $rows[] = $items->slice($i, $size)->values();
                    $i += $size;
                    $pIdx++;
                }
            @endphp
            <div style="background:var(--ui-surface-2);border:1px solid var(--ui-sep);border-radius:var(--ui-r-lg);padding:20px 16px;">
                @foreach($rows as $row)
                    <div class="d-flex justify-content-center gap-4 mb-2 flex-wrap">
                        @foreach($row as $item)
                            {{-- Drawn as the public footer draws it: an underlined text button. --}}
                            <span style="color:var(--ui-text-2);font-size:1rem;text-decoration:underline;text-underline-offset:3px;">
                                {{ $item->title }}
                            </span>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"
        integrity="sha384-eeLEhtwdMwD3X9y+8P3Cn7Idl/M+w8H4uZqkgD/2eJVkWIN1yKzEj6XegJ9dL3q0" crossorigin="anonymous"></script>
<script>
    const tbody = document.getElementById('sortableBody');
    if (tbody) {
        Sortable.create(tbody, {
            animation: 150,
            handle: 'tr',
            ghostClass: 'sortable-ghost',
            onEnd: function () {
                const ids = [...tbody.querySelectorAll('tr[data-id]')].map(r => r.dataset.id);
                fetch('{{ route('admin.footer-menus.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ ids })
                });
            }
        });
    }
</script>
@endpush

@push('styles')
<style>
    .sortable-ghost { opacity: 0.4; background: var(--ui-blue-tint); }
</style>
@endpush
