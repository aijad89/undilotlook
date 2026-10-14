{{--
    The company form, shared by create and edit. It writes TWO rows: the company
    and the one account that signs in for it. The owner's name, email, phone and
    IC are columns on the USER — they are not duplicated on the company.

    Expects:
      $company  — the Company being edited, or a new one
      $zones    — the zones this admin may file a company under
--}}
@php
    $user = $company->user;
    $editing = $company->exists;
    $value = fn (string $field, $default = null) => old($field, $company->$field ?? $default);
    // The form field and the user column are not the same name — `owner_name` is
    // `users.name` — and `old('name')` is the COMPANY's. Both have to be said.
    $owner = fn (string $field, string $column) => old($field, $user?->$column);
@endphp

<div class="mac-card mac-card-padded mb-3">
    <h3 class="mac-card-title">The company</h3>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="mac-label" for="name">Name</label>
            <input type="text" id="name" name="name" class="mac-form-control"
                   value="{{ $value('name') }}" required maxlength="255">
        </div>

        <div class="col-md-6">
            <label class="mac-label" for="state">Zone</label>
            <select id="state" name="state" class="mac-select" required @disabled(count($zones) === 1)>
                @foreach($zones as $zone)
                    <option value="{{ $zone }}" @selected($value('state') === $zone)>{{ $zone }}</option>
                @endforeach
            </select>
            @if(count($zones) === 1)
                {{-- Disabled inputs are not posted, so the one zone still has to
                     travel. The rule on the far side accepts this list only. --}}
                <input type="hidden" name="state" value="{{ $zones[0] }}">
                <small class="mac-muted">Your zone. Only a super or HQ admin can file a company elsewhere.</small>
            @endif
        </div>

        <div class="col-md-6">
            <label class="mac-label" for="entrepreneur_category">Entrepreneur category</label>
            <select id="entrepreneur_category" name="entrepreneur_category" class="mac-select">
                <option value="">— None —</option>
                @foreach(\App\Support\EntrepreneurCategories::all() as $category)
                    <option value="{{ $category }}" @selected($value('entrepreneur_category') === $category)>
                        {{ $category }}
                    </option>
                @endforeach
            </select>
            <small class="mac-muted">A government agency or NGO is reserved a lot rather than drawn one.</small>
        </div>

        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                       @checked(old('is_active', $editing ? $company->is_active : true))>
                <label class="form-check-label mac-text-sm" for="is_active">
                    On the books
                    <span class="mac-muted d-block">Switch off to keep the record without offering it to a draw.</span>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="mac-card mac-card-padded mb-3">
    <h3 class="mac-card-title">The account</h3>
    <p class="mac-muted mac-text-sm">
        One account per company — this is who signs in and draws the lot.
    </p>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="mac-label" for="owner_name">Owner's name</label>
            <input type="text" id="owner_name" name="owner_name" class="mac-form-control"
                   value="{{ $owner('owner_name', 'name') }}" required maxlength="255">
        </div>

        <div class="col-md-6">
            <label class="mac-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="mac-form-control"
                   value="{{ $owner('email', 'email') }}" required maxlength="255">
        </div>

        <div class="col-md-6">
            <label class="mac-label" for="phone">Phone</label>
            <input type="text" id="phone" name="phone" class="mac-form-control"
                   value="{{ $owner('phone', 'phone') }}" maxlength="30">
        </div>

        <div class="col-md-6">
            <label class="mac-label" for="ic_number">IC number</label>
            <input type="text" id="ic_number" name="ic_number" class="mac-form-control"
                   value="{{ $owner('ic_number', 'ic_number') }}" placeholder="990101-01-1234">
        </div>

        <div class="col-md-6">
            <label class="mac-label" for="password">
                {{ $editing ? 'Reset password' : 'Password' }}
            </label>
            <input type="password" id="password" name="password" class="mac-form-control" autocomplete="new-password">
            <small class="mac-muted">
                @if($editing)
                    Leave empty to keep the current password.
                @else
                    Leave empty and the owner sets their own through "forgot password".
                @endif
                At least 8 characters, with a letter and a number.
            </small>
        </div>

        <div class="col-md-6">
            <label class="mac-label" for="password_confirmation">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   class="mac-form-control" autocomplete="new-password">
        </div>

        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="account_active" name="account_active" value="1"
                       @checked(old('account_active', $editing ? $user->is_active : true))>
                <label class="form-check-label mac-text-sm" for="account_active">
                    Account may sign in
                    <span class="mac-muted d-block">
                        Switching this off ends the session of whoever is already signed in.
                    </span>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="mb-3 d-flex gap-2">
    <button type="submit" class="mac-btn-primary">Save</button>
    <a href="{{ route('admin.companies.index') }}" class="mac-btn-secondary">Cancel</a>
</div>
