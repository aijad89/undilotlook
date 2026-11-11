{{-- A join request's answers as the company gave them: read by the office
     and by the participant, so one wording serves both. --}}
@isset($a['contact_name'])
    <div>Contact: {{ $a['contact_name'] }}, {{ $a['contact_phone'] }}</div>
@endisset
<div>Recognition: {{ \App\Models\Company::RECOGNITIONS[$a['recognition'] ?? ''] ?? '—' }}</div>
<div>Classification certificate:
    @if(! empty($a['classification_cert']['number']))
        {{ $a['classification_cert']['number'] }}@if(! empty($a['classification_cert']['end'])), valid until {{ \Illuminate\Support\Carbon::parse($a['classification_cert']['end'])->format('d M Y') }}@endif
    @else
        none
    @endif
</div>
@if(! empty($a['quality']))
    <div>Quality compliance:
        @if(! empty($a['quality_cert']['number']))
            {{ \App\Models\Company::QUALITY_STANDARDS[$a['quality']] ?? $a['quality'] }}, {{ $a['quality_cert']['number'] }}@if(! empty($a['quality_cert']['end'])), valid until {{ \Illuminate\Support\Carbon::parse($a['quality_cert']['end'])->format('d M Y') }}@endif
        @else
            not applicable
        @endif
    </div>
@endif
