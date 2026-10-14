<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    /**
     * EVERY COLUMN IS SET, INCLUDING THE NULLABLE ONES, and that is not tidiness.
     *
     * `Model::preventAccessingMissingAttributes()` is on outside production, and
     * `actingAs($user)` hands the view the factory's OWN instance rather than one
     * retrieved from the database. Any column the factory does not name is simply
     * absent from that instance — so a view that reads it throws
     * MissingAttributeException under `actingAs` while working perfectly after a
     * real form login. The failure then looks like a broken view.
     *
     * In the system this came from that cost 25 red tests before the cause was
     * found. Naming every column here is what prevents it; `null` is a value.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= bcrypt('password'),
            'phone' => fake()->numerify('01#-#######'),
            // Dashed, as the Companies screen stores it.
            'ic_number' => fake()->unique()->numerify('######-##-####'),
            'is_active' => true,
            'last_seen_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /** An account an admin has switched off. EnforceUserIsActive logs these out. */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /** No IC on file, so User::icHash() returns null and there is no QR code. */
    public function withoutIc(): static
    {
        return $this->state(fn () => ['ic_number' => null]);
    }
}
