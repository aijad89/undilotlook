<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyKraf;
use App\Models\KrafBidang;
use App\Models\KrafSubBidang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanyKraf>
 */
class CompanyKrafFactory extends Factory
{
    protected $model = CompanyKraf::class;

    /**
     * Every column, nullable ones included — see UserFactory for why.
     *
     * The sub-category is created UNDER the category, not beside it: a selection
     * whose two columns name unrelated rows is a shape the screens cannot
     * produce, and a test built on one proves nothing about the real pair.
     */
    public function definition(): array
    {
        $bidang = KrafBidang::factory();

        return [
            'company_id' => Company::factory(),
            'bidang_id' => $bidang,
            'sub_bidang_id' => fn (array $attributes) => KrafSubBidang::factory()->create([
                'bidang_id' => $attributes['bidang_id'],
            ])->id,
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }
}
