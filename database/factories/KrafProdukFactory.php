<?php

namespace Database\Factories;

use App\Models\KrafBidang;
use App\Models\KrafProduk;
use App\Models\KrafSubBidang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KrafProduk>
 */
class KrafProdukFactory extends Factory
{
    protected $model = KrafProduk::class;

    /**
     * Every column, nullable ones included — see UserFactory for why.
     *
     * A product sits in a sub-category, and its category is that sub-category's.
     */
    public function definition(): array
    {
        return [
            'sub_bidang_id' => KrafSubBidang::factory(),
            'bidang_id' => fn (array $attributes) => KrafSubBidang::find($attributes['sub_bidang_id'])->bidang_id,
            'name' => fake()->unique()->words(2, true),
            'is_active' => true,
        ];
    }

    /** A product entered against a category only, before products sat in sub-categories. */
    public function unplaced(?KrafBidang $category = null): static
    {
        return $this->state(fn () => [
            'sub_bidang_id' => null,
            'bidang_id' => $category?->id ?? KrafBidang::factory(),
        ]);
    }
}
