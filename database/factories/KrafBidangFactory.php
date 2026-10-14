<?php

namespace Database\Factories;

use App\Models\KrafBidang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KrafBidang>
 */
class KrafBidangFactory extends Factory
{
    protected $model = KrafBidang::class;

    /**
     * Every column, nullable ones included — see UserFactory for why.
     *
     * THE DEFAULT IS A CATEGORY, `code` null. The table holds two lists and a
     * test that does not say which one it meant is a test that will pass against
     * either — so the coded half has to be asked for by name, with code().
     */
    public function definition(): array
    {
        return [
            'bil' => null,
            'name' => fake()->unique()->randomElement([
                'TEKSTIL', 'HASIL RIMBA', 'HASIL LOGAM', 'SERAMIK', 'ANEKA KRAF',
            ]),
            'pecahan_bidang' => null,
            'pecahan_sub_bidang' => null,
            'code' => null,
            'is_active' => true,
            'allow_multiple_sub_bidang' => false,
        ];
    }

    /** A row from the printed coding sheet — the half a draw entry matches on. */
    public function code(string $code = 'TBPS'): static
    {
        return $this->state(fn () => [
            'bil' => fake()->unique()->numberBetween(1, 99),
            'code' => $code,
            'pecahan_bidang' => fake()->words(2, true),
        ]);
    }

    /** Withdrawn from the coding sheet. Still carried by whatever already has it. */
    public function retired(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
