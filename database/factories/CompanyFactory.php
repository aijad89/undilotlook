<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * Every column, nullable ones included — see UserFactory for why.
     *
     * `user_id` creates its own account because the column is NOT NULL and
     * unique: one user owns exactly one company, so a factory that shared an
     * account between two companies would hit the index rather than the rule.
     *
     * `state` is the office ZONE this company is administered under — the string
     * a state admin is scoped by, from AdminZones. It is not an address; there is
     * no address here at all.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'state' => 'Johor',
            'entrepreneur_category' => 'Micro, Small & Medium (MSM)',
            'kraf_code' => null,
            'plot_size' => null,
            'is_active' => true,
        ];
    }

    /** A body that is not a craft entrepreneur — the draw must not place it. */
    public function externalEntity(): static
    {
        return $this->state(fn () => [
            'entrepreneur_category' => 'Government Agency (*Registration under external agency name)',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
