<?php

use App\Models\Company;
use App\Models\KrafBidang;
use App\Models\KrafPengelasan;
use App\Models\KrafSubBidang;

/**
 * A company's craft selection is a category, a sub-category, and one or more of
 * that sub-category's classifications, ticked. At least one each; only that
 * sub-category's own; and a classification a company ticked cannot be deleted.
 */
function batik(): array
{
    $category = KrafBidang::factory()->create(['name' => 'TEKSTIL']);
    $sub = KrafSubBidang::factory()->create(['bidang_id' => $category->id, 'name' => 'Batik']);
    [$canting, $blok, $skrin] = collect(['Batik Canting / Batik Lukis', 'Batik Blok / Batik Terap', 'Batik Skrin'])
        ->map(fn ($name) => KrafPengelasan::factory()->create(['sub_bidang_id' => $sub->id, 'name' => $name]));

    return [$category, $sub, $canting, $blok, $skrin];
}

it('saves the classifications ticked, and shows them on the company page', function () {
    [$category, $sub, $canting, $blok, $skrin] = batik();
    $company = Company::factory()->create(['state' => 'Johor']);
    $admin = stateAdmin('Johor');

    $this->actingAs($admin, 'admin')->post(route('admin.companies.kraf.store', $company), [
        'bidang_id' => $category->id, 'sub_bidang_id' => [$sub->id], 'pengelasan_id' => [$canting->id, $skrin->id],
    ])->assertSessionHas('success');

    expect($company->krafSelections()->sole()->pengelasan->pluck('id')->all())->toBe([$canting->id, $skrin->id]);

    $this->actingAs($admin, 'admin')->get(route('admin.companies.edit', $company))
        ->assertOk()->assertSee('Batik Canting / Batik Lukis, Batik Skrin');
});

it('takes classifications as optional, and only from that sub-category', function () {
    [$category, $sub] = batik();
    $other = KrafSubBidang::factory()->create(['bidang_id' => $category->id]);
    $stray = KrafPengelasan::factory()->create(['sub_bidang_id' => $other->id]);
    $company = Company::factory()->create(['state' => 'Johor']);
    $admin = stateAdmin('Johor');

    // None ticked: saved, with none.
    $this->actingAs($admin, 'admin')->post(route('admin.companies.kraf.store', $company), [
        'bidang_id' => $category->id, 'sub_bidang_id' => [$sub->id],
    ])->assertSessionHas('success');
    expect($company->krafSelections()->sole()->pengelasan)->toHaveCount(0);
    $company->krafSelections()->sole()->delete();

    // One of its own plus a stray: saved, with the stray left off.
    $canting = KrafPengelasan::where('sub_bidang_id', $sub->id)->first();
    $this->actingAs($admin, 'admin')->post(route('admin.companies.kraf.store', $company), [
        'bidang_id' => $category->id, 'sub_bidang_id' => [$sub->id], 'pengelasan_id' => [$canting->id, $stray->id],
    ])->assertSessionHas('success');

    expect($company->krafSelections()->sole()->pengelasan->pluck('id')->all())->toBe([$canting->id]);
});

it('will not delete a classification a company has ticked', function () {
    [$category, $sub, $canting, $blok] = batik();
    $company = Company::factory()->create(['state' => 'Johor']);

    $this->actingAs(superAdmin(), 'admin')->post(route('admin.companies.kraf.store', $company), [
        'bidang_id' => $category->id, 'sub_bidang_id' => [$sub->id], 'pengelasan_id' => [$canting->id],
    ]);

    $this->actingAs(superAdmin(), 'admin')
        ->delete(route('admin.settings.kraf-hierarchy.pengelasan.destroy', $canting))
        ->assertSessionHas('error');
    $this->actingAs(superAdmin(), 'admin')
        ->delete(route('admin.settings.kraf-hierarchy.pengelasan.destroy', $blok))
        ->assertSessionHas('success');

    expect($canting->fresh())->not->toBeNull()->and($blok->fresh())->toBeNull();
});

it('takes several classifications at once, comma-separated, without doubling any', function () {
    $category = KrafBidang::factory()->create();
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.settings.kraf-hierarchy.sub.store', $category), [
        'name' => 'Batik',
        'classification' => 'Batik Canting / Batik Lukis, Batik Blok / Batik Terap ,, batik canting / batik lukis',
    ])->assertSessionHas('success');

    $sub = KrafSubBidang::where('name', 'Batik')->sole();
    expect($sub->pengelasan()->pluck('name')->all())->toBe(['Batik Canting / Batik Lukis', 'Batik Blok / Batik Terap']);

    $this->actingAs($admin, 'admin')->post(route('admin.settings.kraf-hierarchy.pengelasan.store', $sub), [
        'name' => 'Batik Blok / Batik Terap, Batik Skrin',
    ])->assertSessionHas('success');

    expect($sub->pengelasan()->pluck('name')->all())
        ->toBe(['Batik Canting / Batik Lukis', 'Batik Blok / Batik Terap', 'Batik Skrin']);
});

it('comes back to the card it was sent from, with the message in it', function () {
    [$category, $sub, $canting] = batik();
    $company = Company::factory()->create(['state' => 'Johor']);
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')
        ->from(route('admin.settings.kraf-hierarchy'))
        ->post(route('admin.settings.kraf-hierarchy.pengelasan.store', $sub), ['name' => 'Batik Pelangi'])
        ->assertRedirect(route('admin.settings.kraf-hierarchy').'#category-'.$category->id);

    $this->actingAs($admin, 'admin')->get(route('admin.settings.kraf-hierarchy'))
        ->assertSeeInOrder(['id="category-'.$category->id.'"', 'Classification added.']);

    $this->actingAs($admin, 'admin')
        ->from(route('admin.companies.edit', $company))
        ->post(route('admin.companies.kraf.store', $company), [
            'bidang_id' => $category->id, 'sub_bidang_id' => [$sub->id], 'pengelasan_id' => [$canting->id],
        ])
        ->assertRedirect(route('admin.companies.edit', $company).'#craft-categories');

    $this->actingAs($admin, 'admin')->get(route('admin.companies.edit', $company))
        ->assertSeeInOrder(['id="craft-categories"', 'Craft selection added.']);
});

