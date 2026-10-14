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
        ->assertSeeInOrder(['Sooner Fair', 'Later Fair', 'Telah berlangsung', 'Old Fair'])
        ->assertDontSee('Draft Fair')
        ->assertDontSeeText('98765');
});

it('shows the footer pages and social links that are switched on', function () {
    FooterMenu::create(['title' => 'Hubungi Kami', 'content' => '<p>Tel 03-1234</p>', 'sort_order' => 0, 'is_active' => true]);
    FooterMenu::create(['title' => 'Hidden Page', 'content' => '<p>x</p>', 'sort_order' => 1, 'is_active' => false]);
    SocialLink::create(['label' => 'Facebook', 'url' => 'https://facebook.com/kraftangan', 'icon_type' => 'bootstrap', 'icon_class' => 'bi-facebook', 'sort_order' => 0, 'is_active' => true]);

    $this->get(route('home'))
        ->assertSee('Hubungi Kami')
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
