<?php

use App\Models\CompanyKraf;
use App\Models\KrafBidang;
use App\Models\KrafPengelasan;
use App\Models\KrafSubBidang;
use App\Models\Plot;
use Illuminate\Support\Facades\DB;

/**
 * A row removed by ON DELETE CASCADE leaves no audit row. The craft settings
 * close that by refusing: a `kraf_bidangs` or `kraf_sub_bidangs` row is deleted
 * only once nothing points at it, so the cascade has nothing to take.
 */
it('refuses to delete a code or a sub-category that is still in use', function () {
    $category = KrafBidang::factory()->create();
    $code = KrafBidang::factory()->code('TBPS')->create();
    $sub = KrafSubBidang::factory()->create(['bidang_id' => $category->id]);
    Plot::factory()->coded($code)->create();
    CompanyKraf::factory()->create(['bidang_id' => $category->id, 'sub_bidang_id' => $sub->id]);

    $admin = superAdmin();

    $this->actingAs($admin, 'admin')
        ->delete(route('admin.settings.kraf-codes.destroy', $code))
        ->assertSessionHas('error');

    $this->actingAs($admin, 'admin')
        ->delete(route('admin.settings.kraf-hierarchy.sub.destroy', $sub))
        ->assertSessionHas('error');

    expect($category->fresh())->not->toBeNull()
        ->and($code->fresh())->not->toBeNull()
        ->and($sub->fresh())->not->toBeNull()
        ->and(DB::table('company_krafs')->count())->toBe(1);
});

it('deletes a sub-category together with its own classifications', function () {
    $sub = KrafSubBidang::factory()->create();
    KrafPengelasan::factory()->count(2)->create(['sub_bidang_id' => $sub->id]);

    $this->actingAs(superAdmin(), 'admin')
        ->delete(route('admin.settings.kraf-hierarchy.sub.destroy', $sub))
        ->assertSessionHas('success');

    expect($sub->fresh())->toBeNull()
        ->and(DB::table('kraf_pengelasan')->count())->toBe(0);
});

it('lets the last classification of a sub-category go, since they are optional', function () {
    $sub = KrafSubBidang::factory()->create();
    $only = KrafPengelasan::factory()->create(['sub_bidang_id' => $sub->id]);

    $this->actingAs(superAdmin(), 'admin')
        ->delete(route('admin.settings.kraf-hierarchy.pengelasan.destroy', $only))
        ->assertSessionHas('success');

    expect($only->fresh())->toBeNull()->and($sub->fresh())->not->toBeNull();
});

it('deletes a sub-category once nothing points at it', function () {
    $sub = KrafSubBidang::factory()->create();

    $this->actingAs(superAdmin(), 'admin')
        ->delete(route('admin.settings.kraf-hierarchy.sub.destroy', $sub))
        ->assertSessionHas('success');

    expect($sub->fresh())->toBeNull();
});

it('adds a sub-category with or without classifications, and with no craft code', function () {
    $category = KrafBidang::factory()->create();
    $code = KrafBidang::factory()->code('TBP')->create();
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')->post(route('admin.settings.kraf-hierarchy.sub.store', $category), [
        'name' => 'Batik', 'classification' => 'Batik Tulis',
    ])->assertSessionHas('success');

    $sub = KrafSubBidang::where('name', 'Batik')->sole();
    expect($sub->pengelasan()->pluck('name')->all())->toBe(['Batik Tulis']);

    $this->actingAs($admin, 'admin')->post(route('admin.settings.kraf-hierarchy.sub.store', $category), [
        'name' => 'Songket', 'classification' => '',
    ])->assertSessionHas('success');
    expect(KrafSubBidang::where('name', 'Songket')->sole()->pengelasan()->count())->toBe(0);
});

it('has no way to add or delete a craft category', function () {
    $category = KrafBidang::factory()->create();

    $this->actingAs(superAdmin(), 'admin')
        ->get(route('admin.settings.kraf-hierarchy'))
        ->assertOk()
        ->assertDontSee('Add category')
        ->assertDontSee('Delete category');

    // The pages are gone and so are the addresses behind them.
    $this->actingAs(superAdmin(), 'admin')->post(url('admin/settings/kraf-hierarchy'), ['name' => 'NEW'])->assertStatus(405);
    $this->actingAs(superAdmin(), 'admin')->delete(url("admin/settings/kraf-hierarchy/{$category->id}"))->assertNotFound();
    expect($category->fresh())->not->toBeNull();
});
