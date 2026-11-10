<?php

use App\Models\Company;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\EventRegistration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * A participant asks to join with documents of their own choosing; the office
 * of their zone approves onto the draw list or rejects with a reason.
 */
beforeEach(fn () => Storage::fake('local'));

function joinable(array $attributes = []): Event
{
    return Event::factory()->create($attributes);
}

/** A valid join: how they paid, and the receipt. */
function payment(string $method = 'transfer'): array
{
    return ['payment_method' => $method, 'receipt' => UploadedFile::fake()->create('slip.pdf', 100, 'application/pdf')];
}

it('needs how they paid and a receipt, and keeps nothing else from a paid request', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);

    $this->actingAs($company->user, 'web')
        ->post(route('events.join', $event))
        ->assertSessionHasErrors(['payment_method', 'receipt'])
        ->assertSessionDoesntHaveErrors('documents');

    $this->actingAs($company->user, 'web')
        ->post(route('events.join', $event), ['payment_method' => 'bitcoin', 'receipt' => UploadedFile::fake()->create('virus.exe', 10)])
        ->assertSessionHasErrors(['payment_method', 'receipt']);

    // A waiver's supporting documents are checked like any file.
    $this->actingAs($company->user, 'web')
        ->post(route('events.join', $event), ['payment_method' => 'waiver', 'waiver_category' => 'award', 'documents' => [UploadedFile::fake()->create('virus.exe', 10)]])
        ->assertSessionHasErrors('documents.0');

    // Back on the event page: one message saying it was sent, not two.
    $this->actingAs($company->user, 'web')
        ->from(route('events.show', $event))
        ->followingRedirects()
        ->post(route('events.join', $event), payment('cheque') + ['documents' => [UploadedFile::fake()->image('booth.jpg')]])
        ->assertSee('The office is reviewing your documents')
        ->assertDontSee('Sent. The office will review');

    $request = EventJoinRequest::sole();
    expect($request->status)->toBe('pending')
        ->and($request->payment_method)->toBe('cheque')
        // Paid: the receipt only; a supporting document sent anyway is not kept.
        ->and(array_column($request->documents, 'name'))->toBe(['Payment receipt']);
    Storage::disk('local')->assertExists($request->documents[0]['path']);

    auth()->forgetGuards();
    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->get(route('admin.events.registrations.index', $event))
        ->assertSee('Cheque')
        ->assertSee('Payment receipt');
});

it('sends a request with the receipt alone', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);

    $this->actingAs($company->user, 'web')
        ->post(route('events.join', $event), payment('cash'))
        ->assertSessionHas('success');

    expect(EventJoinRequest::sole()->payment_method)->toBe('cash');
});

it('refuses a join request outside the registration window', function () {
    $event = joinable(['registration_open_at' => now()->addDay(), 'registration_close_at' => now()->addDays(3)]);
    $company = Company::factory()->create();

    $this->actingAs($company->user, 'web')
        ->post(route('events.join', $event), payment())
        ->assertSessionHas('error');

    expect(EventJoinRequest::count())->toBe(0);
});

it('lets the office of the zone approve onto the draw list, and no other zone', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor', 'kraf_code' => 'TBPS']);
    $request = EventJoinRequest::factory()->create(['event_id' => $event->id, 'company_id' => $company->id]);

    $this->actingAs(stateAdmin('Kedah'), 'admin')
        ->post(route('admin.events.registrations.approve', [$event, $request]), ['plot_size' => '3x3'])
        ->assertForbidden();

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->get(route('admin.events.registrations.index', $event))
        ->assertSee('Join requests (1)');

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->post(route('admin.events.registrations.approve', [$event, $request]), ['plot_size' => '3x3'])
        ->assertSessionHas('success');

    $row = EventRegistration::sole();
    expect($row->company_id)->toBe($company->id)
        ->and($row->code)->toBe('TBPS')
        ->and($request->fresh()->status)->toBe('approved');

    auth()->forgetGuards();
    $this->actingAs($company->user, 'web')->get(route('dashboard'))->assertSee($event->title);
});

it('shows a rejection reason to the participant, who may send again', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);
    $request = EventJoinRequest::factory()->create(['event_id' => $event->id, 'company_id' => $company->id]);

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->post(route('admin.events.registrations.reject', [$event, $request]), ['reject_reason' => 'SSM certificate has expired.'])
        ->assertSessionHas('success');

    auth()->forgetGuards();
    $this->actingAs($company->user, 'web')->get(route('events.show', $event))
        ->assertSee('SSM certificate has expired.')
        ->assertSee('Do you want to join this event?');

    $this->actingAs($company->user, 'web')
        ->post(route('events.join', $event), payment())
        ->assertSessionHas('success');

    expect($request->fresh()->status)->toBe('pending')->and($request->fresh()->reject_reason)->toBeNull();
});

it('serves a document to its own company and the zone office only', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);
    Storage::disk('local')->put('join-requests/x.pdf', 'pdf');
    $request = EventJoinRequest::factory()->create([
        'event_id' => $event->id, 'company_id' => $company->id,
        'documents' => [['name' => 'SSM certificate', 'path' => 'join-requests/x.pdf', 'original' => 'ssm.pdf']],
    ]);
    $url = route('join-requests.document', [$request, 0]);

    $this->actingAs(Company::factory()->create()->user, 'web')->get($url)->assertForbidden();
    auth()->forgetGuards();
    $this->actingAs(stateAdmin('Kedah'), 'admin')->get($url)->assertForbidden();
    auth()->forgetGuards();
    $this->actingAs(stateAdmin('Johor'), 'admin')->get($url)->assertOk();
    auth()->forgetGuards();
    $this->actingAs($company->user, 'web')->get($url)->assertOk();
});

it('settles a pending request when the office adds the company directly', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);
    $request = EventJoinRequest::factory()->create(['event_id' => $event->id, 'company_id' => $company->id]);

    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->post(route('admin.events.registrations.store', $event), ['company_id' => [$company->id]]);

    expect($request->fresh()->status)->toBe('approved');
});

it('says Register now on the home page only while registration is open', function () {
    joinable(['title' => 'Open Fair']);
    joinable(['title' => 'Later Fair', 'registration_open_at' => now()->addDay(), 'registration_close_at' => now()->addDays(2)]);

    $html = $this->get(route('home'))->assertOk()->getContent();

    expect(substr_count($html, 'Register now'))->toBeGreaterThan(0)
        ->and($html)->toContain('Coming soon');
    expect(App\Models\Event::firstWhere('title', 'Open Fair')->publicStage())->toBe('register')
        ->and(App\Models\Event::firstWhere('title', 'Later Fair')->publicStage())->toBe('soon');
});

it('puts the steps in one form, question first, and opens on payment after a failed send', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);
    $this->actingAs($company->user, 'web');

    $this->get(route('events.show', $event))
        ->assertSeeInOrder(['data-step="1"', 'Do you want to join this event?', 'data-step="2"', 'How did you pay?'], false)
        ->assertSee('data-start="1"', false);

    // A failed send comes back to the payment step, not to the question.
    $this->from(route('events.show', $event))->followingRedirects()
        ->post(route('events.join', $event))
        ->assertSee('data-start="2"', false);
});

it('takes a fee waiver instead of a receipt, with a certificate where the category needs one', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);
    $this->actingAs($company->user, 'web');

    // A waiver needs a category, and no receipt.
    $this->post(route('events.join', $event), ['payment_method' => 'waiver'])
        ->assertSessionHasErrors('waiver_category')
        ->assertSessionDoesntHaveErrors('receipt');

    // Every waiver needs a document that proves it, OKU and first-timer alike.
    foreach (['disabled', 'first_time'] as $category) {
        $this->post(route('events.join', $event), ['payment_method' => 'waiver', 'waiver_category' => $category])
            ->assertSessionHasErrors('documents');
    }

    $this->post(route('events.join', $event), [
        'payment_method' => 'waiver', 'waiver_category' => 'first_time',
        'documents' => [UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf')],
    ])->assertSessionHas('success');

    $request = EventJoinRequest::sole();
    expect($request->payment_method)->toBe('waiver')
        ->and($request->waiver_category)->toBe('first_time')
        ->and(array_column($request->documents, 'name'))->toBe(['letter.pdf']);

    auth()->forgetGuards();
    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->get(route('admin.events.registrations.index', $event))
        ->assertSee('Fee waiver')
        ->assertSee('First time joining a promotion with Kraftangan Malaysia');
});

it('takes the OKU certificate from Supporting documents, and drops a category sent with a payment', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);
    $this->actingAs($company->user, 'web');

    $this->post(route('events.join', $event), [
        'payment_method' => 'waiver', 'waiver_category' => 'disabled',
        'documents' => [UploadedFile::fake()->create('oku.pdf', 100, 'application/pdf')],
    ])->assertSessionHas('success');

    expect(array_column(EventJoinRequest::sole()->documents, 'name'))->toBe(['oku.pdf']);

    $other = joinable();
    $this->post(route('events.join', $other), payment('cash') + ['waiver_category' => 'award'])->assertSessionHas('success');
    expect(EventJoinRequest::where('event_id', $other->id)->sole()->waiver_category)->toBeNull();
});
