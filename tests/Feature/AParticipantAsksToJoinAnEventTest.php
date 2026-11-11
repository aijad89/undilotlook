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

/** Steps 2 and 3 at their plainest: no recognition or certificate, a contact, five photos. */
function credentials(): array
{
    return ['recognition' => 'none', 'classification' => 'no', 'contact_name' => 'Siti Aminah', 'contact_phone' => '012-345 6789', 'product_images' => photos()];
}

/** A set of product photos. */
function photos(int $count = 5): array
{
    return array_map(fn ($i) => UploadedFile::fake()->image("product{$i}.jpg"), range(1, $count));
}

/** The names of a request's files, the product photos left out. */
function fileNames(array $documents): array
{
    return array_values(array_filter(array_column($documents, 'name'), fn ($name) => ! str_starts_with($name, 'Product photo')));
}

/** A valid join: declared, credentials, how they paid, and the receipt. */
function payment(string $method = 'transfer'): array
{
    return ['declaration' => 1, 'payment_method' => $method, 'receipt' => UploadedFile::fake()->create('slip.pdf', 100, 'application/pdf')] + credentials();
}

it('needs how they paid and a receipt, and keeps nothing else from a paid request', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);

    $this->actingAs($company->user, 'web')
        ->post(route('events.join', $event))
        ->assertSessionHasErrors(['declaration', 'payment_method', 'receipt'])
        ->assertSessionDoesntHaveErrors('documents');

    $this->actingAs($company->user, 'web')
        ->post(route('events.join', $event), ['payment_method' => 'bitcoin', 'receipt' => UploadedFile::fake()->create('virus.exe', 10)])
        ->assertSessionHasErrors(['payment_method', 'receipt']);

    // A waiver's supporting documents are checked like any file.
    $this->actingAs($company->user, 'web')
        ->post(route('events.join', $event), ['declaration' => 1, 'payment_method' => 'waiver', 'waiver_category' => 'award', 'documents' => [UploadedFile::fake()->create('virus.exe', 10)]])
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
        ->and(fileNames($request->documents))->toBe(['Payment receipt']);
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
        ->assertSee('I want to join this event, and I declare');

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

it('asks one question per step, in order, and opens on the first step that failed', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);
    $this->actingAs($company->user, 'web');

    $this->get(route('events.show', $event))
        ->assertSeeInOrder([
            'data-step="declaration"', 'I want to join this event, and I declare',
            'data-step="recognition"', 'Recognition received',
            'data-step="classification"', 'Classification Certificate?',
            'data-step="contact"', 'Contact person',
            'data-step="photos"', 'Product photos',
            'data-step="payment"', 'How did you pay?',
        ], false)
        ->assertSee('data-start="declaration"', false);

    $failsOn = fn (array $sent) => $this->from(route('events.show', $event))->followingRedirects()
        ->post(route('events.join', $event), $sent);

    $failsOn(['declaration' => 1])->assertSee('data-start="recognition"', false);
    $failsOn(['declaration' => 1, 'recognition' => 'none'])->assertSee('data-start="classification"', false);
    $failsOn(['declaration' => 1, 'recognition' => 'none', 'classification' => 'no'])->assertSee('data-start="contact"', false);
    $failsOn(['product_images' => photos(4), 'declaration' => 1] + credentials())->assertSee('data-start="photos"', false);
    $failsOn(['declaration' => 1] + credentials())->assertSee('data-start="payment"', false);

    // Not declared: back on the declaration, whatever else is wrong.
    $failsOn([])
        ->assertSee('data-start="declaration"', false)
        ->assertSee('Tick the declaration to join.');
});

it('refuses a request that is not declared, even with everything else in order', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);

    $this->actingAs($company->user, 'web')
        ->post(route('events.join', $event), ['declaration' => null] + payment())
        ->assertSessionHasErrors('declaration');

    expect(EventJoinRequest::count())->toBe(0);
});

it('takes a fee waiver instead of a receipt, with a certificate where the category needs one', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);
    $this->actingAs($company->user, 'web');

    // A waiver needs a category, and no receipt.
    $this->post(route('events.join', $event), ['declaration' => 1, 'payment_method' => 'waiver'])
        ->assertSessionHasErrors('waiver_category')
        ->assertSessionDoesntHaveErrors('receipt');

    // Every waiver needs a document that proves it, OKU and first-timer alike.
    foreach (['disabled', 'first_time'] as $category) {
        $this->post(route('events.join', $event), ['declaration' => 1, 'payment_method' => 'waiver', 'waiver_category' => $category])
            ->assertSessionHasErrors('documents');
    }

    $this->post(route('events.join', $event), [
        'declaration' => 1, 'payment_method' => 'waiver', 'waiver_category' => 'first_time',
        'documents' => [UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf')],
    ] + credentials())->assertSessionHas('success');

    $request = EventJoinRequest::sole();
    expect($request->payment_method)->toBe('waiver')
        ->and($request->waiver_category)->toBe('first_time')
        ->and(fileNames($request->documents))->toBe(['letter.pdf']);

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
        'declaration' => 1, 'payment_method' => 'waiver', 'waiver_category' => 'disabled',
        'documents' => [UploadedFile::fake()->create('oku.pdf', 100, 'application/pdf')],
    ] + credentials())->assertSessionHas('success');

    expect(fileNames(EventJoinRequest::sole()->documents))->toBe(['oku.pdf']);

    $other = joinable();
    $this->post(route('events.join', $other), payment('cash') + ['waiver_category' => 'award'])->assertSessionHas('success');
    expect(EventJoinRequest::where('event_id', $other->id)->sole()->waiver_category)->toBeNull();
});

/** A certificate as the form sends it. */
function certificate(string $field, string $number = 'PKT-001', ?string $end = null, bool $file = true): array
{
    return [
        "{$field}_number" => $number,
        "{$field}_start" => now()->subYear()->toDateString(),
        "{$field}_end" => $end ?? now()->addYear()->toDateString(),
    ] + ($file ? ["{$field}_file" => UploadedFile::fake()->create("{$field}.pdf", 100, 'application/pdf')] : []);
}

it('asks for recognition and the classification certificate, and refuses an expired one', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);
    $this->actingAs($company->user, 'web');

    $this->post(route('events.join', $event), ['recognition' => null, 'classification' => null] + payment())
        ->assertSessionHasErrors(['recognition', 'classification']);
    $this->post(route('events.join', $event), ['recognition' => 'both', 'classification' => 'maybe'] + payment())
        ->assertSessionHasErrors(['recognition', 'classification']);

    // Yes: number, dates and the file.
    $this->post(route('events.join', $event), ['classification' => 'yes'] + payment())
        ->assertSessionHasErrors(['classification_number', 'classification_start', 'classification_end', 'classification_file']);

    $this->post(route('events.join', $event), ['classification' => 'yes'] + certificate('classification', end: now()->subDay()->toDateString()) + payment())
        ->assertSessionHasErrors(['classification_end' => 'This certificate has expired. Renew it before joining.']);

    expect(EventJoinRequest::count())->toBe(0);

    $this->post(route('events.join', $event), ['recognition' => 'adiguru', 'classification' => 'yes'] + certificate('classification') + payment())
        ->assertSessionHas('success');

    $request = EventJoinRequest::sole();
    expect($request->answers['recognition'])->toBe('adiguru')
        ->and($request->answers['classification_cert']['number'])->toBe('PKT-001')
        ->and(fileNames($request->documents))->toBe(['Payment receipt', 'Classification certificate'])
        ->and($company->fresh()->credentials)->toBe($request->answers);

    auth()->forgetGuards();
    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->get(route('admin.events.registrations.index', $event))
        ->assertSee('Recognition: Adiguru Kraf')
        ->assertSee('PKT-001');
});

it('fills in what the company said last time, and keeps the certificate on file while it is the same one', function () {
    $company = Company::factory()->create(['state' => 'Johor']);
    $this->actingAs($company->user, 'web');

    $first = joinable();
    $this->post(route('events.join', $first), ['recognition' => 'tokoh', 'classification' => 'yes'] + certificate('classification') + payment())
        ->assertSessionHas('success');
    $path = $company->fresh()->credentials['classification_cert']['path'];

    $second = joinable();
    $this->get(route('events.show', $second))
        ->assertSee('Filled in from your last request')
        ->assertSee('value="PKT-001"', false)
        ->assertSee('On file: classification.pdf');

    // Renewed (a new end date): its new file is needed.
    $this->post(route('events.join', $second), ['recognition' => 'tokoh', 'classification' => 'yes'] + certificate('classification', end: now()->addYears(2)->toDateString(), file: false) + payment())
        ->assertSessionHasErrors('classification_file');

    // The same certificate: no file sent, the one on file is kept.
    $this->post(route('events.join', $second), ['recognition' => 'tokoh', 'classification' => 'yes'] + certificate('classification', file: false) + payment())
        ->assertSessionHas('success');

    $docs = EventJoinRequest::where('event_id', $second->id)->sole()->documents;
    expect($docs[1]['path'])->toBe($path);
    Storage::disk('local')->assertExists($path);
});

it('asks the quality question of Batik, Songket and Tenun only, offering their own standard', function () {
    App\Models\KrafBidang::factory()->code('TTN')->create(['pecahan_bidang' => 'TENUN']);
    App\Models\KrafBidang::factory()->code('TTH')->create(['pecahan_bidang' => 'TEKATAN']);
    $event = joinable();

    $tenun = Company::factory()->create(['state' => 'Johor', 'kraf_code' => 'TTN']);
    $this->actingAs($tenun->user, 'web');
    $this->get(route('events.show', $event))
        ->assertSee('Quality compliance certificate')
        ->assertSee('Songket / Tenunan Standard entrepreneur')
        ->assertDontSee('Batik Standard entrepreneur');

    $this->post(route('events.join', $event), payment())->assertSessionHasErrors('quality');
    $this->post(route('events.join', $event), ['quality' => 'batik'] + payment())->assertSessionHasErrors('quality');
    $this->post(route('events.join', $event), ['quality' => 'songket_tenun'] + payment())->assertSessionHasErrors('quality_number');
    $this->post(route('events.join', $event), ['quality' => 'songket_tenun'] + certificate('quality', 'SPK-9') + payment())
        ->assertSessionHas('success');
    expect(fileNames(EventJoinRequest::sole()->documents))->toBe(['Payment receipt', 'Quality compliance certificate']);

    // Tekatan has no standard: not asked.
    auth()->forgetGuards();
    $tekat = Company::factory()->create(['state' => 'Johor', 'kraf_code' => 'TTH']);
    $this->actingAs($tekat->user, 'web');
    $this->get(route('events.show', $event))->assertDontSee('Quality compliance certificate');
    $this->post(route('events.join', $event), payment())->assertSessionHas('success');
});

it('keeps the company certificate when a rejected request is sent again', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);
    $this->actingAs($company->user, 'web')
        ->post(route('events.join', $event), ['classification' => 'yes'] + certificate('classification') + payment());
    $path = $company->fresh()->credentials['classification_cert']['path'];
    EventJoinRequest::sole()->update(['status' => 'rejected']);

    $this->post(route('events.join', $event), ['classification' => 'yes'] + certificate('classification', file: false) + payment())
        ->assertSessionHas('success');

    Storage::disk('local')->assertExists($path);
});

it('asks for a contact person, a mobile number and exactly five product photos, kept for next time', function () {
    $company = Company::factory()->create(['state' => 'Johor']);
    $this->actingAs($company->user, 'web');
    $event = joinable();

    $this->post(route('events.join', $event), ['contact_name' => '', 'contact_phone' => '03-1234 5678', 'product_images' => null] + payment())
        ->assertSessionHasErrors(['contact_name', 'contact_phone', 'product_images']);
    $this->post(route('events.join', $event), ['product_images' => photos(4)] + payment())
        ->assertSessionHasErrors(['product_images' => 'Upload exactly 5 photos of your products.']);
    $this->post(route('events.join', $event), ['product_images' => [...photos(4), UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]] + payment())
        ->assertSessionHasErrors('product_images.4');

    $this->post(route('events.join', $event), payment())->assertSessionHas('success');
    $request = EventJoinRequest::sole();
    expect(array_slice(array_column($request->documents, 'name'), -5))->toBe(['Product photo 1', 'Product photo 2', 'Product photo 3', 'Product photo 4', 'Product photo 5'])
        ->and($request->answers['contact_phone'])->toBe('012-345 6789');

    // Next time: filled in, and the photos on file stand without a new set.
    $second = joinable();
    $this->get(route('events.show', $second))
        ->assertSee('value="Siti Aminah"', false)
        ->assertSee('5 photos on file');
    $this->post(route('events.join', $second), ['product_images' => null] + payment())->assertSessionHas('success');
    expect(EventJoinRequest::where('event_id', $second->id)->sole()->documents[1]['path'])->toBe($request->documents[1]['path']);

    auth()->forgetGuards();
    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->get(route('admin.events.registrations.index', $event))
        ->assertSee('Contact: Siti Aminah, 012-345 6789')
        ->assertSee('Product photo 5');
});

it('saves a draft with whatever is filled in, which the office does not see', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);
    $this->actingAs($company->user, 'web');

    // Nothing required, but what is given is still checked for form.
    $this->post(route('events.join', $event), ['draft' => 1, 'contact_phone' => '03-1234 5678'])
        ->assertSessionHasErrors('contact_phone');
    $this->post(route('events.join', $event), ['draft' => 1, 'product_images' => photos(4)])
        ->assertSessionHasErrors('product_images');

    // An expired certificate can wait in a draft; it is refused on Join.
    $expired = ['classification' => 'yes'] + certificate('classification', end: now()->subDay()->toDateString());
    $this->post(route('events.join', $event), ['draft' => 1, 'at' => 'classification', 'recognition' => 'tokoh'] + $expired)
        ->assertSessionHas('success', 'Draft saved. Come back to finish it any time before registration closes.')
        ->assertSessionHas('step', 'classification');

    expect(EventJoinRequest::sole()->status)->toBe('draft')
        ->and($company->fresh()->credentials['recognition'])->toBe('tokoh');

    // Right after: back on the step it was saved from, saying so once.
    $this->get(route('events.show', $event))
        ->assertSee('data-start="classification"', false)
        ->assertSee('Draft saved.')
        ->assertDontSee('You saved a draft on');
    // A later visit: filled in, and reminded it was not sent.
    $this->get(route('events.show', $event))
        ->assertSee('You saved a draft on')
        ->assertSee('value="PKT-001"', false);

    auth()->forgetGuards();
    $this->actingAs(stateAdmin('Johor'), 'admin')
        ->get(route('admin.events.registrations.index', $event))
        ->assertDontSee('Join requests (');
    $this->actingAs(stateAdmin('Johor'), 'admin')->get(route('admin.dashboard'))->assertDontSee('join request');

    auth()->forgetGuards();
    $this->actingAs($company->user, 'web')
        ->post(route('events.join', $event), ['recognition' => 'tokoh', 'classification' => 'yes'] + certificate('classification', end: now()->subDay()->toDateString(), file: false) + payment())
        ->assertSessionHasErrors('classification_end');
    expect(EventJoinRequest::sole()->status)->toBe('draft');
});

it('keeps a receipt saved with the draft until Join, and drops a replaced one', function () {
    $event = joinable();
    $company = Company::factory()->create(['state' => 'Johor']);
    $this->actingAs($company->user, 'web');

    $this->post(route('events.join', $event), ['draft' => 1, 'payment_method' => 'cash', 'receipt' => UploadedFile::fake()->create('first.pdf', 10, 'application/pdf')]);
    $first = EventJoinRequest::sole()->documents[0]['path'];

    $this->get(route('events.show', $event))
        ->assertSee('Saved with your draft: first.pdf')
        ->assertSee('value="cash" checked', false);

    // Replaced in a second draft: the first file goes.
    $this->post(route('events.join', $event), ['draft' => 1, 'payment_method' => 'cash', 'receipt' => UploadedFile::fake()->create('second.pdf', 10, 'application/pdf')]);
    $second = EventJoinRequest::sole()->documents[0]['path'];
    Storage::disk('local')->assertMissing($first);

    // Switching to a waiver: the receipt does not count as its documents.
    $this->post(route('events.join', $event), ['declaration' => 1, 'payment_method' => 'waiver', 'waiver_category' => 'award'] + credentials())
        ->assertSessionHasErrors('documents');

    // Nor in a draft: saved as a waiver, it holds no receipt, and the receipt goes.
    $this->post(route('events.join', $event), ['draft' => 1, 'payment_method' => 'waiver']);
    expect(EventJoinRequest::sole()->documents)->toBe([]);
    Storage::disk('local')->assertMissing($second);

    $this->post(route('events.join', $event), ['draft' => 1, 'payment_method' => 'cash', 'receipt' => UploadedFile::fake()->create('third.pdf', 10, 'application/pdf')]);
    $second = EventJoinRequest::sole()->documents[0]['path'];

    // Join with no new receipt: the draft's is sent.
    $sent = payment('cash');
    unset($sent['receipt']);
    $this->post(route('events.join', $event), $sent)->assertSessionHas('success', 'Sent. The office will review your documents.');

    $request = EventJoinRequest::sole();
    expect($request->status)->toBe('pending')
        ->and($request->documents[0]['path'])->toBe($second);
    Storage::disk('local')->assertExists($second);
});
