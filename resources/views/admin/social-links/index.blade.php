@extends('layouts.admin')
@section('title', 'Social Links')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">

    <div class="mac-header mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h2>Social Links</h2>
            <div class="mac-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="sep" aria-hidden="true">›</span>
                Social Links
            </div>
            <p class="mac-muted mb-0">The icons in the site footer, in the order shown here.</p>
        </div>
        <a href="{{ route('admin.social-links.create') }}" class="mac-btn-primary">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <path d="M8 2v12M2 8h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            New Social Link
        </a>
    </div>

    @include('partials.admin-alerts')

    <div class="mac-card">
        <div class="mac-card-header d-flex justify-content-between align-items-center">
            <div class="mac-card-title">
                <i class="bi bi-share"></i>
                Follow Us Buttons
            </div>
            <div class="mac-muted" style="font-size:0.8125rem;">Drag rows to reorder</div>
        </div>
        <div class="table-responsive">
            <table class="mac-table">
                <thead>
                    <tr>
                        <th style="width:36px"><span class="visually-hidden">Drag to reorder</span></th>
                        <th style="width:40px">#</th>
                        <th>Platform</th>
                        <th>Icon</th>
                        <th>URL</th>
                        <th style="width:90px">Status</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody id="sortableBody">
                    @forelse($links as $link)
                        <tr data-id="{{ $link->id }}" style="cursor:grab;">
                            <td class="mac-muted"><i class="bi bi-grip-vertical"></i></td>
                            <td class="mac-muted">{{ $loop->iteration }}</td>
                            <td style="font-weight:500;">{{ $link->label }}</td>
                            <td>
                                @if($link->icon_type === 'bootstrap')
                                    <i class="bi {{ $link->icon_class }}" style="font-size:1.25rem;"></i>
                                    <span class="mac-muted" style="font-size:0.75rem;margin-left:6px;">{{ $link->icon_class }}</span>
                                @else
                                    @if($link->icon_image)
                                        <img loading="lazy" src="{{ $link->icon_image_url }}" width="16" height="24"
                                             style="object-fit:contain;border-radius:4px;" alt="{{ $link->label }}">
                                        <span class="mac-muted" style="font-size:0.75rem;margin-left:6px;">Custom</span>
                                    @else
                                        <span class="mac-muted">—</span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <a href="{{ $link->url }}" target="_blank" rel="noopener"
                                   style="font-size:0.8125rem;color:var(--ui-blue-text);text-decoration:none;word-break:break-all;">
                                    {{ Str::limit($link->url, 50) }}
                                </a>
                            </td>
                            <td>
                                @if($link->is_active)
                                    <span class="mac-status mac-status-green"><span class="mac-status-dot"></span>Active</span>
                                @else
                                    <span class="mac-status mac-status-gray"><span class="mac-status-dot"></span>Hidden</span>
                                @endif
                            </td>
                            <td style="text-align:right;white-space:nowrap;">
                                <a href="{{ route('admin.social-links.edit', $link) }}" class="mac-action-btn">Edit</a>
                                <form action="{{ route('admin.social-links.destroy', $link) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="mac-action-btn-danger"
                                            data-confirm="Delete {{ $link->label }}?">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="mac-empty py-5">
                                    <div class="mac-empty-icon"><i class="bi bi-share"></i></div>
                                    <div class="mac-empty-text">No social links yet</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

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
            ghostClass: 'sortable-ghost',
            onEnd: function () {
                const ids = [...tbody.querySelectorAll('tr[data-id]')].map(r => r.dataset.id);
                fetch('{{ route('admin.social-links.reorder') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ ids })
                });
            }
        });
    }
</script>
@endpush

@push('styles')
<style>.sortable-ghost { opacity:0.4; background:var(--ui-blue-tint); }</style>
@endpush
