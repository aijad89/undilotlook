<?php

use App\Models\Admin;

/**
 * The admin sidebar: Dashboard, User, The Event, Announcements, Setting, Sign Out.
 * Each role sees only what it may open, a group opens by itself on its own pages,
 * and a screen reader is told which group is open and which page is current.
 */

/** The sidebar's markup alone, so a label elsewhere on the page cannot satisfy an assertion. */
function sidebarOf(Admin $admin, string $route = 'admin.dashboard', array $params = []): string
{
    $html = test()->actingAs($admin, 'admin')->get(route($route, $params))->assertOk()->getContent();
    preg_match('#<div id="sidebar">.*?</nav>#s', $html, $m);

    return $m[0] ?? '';
}

function sidebarLabels(string $sidebar): array
{
    preg_match_all('#<(?:a|button)\b[^>]*>(.*?)</(?:a|button)>#s', $sidebar, $m);

    return array_values(array_filter(array_map(fn ($l) => trim(strip_tags($l)), $m[1])));
}

it('gives a super admin every item, admins in two lists', function () {
    expect(sidebarLabels(sidebarOf(superAdmin())))->toBe([
        'Dashboard',
        'User', 'Companies', 'HQ Admins', 'State Admins',
        'The Event', 'Events', 'Lot Draw', 'Reports',
        'Announcements',
        'Setting', 'Dropdown Lists', 'Craft Category Code', 'Craft Hierarchy', 'Footer Pages', 'Social Links', 'Audit Trail',
        'Sign Out',
    ]);
});

it('gives an hq admin the state admins but not the hq list', function () {
    $labels = sidebarLabels(sidebarOf(Admin::factory()->hq()->create()));

    expect($labels)->toContain('State Admins', 'Setting', 'Audit Trail')
        ->not->toContain('HQ Admins');
});

it('gives a state admin no admin lists and no settings', function () {
    expect(sidebarLabels(sidebarOf(stateAdmin())))->toBe([
        'Dashboard',
        'User', 'Companies',
        'The Event', 'Events', 'Lot Draw', 'Reports',
        'Announcements',
        'Sign Out',
    ]);
});

it('opens the group that holds the current page and marks the page as current', function () {
    $sidebar = sidebarOf(superAdmin(), 'admin.polling.index');

    expect($sidebar)
        ->toMatch('#<button[^>]*aria-expanded="true"[^>]*>\s*<i[^>]*></i>\s*The Event#')
        ->toMatch('#<button[^>]*aria-expanded="false"[^>]*>\s*<i[^>]*></i>\s*User#')
        ->toMatch('#<a class="active" href="[^"]*/admin/polling"\s+aria-current="page"\s*>#');

    expect(substr_count($sidebar, 'aria-current="page"'))->toBe(1);
});

it('lights the right admin list up while editing an admin', function () {
    $state = stateAdmin();
    $sidebar = sidebarOf(superAdmin(), 'admin.admins.edit', [$state]);

    expect($sidebar)->toMatch('#<a class="active" href="[^"]*/admin/admins/state"\s+aria-current="page"\s*>#');
});
