<?php

namespace Database\Factories;

use App\Models\KrafBidang;
use App\Models\KrafSubBidang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KrafSubBidang>
 */
class KrafSubBidangFactory extends Factory
{
    protected $model = KrafSubBidang::class;

    /**
     * Every column, nullable ones included — see UserFactory for why.
     */
    public function definition(): array
    {
        return [
            'bidang_id' => KrafBidang::factory(),
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
