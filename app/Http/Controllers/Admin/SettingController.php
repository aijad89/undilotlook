<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KrafBidang;
use App\Models\KrafPengelasan;
use App\Models\KrafProduk;
use App\Models\KrafSubBidang;
use App\Support\AdminZones;
use App\Support\EntrepreneurCategories;
use App\Support\PlotSizes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The lists the rest of the application picks from.
 *
 * THREE OF THEM ARE SETTINGS ROWS, TWO ARE TABLES, and the difference is not
 * arbitrary. Zones, booth sizes and entrepreneur categories are short lists of
 * strings that nothing references by id, so they live as JSON in `settings`. The
 * craft hierarchy and the craft codes are rows that companies, plots and draw
 * entries point at, so they are tables.
 *
 * ZONES ARE NOT EDITED AS TEXT, and that is the one real trap on this screen. A
 * zone string IS the access-control value: a state admin reaches a company when
 * `companies.state` equals their own `admins.state` exactly. Retyping a zone in
 * a textarea would leave every row holding the old spelling stranded, silently
 * and with no error anywhere — so a zone is renamed through AdminZones::rename(),
 * which carries all three columns across in one transaction, and a zone still in
 * use cannot be deleted at all.
 */
class SettingController extends Controller
{
    /**
     * The lists that are settings rows.
     *
     * @return array<string, class-string<\App\Support\ManagedList>>
     */
    private const LISTS = [
        'plot_sizes' => PlotSizes::class,
        'entrepreneur_categories' => EntrepreneurCategories::class,
    ];

    public function lists(Request $request)
    {
        return view('admin.settings.lists', [
            'zones' => AdminZones::all(),
            'zoneUsage' => AdminZones::usageForAll(),
            'plotSizes' => PlotSizes::all(),
            'entrepreneurCategories' => EntrepreneurCategories::all(),
            'canEditZones' => $request->user('admin')->isSuperAdmin(),
        ]);
    }

    /**
     * Replace one managed list wholesale.
     *
     * The textarea is one value per line. ManagedList::set() trims, drops blanks
     * and refuses an empty result, so a cleared box cannot empty a dropdown the
     * forms depend on.
     */
    public function updateList(Request $request, string $list)
    {
        abort_unless(isset(self::LISTS[$list]), 404);

        $data = $request->validate(['values' => ['required', 'string']]);

        $class = self::LISTS[$list];
        $class::set(preg_split('/\R/', $data['values']) ?: []);

        return back()->with('success', 'List saved.');
    }

    public function storeZone(Request $request)
    {
        $this->authorizeZones($request);

        $data = $request->validate(['zone' => ['required', 'string', 'max:255']]);

        if (AdminZones::has($data['zone'])) {
            return back()->with('error', 'That zone is already on the list.');
        }

        AdminZones::set([...AdminZones::all(), $data['zone']]);

        return back()->with('success', 'Zone added.');
    }

    /**
     * Rename a zone AND every row filed under it, in one transaction.
     *
     * This is why zones are not a textarea. The rows are the point: the list is
     * only a label for what `admins.state`, `companies.state` and `events.state`
     * already hold.
     */
    public function renameZone(Request $request)
    {
        $this->authorizeZones($request);

        $data = $request->validate([
            'from' => ['required', AdminZones::rule()],
            'to' => ['required', 'string', 'max:255'],
        ]);

        $moved = AdminZones::rename($data['from'], $data['to']);

        return back()->with('success', "Renamed. {$moved} rows carried across.");
    }

    public function destroyZone(Request $request)
    {
        $this->authorizeZones($request);

        $data = $request->validate(['zone' => ['required', AdminZones::rule()]]);

        if (AdminZones::isInUse($data['zone'])) {
            return back()->with('error', 'That zone is still in use. Rename it or move its rows first.');
        }

        AdminZones::set(array_values(array_diff(AdminZones::all(), [$data['zone']])));

        return back()->with('success', 'Zone removed.');
    }

    /**
     * The coding sheet — the `code` IS NOT NULL half of `kraf_bidangs`.
     *
     * Named explicitly, as every query against this table must be: the other half
     * is the craft hierarchy and shares the table.
     */
    public function krafCodes()
    {
        return view('admin.settings.kraf-codes', [
            'codes' => KrafBidang::codes()->orderBy('bil')->orderBy('name')->get(),
        ]);
    }

    public function storeKrafCode(Request $request)
    {
        $data = $this->validatedCode($request);

        KrafBidang::create($data);

        return back()->with('success', "Code {$data['code']} added.");
    }

    public function updateKrafCode(Request $request, KrafBidang $code)
    {
        $this->abortUnlessCode($code);

        $code->update($this->validatedCode($request, $code));

        return back()->with('success', 'Code saved.');
    }

    /**
     * Withdraw a code, or put it back.
     *
     * `is_active` is how a code is retired, which is why it is not fillable and
     * moves only through here. Deleting one is a different act with a far longer
     * reach — see destroyKrafCode().
     */
    public function toggleKrafCode(KrafBidang $code)
    {
        $this->abortUnlessCode($code);

        $code->is_active = ! $code->is_active;
        $code->save();

        return back()->with('success', $code->is_active
            ? "Code {$code->code} is offered again."
            : "Code {$code->code} withdrawn. Rows already carrying it keep it.");
    }

    /**
     * Deleting a code row takes rows in five other tables with it, by cascade —
     * including plots and preset allocations. Withdrawing it with
     * toggleKrafCode() is almost always what was meant, so the count is put in
     * front of the admin first.
     */
    public function destroyKrafCode(KrafBidang $code)
    {
        $this->abortUnlessCode($code);

        if ($used = $this->krafBidangUsage($code)) {
            return back()->with('error', sprintf(
                'Cannot delete %s: %s still point at it. Withdraw it instead.',
                $code->code,
                $used
            ));
        }

        $code->delete();

        return back()->with('success', 'Code deleted.');
    }

    /** The craft hierarchy — the `code` IS NULL half, with its levels below. */
    public function krafHierarchy()
    {
        return view('admin.settings.kraf-hierarchy', [
            'categories' => KrafBidang::categories()
                ->with(['subBidangs.pengelasan', 'subBidangs.codeBidang', 'produks'])
                ->orderBy('name')
                ->get(),
            'codes' => KrafBidang::codes()->active()->orderBy('bil')->get(['id', 'code', 'name']),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('kraf_bidangs', 'name')->whereNull('code')],
        ]);

        // `code` stays null. That is what makes this a category rather than a
        // coding-sheet row, and it is the only thing that does.
        KrafBidang::create(['name' => $data['name']]);

        return back()->with('success', 'Craft category added.');
    }

    public function storeSubBidang(Request $request, KrafBidang $category)
    {
        $this->abortUnlessCategory($category);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Optional, and it stays optional: setting a company's craft category
            // must never require a code to exist first.
            'kraf_bidang_code_id' => ['nullable', Rule::exists('kraf_bidangs', 'id')->whereNotNull('code')],
        ]);

        $category->subBidangs()->create($data);

        return back()->with('success', 'Sub-category added.');
    }

    public function storePengelasan(Request $request, KrafSubBidang $subBidang)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $subBidang->pengelasan()->create($data);

        return back()->with('success', 'Classification added.');
    }

    public function storeProduk(Request $request, KrafBidang $category)
    {
        $this->abortUnlessCategory($category);

        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $category->produks()->create($data);

        return back()->with('success', 'Product added.');
    }

    /**
     * Delete one row of the hierarchy.
     *
     * A category reaches six tables by cascade and a sub-category five, so both
     * are counted first and refused while anything points at them. The counts
     * come from the tables themselves rather than from the migration — a cascade
     * is a property of the live database.
     */
    public function destroyCategory(KrafBidang $category)
    {
        $this->abortUnlessCategory($category);

        if ($used = $this->krafBidangUsage($category)) {
            return back()->with('error', "Cannot delete {$category->name}: {$used} still point at it.");
        }

        $category->delete();

        return back()->with('success', 'Craft category deleted.');
    }

    public function destroySubBidang(KrafSubBidang $subBidang)
    {
        $counts = collect([
            'classifications' => DB::table('kraf_pengelasan')->where('sub_bidang_id', $subBidang->id)->count(),
            'company selections' => DB::table('company_krafs')->where('sub_bidang_id', $subBidang->id)->count(),
            'plots' => DB::table('plots')->where('sub_bidang_id', $subBidang->id)->count(),
            'plan allocations' => DB::table('bidang_allocations')->where('sub_bidang_id', $subBidang->id)->count(),
            'preset allocations' => DB::table('preset_allocations')->where('sub_bidang_id', $subBidang->id)->count(),
        ])->filter();

        if ($counts->isNotEmpty()) {
            return back()->with('error', sprintf(
                'Cannot delete %s: %s still point at it.',
                $subBidang->name,
                $counts->map(fn ($n, $what) => "{$n} {$what}")->join(', ', ' and ')
            ));
        }

        $subBidang->delete();

        return back()->with('success', 'Sub-category deleted.');
    }

    public function destroyPengelasan(KrafPengelasan $pengelasan)
    {
        $pengelasan->delete();

        return back()->with('success', 'Classification deleted.');
    }

    public function destroyProduk(KrafProduk $produk)
    {
        $produk->delete();

        return back()->with('success', 'Product deleted.');
    }

    /**
     * What still points at a `kraf_bidangs` row, as a readable phrase, or '' when
     * nothing does.
     *
     * One method for both halves of the table because the cascade does not care
     * which half a row belongs to: every one of these foreign keys names
     * `kraf_bidangs`.
     */
    private function krafBidangUsage(KrafBidang $bidang): string
    {
        $counts = collect([
            'company selections' => DB::table('company_krafs')->where('bidang_id', $bidang->id)->count(),
            'sub-categories' => DB::table('kraf_sub_bidangs')->where('bidang_id', $bidang->id)->count(),
            'products' => DB::table('kraf_produks')->where('bidang_id', $bidang->id)->count(),
            'plots' => DB::table('plots')->where('bidang_id', $bidang->id)->count(),
            'plan allocations' => DB::table('bidang_allocations')->where('bidang_id', $bidang->id)->count(),
            'preset allocations' => DB::table('preset_allocations')->where('bidang_id', $bidang->id)->count(),
            'coded sub-categories' => DB::table('kraf_sub_bidangs')->where('kraf_bidang_code_id', $bidang->id)->count(),
        ])->filter();

        return $counts->map(fn ($n, $what) => "{$n} {$what}")->join(', ', ' and ');
    }

    /** @return array<string, mixed> */
    private function validatedCode(Request $request, ?KrafBidang $except = null): array
    {
        return $request->validate([
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('kraf_bidangs', 'code')->ignore($except?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'bil' => ['nullable', 'integer', 'min:1'],
            'pecahan_bidang' => ['nullable', 'string', 'max:255'],
            'pecahan_sub_bidang' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /** A coded row and a category are not interchangeable, so each route says which. */
    private function abortUnlessCode(KrafBidang $bidang): void
    {
        abort_if(blank($bidang->code), 404);
    }

    private function abortUnlessCategory(KrafBidang $bidang): void
    {
        abort_unless(blank($bidang->code), 404);
    }

    private function authorizeZones(Request $request): void
    {
        abort_unless(
            $request->user('admin')->isSuperAdmin(),
            403,
            'Zones decide who sees what. Only a super admin may change them.'
        );
    }
}
