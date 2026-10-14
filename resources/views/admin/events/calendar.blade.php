@extends('layouts.admin')
@section('title', 'Event Calendar')

@section('content')
@include('partials.mac-styles')

<style>
    /* ── FullCalendar overrides for mac-style ── */
    .fc .fc-toolbar-title { font-size: 1rem; font-weight: 700; color: #1d1d1f; }
    .fc .fc-button {
        background: rgba(0,0,0,0.04) !important;
        border: 1px solid rgba(0,0,0,0.08) !important;
        color: #374151 !important;
        font-size: 0.82rem !important;
        font-weight: 600 !important;
        border-radius: 8px !important;
        box-shadow: none !important;
        padding: 5px 12px !important;
    }
    .fc .fc-button:hover { background: rgba(0,0,0,0.08) !important; }
    .fc .fc-button-active,
    .fc .fc-button-primary:not(:disabled).fc-button-active {
        background: #D97706 !important;
        border-color: #D97706 !important;
        color: #fff !important;
    }
    .fc .fc-daygrid-day-number { color: #374151; font-size: 0.82rem; }
    .fc .fc-day-today .fc-daygrid-day-number { color: #D97706; font-weight: 700; }
    .fc .fc-day-today { background: rgba(217,119,6,0.04) !important; }
    /* No `border: none !important` here — registration bars below rely on a border to read
       as an outlined band rather than a solid event chip. */
    .fc-event { border-radius: 4px !important; font-size: 0.74rem; font-weight: 600; cursor: pointer; }
    .fc .fc-col-header-cell-cushion { color: #6B7280; font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; }
    .fc-daygrid-dot-event .fc-event-title { font-weight: 600; }

    /* Registration windows are normal (non-background) events, so FullCalendar lays
       overlapping ones out on their own rows inside the day cell — the Google Calendar
       behaviour — each carrying its own label. See EventController::calendarData().
       These rules just give each bar breathing room and keep long event names on one
       line so a stack of them stays scannable. */
    .fc .fc-daygrid-event { padding: 1px 6px; margin-top: 2px; }
    .fc .fc-daygrid-event .fc-event-title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fc .fc-daygrid-more-link { font-size: 0.72rem; font-weight: 700; color: #D97706; }

    /* Registration bar reads as a timeline: green cap on the day registration OPENS, red cap
       on the day it CLOSES, blue body in between. FullCalendar cuts a multi-week bar into one
       segment per week but marks only the genuine first and last segments with
       fc-event-start / fc-event-end, so the caps land on the real dates and the intervening
       weeks stay plain body. Side borders are cleared first so a mid-week segment shows no
       stray cap. */
       !important is required, not lazy: FullCalendar writes the event's borderColor as an
       INLINE style on the element, and an inline declaration beats any stylesheet selector
       no matter how specific — without it the caps silently render blue. */
    .fc .reg-window { border-left: none !important; border-right: none !important; }
    .fc .reg-window.fc-event-start { border-left: 5px solid {{ $palette['registration_open_cap'] }} !important; }
    .fc .reg-window.fc-event-end   { border-right: 5px solid {{ $palette['registration_close_cap'] }} !important; }

    /* Legend */
    .cal-legend { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 16px; padding-top: 14px; border-top: 1px solid rgba(0,0,0,0.07); font-size: 0.77rem; color: #6B7280; }
    .cal-legend-item { display: flex; align-items: center; gap: 6px; }
    .cal-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }

    /* Popup */
    #cal-popup {
        position: fixed;
        z-index: 9999;
        background: #fff;
        border: 1px solid rgba(0,0,0,0.09);
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        padding: 14px 16px;
        min-width: 240px;
        max-width: 300px;
        display: none;
    }
    #cal-popup.show { display: block; }
    .pop-title { font-size: 0.9rem; font-weight: 700; color: #1d1d1f; margin-bottom: 7px; padding-right: 20px; }
    .pop-meta  { font-size: 0.78rem; color: #6B7280; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
    .pop-badge { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 0.7rem; font-weight: 700; margin-bottom: 8px; }
    .pop-actions { margin-top: 10px; display: flex; gap: 7px; flex-wrap: wrap; }
    .pop-close { position: absolute; top: 9px; right: 12px; background: none; border: none; font-size: 1.1rem; color: #9CA3AF; cursor: pointer; line-height: 1; }
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
                Registration Window <span style="color:#9CA3AF;">(opens · closes)</span>
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

    const statusColors = {
        open:      { label: 'Published',      bg: 'rgba(34,197,94,0.12)',  color: '#15803D' },
        closed:    { label: 'Unpublished',    bg: 'rgba(239,68,68,0.12)',  color: '#B91C1C' },
        completed: { label: 'Ended', bg: 'rgba(139,92,246,0.12)', color: '#6D28D9' },
        draft:     { label: 'Draft',     bg: 'rgba(156,163,175,0.15)', color: '#6B7280' },
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
            dayMaxEvents: 3,
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
                const sc     = statusColors[status] || statusColors.draft;

                const isReg = props.type === 'registration';
                const fmt = (d) => d.toLocaleDateString('en-GB', {day:'numeric', month:'short', year:'numeric'});

                // A registration bar spans the registration window, not the event itself, so
                // name the underlying event and label the dates for what they actually are.
                document.getElementById('pop-title').textContent = isReg ? props.eventTitle : info.event.title;

                const badge = document.getElementById('pop-badge');
                badge.textContent = sc.label;
                badge.style.background = sc.bg;
                badge.style.color      = sc.color;

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
