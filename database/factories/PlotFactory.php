<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\KrafBidang;
use App\Models\Plot;
use App\Models\PlotSize;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plot>
 */
class PlotFactory extends Factory
{
    protected $model = Plot::class;

    /**
     * Every column, nullable ones included — see UserFactory for why.
     *
     * The plan row is made on the SAME event, never beside it: a plot whose size
     * row belongs to another event is a shape the plan screen cannot produce.
     * Uncoded by default, which puts it in the open pool.
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'plot_size_id' => fn (array $attributes) => PlotSize::factory()->create([
                'event_id' => $attributes['event_id'],
            ])->id,
            'company_id' => null,
            'allocated_to' => null,
            'bidang_id' => null,
            'sub_bidang_id' => null,
            'plot_code' => fn () => 'LOC-PLOT'.fake()->unique()->numberBetween(1, 99999),
            'is_allocated' => false,
            'allocation_type' => 'algorithm',
            'is_locked' => false,
            'allocated_at' => null,
        ];
    }

    /** Set aside for one craft code from the coding sheet. */
    public function coded(KrafBidang $code): static
    {
        return $this->state(fn () => ['bidang_id' => $code->id]);
    }

    /** On an existing plan row, and therefore on that row's event. */
    public function on(PlotSize $size): static
    {
        return $this->state(fn () => [
            'event_id' => $size->event_id,
            'plot_size_id' => $size->id,
        ]);
    }
}
