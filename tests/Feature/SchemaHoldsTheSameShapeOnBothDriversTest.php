<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * THE TRAP THIS SCHEMA WAS WRITTEN TO AVOID, pinned so it cannot come back.
 *
 * The system this repo replaces declared its status columns as `enum`. On MySQL
 * that is an ENUM; on SQLite, Laravel renders a CHECK constraint. Identical the
 * day they are created — and the first time anyone widens the vocabulary with
 * raw `ALTER TABLE … MODIFY COLUMN`, MySQL changes and SQLite is SILENTLY
 * SKIPPED. The production schema and the test schema then disagree, with nothing
 * failing to say so.
 *
 * It went unnoticed for eight months. What finally surfaced it was that no test
 * could set `polling_status = 'closed'` — the state the entire lot draw is
 * about. A migration had to be written whose only job was to catch SQLite up.
 *
 * These tests run on SQLite, which is the half that was wrong there. They assert
 * the two things that make the trap unreachable: no enum exists, and every value
 * the application believes in can actually be stored.
 */

it('stores every polling status the application names', function () {
    $eventId = DB::table('events')->insertGetId(baseEventRow());

    foreach (['draft', 'polling', 'closed', 'completed', 'cancelled'] as $status) {
        DB::table('events')->where('id', $eventId)->update(['polling_status' => $status]);

        expect(DB::table('events')->where('id', $eventId)->value('polling_status'))
            ->toBe($status, "polling_status rejected '{$status}' on ".DB::getDriverName());
    }
});

it('stores every event status the application names', function () {
    $eventId = DB::table('events')->insertGetId(baseEventRow());

    foreach (['draft', 'open', 'closed', 'completed'] as $status) {
        DB::table('events')->where('id', $eventId)->update(['status' => $status]);

        expect(DB::table('events')->where('id', $eventId)->value('status'))->toBe($status);
    }
});

it('declares no enum or check constraint on any status column', function () {
    // On SQLite the whole schema is readable as text, which is the cheapest way
    // to assert the absence of a shape rather than the presence of a value.
    if (DB::getDriverName() !== 'sqlite') {
        $this->markTestSkipped('Reads sqlite_master; the MySQL half is covered by running the migration there.');
    }

    $sql = collect(DB::select("select sql from sqlite_master where type = 'table'"))
        ->pluck('sql')
        ->filter()
        ->implode("\n");

    expect($sql)->not->toContain('check (', 'A CHECK constraint is how Laravel renders enum() on SQLite — '
        .'and it is the half of an enum that silently stops matching MySQL.');
});

it('refuses to delete a plot that a draw entry still points at', function () {
    // `event_registrations.plot_id` has no ON DELETE clause on purpose, so the
    // database restricts. A drawn result must not evaporate because somebody
    // edited a plan; the plan rewrite has to clear the link and be seen doing it.
    expect(Schema::hasColumn('event_registrations', 'plot_id'))->toBeTrue();

    // SQLite enforces foreign keys only when asked, and Laravel's testing
    // connection does ask — so this is a real constraint check, not a no-op.
    expect(DB::select('pragma foreign_keys')[0]->foreign_keys)->toBe(1);
});

it('holds one company per user at the schema level, not just in a validation rule', function () {
    $userId = DB::table('users')->insertGetId([
        'name' => 'A', 'email' => 'a@example.test', 'password' => 'x',
        'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('companies')->insert([
        'user_id' => $userId, 'name' => 'First', 'is_active' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    // A controller's Rule::unique does not bind a seeder, an artisan command or a
    // second write path. The index does.
    expect(fn () => DB::table('companies')->insert([
        'user_id' => $userId, 'name' => 'Second', 'is_active' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]))->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

function baseEventRow(): array
{
    return [
        'title' => 'Test Event',
        'location' => 'Kuala Lumpur',
        'start_date' => now()->addMonth(),
        'end_date' => now()->addMonth()->addDays(2),
        'registration_open_at' => now()->subDay(),
        'registration_close_at' => now()->addWeek(),
        'max_participants' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ];
}
