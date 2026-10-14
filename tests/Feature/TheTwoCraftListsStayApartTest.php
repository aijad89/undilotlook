<?php

use App\Models\Company;
use App\Models\CompanyKraf;
use App\Models\KrafBidang;
use App\Models\KrafSubBidang;
use App\Support\KrafPicker;

/**
 * `kraf_bidangs` holds TWO lists — the craft hierarchy (`code` IS NULL) and the
 * printed coding sheet (`code` IS NOT NULL) — and a query that forgets to say
 * which one it means still returns rows. That is what makes the mistake quiet:
 * the category picker offers codes, the code list offers categories, and nothing
 * anywhere errors.
 *
 * These hold the two scopes and the picker apart.
 */
it('offers only categories in the picker and only codes in the code list', function () {
    $category = KrafBidang::factory()->create(['name' => 'TEKSTIL']);
    $code = KrafBidang::factory()->code('TBPS')->create(['name' => 'TENUNAN']);
    $retired = KrafBidang::factory()->code('XXX')->retired()->create(['name' => 'LAMA']);

    $company = Company::factory()->create();

    $picker = KrafPicker::data($company);

    expect($picker['bidangs']->pluck('id'))
        ->toContain($category->id)
        ->not->toContain($code->id)
        ->not->toContain($retired->id);

    expect(KrafPicker::codes()->pluck('id'))
        ->toContain($code->id)
        ->not->toContain($category->id)
        // `is_active` is how a code is withdrawn; offering one would be assigning it.
        ->not->toContain($retired->id);
});

it('keeps a selection on the hierarchy half, never on a coding-sheet row', function () {
    $selection = CompanyKraf::factory()->create();

    expect($selection->bidang->code)->toBeNull()
        ->and($selection->subBidang->bidang_id)->toBe($selection->bidang_id);
});

it('reaches a craft code from a sub-category only through kraf_bidang_code_id', function () {
    $code = KrafBidang::factory()->code('RKP')->create();
    $sub = KrafSubBidang::factory()->codedAs($code)->create();

    expect($sub->codeBidang->code)->toBe('RKP')
        // The sub-category's own parent is still a category, not the code row.
        ->and($sub->bidang->code)->toBeNull();
});

it('leaves a sub-category unmapped by default, because a code need not exist first', function () {
    expect(KrafSubBidang::factory()->create()->kraf_bidang_code_id)->toBeNull();
});
