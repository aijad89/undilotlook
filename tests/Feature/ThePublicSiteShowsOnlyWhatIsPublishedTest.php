<?php

use App\Models\Event;
use App\Models\FooterMenu;
use App\Models\SocialLink;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The public site: published events, the footer pages and social links the
 * office manages, and nothing a participant should not see.
 */
it('lists published events for anyone, upcoming first, and never a draft or a lot count', function () {
    Event::factory()->create(['title' => 'Draft Fair', 'status' => 'draft']);
    Event::factory()->create(['title' => 'Later Fair', 'status' => 'open', 'start_date' => now()->addMonths(2), 'end_date' => now()->addMonths(2)->addDay()]);
    Event::factory()->create(['title' => 'Sooner Fair', 'status' => 'open', 'start_date' => now()->addMonth(), 'end_date' => now()->addMonth()->addDay(), 'total_plots' => 98765]);
    Event::factory()->create(['title' => 'Old Fair', 'status' => 'completed', 'start_date' => now()->subMonth(), 'end_date' => now()->subMonth()->addDay()]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Sooner Fair', 'Later Fair', 'Past events', 'Old Fair'])
        ->assertDontSee('Draft Fair')
        ->assertDontSeeText('98765');
});

it('opens a published event on its own page, and a draft is a 404 there too', function () {
    $open = Event::factory()->create(['title' => 'Open Fair', 'description' => 'Woodcarving and batik.', 'status' => 'open', 'total_plots' => 98765]);
    $draft = Event::factory()->create(['title' => 'Draft Fair', 'status' => 'draft']);

    $this->get(route('home'))->assertSee(route('events.show', $open));

    $this->get(route('events.show', $open))
        ->assertOk()
        ->assertSee('Open Fair')
        ->assertSee('Woodcarving and batik.')
        ->assertDontSeeText('98765');

    $this->get(route('events.show', $draft))->assertNotFound();
});

it('shows the footer pages and social links that are switched on', function () {
    FooterMenu::create(['title' => 'Contact Us', 'content' => '<p>Tel 03-1234</p>', 'sort_order' => 0, 'is_active' => true]);
    FooterMenu::create(['title' => 'Hidden Page', 'content' => '<p>x</p>', 'sort_order' => 1, 'is_active' => false]);
    SocialLink::create(['label' => 'Facebook', 'url' => 'https://facebook.com/kraftangan', 'icon_type' => 'bootstrap', 'icon_class' => 'bi-facebook', 'sort_order' => 0, 'is_active' => true]);

    $this->get(route('home'))
        ->assertSee('Contact Us')
        ->assertDontSee('Hidden Page')
        ->assertSee('https://facebook.com/kraftangan');
});

it('cleans a footer page of anything but plain formatting', function () {
    $this->actingAs(superAdmin(), 'admin')
        ->post(route('admin.footer-menus.store'), [
            'title' => 'Terma', 'content' => '<p onclick="steal()">Hello<script>alert(1)</script></p>', 'is_active' => 1,
        ])
        ->assertRedirect(route('admin.footer-menus.index'));

    expect(FooterMenu::sole()->content)->toBe('<p>Hello</p>');
});

it('refuses an SVG icon, which could carry script on this origin', function () {
    Storage::fake('public');

    $this->actingAs(superAdmin(), 'admin')
        ->post(route('admin.social-links.store'), [
            'label' => 'X', 'url' => 'https://example.test', 'icon_type' => 'image',
            'icon_image' => UploadedFile::fake()->createWithContent('icon.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'is_active' => 1,
        ])
        ->assertSessionHasErrors('icon_image');

    expect(SocialLink::count())->toBe(0);
});

it('keeps the public site screens to super and hq', function () {
    $this->actingAs(stateAdmin('Johor'), 'admin')->get(route('admin.footer-menus.index'))->assertForbidden();
    $this->actingAs(stateAdmin('Johor'), 'admin')->get(route('admin.social-links.index'))->assertForbidden();

    $this->actingAs(superAdmin(), 'admin')->get(route('admin.footer-menus.create'))->assertOk();
    $this->actingAs(superAdmin(), 'admin')->get(route('admin.social-links.create'))->assertOk();
});

it('gives each public event one stage, the event dates winning over the draw', function () {
    $make = fn (array $a) => Event::factory()->create(array_merge(['status' => 'open', 'start_date' => now()->addMonth(), 'end_date' => now()->addMonth()->addDay()], $a));

    expect($make(['polling_status' => 'polling', 'polling_deadline' => now()->addDay()])->publicStage())->toBe('open')
        ->and($make(['polling_status' => 'polling', 'polling_deadline' => now()->subMinute()])->publicStage())->toBe('closed')
        ->and($make(['polling_status' => 'completed'])->publicStage())->toBe('closed')
        ->and($make(['polling_status' => 'draft'])->publicStage())->toBe('register')
        ->and($make(['polling_status' => 'draft', 'registration_open_at' => now()->addDay(), 'registration_close_at' => now()->addDays(2)])->publicStage())->toBe('soon')
        ->and($make(['polling_status' => 'polling', 'polling_deadline' => now()->addDay(), 'start_date' => now()->subDay()])->publicStage())->toBe('now')
        ->and($make(['polling_status' => 'polling', 'polling_deadline' => now()->addDay(), 'start_date' => now()->subWeek(), 'end_date' => now()->subDay()])->publicStage())->toBe('ended');
});

it('shows past events six at a time, and More asks for six more', function () {
    foreach (range(1, 8) as $i) {
        Event::factory()->create(['title' => "Old Fair $i", 'status' => 'completed', 'start_date' => now()->subMonths($i)->subDay(), 'end_date' => now()->subMonths($i)]);
    }

    $this->get(route('home'))->assertSee('Old Fair 6')->assertDontSee('Old Fair 7')->assertSee('past=12', false);
    $this->get(route('home', ['past' => 12]))->assertSee('Old Fair 8')->assertDontSee('past=18', false);
});

it('gives a signed-in participant the portal bar on the public pages too, bell and all', function () {
    $event = Event::factory()->create(['status' => 'open']);
    $this->actingAs(\App\Models\User::factory()->create(), 'web');

    foreach ([route('home'), route('events.show', $event), route('dashboard')] as $url) {
        $this->get($url)->assertOk()
            ->assertSee(route('notifications.index'))
            ->assertSee('apTextSizeToggle', false);
    }
});
