<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\PollingGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The admin landing screen.
 *
 * NOT OPTIONAL: bootstrap/app.php's `redirectUsersTo` sends every signed-in
 * admin here, so this route existing is what keeps an already-signed-in admin
 * from being bounced to the PARTICIPANT login form when they open /admin/login.
 *
 * Four sections — what needs fixing, the events, the companies, and shortcuts.
 * Every count is scoped the way the screen it links to is scoped: a state admin
 * counts their own zone (through `companies.state`) and never sees a draft
 * event; HQ and super count everything. Counts only, aggregated in SQL.
 */
class DashboardController extends Controller
{
    /** How many events the events card lists. */
    private const EVENTS_SHOWN = 6;

    public function index(Request $request)
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $global = $admin->hasGlobalAccess();

        // A draw-list row is in this admin's zone when its company is.
        $inZone = fn ($q) => $q->unless($global, fn ($q) => $q->whereHas(
            'company', fn ($c) => $c->where('state', $admin->state)
        ));
        $companies = fn () => Company::query()->unless($global, fn ($q) => $q->where('state', $admin->state));

        $events = $this->events($global, $inZone);

        return view('admin.dashboard', [
            'admin' => $admin,
            'global' => $global,
            'attention' => $this->attention($global, $inZone, $companies, $events),
            'events' => $events,
            'companyStats' => $this->companyStats($global, $companies),
        ]);
    }

    /**
     * Upcoming and running events, soonest first, each with its draw-list counts
     * (in this admin's zone) and its plots (event-wide — plots have no zone).
     */
    private function events(bool $global, \Closure $inZone)
    {
        return Event::query()
            ->unless($global, fn ($q) => $q->where('status', '!=', 'draft'))
            ->where('status', '!=', 'completed')
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->startOfDay()))
            ->withCount([
                'registrations as on_list' => $inZone,
                'registrations as coded' => fn ($q) => $inZone($q)->whereNotNull('code')->where('code', '!=', ''),
                'registrations as confirmed' => fn ($q) => $inZone($q)->where('confirmed_participation', true),
                'plots as plots_total',
                'plots as plots_taken' => fn ($q) => $q->where('is_allocated', true),
            ])
            ->orderByRaw('start_date is null, start_date')
            ->limit(self::EVENTS_SHOWN)
            ->get();
    }

    /**
     * What needs fixing, as a list of [tone, text, link] rows. An empty section
     * shows a single "all clear" line instead.
     */
    private function attention(bool $global, \Closure $inZone, \Closure $companies, $events): array
    {
        $rows = [];

        // A1 — draw-list rows with no craft code, per event.
        foreach ($events as $event) {
            $missing = $event->on_list - $event->coded;
            if ($missing > 0) {
                $rows[] = ['warn', "{$event->title}: {$missing} draw-list ".str('row')->plural($missing).' with no craft code',
                    route('admin.events.registrations.index', $event)];
            }
        }

        // A2/A3 — a draw that has not opened yet and could not open now. Event-wide,
        // so for super and HQ only: a state admin cannot fix other zones' rows.
        if ($global) {
            foreach ($events->where('polling_status', 'draft') as $event) {
                if ($event->on_list > 0 && ($reason = PollingGuard::blocker($event))) {
                    $when = $event->polling_start_date ? ' (draw opens '.$event->polling_start_date->diffForHumans().')' : '';
                    $rows[] = ['bad', "{$event->title}{$when}: ".str($reason)->after('Cannot start polling: '),
                        route('admin.polling.show', $event)];
                }
            }
        }

        // A4 — a draw open now, with companies that have not confirmed yet.
        foreach ($events->where('polling_status', 'polling') as $event) {
            $waiting = $event->on_list - $event->confirmed;
            if ($waiting > 0) {
                $deadline = $event->polling_deadline ? ', closes '.$event->polling_deadline->diffForHumans() : '';
                $rows[] = ['warn', "{$event->title}: {$waiting} ".str('company')->plural($waiting)." not confirmed yet{$deadline}",
                    route('admin.events.registrations.index', $event)];
            }
        }

        // A5/A6 — companies missing what describes them.
        $noCategory = $companies()->active()->whereDoesntHave('krafSelections')->count();
        if ($noCategory > 0) {
            $rows[] = ['warn', "{$noCategory} ".str('company')->plural($noCategory).' with no craft category',
                route('admin.companies.index', ['missing' => 'category'])];
        }

        $noCode = $companies()->active()->whereNull('kraf_code')->count();
        if ($noCode > 0) {
            $rows[] = ['info', "{$noCode} ".str('company')->plural($noCode).' with no craft code — the draw list cannot fill one in',
                route('admin.companies.index', ['missing' => 'code'])];
        }

        return $rows;
    }

    /** Totals, by craft category, and — for super and HQ — by zone. */
    private function companyStats(bool $global, \Closure $companies): array
    {
        $ids = $companies()->select('id');

        return [
            'active' => $companies()->where('is_active', true)->count(),
            'inactive' => $companies()->where('is_active', false)->count(),
            'new' => $companies()->where('created_at', '>=', now()->startOfMonth())->count(),

            // A company with two selections in one category counts once there.
            'byCategory' => DB::table('company_krafs')
                ->join('kraf_bidangs', 'kraf_bidangs.id', '=', 'company_krafs.bidang_id')
                ->whereIn('company_krafs.company_id', $ids)
                ->selectRaw('kraf_bidangs.name, count(distinct company_krafs.company_id) as n')
                ->groupBy('kraf_bidangs.name')
                ->orderByDesc('n')
                ->pluck('n', 'name'),

            'byZone' => $global
                ? Company::query()->whereNotNull('state')
                    ->selectRaw('state, count(*) as n')->groupBy('state')->orderByDesc('n')->pluck('n', 'state')
                : collect(),
        ];
    }
}
