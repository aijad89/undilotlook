{{-- Where an event stands, as one badge. Event::publicStage() decides it. --}}
@php
    [$stageLabel, $stageTone] = [
        'ended' => ['Ended', 'neutral'],
        'now' => ['Happening now', 'info'],
        'open' => ['Lot draw open', 'ok'],
        'register' => ['Register now', 'ok'],
        'soon' => ['Coming soon', 'neutral'],
        'closed' => ['Lot draw closed', 'neutral'],
    ][$event->publicStage()];
@endphp
<span class="ev-badge ev-badge-{{ $stageTone }}">{{ $stageLabel }}</span>
