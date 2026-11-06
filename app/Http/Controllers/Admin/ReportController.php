<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\KrafBidang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV exports. Open to every admin; a state admin's exports are narrowed to
 * companies in their own zone, the same scope as every screen.
 */
class ReportController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user('admin');

        return view('admin.reports.index', [
            // The events rule EventVisibility applies: a state admin sees every
            // event that is not a draft.
            'events' => Event::latest('start_date')
                ->unless($admin->hasGlobalAccess(), fn ($q) => $q->where('status', '!=', 'draft'))
                ->get(['id', 'title']),
            'categories' => KrafBidang::categories()->orderBy('name')->get(['id', 'name']),
            'zones' => $admin->hasGlobalAccess() ? \App\Support\AdminZones::all() : [$admin->state],
        ]);
    }

    /** Every company in each draw list, with what it draws under and what it holds. */
    public function registrations(Request $request): StreamedResponse
    {
        $query = $this->scoped($request, EventRegistration::query())
            ->with(['event', 'company.krafSelections.bidang', 'plot'])
            ->when($request->input('event_id'), fn ($q, $id) => $q->where('event_id', $id))
            ->orderBy('event_id')->orderBy('id');

        return $this->csv('draw_lists', [
            'Event', 'Company', 'Zone', 'Craft category', 'Craft code', 'Booth size', 'Lot', 'Confirmed',
        ], $query, fn ($r) => [
            $r->event->title,
            $r->company->name,
            $r->company->state,
            $this->category($r->company),
            $r->code,
            $r->plot_size,
            $r->plot?->plot_code,
            $r->confirmed_participation ? 'Yes' : 'No',
        ]);
    }

    public function companies(Request $request): StreamedResponse
    {
        $admin = $request->user('admin');

        $query = Company::with(['user', 'krafSelections.bidang', 'krafSelections.subBidang'])
            ->unless($admin->hasGlobalAccess(), fn ($q) => $q->where('state', $admin->state))
            ->listFilters($request)
            ->when($request->input('bidang_id'), fn ($q, $id) => $q->whereHas('krafSelections', fn ($k) => $k->where('bidang_id', $id)))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->input('active') === '1'))
            ->orderBy('name');

        // The Companies table's columns first, then the extra contact columns.
        $n = 0;

        return $this->csv('companies', [
            'No.', 'Name', 'IC', 'Community ID', 'Company', 'Craft category', 'Sub-category', 'Zone',
            'Entrepreneur category', 'Active', 'Email', 'Phone',
        ], $query, function ($c) use (&$n) {
            return [
                ++$n,
                $c->user->name,
                $c->user->ic_number,
                $c->user->community_id,
                $c->name,
                $c->krafSelections->map(fn ($k) => $k->bidang?->name)->join('; '),
                $c->krafSelections->map(fn ($k) => $k->subBidang?->name)->join('; '),
                $c->state,
                $c->entrepreneur_category,
                $c->is_active ? 'Yes' : 'No',
                $c->user->email,
                $c->user->phone,
            ];
        });
    }

    /** One row per event: how full its draw is, and how far it has got. */
    public function eventSummary(Request $request): StreamedResponse
    {
        $admin = $request->user('admin');
        $mine = fn ($q) => $admin->hasGlobalAccess() ? $q : $q->whereHas('company', fn ($c) => $c->where('state', $admin->state));

        $query = Event::query()
            ->withCount([
                'registrations as in_draw' => $mine,
                'registrations as seated' => fn ($q) => $mine($q->whereNotNull('plot_id')),
                'plots',
            ])
            ->unless($admin->hasGlobalAccess(), fn ($q) => $q->where('status', '!=', 'draft'))
            ->when($request->input('date_from'), fn ($q, $d) => $q->whereDate('start_date', '>=', $d))
            ->when($request->input('date_to'), fn ($q, $d) => $q->whereDate('start_date', '<=', $d))
            ->orderByDesc('start_date');

        return $this->csv('event_summary', [
            'Event', 'Zone', 'Location', 'Status', 'Draw stage', 'Start', 'End', 'In the draw', 'Seated', 'Plots',
        ], $query, fn ($e) => [
            $e->title,
            $e->state,
            $e->location,
            $e->status_label,
            $e->polling_status_label,
            $e->start_date?->format('d/m/Y'),
            $e->end_date?->format('d/m/Y'),
            $e->in_draw,
            $e->seated,
            $e->plots_count,
        ]);
    }

    /** Who holds which lot. */
    public function lotDraw(Request $request): StreamedResponse
    {
        $query = $this->scoped($request, EventRegistration::query())
            ->whereNotNull('plot_id')
            ->with(['event', 'company', 'plot.plotSize'])
            ->when($request->input('event_id'), fn ($q, $id) => $q->where('event_id', $id))
            ->orderBy('event_id')->orderBy('id');

        return $this->csv('lot_results', [
            'Event', 'Lot', 'Location', 'Size', 'Company', 'Zone', 'Craft code', 'How', 'Allocated at',
        ], $query, fn ($r) => [
            $r->event->title,
            $r->plot->plot_code,
            $r->plot->plotSize->location,
            $r->plot->plotSize->size,
            $r->company->name,
            $r->company->state,
            $r->code,
            $r->plot->allocation_type === 'preset' ? 'Preset' : 'Drawn',
            $r->plot->allocated_at?->format('d/m/Y H:i'),
        ]);
    }

    /** Draw-list rows a state admin may see: their zone's companies. */
    private function scoped(Request $request, Builder $query): Builder
    {
        $admin = $request->user('admin');

        return $query
            ->unless($admin->hasGlobalAccess(), fn ($q) => $q->whereHas('company', fn ($c) => $c->where('state', $admin->state)))
            ->when($request->input('state'), fn ($q, $zone) => $q->whereHas('company', fn ($c) => $c->where('state', $zone)));
    }

    /** The company's craft category — its primary selection, else its first. */
    private function category(Company $company): ?string
    {
        return $company->krafSelections->sortByDesc('is_primary')->first()?->bidang?->name;
    }

    /**
     * Stream a CSV, 200 rows at a time.
     *
     * Every cell goes through the formula guard: a value starting with = + - @
     * tab or CR is evaluated by Excel when the file is opened, and names, phones
     * and emails here are typed by whoever filled in a form. A leading apostrophe
     * makes the spreadsheet treat it as text.
     */
    private function csv(string $name, array $header, Builder $query, callable $row): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $query, $row) {
            $out = fopen('php://output', 'w');
            // A byte-order mark, so Excel reads the file as UTF-8 and Malay names
            // with any accent survive.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);

            $query->chunk(200, function ($records) use ($out, $row) {
                foreach ($records as $record) {
                    fputcsv($out, array_map(function ($value) {
                        $value = (string) ($value ?? '');

                        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
                    }, $row($record)));
                }
            });

            fclose($out);
        }, $name.'_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
