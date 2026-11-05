<?php

namespace App\Support;

use App\Models\Company;
use App\Models\KrafBidang;
use App\Models\KrafSubBidang;

/**
 * Everything the craft-category picker needs to draw itself for one company.
 *
 * The picker is a section of the company edit page, not a screen of its own, so
 * the query half lives here and the view can be included from anywhere without a
 * controller behind it.
 *
 * A selection is a CATEGORY, a sub-category under it, one or more of that
 * sub-category's classifications (`company_kraf_pengelasan`), and any of its
 * products (`company_kraf_produk`). None of it is read by the draw, which
 * matches on the craft code set per event.
 *
 * Only reads. Everything that writes stays with the controller that saves the
 * company: which sub-categories may be picked together and which row is primary
 * are decisions, and splitting them from the save would mean writing them twice.
 */
class KrafPicker
{
    /** The category whose sub-categories are picked several at a time. */
    public const MULTI_SUB_CATEGORY = 'HASIL LOGAM';

    /**
     * @param  int|null  $editId  A company_krafs row to load back into the picker.
     * @return array<string, mixed>
     */
    public static function data(Company $company, ?int $editId = null): array
    {
        // Scoped to this company, so an id belonging to another one simply is not
        // found. Not firstOrFail(): the picker shares a page with the whole company
        // form, and a stale ?edit_id= — a row since removed, a copied link — would
        // 404 that entire page rather than just opening an empty picker.
        $editSelection = $editId
            ? $company->krafSelections()->where('id', $editId)->with(['bidang', 'subBidang'])->first()
            : null;

        // Nothing loaded means nothing is being edited, whatever the URL said.
        $editId = $editSelection?->id;

        // HASIL LOGAM is stored as one row per sub-category, so editing it means
        // loading the whole set, not the single row that was clicked.
        $editSelections = collect();
        if ($editSelection && $editSelection->bidang?->name === self::MULTI_SUB_CATEGORY) {
            $editSelections = $company->krafSelections()
                ->where('bidang_id', $editSelection->bidang_id)
                ->with(['bidang', 'subBidang'])->get();
        }

        $selectedSubBidangId = $editSelection?->sub_bidang_id;

        return [
            'selections' => $company->krafSelections()->with(['bidang', 'subBidang', 'pengelasan', 'produks'])->get(),

            // Classifications and products by sub-category, for the tick boxes under the picker.
            'pengelasanBySub' => \App\Models\KrafPengelasan::orderBy('id')->get(['id', 'sub_bidang_id', 'name'])
                ->groupBy('sub_bidang_id')->map->pluck('name', 'id'),
            'produksBySub' => \App\Models\KrafProduk::whereNotNull('sub_bidang_id')->orderBy('id')
                ->get(['id', 'sub_bidang_id', 'name'])
                ->groupBy('sub_bidang_id')->map->pluck('name', 'id'),

            // The taxonomy half of kraf_bidangs — `code` IS NULL. The coded rows
            // are the lot-draw coding sheet and belong in codes() below, not in
            // the picker's first step.
            'bidangs' => KrafBidang::categories()->orderBy('name')->get(),
            'subBidangs' => KrafSubBidang::with('bidang')->get()->groupBy('bidang_id'),

            'editId' => $editId,
            'selectedBidangId' => $editSelection?->bidang_id,
            'selectedSubBidangId' => $selectedSubBidangId,
            'selectedSubBidangIds' => $editSelections->isNotEmpty()
                ? $editSelections->pluck('sub_bidang_id')->all()
                : ($selectedSubBidangId ? [$selectedSubBidangId] : []),
        ];
    }

    /**
     * The craft codes a company may carry — the coding sheet a registration is
     * matched on when lots are drawn, which is a different list from the taxonomy
     * above. Retired codes are left out: `is_active` is how a code is withdrawn,
     * and offering one would be assigning it.
     *
     * @return \Illuminate\Support\Collection<int, KrafBidang>
     */
    public static function codes()
    {
        return KrafBidang::codes()->active()->orderBy('bil')->get(['id', 'name', 'code']);
    }
}
