{{-- Where an event stands, as one badge. Event::publicStage() decides it. --}}
@php
    [$stageLabel, $stageTone] = [
        'ended' => ['Ended', 'neutral'],
        'now' => ['Happening now', 'info'],
        'open' => ['Lot draw open', 'ok'],
        'soon' => ['Coming soon', 'gold'],
        'closed' => ['Lot draw closed', 'neutral'],
    ][$event->publicStage()];
@endphp
<span class="ev-badge ev-badge-{{ $stageTone }}">{{ $stageLabel }}</span>
