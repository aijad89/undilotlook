<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\AdminZones;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Admin accounts.
 *
 * Listed in two groups, which is how the sidebar shows them:
 *   hq     HQ and super accounts. Super only.
 *   state  State admins, one office zone each. Super and hq.
 *
 * Reaching these screens is the `manage-admins` gate (super and hq). Past it,
 * every request asks two separate questions, and both are needed:
 *
 *   WHO is being acted on   authorizeTarget()  — hq works on state admins only.
 *   WHAT role is being set  authorizeRole()    — Admin::assignableRolesFor().
 *
 * Close either one alone and the other stays open. Target only: hq takes a state
 * admin and sets role=super. Role list only: hq leaves a super's role alone and
 * rewrites its email and password, which is account takeover.
 *
 * The last super cannot be deleted or demoted; that floor is on the model
 * (Admin::booted()), and its ValidationException returns to the form like any
 * other error.
 */
class AdminController extends Controller
{
    private const GROUP_ROLES = [
        'hq' => ['super', 'hq'],
        'state' => ['admin'],
    ];

    public function index(Request $request, string $group)
    {
        $this->authorizeGroup($group);

        $admins = Admin::query()
            ->whereIn('role', self::GROUP_ROLES[$group])
            ->when($request->input('search'), fn ($q, $search) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($group === 'state' && $request->input('state'), fn ($q, $zone) => $q->where('state', $zone))
            // Supers first in the hq list: they are the ones that manage everyone else.
            ->orderByRaw("case when role = 'super' then 0 else 1 end")
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.admins.index', [
            'group' => $group,
            'admins' => $admins,
            'actor' => $request->user('admin'),
        ]);
    }

    public function create(Request $request)
    {
        $group = $request->input('group') === 'hq' ? 'hq' : 'state';
        $this->authorizeGroup($group);

        return view('admin.admins.create', [
            'admin' => new Admin(['role' => $group === 'hq' ? 'hq' : 'admin']),
            'group' => $group,
            'roles' => Admin::assignableRolesFor($request->user('admin')),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules($request));

        // AFTER validation, so each check answers its own question: the rules
        // reject a role the SYSTEM has no such thing as, this rejects one the
        // CALLER may not hand out — a 403, because the form never offered it.
        $this->authorizeRole($request, $data['role']);

        $admin = Admin::create($this->attributes($data));

        return redirect()->route($this->listRoute($admin))
            ->with('success', "{$admin->name} can now sign in.");
    }

    public function edit(Request $request, Admin $admin)
    {
        $this->authorizeTarget($request, $admin);
        $group = $this->groupOf($admin);

        return view('admin.admins.edit', [
            'admin' => $admin,
            'group' => $group,
            'roles' => Admin::assignableRolesFor($request->user('admin')),
        ]);
    }

    public function update(Request $request, Admin $admin)
    {
        // Target first: a request against an account that is not yours is not
        // even validated.
        $this->authorizeTarget($request, $admin);

        $data = $request->validate($this->rules($request, $admin));
        $this->authorizeRole($request, $data['role']);

        $admin->update($this->attributes($data));

        return redirect()->route($this->listRoute($admin))
            ->with('success', "{$admin->name} saved.");
    }

    public function destroy(Request $request, Admin $admin)
    {
        $this->authorizeTarget($request, $admin);

        if ($admin->is($request->user('admin'))) {
            return back()->withErrors('You cannot delete your own account.');
        }

        $list = $this->listRoute($admin);
        $admin->delete();

        return redirect()->route($list)->with('success', "{$admin->name} deleted.");
    }

    /* ── The two questions ───────────────────────────────────────────── */

    /** The hq list — HQ and super accounts — is super's alone. */
    private function authorizeGroup(string $group): void
    {
        abort_if($group === 'hq' && ! request()->user('admin')->isSuperAdmin(), 403);
    }

    /**
     * hq works on state admins only: not a super, and not a peer. Stopping at
     * "not a super" is not enough: hq could then edit another hq — and, since
     * the only role hq may hand out is State Admin, demote it.
     */
    private function authorizeTarget(Request $request, Admin $target): void
    {
        abort_if(! $request->user('admin')->isSuperAdmin() && ! $target->isStateAdmin(), 403);
    }

    private function authorizeRole(Request $request, string $role): void
    {
        abort_unless(array_key_exists($role, Admin::assignableRolesFor($request->user('admin'))), 403);
    }

    /* ── Helpers ─────────────────────────────────────────────────────── */

    private function rules(Request $request, ?Admin $admin = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins')->ignore($admin?->id)],
            'role' => ['required', Rule::in(array_keys(Admin::ROLES))],
            // A state admin's zone is what scopes everything they see, so it is
            // required and must be a zone that exists. HQ and super have none.
            'state' => [Rule::requiredIf($request->input('role') === 'admin'), 'nullable', Rule::in(AdminZones::all())],
            // Blank on edit keeps the current password; the floor applies where a
            // password is SET, never where one is only checked. No default exists.
            'password' => [$admin ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ];
    }

    /** Validated input to columns. The `hashed` cast on Admin hashes the password. */
    private function attributes(array $data): array
    {
        $attributes = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'state' => $data['role'] === 'admin' ? $data['state'] : null,
        ];

        if (! empty($data['password'])) {
            $attributes['password'] = $data['password'];
        }

        return $attributes;
    }

    private function groupOf(Admin $admin): string
    {
        return $admin->isStateAdmin() ? 'state' : 'hq';
    }

    private function listRoute(Admin $admin): string
    {
        return 'admin.admins.'.$this->groupOf($admin);
    }
}
