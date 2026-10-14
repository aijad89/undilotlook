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
     *
     * `kraf_bidang_code_id` is null by default and that is the ordinary case: a
     * sub-category need not be mapped to a craft code for a company to pick it.
     * Ask for the mapping with codedAs().
     */
    public function definition(): array
    {
        return [
            'bidang_id' => KrafBidang::factory(),
            'kraf_bidang_code_id' => null,
            'name' => fake()->unique()->words(2, true),
        ];
    }

    /** Mapped to a coding-sheet row — the one place hierarchy and codes meet. */
    public function codedAs(?KrafBidang $code = null): static
    {
        return $this->state(fn () => [
            'kraf_bidang_code_id' => $code?->id ?? KrafBidang::factory()->code(),
        ]);
    }
}
