@extends('layouts.admin')
@section('title', 'Event Calendar')

@section('content')
@include('partials.mac-styles')

<style>
    /* ── FullCalendar on the shared tokens ── */
    .fc { --fc-border-color: var(--ui-sep); --fc-today-bg-color: var(--ui-blue-tint); --fc-page-bg-color: var(--ui-surface); --fc-neutral-bg-color: var(--ui-surface-2); --fc-list-event-hover-bg-color: var(--ui-neutral-tint); }
    .fc .fc-toolbar-title { font-size: 1.1rem; font-weight: 700; color: var(--ui-text); }
    .fc .fc-button {
        min-height: 36px;
        background: var(--ui-surface) !important;
        border: 1px solid var(--ui-border-strong) !important;
        color: var(--ui-text-2) !important;
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        border-radius: var(--ui-r) !important;
        box-shadow: none !important;
        padding: 5px 12px !important;
        text-transform: capitalize;
    }
    .fc .fc-button:hover { background: var(--ui-neutral-tint) !important; color: var(--ui-text) !important; }
    .fc .fc-button:focus-visible { box-shadow: var(--ui-focus-ring) !important; }
    .fc .fc-button-active,
    .fc .fc-button-primary:not(:disabled).fc-button-active {
        background: var(--ui-navy-900) !important;
        border-color: var(--ui-navy-900) !important;
        color: #fff !important;
    }
    .fc .fc-daygrid-day-number { color: var(--ui-text-2); font-size: 0.85rem; text-decoration: none; }
    .fc .fc-day-today .fc-daygrid-day-number { color: var(--ui-blue-text); font-weight: 700; }
    .fc .fc-day-other .fc-daygrid-day-number { color: var(--ui-muted); opacity: 0.6; }
    /* No `border: none !important` here — registration bars below rely on a border to read
       as an outlined band rather than a solid event chip. */
    .fc-event { border-radius: var(--ui-r-sm) !important; font-size: 0.76rem; font-weight: 600; cursor: pointer; }
    .fc .fc-col-header-cell-cushion { padding: 8px 4px; color: var(--ui-muted); font-size: 0.8rem; font-weight: 600; text-decoration: none; }
    .fc-daygrid-dot-event .fc-event-title { font-weight: 600; }

    /* Registration windows are normal (non-background) events, so FullCalendar lays
       overlapping ones out on their own rows inside the day cell — the Google Calendar
       behaviour — each carrying its own label. See EventController::calendarData().
       These rules just give each bar breathing room and keep long event names on one
       line so a stack of them stays scannable. */
    .fc .fc-daygrid-event { padding: 1px 6px; margin-top: 2px; }
    .fc .fc-daygrid-event .fc-event-title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fc .fc-daygrid-more-link { font-size: 0.75rem; font-weight: 700; color: var(--ui-blue-text); }

    /* Registration bar reads as a timeline: a cap on the day registration OPENS, another
       on the day it CLOSES, the body in between. FullCalendar cuts a multi-week bar into one
       segment per week but marks only the genuine first and last segments with
       fc-event-start / fc-event-end, so the caps land on the real dates and the intervening
       weeks stay plain body. Side borders are cleared first so a mid-week segment shows no
       stray cap.
       !important is required, not lazy: FullCalendar writes the event's borderColor as an
       INLINE style on the element, and an inline declaration beats any stylesheet selector
       no matter how specific — without it the caps silently render in the body colour. */
    .fc .reg-window { border-left: none !important; border-right: none !important; }
    .fc .reg-window.fc-event-start { border-left: 5px solid {{ $palette['registration_open_cap'] }} !important; }
    .fc .reg-window.fc-event-end   { border-right: 5px solid {{ $palette['registration_close_cap'] }} !important; }

    /* Legend */
    .cal-legend { display: flex; gap: 8px 18px; flex-wrap: wrap; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--ui-sep); font-size: 0.82rem; color: var(--ui-muted); }
    .cal-legend-item { display: flex; align-items: center; gap: 6px; }
    .cal-legend-item .cal-legend-aside { color: var(--ui-muted); opacity: 0.8; }
    .cal-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }

    /* Popup */
    #cal-popup {
        position: fixed;
        z-index: 9999;
        background: var(--ui-surface);
        border: 1px solid var(--ui-border);
        border-radius: var(--ui-r-lg);
        box-shadow: var(--ui-shadow-pop);
        padding: 14px 16px;
        min-width: 240px;
        max-width: 300px;
        display: none;
    }
    #cal-popup.show { display: block; }
    .pop-title { font-size: 0.95rem; font-weight: 700; color: var(--ui-text); margin-bottom: 7px; padding-right: 20px; }
    .pop-meta  { font-size: 0.82rem; color: var(--ui-muted); margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
    .pop-badge { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; margin-bottom: 8px; }
    .pop-badge.is-open { background: var(--ui-ok-tint); color: var(--ui-ok-text); }
    .pop-badge.is-closed { background: var(--ui-bad-tint); color: var(--ui-bad-text); }
    .pop-badge.is-completed { background: var(--ui-violet-tint); color: var(--ui-violet-text); }
    .pop-badge.is-draft { background: var(--ui-neutral-tint); color: var(--ui-neutral-text); }
    .pop-actions { margin-top: 10px; display: flex; gap: 7px; flex-wrap: wrap; }
    .pop-close { position: absolute; top: 6px; right: 8px; width: 32px; height: 32px; background: none; border: none; border-radius: var(--ui-r-sm); font-size: 1.1rem; color: var(--ui-muted); cursor: pointer; line-height: 1; }
    .pop-close:hover { background: var(--ui-neutral-tint); color: var(--ui-text); }

    /* A phone gets the toolbar in rows: title, then the arrows, then the views. */
    @media (max-width: 767.98px) {
        .fc .fc-toolbar.fc-header-toolbar { flex-wrap: wrap; gap: 8px; }
        .fc .fc-toolbar-chunk:nth-child(2) { order: -1; width: 100%; text-align: center; }
        .fc .fc-toolbar-title { font-size: 1.05rem; }
        .fc .fc-col-header-cell-cushion { font-size: 0.72rem; }
    }
</style>

<div class="mac-page">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div class="mac-header">
            <h2>Event Calendar</h2>
            <div class="mac-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="sep" aria-hidden="true">›</span>
                <a href="{{ route('admin.events.index') }}">Events</a>
                <span class="sep" aria-hidden="true">›</span>
                Calendar
            </div>
        </div>
        {{-- Keep the active filters when switching views — losing them on every switch
             makes the filter feel broken. --}}
        <a href="{{ route('admin.events.index', request()->only('search', 'status', 'state')) }}" class="mac-btn-secondary">← List View</a>
    </div>

    @include('partials.admin-alerts')

    {{-- Filters — same fields as the events list, so switching between the two views keeps
         the same narrowing. Submits GET back to this page; the values are then handed to the
         calendar feed URL below. --}}
    <div class="mac-filter-bar">
        <form method="GET" action="{{ route('admin.events.calendar') }}" class="d-flex align-items-center gap-3 flex-wrap">
            <div style="flex:1; min-width:180px;">
                <input type="text" name="search" class="mac-input" aria-label="Search events" placeholder="Search events..." value="{{ request('search') }}">
            </div>
            <div style="min-width:140px;">
                <select name="status" class="mac-select" aria-label="Filter by status">
                    <option value="">All Status</option>
                    @foreach(\App\Models\Event::STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:160px;">
                <select name="state" class="mac-select" aria-label="Filter by zone">
                    @include('partials.zone-options', ['selected' => request('state'), 'placeholder' => 'All zones'])
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="mac-filter-btn">Search</button>
                @if(request()->hasAny(['search', 'status', 'state']))
                    <a href="{{ route('admin.events.calendar') }}" class="mac-clear-btn">Clear</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Calendar Card --}}
    <div class="mac-card" style="padding: 20px 24px;">
        <div id="calendar"></div>
        <div class="cal-legend">
            <div class="cal-legend-item"><div class="cal-dot" style="background:{{ $palette['event_open'] }};"></div> Published</div>
            <div class="cal-legend-item"><div class="cal-dot" style="background:{{ $palette['event_closed'] }};"></div> Unpublished</div>
            <div class="cal-legend-item"><div class="cal-dot" style="background:{{ $palette['event_completed'] }};"></div> Ended</div>
            <div class="cal-legend-item"><div class="cal-dot" style="background:{{ $palette['event_draft'] }};"></div> Draft</div>
            <div class="cal-legend-item">
                <span style="display:inline-flex;height:10px;border-radius:3px;overflow:hidden;flex-shrink:0;">
                    <span style="width:4px;background:{{ $palette['registration_open_cap'] }};"></span>
                    <span style="width:16px;background:{{ $palette['registration_fill'] }};"></span>
                    <span style="width:4px;background:{{ $palette['registration_close_cap'] }};"></span>
                </span>
                Registration Window <span class="cal-legend-aside">(opens · closes)</span>
            </div>
            <div class="cal-legend-item"><div class="cal-dot" style="background:{{ $palette['polling'] }};"></div> Voting window</div>
        </div>
    </div>

</div>

{{-- Popup --}}
<div id="cal-popup">
    <button class="pop-close" onclick="closePopup()">×</button>
    <div class="pop-title" id="pop-title"></div>
    <span class="pop-badge" id="pop-badge"></span>
    <div class="pop-meta" id="pop-dates"><i class="bi bi-calendar3"></i> <span></span></div>
    <div class="pop-meta" id="pop-loc"><i class="bi bi-geo-alt"></i> <span></span></div>
    <div class="pop-actions" id="pop-actions"></div>
</div>

@endsection

@push('scripts')
{{-- No FullCalendar stylesheet link: v6 ships its CSS inside index.global.min.js and
     publishes no index.global.min.css, so requesting one returns text/plain and the
     browser rejects it with a console error on every load. --}}
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'
        integrity="sha384-5JIwZN3kuxX2zKsavvNmbZ3zhZZMUtu/eQiK3BbXukpSXp0Cd2ZP4OAYKx7mrPgI" crossorigin="anonymous"></script>
<script>
    function closePopup() {
        document.getElementById('cal-popup').classList.remove('show');
    }

    document.addEventListener('click', function(e) {
        const popup = document.getElementById('cal-popup');
        if (popup.classList.contains('show') && !e.target.closest('#cal-popup') && !e.target.closest('.fc-event')) {
            closePopup();
        }
    });

    // The colours live in the stylesheet (.pop-badge.is-*); this names the word.
    const statusColors = {
        open:      { label: 'Published' },
        closed:    { label: 'Unpublished' },
        completed: { label: 'Ended' },
        draft:     { label: 'Draft' },
    };

    document.addEventListener('DOMContentLoaded', function () {
        const calEl = document.getElementById('calendar');

        const calendar = new FullCalendar.Calendar(calEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left:   'prev,next today',
                center: 'title',
                right:  'dayGridMonth,dayGridWeek,listMonth',
            },
            {{-- Carry the active filters into the feed so the calendar shows the same result
                 set the filter bar describes — see EventController::calendarData(). --}}
            events: '{{ route('admin.events.calendar-data', request()->only('search', 'status', 'state')) }}',
            // A day can carry several registration windows plus the event itself. Cap the
            // rows and let FullCalendar add its own "+N more" link (Google Calendar style)
            // rather than letting one busy day stretch the whole month grid.
            // On a phone the grid already grows to fit (height: 'auto'), and a "+N more"
            // link does not fit a 50px day; show every bar there instead.
            dayMaxEvents: window.matchMedia('(max-width: 767.98px)').matches ? false : 3,
            // The grid grows to its rows instead of a fixed aspect ratio, which on a
            // phone hid the last weeks inside the card.
            height: 'auto',
            eventDidMount: function(info) {
                // Bars are ellipsised when the name is too long for the cell, so keep the
                // full label reachable on hover.
                info.el.setAttribute('title', info.event.title);
            },
            eventClick: function(info) {
                const props = info.event.extendedProps;

                info.jsEvent.preventDefault();

                // A voting bar goes straight to its lot draw.
                if (props.type === 'polling') {
                    window.location = info.event.url;
                    return;
                }

                const popup  = document.getElementById('cal-popup');
                const start  = info.event.start;
                const end    = info.event.end ? new Date(info.event.end.getTime() - 86400000) : start;
                const status = props.status || 'draft';
                const known  = statusColors[status] ? status : 'draft';
                const sc     = statusColors[known];

                const isReg = props.type === 'registration';
                const fmt = (d) => d.toLocaleDateString('en-GB', {day:'numeric', month:'short', year:'numeric'});

                // A registration bar spans the registration window, not the event itself, so
                // name the underlying event and label the dates for what they actually are.
                document.getElementById('pop-title').textContent = isReg ? props.eventTitle : info.event.title;

                const badge = document.getElementById('pop-badge');
                badge.textContent = sc.label;
                badge.className = 'pop-badge is-' + known;

                document.getElementById('pop-dates').querySelector('span').textContent =
                    (isReg ? 'Registration: ' : '') + fmt(start) + (end > start ? ' – ' + fmt(end) : '');

                document.getElementById('pop-loc').querySelector('span').textContent = props.location || '—';

                const actionsEl = document.getElementById('pop-actions');
                actionsEl.innerHTML = '';

                if (info.event.url) {
                    const viewLink = document.createElement('a');
                    viewLink.href      = info.event.url;
                    viewLink.className = 'mac-btn-primary';
                    viewLink.style.cssText = 'font-size:0.78rem;padding:5px 14px;';
                    viewLink.innerHTML = '<i class="bi bi-list-check"></i> Draw list';
                    actionsEl.appendChild(viewLink);
                }

                // Position popup. #cal-popup is `position: fixed`, so its coordinates are
                // already viewport-relative — same as getBoundingClientRect(). Adding
                // window.scrollY here double-counts the scroll offset, pushing the popup far
                // past the event on any page tall enough to need scrolling (which dayGridMonth
                // reliably is, since it has far more rows than dayGridWeek).
                const rect = info.el.getBoundingClientRect();
                const top  = rect.bottom + 8;
                const left = Math.min(rect.left, window.innerWidth - 320);
                popup.style.top  = top + 'px';
                popup.style.left = left + 'px';
                popup.classList.add('show');

                info.jsEvent.stopPropagation();
            },
        });

        calendar.render();
    });
</script>
@endpush
