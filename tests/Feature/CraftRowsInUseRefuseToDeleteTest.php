<?php

use App\Models\CompanyKraf;
use App\Models\KrafBidang;
use App\Models\KrafPengelasan;
use App\Models\KrafSubBidang;
use Illuminate\Support\Facades\DB;

/**
 * A row removed by ON DELETE CASCADE leaves no audit row. The craft settings
 * close that by refusing: a `kraf_bidangs` or `kraf_sub_bidangs` row is deleted
 * only once nothing points at it, so the cascade has nothing to take.
 */
it('refuses to delete a craft category, a code or a sub-category that is still in use', function () {
    $category = KrafBidang::factory()->create();
    $code = KrafBidang::factory()->code('TBPS')->create();
    $sub = KrafSubBidang::factory()->codedAs($code)->create(['bidang_id' => $category->id]);
    CompanyKraf::factory()->create(['bidang_id' => $category->id, 'sub_bidang_id' => $sub->id]);

    $admin = superAdmin();

    $this->actingAs($admin, 'admin')
        ->delete(route('admin.settings.kraf-hierarchy.destroy', $category))
        ->assertSessionHas('error');

    $this->actingAs($admin, 'admin')
        ->delete(route('admin.settings.kraf-codes.destroy', $code))
        ->assertSessionHas('error');

    $this->actingAs($admin, 'admin')
        ->delete(route('admin.settings.kraf-hierarchy.sub.destroy', $sub))
        ->assertSessionHas('error');

    // A classification is the hierarchy's own level below a sub-category, and it
    // cascades the same way — so it refuses alone, with nothing else in use.
    $classified = KrafSubBidang::factory()->create(['bidang_id' => $category->id]);
    KrafPengelasan::factory()->create(['sub_bidang_id' => $classified->id]);

    $this->actingAs($admin, 'admin')
        ->delete(route('admin.settings.kraf-hierarchy.sub.destroy', $classified))
        ->assertSessionHas('error');

    expect($category->fresh())->not->toBeNull()
        ->and($code->fresh())->not->toBeNull()
        ->and($sub->fresh())->not->toBeNull()
        ->and($classified->fresh())->not->toBeNull()
        ->and(DB::table('kraf_pengelasan')->count())->toBe(1)
        ->and(DB::table('company_krafs')->count())->toBe(1);
});

it('deletes a craft row once nothing points at it', function () {
    $category = KrafBidang::factory()->create();

    $this->actingAs(superAdmin(), 'admin')
        ->delete(route('admin.settings.kraf-hierarchy.destroy', $category))
        ->assertSessionHas('success');

    expect($category->fresh())->toBeNull();
});
