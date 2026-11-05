{{--
    The <option> list for a Kraftangan zone, wherever one is picked or filtered on.

    One source, because in `daftar` there were four. Every screen that touched a
    zone carried its own literal array of place names, and they had all drifted
    into the WRONG vocabulary: a list of Malaysian states, compared against a
    column that holds offices. A zone is not a state — one office covers
    Selangor, Kuala Lumpur and Putrajaya; another covers Sabah and Labuan; and
    Kedah is split, because Kedah and Langkawi each have their own office.

    The cost there was quiet: "Selangor" matched no row and returned an empty
    page, while the companies under the Selangor/KL/Putrajaya office could not be
    filtered at all, because that option did not exist. 102 of 284 companies were
    unreachable this way.

    The list is `App\Support\AdminZones`, editable at Setting › Dropdown Lists ›
    Administrative Zones. It is the same value in `admins.state`,
    `companies.state` and `events.state` — labelled **Admin State** on an admin
    and **Registration State** on a company, the same fact for two audiences.

    Expects:
      $selected     — the value to preselect (optional)
      $placeholder  — text for the empty first option; omit it for no empty option
--}}
@isset($placeholder)
    <option value="">{{ $placeholder }}</option>
@endisset
@foreach(\App\Support\AdminZones::all() as $zone)
    <option value="{{ $zone }}" {{ (string) ($selected ?? '') === $zone ? 'selected' : '' }}>{{ $zone }}</option>
@endforeach
