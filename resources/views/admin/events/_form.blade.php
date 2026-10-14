{{--
    The event form, shared by create and edit.

    WHAT IS DELIBERATELY NOT HERE. `polling_status`, `use_preset`,
    `draw_animation` and `total_plots` belong to the draw, which has its own
    screen; `total_plots` is computed from the plan and would be a lie if typed.
    `colour` is a calendar tint and there is no calendar yet.

    Expects:
      $event  — the Event being edited, or a new one
--}}
@php
    $value = fn (string $field, $default = null) => old($field, $event->$field ?? $default);
    $datetime = fn (string $field) => old($field, $event->$field?->format('Y-m-d\TH:i'));
@endphp

<div class="mac-card mac-card-padded">
    <div class="row g-3">
        <div class="col-md-8">
            <label class="mac-label" for="title">Title</label>
            <input type="text" id="title" name="title" class="mac-form-control"
                   value="{{ $value('title') }}" required maxlength="255">
        </div>

        <div class="col-md-4">
            <label class="mac-label" for="status">Status</label>
            <select id="status" name="status" class="mac-select" required>
                @foreach(\App\Models\Event::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected($value('status', 'draft') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-8">
            <label class="mac-label" for="location">Location</label>
            <input type="text" id="location" name="location" class="mac-form-control"
                   value="{{ $value('location') }}" required maxlength="255">
            <small class="mac-muted">Where the event is held — a venue, in plain words.</small>
        </div>

        <div class="col-md-4">
            <label class="mac-label" for="state">Zone</label>
            <select id="state" name="state" class="mac-select">
                @include('partials.zone-options', [
                    'selected' => $value('state'),
                    'placeholder' => 'No zone',
                ])
            </select>
            <small class="mac-muted">The office that runs it, not where it is.</small>
        </div>

        <div class="col-12">
            <label class="mac-label" for="description">Description</label>
            <textarea id="description" name="description" class="mac-form-control" rows="3">{{ $value('description') }}</textarea>
        </div>

        <div class="col-12"><hr class="mac-divider"></div>

        <div class="col-md-6">
            <label class="mac-label" for="start_date">Starts</label>
            <input type="datetime-local" id="start_date" name="start_date" class="mac-form-control"
                   value="{{ $datetime('start_date') }}" required>
        </div>

        <div class="col-md-6">
            <label class="mac-label" for="end_date">Ends</label>
            <input type="datetime-local" id="end_date" name="end_date" class="mac-form-control"
                   value="{{ $datetime('end_date') }}" required>
        </div>

        <div class="col-md-6">
            <label class="mac-label" for="registration_open_at">Draw list opens</label>
            <input type="datetime-local" id="registration_open_at" name="registration_open_at"
                   class="mac-form-control" value="{{ $datetime('registration_open_at') }}" required>
        </div>

        <div class="col-md-6">
            <label class="mac-label" for="registration_close_at">Draw list closes</label>
            <input type="datetime-local" id="registration_close_at" name="registration_close_at"
                   class="mac-form-control" value="{{ $datetime('registration_close_at') }}" required>
        </div>

        <div class="col-md-6">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="auto_open" name="auto_open" value="1"
                       @checked($value('auto_open'))>
                <label class="form-check-label mac-text-sm" for="auto_open">
                    Publish automatically when the window opens
                </label>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="auto_close" name="auto_close" value="1"
                       @checked($value('auto_close'))>
                <label class="form-check-label mac-text-sm" for="auto_close">
                    Unpublish automatically when it closes
                </label>
            </div>
        </div>

        <div class="col-md-6">
            <label class="mac-label" for="max_participants">Maximum participants</label>
            <input type="number" id="max_participants" name="max_participants" class="mac-form-control"
                   value="{{ $value('max_participants', 100) }}" min="1" required>
        </div>

        <div class="col-12"><hr class="mac-divider"></div>

        <div class="col-md-6">
            <label class="mac-label" for="banner">Banner</label>
            <input type="file" id="banner" name="banner" class="mac-form-control"
                   accept="image/jpeg,image/png,image/webp">
            <small class="mac-muted">Resized to 797×235 on upload. Leave empty to keep the current one.</small>
        </div>

        @if($event->banner)
            <div class="col-md-6">
                <img src="{{ $event->banner_url }}" alt="Current banner for {{ $event->title }}"
                     class="img-fluid rounded" style="max-height:110px;">
            </div>
        @endif
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <button type="submit" class="mac-btn-primary">Save</button>
    <a href="{{ route('admin.events.index') }}" class="mac-btn-secondary">Cancel</a>
</div>
