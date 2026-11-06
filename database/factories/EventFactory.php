<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Every column, nullable ones included — see UserFactory for why.
     *
     * The default is a PUBLISHED event whose draw has not started: `status` open,
     * `polling_status` draft. That is the state most screens are about, and it is
     * also the one that lets EventVisibility's two rules be tested apart — a
     * draft event and an out-of-state event fail for different reasons.
     */
    public function definition(): array
    {
        $start = now()->addDays(7);

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'location' => fake()->city(),
            'state' => 'Johor',

            'start_date' => $start,
            'end_date' => $start->clone()->addDays(2),
            'registration_open_at' => now()->subDays(10),
            'registration_close_at' => now()->addDays(3),
            'auto_open' => false,
            'auto_close' => false,

            'status' => 'open',
            'polling_status' => 'draft',
            'polling_start_date' => null,
            'polling_deadline' => null,
            'auto_polling_open' => false,
            'auto_polling_close' => false,

            // Null, not a number: the plan computes it, and a factory that made
            // one up would be stating a plan that does not exist.
            'total_plots' => null,

            'use_preset' => false,
            'max_participants' => 100,
            'banner' => null,
            'colour' => null,
        ];
    }

    /** Not opened yet — super and hq only, whoever is registered. */
    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    /** The draw is open and accepting pulls. */
    public function polling(): static
    {
        return $this->state(fn () => [
            'polling_status' => 'polling',
            'polling_start_date' => now()->subHour(),
            'polling_deadline' => now()->addDay(),
        ]);
    }
}
