@extends('layouts.admin')

@section('title', 'Craft Hierarchy')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @unless(session('anchor'))
        @include('partials.admin-alerts')
    @endunless

    <div class="mac-header mb-4">
        <h2>Craft Hierarchy</h2>
        <div class="mac-breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep" aria-hidden="true">›</span> Setting <span class="sep" aria-hidden="true">›</span> Craft Hierarchy</div>
        <p class="mac-muted mb-0">
            What a company picks from: a category, a sub-category under it, and that
            sub-category's classifications and products. Craft codes are separate, in
            <a href="{{ route('admin.settings.kraf-codes') }}">Craft Category Code</a>.
        </p>
    </div>

    @forelse($categories as $category)
        <div class="mac-card mac-card-padded mb-3" id="category-{{ $category->id }}" style="scroll-margin-top: 80px">
            <h3 class="mac-card-title">{{ $category->name }}</h3>

            @if(session('anchor') === "category-{$category->id}")
                @include('partials.admin-alerts')
            @endif

            <h4 class="mac-section-title">Sub-categories</h4>

            @forelse($category->subBidangs as $sub)
                <div class="mac-detail-row">
                    <div class="mac-detail-label kh-sub-label">{{ $sub->name }}</div>

                    <div class="mac-detail-value kh-lines mac-text-sm mac-muted">
                        {{-- Each line: label, then [field] [+] [bin] in a fixed spot, tags underneath. --}}
                        <div class="kh-line">
                            <span class="kh-line-label">Classifications</span>
                            <div>
                                <div class="kh-line-controls">
                                    <form method="POST" class="d-flex gap-1 flex-grow-1"
                                          action="{{ route('admin.settings.kraf-hierarchy.pengelasan.store', $sub) }}">
                                        @csrf
                                        <input type="text" name="name" class="mac-form-control mac-text-sm"
                                               placeholder="Add classifications, comma-separated"
                                               aria-label="Add a classification under {{ $sub->name }}" required>
                                        <button type="submit" class="mac-action-btn" title="Add">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </form>
                                    {{-- This bin removes the sub-category itself, with all it holds. --}}
                                    <form method="POST"
                                          action="{{ route('admin.settings.kraf-hierarchy.sub.destroy', $sub) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mac-action-btn mac-action-btn-danger"
                                                title="Delete sub-category"
                                                data-confirm="Delete {{ $sub->name }}, with its classifications and products?">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                @if($sub->pengelasan->isNotEmpty())
                                    <div class="kh-line-items">
                                        @foreach($sub->pengelasan as $pengelasan)
                                            <span class="mac-badge mac-badge-gray d-inline-flex align-items-center gap-1">
                                                {{ $pengelasan->name }}
                                                <form method="POST"
                                                      action="{{ route('admin.settings.kraf-hierarchy.pengelasan.destroy', $pengelasan) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="mac-clear-btn p-0 border-0 bg-transparent"
                                                            title="Delete {{ $pengelasan->name }}"
                                                            aria-label="Delete {{ $pengelasan->name }}">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </form>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="kh-line">
                            <span class="kh-line-label">Products</span>
                            <div>
                                <div class="kh-line-controls">
                                    <form method="POST" class="d-flex gap-1 flex-grow-1"
                                          action="{{ route('admin.settings.kraf-hierarchy.produk.store', $sub) }}">
                                        @csrf
                                        <input type="text" name="name" class="mac-form-control mac-text-sm"
                                               placeholder="Add products, comma-separated"
                                               aria-label="Add a product under {{ $sub->name }}" required>
                                        <button type="submit" class="mac-action-btn" title="Add">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </form>
                                    <form method="POST"
                                          action="{{ route('admin.settings.kraf-hierarchy.produk.destroy-all', $sub) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mac-action-btn mac-action-btn-danger"
                                                title="Delete all products"
                                                data-confirm="Delete all {{ $sub->produks->count() }} products of {{ $sub->name }}?"
                                                @disabled($sub->produks->isEmpty())>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                @if($sub->produks->isNotEmpty())
                                    <div class="kh-line-items">
                                        @foreach($sub->produks as $produk)
                                            <span class="mac-badge mac-badge-gray d-inline-flex align-items-center gap-1">
                                                {{ $produk->name }}
                                                <form method="POST"
                                                      action="{{ route('admin.settings.kraf-hierarchy.produk.destroy', $produk) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="mac-clear-btn p-0 border-0 bg-transparent"
                                                            title="Delete {{ $produk->name }}"
                                                            aria-label="Delete {{ $produk->name }}">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </form>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="mac-muted mac-text-sm">None yet — a company cannot pick this category until there is one.</p>
            @endforelse

            <form method="POST"
                  action="{{ route('admin.settings.kraf-hierarchy.sub.store', $category) }}"
                  class="row g-2 mt-2">
                @csrf
                <div class="col-md-3">
                    <input type="text" name="name" class="mac-form-control" placeholder="New sub-category"
                           aria-label="New sub-category under {{ $category->name }}" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="classification" class="mac-form-control"
                           placeholder="Classifications (optional)"
                           aria-label="Classifications for the new sub-category under {{ $category->name }}, comma-separated">
                </div>
                <div class="col-md-3">
                    <input type="text" name="products" class="mac-form-control"
                           placeholder="Products (optional)"
                           aria-label="Products for the new sub-category under {{ $category->name }}, comma-separated">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="mac-btn-secondary w-100">Add</button>
                </div>
            </form>

            {{-- Products entered against the category alone, before products sat in a
                 sub-category. Companies cannot tick these; delete them, or add them
                 again under the right sub-category. --}}
            @if($category->produks->isNotEmpty())
                <div class="border-top mt-4 pt-3">
                    <h4 class="mac-section-title">Products not placed in a sub-category</h4>
                    <p class="mac-muted mac-text-sm">
                        Companies cannot pick these. Add each one again under its sub-category, then delete it here.
                    </p>
                    <div class="d-flex flex-wrap align-items-center gap-1 mac-text-sm mac-muted">
                        @foreach($category->produks as $produk)
                            <span class="mac-badge mac-badge-gray d-inline-flex align-items-center gap-1">
                                {{ $produk->name }}
                                <form method="POST"
                                      action="{{ route('admin.settings.kraf-hierarchy.produk.destroy', $produk) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mac-clear-btn p-0 border-0 bg-transparent"
                                            title="Delete {{ $produk->name }}"
                                            aria-label="Delete {{ $produk->name }}">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </form>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="mac-card mac-card-padded">
            <div class="mac-empty">
                <div class="mac-empty-icon"><i class="bi bi-diagram-3"></i></div>
                <p class="mac-empty-text">No craft categories yet.</p>
            </div>
        </div>
    @endforelse

    <style>
        /* Inside a sub-category row: one line per list, a fixed label column, tags flowing beside it. */
        .kh-sub-label { width: 160px; }
        .kh-lines { flex: 1 1 auto; display: flex; flex-direction: column; gap: 10px; text-align: left; }
        .kh-line { display: grid; grid-template-columns: 110px minmax(0, 1fr); gap: 8px; align-items: start; }
        .kh-line + .kh-line { border-top: 1px dashed var(--ui-border); padding-top: 10px; }
        .kh-line-label { padding-top: 6px; font-weight: 600; color: var(--ui-text-2); }
        .kh-line-controls { display: flex; gap: 4px; max-width: 520px; }
        .kh-line-items { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; margin-top: 8px; }
        .kh-line-items > .mac-badge { min-height: 32px; }
        @media (max-width: 640px) { .kh-line { grid-template-columns: 1fr; gap: 4px; } .kh-sub-label { width: auto; } }
    </style>
</div>
@endsection
