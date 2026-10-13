<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    protected static ?string $password = null;

    /**
     * Every column, nullable ones included — see UserFactory for why.
     *
     * The default is a STATE admin, which is the scoped role. A test that wants
     * the unscoped behaviour has to say so, because the opposite default hides
     * scoping bugs: everything passes when every test actor can see everything.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= bcrypt('password'),
            'role' => 'admin',
            'state' => 'Johor',
            'last_seen_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function super(): static
    {
        return $this->state(fn () => ['role' => Admin::SUPER_ROLE, 'state' => null]);
    }

    public function hq(): static
    {
        return $this->state(fn () => ['role' => 'hq', 'state' => null]);
    }
}
