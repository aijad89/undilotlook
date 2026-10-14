@extends('layouts.admin')

@section('title', 'Craft Category Code')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-header mb-4">
        <h2>Craft Category Code</h2>
        <div class="mac-breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep" aria-hidden="true">›</span> Dropdown Lists <span class="sep" aria-hidden="true">›</span> Craft Category Code</div>
        <p class="mac-muted mb-0">
            The printed coding sheet. A draw entry carries one of these codes as a
            STRING, and a plot is categorised by the row itself — that is what the
            allocator matches on. This is one half of <code>kraf_bidangs</code>; the
            other half is the <a href="{{ route('admin.settings.kraf-hierarchy') }}">craft
            hierarchy</a>, and the two never mix.
        </p>
    </div>

    <div class="mac-card mac-card-padded mb-3">
        <h3 class="mac-card-title">Add a code</h3>

        <form method="POST" action="{{ route('admin.settings.kraf-codes.store') }}">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="mac-label" for="bil">No.</label>
                    <input type="number" id="bil" name="bil" class="mac-form-control" min="1" value="{{ old('bil') }}">
                </div>
                <div class="col-md-2">
                    <label class="mac-label" for="code">Code</label>
                    <input type="text" id="code" name="code" class="mac-form-control"
                           value="{{ old('code') }}" maxlength="20" required>
                </div>
                <div class="col-md-4">
                    <label class="mac-label" for="name">Craft</label>
                    <input type="text" id="name" name="name" class="mac-form-control"
                           value="{{ old('name') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="mac-label" for="pecahan_bidang">Breakdown</label>
                    <input type="text" id="pecahan_bidang" name="pecahan_bidang" class="mac-form-control"
                           value="{{ old('pecahan_bidang') }}">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="mac-btn-primary w-100">Add</button>
                </div>
            </div>
        </form>
    </div>

    <div class="mac-card">
        <div class="table-responsive">
            <table class="mac-table">
                <thead>
                    <tr>
                        {{-- One header over the row's own grid, so each name sits over its
                             field: the fields live in one wide cell, not in the columns. --}}
                        <th colspan="4">
                            <div class="row g-1">
                                <div class="col-md-1">No.</div>
                                <div class="col-md-2">Code</div>
                                <div class="col-md-3">Craft</div>
                                <div class="col-md-5">Breakdown</div>
                            </div>
                        </th>
                        <th style="width:110px;">Offered</th>
                        <th class="text-end" style="width:140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($codes as $code)
                        <tr>
                            <td colspan="4">
                                <form method="POST" action="{{ route('admin.settings.kraf-codes.update', $code) }}"
                                      class="row g-1" id="code-{{ $code->id }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="col-md-1">
                                        <input type="number" name="bil" class="mac-form-control"
                                               value="{{ $code->bil }}" aria-label="Number" min="1">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="code" class="mac-form-control"
                                               value="{{ $code->code }}" aria-label="Code" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" name="name" class="mac-form-control"
                                               value="{{ $code->name }}" aria-label="Craft" required>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" name="pecahan_bidang" class="mac-form-control"
                                               value="{{ $code->pecahan_bidang }}" aria-label="Breakdown">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="submit" class="mac-action-btn" title="Save">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </div>
                                </form>
                            </td>
                            <td data-label="Status">
                                <span class="mac-badge {{ $code->is_active ? 'mac-badge-green' : 'mac-badge-gray' }}">
                                    {{ $code->is_active ? 'Offered' : 'Withdrawn' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.settings.kraf-codes.toggle', $code) }}"
                                      class="d-inline">
                                    @csrf
                                    <button type="submit" class="mac-action-btn"
                                            title="{{ $code->is_active ? 'Withdraw' : 'Offer again' }}">
                                        <i class="bi bi-{{ $code->is_active ? 'eye-slash' : 'eye' }}"></i>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.settings.kraf-codes.destroy', $code) }}"
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mac-action-btn mac-action-btn-danger"
                                            title="Delete"
                                            data-confirm="Delete {{ $code->code }}? Withdrawing it is usually what is meant.">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="mac-empty">
                                    <div class="mac-empty-icon"><i class="bi bi-upc"></i></div>
                                    <p class="mac-empty-text">No codes yet. The draw cannot match anything until there are.</p>
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
