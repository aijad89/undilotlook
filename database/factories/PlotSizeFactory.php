<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\PlotSize;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlotSize>
 */
class PlotSizeFactory extends Factory
{
    protected $model = PlotSize::class;

    /** Every column, nullable ones included — see UserFactory for why. */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'size' => '3x3',
            'quantity' => 1,
            'location' => null,
        ];
    }
}
