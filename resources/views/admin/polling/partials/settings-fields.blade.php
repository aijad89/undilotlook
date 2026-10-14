{{--
    When the draw runs. Shared by the create wizard's first page and the edit
    form, so the two cannot say different things about the same columns.

    Expects:
      $events — events available to configure, or null when editing
      $event  — the Event being edited, or null while creating
      $draft  — the create wizard's session draft (absent when editing)

    Creating picks the event and always starts in Draft; editing names the event
    it is on and shows its stage, which only the draw page's buttons move.
    Everything below that row is identical.
--}}
@php
    $event  = $event ?? null;
    $draft  = $draft ?? [];
    $isEdit = (bool) $event;

    $oldStart = old('polling_start_date', $draft['polling_start_date'] ?? $event?->polling_start_date);
    $oldEnd   = old('polling_deadline', $draft['polling_deadline'] ?? $event?->polling_deadline);
@endphp

<div class="row g-3">
    {{-- Row 1: Event | Status --}}
    <div class="col-md-6">
        <label class="mac-label" for="eventSelect">Event @unless($isEdit)<span class="text-danger">*</span>@endunless</label>
        @if($isEdit)
            <select id="eventSelect" class="mac-select" disabled>
                <option selected>{{ $event->title }}</option>
            </select>
        @else
            <select name="event_id" class="mac-select" required id="eventSelect">
                <option value="">-- Select Event --</option>
                @foreach($events as $selectable)
                    <option value="{{ $selectable->id }}" {{ old('event_id', $draft['event_id'] ?? null) == $selectable->id ? 'selected' : '' }}>
                        {{ $selectable->title }}
                    </option>
                @endforeach
            </select>
        @endif
        @error('event_id')<small class="text-danger">{{ $message }}</small>@enderror
    </div>
    <div class="col-md-6">
        @if($isEdit)
            <label class="mac-label">Polling Status</label>
            <div class="mt-1">
                <span class="mac-status {{ $event->polling_status_colour }}">
                    <span class="mac-status-dot"></span> {{ $event->polling_status_label }}
                </span>
                <small class="mac-muted ms-2">Moved from the lot draw page, where the checks that guard each step run.</small>
            </div>
        @else
            <label class="mac-label">Initial Status</label>
            <div class="mt-1">
                <span class="mac-status mac-status-gray">
                    <span class="mac-status-dot"></span> Draft
                </span>
                <small class="mac-muted ms-2">Always starts as Draft. Open voting from the lot draw page when ready.</small>
            </div>
        @endif
    </div>

    {{-- Row 2: the polling window. Both dates are required: every screen that
         shows one reads these columns, and a draw with neither is one none of
         them can say anything about. --}}
    <div class="col-md-6">
        <label class="mac-label" for="start_date_d">Polling Start Date <span class="text-danger">*</span></label>
        <div class="d-flex gap-2">
            <input type="date" id="start_date_d" class="mac-form-control" required
                   value="{{ $oldStart ? \Carbon\Carbon::parse($oldStart)->format('Y-m-d') : '' }}">
            <input type="time" id="start_date_t" class="mac-form-control" style="max-width:130px;" aria-label="Polling start time" required
                   value="{{ $oldStart ? \Carbon\Carbon::parse($oldStart)->format('H:i') : '00:00' }}">
        </div>
        <input type="hidden" name="polling_start_date" id="polling_start_date">
        @error('polling_start_date')<small class="text-danger">{{ $message }}</small>@enderror
    </div>
    <div class="col-md-6">
        <label class="mac-label" for="deadline_d">Polling End Date <small class="text-muted">(Deadline)</small> <span class="text-danger">*</span></label>
        <div class="d-flex gap-2">
            <input type="date" id="deadline_d" class="mac-form-control" required
                   value="{{ $oldEnd ? \Carbon\Carbon::parse($oldEnd)->format('Y-m-d') : '' }}">
            <input type="time" id="deadline_t" class="mac-form-control" style="max-width:130px;" aria-label="Polling deadline time" required
                   value="{{ $oldEnd ? \Carbon\Carbon::parse($oldEnd)->format('H:i') : '23:59' }}">
        </div>
        <input type="hidden" name="polling_deadline" id="polling_deadline">
        @error('polling_deadline')<small class="text-danger">{{ $message }}</small>@enderror
    </div>

    {{-- Row 3: Auto toggles --}}
    <div class="col-md-6">
        <label class="mac-label">Auto-Open Polling</label>
        <div class="form-check form-switch mt-1">
            <input type="hidden" name="auto_polling_open" value="0">
            <input class="form-check-input" type="checkbox" name="auto_polling_open" value="1" id="auto_polling_open"
                   {{ old('auto_polling_open', $draft['auto_polling_open'] ?? $event?->auto_polling_open ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="auto_polling_open">
                Automatically set polling status to <strong>Open</strong> when polling start date is reached
            </label>
        </div>
    </div>
    <div class="col-md-6">
        <label class="mac-label">Auto-Close Polling</label>
        <div class="form-check form-switch mt-1">
            <input type="hidden" name="auto_polling_close" value="0">
            <input class="form-check-input" type="checkbox" name="auto_polling_close" value="1" id="auto_polling_close"
                   {{ old('auto_polling_close', $draft['auto_polling_close'] ?? $event?->auto_polling_close ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="auto_polling_close">
                Automatically set polling status to <strong>Closed</strong> when polling deadline is reached
            </label>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4 mb-0">
    <i class="bi bi-info-circle"></i>
    Both toggles need the <strong>scheduler</strong> running to do anything. Leave them off and open
    or close the vote by hand from the lot draw page.
</div>
