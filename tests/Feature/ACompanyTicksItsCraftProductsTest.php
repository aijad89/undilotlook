<?php

use App\Models\Company;
use App\Models\KrafBidang;
use App\Models\KrafPengelasan;
use App\Models\KrafProduk;
use App\Models\KrafSubBidang;

/**
 * Products sit in a sub-category, beside its classifications. A company ticks
 * them when it adds a selection — optional, and only that sub-category's own.
 * A product a company ticked cannot be deleted in Settings, and products from
 * before they sat in a sub-category are shown apart and cannot be ticked.
 */
function batikWithProducts(): array
{
    $category = KrafBidang::factory()->create(['name' => 'TEKSTIL']);
    $sub = KrafSubBidang::factory()->create(['bidang_id' => $category->id, 'name' => 'Batik']);
    $canting = KrafPengelasan::factory()->create(['sub_bidang_id' => $sub->id]);
    [$pakaian, $hiasan] = KrafProduk::factory()->count(2)
        ->sequence(['name' => 'Pakaian'], ['name' => 'Hiasan Rumah'])
        ->create(['sub_bidang_id' => $sub->id]);

    return [$category, $sub, $canting, $pakaian, $hiasan];
}

it('saves the products ticked with a selection, only that sub-category\'s own', function () {
    [$category, $sub, $canting, $pakaian] = batikWithProducts();
    $stray = KrafProduk::factory()->create(['name' => 'Keris']);
    $unplaced = KrafProduk::factory()->unplaced($category)->create(['name' => 'Soft Toys']);
    $company = Company::factory()->create(['state' => 'Johor']);
    $admin = stateAdmin('Johor');

    $this->actingAs($admin, 'admin')->post(route('admin.companies.kraf.store', $company), [
        'bidang_id' => $category->id, 'sub_bidang_id' => [$sub->id],
        'pengelasan_id' => [$canting->id], 'produk_id' => [$pakaian->id, $stray->id, $unplaced->id],
    ])->assertSessionHas('success');

    expect($company->krafSelections()->sole()->produks->pluck('id')->all())->toBe([$pakaian->id]);

    $this->actingAs($admin, 'admin')->get(route('admin.companies.edit', $company))
        ->assertOk()->assertSee('Pakaian');
});

it('leaves products optional', function () {
    [$category, $sub, $canting] = batikWithProducts();
    $company = Company::factory()->create(['state' => 'Johor']);

    $this->actingAs(stateAdmin('Johor'), 'admin')->post(route('admin.companies.kraf.store', $company), [
        'bidang_id' => $category->id, 'sub_bidang_id' => [$sub->id], 'pengelasan_id' => [$canting->id],
    ])->assertSessionHas('success');

    expect($company->krafSelections()->sole()->produks)->toHaveCount(0);
});

it('will not delete a product a company has ticked', function () {
    [$category, $sub, $canting, $pakaian, $hiasan] = batikWithProducts();
    $company = Company::factory()->create(['state' => 'Johor']);

    $this->actingAs(superAdmin(), 'admin')->post(route('admin.companies.kraf.store', $company), [
        'bidang_id' => $category->id, 'sub_bidang_id' => [$sub->id],
        'pengelasan_id' => [$canting->id], 'produk_id' => [$pakaian->id],
    ]);

    $this->actingAs(superAdmin(), 'admin')
        ->delete(route('admin.settings.kraf-hierarchy.produk.destroy', $pakaian))->assertSessionHas('error');
    $this->actingAs(superAdmin(), 'admin')
        ->delete(route('admin.settings.kraf-hierarchy.produk.destroy', $hiasan))->assertSessionHas('success');

    expect($pakaian->fresh())->not->toBeNull()->and($hiasan->fresh())->toBeNull();
});

it('adds products into a sub-category, comma-separated, without doubling any', function () {
    KrafBidang::factory()->count(2)->create(); // so the category below is not row 1
    [$category, $sub] = batikWithProducts();
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.settings.kraf-hierarchy.produk.store', $sub), [
        'name' => 'pakaian, Cenderamata ,, Soft Toys',
    ])->assertSessionHas('success');

    $added = KrafProduk::where('sub_bidang_id', $sub->id)->orderBy('id')->get();
    expect($added->pluck('name')->all())->toBe(['Pakaian', 'Hiasan Rumah', 'Cenderamata', 'Soft Toys'])
        // The category column stays in step with the sub-category's.
        ->and($added->pluck('bidang_id')->unique()->all())->toBe([$category->id]);
});

it('takes products on the add-sub-category row too, optionally', function () {
    $category = KrafBidang::factory()->create();
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.settings.kraf-hierarchy.sub.store', $category), [
        'name' => 'Songket', 'classification' => 'Songket Tenun', 'products' => 'Kain, Selendang',
    ])->assertSessionHas('success');
    $this->actingAs($admin, 'admin')->post(route('admin.settings.kraf-hierarchy.sub.store', $category), [
        'name' => 'Tenun', 'classification' => 'Tenun Pahang',
    ])->assertSessionHas('success');

    expect(KrafSubBidang::where('name', 'Songket')->sole()->produks()->pluck('name')->all())->toBe(['Kain', 'Selendang'])
        ->and(KrafSubBidang::where('name', 'Tenun')->sole()->produks()->count())->toBe(0);
});

it('shows products with no sub-category apart, as not placed yet', function () {
    $category = KrafBidang::factory()->create();
    KrafProduk::factory()->unplaced($category)->create(['name' => 'Soft Toys']);

    $this->actingAs(superAdmin(), 'admin')->get(route('admin.settings.kraf-hierarchy'))
        ->assertOk()->assertSeeInOrder(['Products not placed in a sub-category', 'Soft Toys']);
});

it('deletes all of a sub-category\'s products at once, unless a company uses one', function () {
    [$category, $sub, $canting, $pakaian] = batikWithProducts();
    $elsewhere = KrafProduk::factory()->create();
    $company = Company::factory()->create(['state' => 'Johor']);
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.companies.kraf.store', $company), [
        'bidang_id' => $category->id, 'sub_bidang_id' => [$sub->id],
        'pengelasan_id' => [$canting->id], 'produk_id' => [$pakaian->id],
    ]);

    $this->actingAs($admin, 'admin')
        ->delete(route('admin.settings.kraf-hierarchy.produk.destroy-all', $sub))->assertSessionHas('error');
    expect($sub->produks()->count())->toBe(2);

    $company->krafSelections()->sole()->delete();
    $this->actingAs($admin, 'admin')
        ->delete(route('admin.settings.kraf-hierarchy.produk.destroy-all', $sub))->assertSessionHas('success');

    expect($sub->produks()->count())->toBe(0)
        ->and($elsewhere->fresh())->not->toBeNull();
});
