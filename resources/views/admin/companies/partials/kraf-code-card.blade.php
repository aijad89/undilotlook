{{--
    The company's usual craft code. Admin only — no participant screen reads
    `companies.kraf_code`. It is a suggestion: the draw list copies it in when
    the company is added to an event, and the code on the draw list is the one
    the lot draw matches.

    Expects:
      $company — the company being edited
      $codes   — App\Support\KrafPicker::codes()
--}}
<div class="mac-card mac-card-padded mb-3" id="craft-code" style="scroll-margin-top: 80px">
    <h3 class="mac-card-title">
        Craft category code
        <span class="mac-badge mac-badge-gray ms-1">Admin only</span>
    </h3>

    @if(session('anchor') === 'craft-code')
        @include('partials.admin-alerts')
    @endif

    <p class="mac-muted mac-text-sm">
        Filled in for this company when it is added to an event's draw list. The
        participant does not see it.
    </p>

    <form method="POST" action="{{ route('admin.companies.kraf-code.update', $company) }}"
          class="row g-2 align-items-end">
        @csrf
        @method('PUT')
        <div class="col-md-8">
            <label class="mac-label" for="kraf_code">Craft code</label>
            <select id="kraf_code" name="kraf_code" class="mac-select">
                <option value="">— None —</option>
                @foreach($codes as $code)
                    <option value="{{ $code->code }}" @selected($company->kraf_code === $code->code)>
                        {{ $code->code }} — {{ $code->name }}
                    </option>
                @endforeach
                {{-- A code since retired stays visible rather than silently reading as "None". --}}
                @if($company->kraf_code && ! $codes->contains('code', $company->kraf_code))
                    <option value="{{ $company->kraf_code }}" selected disabled>{{ $company->kraf_code }} (no longer on the coding sheet)</option>
                @endif
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="mac-btn-secondary w-100">Save</button>
        </div>
    </form>
</div>
