{{--
    The company's usual craft code and booth size. Admin only — no participant
    screen reads `companies.kraf_code` or `companies.plot_size`. Both are
    suggestions: the draw list copies them in when the company is added to an
    event, and what is on the draw list is what the lot draw uses.

    Expects:
      $company   — the company being edited
      $codes     — App\Support\KrafPicker::codes()
      $plotSizes — App\Support\PlotSizes::all()
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
        Both are filled in for this company when it is added to an event's draw
        list. The participant does not see them.
    </p>

    <form method="POST" action="{{ route('admin.companies.kraf-code.update', $company) }}"
          class="row g-2 align-items-end">
        @csrf
        @method('PUT')
        <div class="col-md-5">
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
            <label class="mac-label" for="plot_size">Booth size</label>
            <select id="plot_size" name="plot_size" class="mac-select">
                <option value="">— None —</option>
                @foreach($plotSizes as $size)
                    <option value="{{ $size }}" @selected($company->plot_size === $size)>{{ $size }}</option>
                @endforeach
                {{-- A size since removed from the list stays visible rather than reading as "None". --}}
                @if($company->plot_size && ! in_array($company->plot_size, $plotSizes, true))
                    <option value="{{ $company->plot_size }}" selected disabled>{{ $company->plot_size }} (no longer on the list)</option>
                @endif
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="mac-btn-secondary w-100">Save</button>
        </div>
    </form>
</div>
