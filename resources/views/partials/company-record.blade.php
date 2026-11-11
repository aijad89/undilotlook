{{-- The answers each request to join starts from, as the company last sent
     them: on the dashboard and the profile alike. Changed by sending a request.
     Takes $company (may be null). --}}
@php
    $held = $company?->credentials ?? [];
    // A certificate on record, and how near its end is.
    $certState = function (?array $cert) {
        if (empty($cert['end'])) {
            return null;
        }
        $end = \Illuminate\Support\Carbon::parse($cert['end'])->endOfDay();

        return $end->isPast() ? ['ap-badge-red', 'Expired'] : ($end->lte(now()->addMonths(2)) ? ['ap-badge-orange', 'Expires soon'] : null);
    };
@endphp
<section class="ap-card ap-card-padded">
    <h2 class="ap-section-title">Your company on record</h2>
    @if($held)
        <dl class="ap-facts">
            <dt>Contact person</dt>
            <dd>{{ $held['contact_name'] ?? '—' }}@isset($held['contact_phone'])<br><span class="ap-tabular">{{ $held['contact_phone'] }}</span>@endisset</dd>
            <dt>Recognition</dt>
            <dd>{{ \App\Models\Company::RECOGNITIONS[$held['recognition'] ?? ''] ?? '—' }}</dd>
            @foreach(['classification_cert' => 'Classification certificate', 'quality_cert' => 'Quality compliance'] as $key => $name)
                @continue($key === 'quality_cert' && empty($held['quality']))
                <dt>{{ $name }}</dt>
                <dd>
                    @if(! empty($held[$key]['number']))
                        <span class="ap-tabular">{{ $held[$key]['number'] }}</span>@if(! empty($held[$key]['end'])), valid until <span class="ap-tabular">{{ \Illuminate\Support\Carbon::parse($held[$key]['end'])->format('d M Y') }}</span>@endif
                        @if($state = $certState($held[$key]))
                            <span class="ap-badge {{ $state[0] }} ms-1">{{ $state[1] }}</span>
                        @endif
                    @else
                        {{ $key === 'quality_cert' ? 'Not applicable' : 'None' }}
                    @endif
                </dd>
            @endforeach
            <dt>Product photos</dt>
            <dd>{{ count($held['product_images'] ?? []) ? count($held['product_images']).' on file' : 'None yet' }}</dd>
        </dl>
        <p class="ap-side-note mb-0">Each request to join starts from these answers. To change them, change them on your next request.</p>
    @else
        <p class="ap-side-note mb-0">Nothing on record yet. Your first request to join fills this in, and every later request starts from it.</p>
    @endif
</section>
