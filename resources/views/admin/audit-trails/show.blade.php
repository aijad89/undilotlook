@extends('layouts.admin')

@section('title', 'Audit Detail')

@section('content')
@include('partials.mac-styles')
<div class="mac-page">

    {{-- Header --}}
    <div class="mac-header d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2>Audit Detail #{{ $auditTrail->id }}</h2>
            <div class="mac-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="sep" aria-hidden="true">›</span>
                <a href="{{ route('admin.audit-trails.index') }}">Audit Trail</a>
                <span class="sep" aria-hidden="true">›</span>
                Detail
            </div>
        </div>
        <a href="{{ route('admin.audit-trails.index') }}" class="mac-btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="row g-4">
        {{-- Info Card --}}
        <div class="col-md-6">
            <div class="mac-card">
                <div class="mac-card-header"><h3 class="mac-card-title">General info</h3></div>
                <div class="mac-detail-row">
                    <span class="mac-detail-label">Action</span>
                    <span class="mac-detail-value">
                        @php [$badgeClass, $badgeLabel] = $auditTrail->action_badge; @endphp
                        @php
                            $macColor = match(true) {
                                str_contains($badgeClass, 'success') => 'green',
                                str_contains($badgeClass, 'danger') => 'red',
                                str_contains($badgeClass, 'warning') => 'orange',
                                str_contains($badgeClass, 'info') => 'blue',
                                default => 'gray',
                            };
                        @endphp
                        <span class="mac-status mac-status-{{ $macColor }}">
                            <span class="mac-status-dot"></span>
                            {{ $badgeLabel }}
                        </span>
                    </span>
                </div>
                <div class="mac-detail-row">
                    <span class="mac-detail-label">Model</span>
                    <span class="mac-detail-value">{{ $auditTrail->model_label }} <code>#{{ $auditTrail->auditable_id ?? '—' }}</code></span>
                </div>
                <div class="mac-detail-row">
                    <span class="mac-detail-label">Performer</span>
                    <span class="mac-detail-value">
                        @if($auditTrail->performer)
                            <span class="mac-status mac-status-gray">
                                <span class="mac-status-dot"></span>
                                {{ class_basename($auditTrail->performer_type) }}
                            </span>
                            {{ $auditTrail->performer->name ?? $auditTrail->performer->email ?? '—' }}
                        @else
                            <span class="mac-muted">System</span>
                        @endif
                    </span>
                </div>
                <div class="mac-detail-row">
                    <span class="mac-detail-label">IP Address</span>
                    <span class="mac-detail-value">{{ $auditTrail->ip_address ?? '—' }}</span>
                </div>
                <div class="mac-detail-row">
                    <span class="mac-detail-label">URL</span>
                    <span class="mac-detail-value mac-text-sm" style="word-break: break-all; text-align: right; max-width: 300px;">{{ $auditTrail->url ?? '—' }}</span>
                </div>
                <div class="mac-detail-row">
                    <span class="mac-detail-label">User Agent</span>
                    <span class="mac-detail-value mac-text-sm" style="word-break: break-all; text-align: right; max-width: 300px;">{{ $auditTrail->user_agent ?? '—' }}</span>
                </div>
                <div class="mac-detail-row">
                    <span class="mac-detail-label">Date</span>
                    <span class="mac-detail-value">{{ $auditTrail->created_at->format('d M Y, H:i:s') }}</span>
                </div>
            </div>
        </div>

        {{-- Changes Card --}}
        <div class="col-md-6">
            <div class="mac-card">
                <div class="mac-card-header"><h3 class="mac-card-title">Changes</h3></div>
                @php $fields = $auditTrail->changed_fields; @endphp

                @if(!empty($fields))
                    <div class="table-responsive">
                        <table class="mac-table">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Old Value</th>
                                    <th>New Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fields as $field => $change)
                                    <tr>
                                        <td style="font-weight: 600;">{{ $field }}</td>
                                        <td>
                                            @if($change['old'] !== null)
                                                <span style="color: var(--ui-bad-text);">{{ is_array($change['old']) ? json_encode($change['old']) : $change['old'] }}</span>
                                            @else
                                                <span class="mac-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($change['new'] !== null)
                                                <span style="color: var(--ui-ok-text);">{{ is_array($change['new']) ? json_encode($change['new']) : $change['new'] }}</span>
                                            @else
                                                <span class="mac-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif($auditTrail->old_values)
                    <div style="padding: 12px 20px 20px;">
                        <div style="font-size: 0.78rem; font-weight: 600; color: var(--ui-muted); margin-bottom: 8px;">Deleted Values</div>
                        <pre style="background: var(--ui-neutral-tint); padding: 16px; border-radius: 10px; font-size: 0.82rem; color: var(--ui-text); margin: 0;">{{ json_encode($auditTrail->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

        {{-- The screen
                             shows ONE row and a delete usually takes more than one: related
                             records go by database-level cascade, which Eloquent never sees.

                             Where a count summary exists it is shown, and the wording changes
                             with it: "cannot show what else went" is no longer true for a model
                             that carries one, and leaving that sentence standing over a table of
                             counts would be its own kind of wrong record.

                             Company, User and Event carry one, and so do the lot plan's own
                             `plan_rewrite` / `plan_cleared` rows. Nothing below names any of
                             them: the block reads whatever keys the summary carries, so another
                             model needs no change here — which is why `owner_deleted` is reached
                             through array_key_exists rather than assumed. --}}
                        @php $summary = $auditTrail->new_values; @endphp

                        @if(is_array($summary) && isset($summary['cascade_deleted']))
                            <div style="font-size: 0.78rem; font-weight: 600; color: var(--ui-muted); margin: 20px 0 8px;">
                                What Went With It
                            </div>

                            <div class="table-responsive">
                                <table class="mac-table">
                                    <thead>
                                        <tr><th>Table</th><th>Rows</th><th>What happened</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($summary['cascade_deleted'] as $table => $count)
                                            <tr>
                                                <td style="font-weight: 600;">{{ $table }}</td>
                                                <td>
                                                    @if($count > 0)
                                                        <span style="color: var(--ui-bad-text); font-weight: 600;">{{ number_format($count) }}</span>
                                                    @else
                                                        <span class="mac-muted">0</span>
                                                    @endif
                                                </td>
                                                <td><span class="mac-badge mac-badge-red">Deleted</span></td>
                                            </tr>
                                        @endforeach
                                        @foreach($summary['links_cleared'] ?? [] as $column => $count)
                                            <tr>
                                                <td style="font-weight: 600;">{{ $column }}</td>
                                                <td>
                                                    @if($count > 0)
                                                        <span style="color: var(--ui-warn-text); font-weight: 600;">{{ number_format($count) }}</span>
                                                    @else
                                                        <span class="mac-muted">0</span>
                                                    @endif
                                                </td>
                                                <td><span class="mac-badge mac-badge-orange">Link cleared</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="alert alert-info mt-3 mb-0" style="font-size: 0.82rem;">
                                <i class="bi bi-info-circle me-1"></i>
                                <strong>Counts, not contents.</strong>
                                The numbers above were taken immediately before the deletion and say
                                <em>how many</em> rows each table lost, not which ones.
                                <strong>Link cleared</strong> means those rows are still there and have
                                only lost a reference — a lot still exists, it has just stopped naming
                                the company that held it.
                                @if(array_key_exists('owner_deleted', $summary))
                                    @php
                                        $ownerNote = $summary['owner_deleted']
                                            ? 'was deleted with it, and its own rows are counted above.'
                                            : 'was kept, which is why every count hanging off the account reads 0.';
                                    @endphp
                                    The owner account <strong>{{ $ownerNote }}</strong>
                                @endif
                            </div>
                        @else
                            <div class="alert alert-info mt-3 mb-0" style="font-size: 0.82rem;">
                                <i class="bi bi-info-circle me-1"></i>
                                <strong>This is the deleted record only.</strong>
                                Rows in related tables are removed by the database along with it —
                                registrations, documents, history and sub-records — and those removals
                                are not recorded here. This screen cannot show what else went with it.
                            </div>
                        @endif
                    </div>
                @elseif($auditTrail->new_values)
                    <div style="padding: 12px 20px 20px;">
                        <div style="font-size: 0.78rem; font-weight: 600; color: var(--ui-muted); margin-bottom: 8px;">Created Values</div>
                        <pre style="background: var(--ui-neutral-tint); padding: 16px; border-radius: 10px; font-size: 0.82rem; color: var(--ui-text); margin: 0;">{{ json_encode($auditTrail->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @else
                    <div class="mac-empty">
                        <div class="mac-empty-text">No data changes recorded.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
