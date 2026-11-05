{{--
    The admin form, shared by create and edit.

    Expects:
      $admin  — the Admin being edited, or a new one
      $roles  — Admin::assignableRolesFor() the signed-in admin: the form offers
                only what the POST will accept, so hq is never shown Super or HQ
      $group  — 'hq' or 'state', the list this was opened from
--}}
@php
    $editing = $admin->exists;
    $role = old('role', $admin->role);
@endphp

<div class="mac-card mac-card-padded mb-3">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="mac-label" for="name">Name</label>
            <input type="text" id="name" name="name" class="mac-form-control"
                   value="{{ old('name', $admin->name) }}" required maxlength="255" autocomplete="off">
        </div>

        <div class="col-md-6">
            <label class="mac-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="mac-form-control"
                   value="{{ old('email', $admin->email) }}" required maxlength="255" autocomplete="off">
        </div>

        <div class="col-md-6">
            <label class="mac-label" for="role">Role</label>
            <select id="role" name="role" class="mac-select" required>
                @foreach($roles as $key => $label)
                    <option value="{{ $key }}" @selected($role === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6" id="zoneField">
            <label class="mac-label" for="state">Zone</label>
            <select id="state" name="state" class="mac-select">
                @include('partials.zone-options', ['selected' => old('state', $admin->state), 'placeholder' => '— Pick a zone —'])
            </select>
            <small class="mac-muted">A state admin sees only this zone's companies and draw lists.</small>
        </div>
    </div>
</div>

<div class="mac-card mac-card-padded mb-3">
    <h3 class="mac-card-title">Password</h3>
    <p class="mac-muted mac-text-sm">
        @if($editing)
            Leave both boxes empty to keep the current password.
        @else
            At least 8 characters, with letters and numbers. Give it to the new admin yourself; nothing is emailed.
        @endif
    </p>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="mac-label" for="password">{{ $editing ? 'New password' : 'Password' }}</label>
            <input type="password" id="password" name="password" class="mac-form-control"
                   autocomplete="new-password" @required(! $editing)>
        </div>
        <div class="col-md-6">
            <label class="mac-label" for="password_confirmation">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="mac-form-control"
                   autocomplete="new-password" @required(! $editing)>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="mac-btn-primary">{{ $editing ? 'Save' : 'Create Admin' }}</button>
    <a href="{{ route('admin.admins.'.$group) }}" class="mac-btn-secondary">Cancel</a>
</div>

<script>
    // The zone belongs to a state admin only; HQ and super see every zone.
    (function () {
        const role = document.getElementById('role');
        const field = document.getElementById('zoneField');
        const zone = document.getElementById('state');
        const sync = () => {
            const isState = role.value === 'admin';
            field.hidden = ! isState;
            zone.required = isState;
        };
        role.addEventListener('change', sync);
        sync();
    })();
</script>
