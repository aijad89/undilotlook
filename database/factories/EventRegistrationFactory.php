<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventRegistration>
 */
class EventRegistrationFactory extends Factory
{
    protected $model = EventRegistration::class;

    /**
     * Every column, nullable ones included — see UserFactory for why.
     *
     * `user_id` is taken from the company rather than invented, because the two
     * answer different questions about the same place in the draw and a
     * registration whose account belongs to another company is a state the
     * screens cannot produce. Pass `user_id => null` for the case that matters:
     * a company in the draw before its account exists.
     *
     * There is no status to set. The row is the approval.
     */
    public function definition(): array
    {
        $company = Company::factory();

        return [
            'event_id' => Event::factory(),
            'company_id' => $company,
            'user_id' => fn (array $attributes) => Company::find($attributes['company_id'])?->user_id,

            'code' => null,
            'code_updated_at' => null,
            'plot_size' => '3x3',
            'plot_id' => null,

            'confirmed_participation' => false,
            'confirmed_at' => null,
            'reminded_at' => null,

            // Verified by default: most tests are about the draw, which only
            // opens once HQ has checked every row. unverified() for the rest.
            'verified_by' => null,
            'verified_at' => now(),
            'hq_note' => null,
            'payment_note' => null,
        ];
    }

    /** Carrying a craft code from the coding sheet, as the draw needs. */
    public function coded(string $code = 'TBPS'): static
    {
        return $this->state(fn () => [
            'code' => $code,
            'code_updated_at' => now(),
        ]);
    }

    /** Added by a zone admin, not yet checked by HQ. */
    public function unverified(): static
    {
        return $this->state(fn () => ['verified_at' => null]);
    }
}
