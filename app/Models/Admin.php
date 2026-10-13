<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

class Admin extends Authenticatable
{
    use Auditable, HasFactory, Notifiable;

    protected array $auditExclude = ['password', 'remember_token'];

    protected $guard = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'state',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * The three admin roles, in ascending privilege, with the description the
     * create/edit form shows beside each.
     *
     * The single source. A flat list of keys that nothing reads invites the
     * descriptions to be spelled out in the form and the values to be repeated in
     * a literal `in:…` rule — three copies of the same strings, drifting apart.
     * `assignableRolesFor()` derives from this, and both forms render what it
     * returns.
     */
    const ROLES = [
        'admin' => 'State Admin — Manages companies and draw lists in their state',
        'hq' => 'HQ Admin — Manages events, the lot draw, settings and site content',
        'super' => 'Super Admin — Everything HQ can do, plus admin accounts and zones',
    ];

    const SUPER_ROLE = 'super';

    /**
     * AT LEAST ONE SUPER ADMIN MUST ALWAYS EXIST.
     *
     * The screen that creates an admin is super-only, and so is the screen that
     * creates an hq. So an empty super bench kills both routes back at once: no
     * new super can be made, and no new hq either. The remaining hq admins keep
     * working and cannot be replaced — it degrades quietly rather than failing
     * loudly. Hence a floor.
     *
     * ON THE MODEL, NOT IN THE CONTROLLER, because there are two ways down and
     * only one of them looks like a deletion:
     *
     *   destroy()  deletes the row.
     *   update()   writes `role`, and has no self-match check of its own, so the
     *              last super can demote ITSELF. That door is quieter than the
     *              first, and a guard on destroy() alone leaves it open.
     *
     * Both funnel through Eloquent events, so one pair of hooks covers both plus
     * anything written later.
     */
    protected static function booted(): void
    {
        static::deleting(function (Admin $admin) {
            if ($admin->isSuperAdmin() && ! static::anotherSuperExists($admin)) {
                throw ValidationException::withMessages([
                    'role' => 'This is the last super admin account. Create another super admin before deleting this one.',
                ]);
            }
        });

        static::updating(function (Admin $admin) {
            $wasSuper = $admin->getOriginal('role') === self::SUPER_ROLE;

            if ($wasSuper && ! $admin->isSuperAdmin() && ! static::anotherSuperExists($admin)) {
                throw ValidationException::withMessages([
                    'role' => 'This is the last super admin account. Its role cannot be changed while it is the only one.',
                ]);
            }
        });
    }

    private static function anotherSuperExists(self $except): bool
    {
        return static::query()
            ->where('role', self::SUPER_ROLE)
            ->when($except->exists, fn ($q) => $q->whereKeyNot($except->getKey()))
            ->exists();
    }

    /**
     * Which roles this admin may hand out.
     *
     * super may create anyone, including another super and an hq. hq may create
     * state admins and nothing else — not a super, and not a peer. Everyone else
     * never reaches the screen.
     *
     * DERIVED FROM THE CALLER, NOT A FIXED LIST, and that is the whole point. A
     * rule of `Rule::in(array_keys(Admin::ROLES))` is indifferent to who is
     * asking, which is harmless only while the screen is super-only. Widen the
     * screen to hq without this and hq can POST `role=super` and mint itself a
     * super account.
     *
     * Returns key => label, exactly like ROLES, because the forms render it
     * directly. A form that offers hq the Super option and then 422s on submit is
     * the UI disagreeing with the route.
     */
    public static function assignableRolesFor(self $admin): array
    {
        if ($admin->isSuperAdmin()) {
            return self::ROLES;
        }

        return array_intersect_key(self::ROLES, array_flip(['admin']));
    }

    /** Full access, and the only role that manages other admins. */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::SUPER_ROLE;
    }

    /** Manages companies and draw lists within one state. */
    public function isStateAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Super and HQ see every state; a state admin sees one.
     *
     * This is the predicate App\Support\EventVisibility asks. Nothing else in the
     * application decides scope — there is deliberately no middleware alias that
     * looks like a state guard, because a guard on every admin route reads as
     * enforcement and would enforce nothing.
     */
    public function hasGlobalAccess(): bool
    {
        return in_array($this->role, ['super', 'hq'], true);
    }

    /** Super or HQ may manage events and the lot draw. */
    public function canManageEvents(): bool
    {
        return $this->hasGlobalAccess();
    }

    /**
     * Super or HQ may edit what the public site says — the footer menus and the
     * social links.
     *
     * None of it is state-scoped: one footer is read by every usahawan in the
     * country, so a state admin editing it is editing thirteen other states'
     * footer.
     *
     * What this does NOT cover is anything that writes the `admins` table.
     * Account management stays super-only, and so do zones.
     */
    public function canManageSiteContent(): bool
    {
        return $this->hasGlobalAccess();
    }
}
