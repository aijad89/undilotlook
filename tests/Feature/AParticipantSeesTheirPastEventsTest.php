<?php

use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Plot;
use App\Models\Poll;

/**
 * Past events: the events a company took part in that have ended, as a table,
 * latest first, with the lot it had. They leave the dashboard, which keeps
 * what is still to come.
 */
function endedEvent(string $title, int $daysAgo, array $attributes = []): Event
{
    return Event::factory()->create([
        'title' => $title,
        'start_date' => now()->subDays($daysAgo + 2),
        'end_date' => now()->subDays($daysAgo),
    ] + $attributes);
}

it('lists the ended events the company was in, latest first, with the lot and its slip', function () {
    $company = Company::factory()->create();
    $mine = Company::factory()->create(); // another company's past is not shown

    $older = endedEvent('Pesta Lama', 400);
    $recent = endedEvent('Karnival Semalam', 3);
    $cancelled = endedEvent('Yang Dibatalkan', 30, ['polling_status' => 'cancelled']);
    $upcoming = Event::factory()->create(['title' => 'Ekspo Akan Datang']);
    $theirs = endedEvent('Bukan Kami', 10);

    $plot = Plot::factory()->create(['plot_code' => 'B-TTK2']);
    EventRegistration::factory()->create(['event_id' => $recent->id, 'company_id' => $company->id, 'plot_id' => $plot->id, 'plot_size' => '3x6']);
    Poll::create(['user_id' => $company->user_id, 'event_id' => $recent->id, 'code' => null]);
    // Assigned by the office, not confirmed: the lot, but no slip.
    EventRegistration::factory()->create(['event_id' => $older->id, 'company_id' => $company->id, 'plot_id' => Plot::factory()->create(['plot_code' => 'A-OFIS'])->id]);
    $noLot = endedEvent('Tanpa Lot', 800);
    EventRegistration::factory()->create(['event_id' => $noLot->id, 'company_id' => $company->id]);
    EventRegistration::factory()->create(['event_id' => $cancelled->id, 'company_id' => $company->id]);
    EventRegistration::factory()->create(['event_id' => $upcoming->id, 'company_id' => $company->id]);
    EventRegistration::factory()->create(['event_id' => $theirs->id, 'company_id' => $mine->id]);

    $this->actingAs($company->user, 'web')->get(route('past-events'))
        ->assertOk()
        ->assertSeeInOrder(['<th scope="col">Event</th>', '<th scope="col">Lot</th>'], false)
        ->assertSeeInOrder(['Karnival Semalam', '3x6', 'B-TTK2', route('zvote.slip', $recent), 'Pesta Lama', 'A-OFIS', 'Tanpa Lot', 'No lot'], false)
        ->assertDontSee('Yang Dibatalkan')
        ->assertDontSee('Ekspo Akan Datang')
        ->assertDontSee('Bukan Kami')
        ->assertSee('Your company on record');

    // No slip for a lot the participant did not confirm.
    expect(substr_count($this->get(route('past-events'))->getContent(), '/lot/slip'))->toBe(1);

    // The dashboard keeps what is still to come.
    $this->get(route('dashboard'))
        ->assertSee('Ekspo Akan Datang')
        ->assertDontSee('Karnival Semalam')
        ->assertDontSee('Pesta Lama');
});

it('says so when there is no past event yet, and is for a signed-in participant only', function () {
    $this->get(route('past-events'))->assertRedirect(route('login'));

    $company = Company::factory()->create();
    $this->actingAs($company->user, 'web')->get(route('past-events'))
        ->assertSee('No past events yet')
        ->assertDontSee('<table', false);

    $this->get(route('dashboard'))->assertSee('href="'.route('past-events').'"', false);
});

it('carries what the company answered for each past event, as it was then, not as it is now', function () {
    $company = Company::factory()->create(['credentials' => [
        'recognition' => 'tokoh', 'classification' => 'no', 'contact_name' => 'Ahmad Baharu', 'contact_phone' => '019-111 2222', 'product_images' => [],
    ]]);
    $asked = endedEvent('Karnival Dulu', 20);
    $added = endedEvent('Ditambah Pejabat', 40);
    EventRegistration::factory()->create(['event_id' => $asked->id, 'company_id' => $company->id]);
    EventRegistration::factory()->create(['event_id' => $added->id, 'company_id' => $company->id]);
    \App\Models\EventJoinRequest::factory()->create(['event_id' => $asked->id, 'company_id' => $company->id, 'status' => 'approved', 'answers' => [
        'recognition' => 'adiguru', 'classification' => 'yes',
        'classification_cert' => ['number' => 'PKT-OLD', 'start' => '2025-01-01', 'end' => '2026-01-01'],
        'quality' => 'batik', 'quality_cert' => ['number' => 'QB-9', 'start' => '2025-01-01', 'end' => '2026-06-30'],
        'contact_name' => 'Siti Lama', 'contact_phone' => '012-000 1111',
        'product_images' => array_fill(0, 5, ['path' => 'x', 'original' => 'x.jpg']),
    ]]);

    $this->actingAs($company->user, 'web')->get(route('past-events'))
        ->assertSeeInOrder(['Contact person', 'Recognition', 'Certificates', 'Photos'])
        // Then: the event's own copy.
        ->assertSeeInOrder(['Karnival Dulu', 'Siti Lama', '012-000 1111', 'Adiguru Kraf', 'Classification: PKT-OLD, until 01 Jan 2026', 'Quality: QB-9, until 30 Jun 2026', '5'])
        // An event the office added: nothing was answered.
        ->assertSeeInOrder(['Ditambah Pejabat', 'Added by the office, no answers sent'])
        // Now: the record card, from the company's current answers.
        ->assertSeeInOrder(['Your company on record', 'Ahmad Baharu', 'Tokoh Kraf Negara']);
});
