<?php

namespace Database\Factories;

use App\Models\KrafBidang;
use App\Models\KrafProduk;
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
     * A product hangs off the CATEGORY, so the default parent is a category row.
     */
    public function definition(): array
    {
        return [
            'bidang_id' => KrafBidang::factory(),
            'name' => fake()->unique()->words(2, true),
            'is_active' => true,
        ];
    }
}
