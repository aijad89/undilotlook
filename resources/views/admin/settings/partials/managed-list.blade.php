{{--
    One ManagedList, edited as a textarea — one value per line.

    Safe as free text precisely because nothing references these by id and nothing
    scopes access by them. Zones are the counter-example and have their own
    rename-and-carry flow instead; do not fold them in here.

    Expects:
      $title   — heading
      $list    — the key in SettingController::LISTS
      $values  — the current values
      $help    — a sentence saying what the list decides
--}}
<div class="mac-card mac-card-padded mb-3">
    <h3 class="mac-card-title">{{ $title }}</h3>
    <p class="mac-muted mac-text-sm">{{ $help }}</p>

    <form method="POST" action="{{ route('admin.settings.lists.update', $list) }}">
        @csrf
        @method('PUT')

        <label class="mac-label visually-hidden" for="values-{{ $list }}">{{ $title }}, one per line</label>
        <textarea id="values-{{ $list }}" name="values" class="mac-form-control" rows="{{ max(3, count($values)) }}"
                  >{{ old('values', implode("\n", $values)) }}</textarea>

        <div class="mt-2 d-flex gap-2 align-items-center">
            <button type="submit" class="mac-btn-secondary">Save {{ strtolower($title) }}</button>
            <span class="mac-muted mac-text-sm">
                Blank lines are dropped. An empty box is refused rather than emptying the dropdown.
            </span>
        </div>
    </form>
</div>
