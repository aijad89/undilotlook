<?php

use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Plot;

/**
 * CSV exports: each one opens, a state admin's covers their zone only, and no
 * cell can turn into a formula when Excel opens the file.
 */
function exportRows($response): array
{
    $lines = array_filter(explode("\n", ltrim($response->streamedContent(), "\xEF\xBB\xBF")));

    return array_map('str_getcsv', array_values($lines));
}

it('exports every report, one header and one row per record', function () {
    $event = Event::factory()->create(['status' => 'open', 'title' => 'Craft Fair']);
    $plot = Plot::factory()->create(['event_id' => $event->id, 'plot_code' => 'A-RKP1', 'is_allocated' => true]);
    EventRegistration::factory()->coded('RKP')->create(['event_id' => $event->id, 'plot_id' => $plot->id]);
    $admin = superAdmin();

    $this->actingAs($admin, 'admin')->get(route('admin.reports.index'))->assertOk();

    foreach (['registrations', 'lot-draw', 'companies', 'event-summary'] as $report) {
        $rows = exportRows($this->actingAs($admin, 'admin')->get(route("admin.reports.{$report}"))->assertOk());
        expect($rows)->toHaveCount(2, "{$report} should be a header and one row");
    }

    $lot = exportRows($this->actingAs($admin, 'admin')->get(route('admin.reports.lot-draw')));
    expect($lot[1][1])->toBe('A-RKP1');
});

it('gives a state admin only their own zone', function () {
    $event = Event::factory()->create(['status' => 'open']);
    foreach (['Johor' => 'Kraf Johor', 'Kedah' => 'Kraf Kedah'] as $zone => $name) {
        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'company_id' => Company::factory()->create(['state' => $zone, 'name' => $name]),
        ]);
    }

    $admin = stateAdmin('Johor');

    foreach (['registrations', 'companies'] as $report) {
        $own = $this->actingAs($admin, 'admin')->get(route("admin.reports.{$report}"))->streamedContent();
        expect($own)->toContain('Kraf Johor')->not->toContain('Kraf Kedah');

        // Asking for another zone by URL narrows further; it never widens.
        $asked = exportRows($this->actingAs($admin, 'admin')->get(route("admin.reports.{$report}", ['state' => 'Kedah'])));
        expect($asked)->toHaveCount(1);
    }
});

it('neutralises a value Excel would run as a formula', function () {
    Company::factory()->create(['name' => '=HYPERLINK("http://evil.test","x")']);

    $rows = exportRows($this->actingAs(superAdmin(), 'admin')->get(route('admin.reports.companies')));

    expect($rows[1][array_search('Company', $rows[0])])->toStartWith("'=");
});
