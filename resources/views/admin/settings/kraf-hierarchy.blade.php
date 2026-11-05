@extends('layouts.admin')

@section('title', 'Craft Hierarchy')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-header mb-4">
        <h2>Craft Hierarchy</h2>
        <div class="mac-breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep" aria-hidden="true">›</span> Setting <span class="sep" aria-hidden="true">›</span> Craft Hierarchy</div>
        <p class="mac-muted mb-0">
            What a company picks from: a category, a sub-category under it, and the
            levels below. This is the half of <code>kraf_bidangs</code> with no code —
            the <a href="{{ route('admin.settings.kraf-codes') }}">coding sheet</a> is the
            other half, and a sub-category may be mapped to one of its rows.
        </p>
    </div>

    <div class="mac-card mac-card-padded mb-3">
        <form method="POST" action="{{ route('admin.settings.kraf-hierarchy.store') }}" class="d-flex gap-2">
            @csrf
            <input type="text" name="name" class="mac-form-control" placeholder="New craft category"
                   aria-label="New craft category" required>
            <button type="submit" class="mac-btn-primary">Add category</button>
        </form>
    </div>

    @forelse($categories as $category)
        <div class="mac-card mac-card-padded mb-3">
            <div class="d-flex justify-content-between align-items-start">
                <h3 class="mac-card-title">{{ $category->name }}</h3>

                <form method="POST" action="{{ route('admin.settings.kraf-hierarchy.destroy', $category) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="mac-action-btn mac-action-btn-danger" title="Delete category"
                            data-confirm="Delete {{ $category->name }}?">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <h4 class="mac-section-title">Sub-categories</h4>

                    @forelse($category->subBidangs as $sub)
                        <div class="mac-detail-row">
                            <div class="mac-detail-label">
                                {{ $sub->name }}
                                @if($sub->codeBidang)
                                    <span class="mac-badge mac-badge-blue ms-1">{{ $sub->codeBidang->code }}</span>
                                @endif
                            </div>
                            <div class="mac-detail-value d-flex justify-content-between align-items-start gap-2">
                                <div class="flex-grow-1">
                                    @if($sub->pengelasan->isNotEmpty())
                                        <div class="mac-text-sm mac-muted mb-1 d-flex flex-wrap gap-1 align-items-center">
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

                                    <form method="POST"
                                          action="{{ route('admin.settings.kraf-hierarchy.pengelasan.store', $sub) }}"
                                          class="d-flex gap-1">
                                        @csrf
                                        <input type="text" name="name" class="mac-form-control mac-text-sm"
                                               placeholder="Add a classification"
                                               aria-label="Add a classification under {{ $sub->name }}" required>
                                        <button type="submit" class="mac-action-btn" title="Add">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </form>
                                </div>

                                <form method="POST"
                                      action="{{ route('admin.settings.kraf-hierarchy.sub.destroy', $sub) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mac-action-btn mac-action-btn-danger"
                                            title="Delete sub-category"
                                            data-confirm="Delete {{ $sub->name }}?">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="mac-muted mac-text-sm">None yet — a company cannot pick this category until there is one.</p>
                    @endforelse

                    <form method="POST"
                          action="{{ route('admin.settings.kraf-hierarchy.sub.store', $category) }}"
                          class="row g-2 mt-2">
                        @csrf
                        <div class="col-md-5">
                            <input type="text" name="name" class="mac-form-control" placeholder="New sub-category"
                                   aria-label="New sub-category under {{ $category->name }}" required>
                        </div>
                        <div class="col-md-5">
                            <select name="kraf_bidang_code_id" class="mac-select"
                                    aria-label="Craft code for this sub-category">
                                <option value="">— No craft code —</option>
                                @foreach($codes as $code)
                                    <option value="{{ $code->id }}">{{ $code->code }} — {{ $code->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="mac-btn-secondary w-100">Add</button>
                        </div>
                    </form>
                </div>

                <div class="col-lg-5">
                    <h4 class="mac-section-title">Products</h4>
                    <p class="mac-muted mac-text-sm">
                        Products hang off the category, not off a classification.
                    </p>

                    @foreach($category->produks as $produk)
                        <div class="d-flex justify-content-between align-items-center mac-text-sm py-1">
                            <span>{{ $produk->name }}</span>
                            <form method="POST"
                                  action="{{ route('admin.settings.kraf-hierarchy.produk.destroy', $produk) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="mac-action-btn mac-action-btn-danger" title="Delete">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        </div>
                    @endforeach

                    <form method="POST"
                          action="{{ route('admin.settings.kraf-hierarchy.produk.store', $category) }}"
                          class="d-flex gap-1 mt-2">
                        @csrf
                        <input type="text" name="name" class="mac-form-control" placeholder="New product"
                               aria-label="New product under {{ $category->name }}" required>
                        <button type="submit" class="mac-action-btn" title="Add"><i class="bi bi-plus-lg"></i></button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="mac-card mac-card-padded">
            <div class="mac-empty">
                <div class="mac-empty-icon"><i class="bi bi-diagram-3"></i></div>
                <p class="mac-empty-text">No craft categories yet.</p>
            </div>
        </div>
    @endforelse
</div>
@endsection
