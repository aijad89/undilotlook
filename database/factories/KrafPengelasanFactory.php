<?php

namespace Database\Factories;

use App\Models\KrafPengelasan;
use App\Models\KrafSubBidang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KrafPengelasan>
 */
class KrafPengelasanFactory extends Factory
{
    protected $model = KrafPengelasan::class;

    /** Every column, nullable ones included — see UserFactory for why. */
    public function definition(): array
    {
        return [
            'sub_bidang_id' => KrafSubBidang::factory(),
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
