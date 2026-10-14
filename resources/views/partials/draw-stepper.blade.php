{{--
    Where a draw is, as four stops. The signature of the system: it appears on
    every draw, for the office and for usahawan alike.

    Expects:
      $status   — events.polling_status
      $labels   — optional [draft, polling, closed, completed, cancelled] wording;
                  defaults to Event::POLLING_STATUSES, the admin's own words
      $compact  — optional; dots and one word, for a table cell
--}}
@php
    $labels = ($labels ?? null) ?: \App\Models\Event::POLLING_STATUSES;
    $stops = ['draft', 'polling', 'closed', 'completed'];
    $cancelled = $status === 'cancelled';
    $at = $cancelled ? -1 : (int) array_search($status ?: 'draft', $stops, true);
@endphp

@if($compact ?? false)
    <span class="ui-stepper-compact {{ $cancelled ? 'is-cancelled' : '' }}">
        <span class="track" aria-hidden="true">
            @foreach($stops as $i => $stop)
                <span class="pip {{ $i < $at ? 'on' : ($i === $at ? 'now' : '') }}"></span>
            @endforeach
        </span>
        <span class="word">{{ $labels[$status ?: 'draft'] ?? $status }}</span>
    </span>
@else
    <ol class="ui-stepper {{ $cancelled ? 'is-cancelled' : '' }}" aria-label="{{ $cancelled ? ($labels['cancelled'] ?? 'Cancelled') : 'Draw stage' }}">
        @foreach($stops as $i => $stop)
            <li class="{{ $i < $at ? 'done' : ($i === $at ? 'current' : '') }}" @if($i === $at) aria-current="step" @endif>
                <span class="dot" aria-hidden="true">@if($i < $at)<i class="bi bi-check-lg"></i>@endif</span>
                <span class="label">{{ $labels[$stop] }}</span>
            </li>
        @endforeach
    </ol>
@endif
