<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Event;
use App\Models\EventJoinRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventJoinRequest>
 */
class EventJoinRequestFactory extends Factory
{
    protected $model = EventJoinRequest::class;

    /** Every column, nullable ones included. A pending request with no files. */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'company_id' => Company::factory(),
            'user_id' => fn (array $row) => Company::find($row['company_id'])->user_id,
            'payment_method' => 'transfer',
            'waiver_category' => null,
            'documents' => [],
            'status' => 'pending',
            'reject_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }
}
