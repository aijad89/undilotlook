<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The whole application schema, in one file.
 *
 * WHY ONE FILE. This repo starts with an empty database, so there is no history
 * to replay and nothing to be gained from spreading the schema over a series of
 * incremental files. The system this replaces had reached 156 migrations, about
 * seventy of which dropped something an earlier one had added; its net schema
 * was a fraction of its file count, and no reader could see the net shape
 * without running the whole chain.
 *
 * TWO RULES THIS FILE FOLLOWS, both learned the expensive way:
 *
 * 1. NO `enum` COLUMNS, anywhere. The vocabulary lives in model constants
 *    (`Event::STATUSES`, `Event::POLLING_STATUSES`) and the column is a plain
 *    string. An enum is identical on MySQL and SQLite the day it is created and
 *    stops being identical the first time anyone reaches for raw SQL to change
 *    it — `ALTER TABLE … MODIFY COLUMN` is silently skipped on SQLite, so the
 *    production schema and the test schema drift with nothing failing. In the
 *    old system that went unnoticed until no test could set
 *    `polling_status = 'closed'`, which is the state the lot draw is about.
 *    There is no enum here to drift.
 *
 * 2. THE UNIQUE INDEX ON `companies.user_id` IS CREATED HERE, not added later.
 *    One user owns exactly one company, and that is a schema promise rather than
 *    a controller's validation rule — a seeder, an artisan command or a second
 *    write path does not read `Rule::unique`. Adding it afterwards is the
 *    awkward version: MySQL drops the plain foreign-key index when a unique one
 *    lands on the same column, so the order of operations has to be managed by
 *    hand.
 *
 * DELETE RULES ARE DELIBERATE and were read off the live database of the system
 * this replaces, not off its migration files — those gave the wrong answer in
 * both directions. The ones worth knowing before changing anything:
 *
 *   companies.user_id          RESTRICT — a user cannot be deleted while they
 *                                         own a company. User::booted() turns
 *                                         that into a readable message.
 *   event_registrations.plot_id NO ACTION — deleting a plot does NOT clear the
 *                                         registration that points at it. That
 *                                         is a sharp edge, kept on purpose: the
 *                                         draw result must not evaporate
 *                                         silently because someone edited a plan.
 *   plots.allocated_to          SET NULL — the plot survives and stops naming
 *   plots.company_id            SET NULL   who drew it. A plot that loses its
 *                                         allocatee is not a destroyed plot, and
 *                                         the audit summaries keep the two apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        /* ── Administration ──────────────────────────────────────────────── */

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            // Three roles: admin (one state), hq, super. The vocabulary and the
            // rules about who may hand out which live in Admin::ROLES and
            // Admin::assignableRolesFor() — not in a column constraint, because
            // the answer depends on who is asking.
            $table->string('role')->default('admin');

            // The office zone, for a state admin. Null for hq and super, who see
            // everything. NOTE this is an administrative zone, not geography.
            $table->string('state')->nullable();

            $table->timestamp('last_seen_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        /* ── Key/value settings ──────────────────────────────────────────── */

        Schema::create('settings', function (Blueprint $table) {
            // Keyed by name, no surrogate id. Setting::get()/set() are the only
            // readers and writers, and App\Support\ManagedList stores every
            // editable dropdown list here as JSON.
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        /* ── Audit trail ─────────────────────────────────────────────────── */

        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();

            // Nullable morphs both sides: the performer may be an Admin, a User,
            // or nobody at all (a console command), and the subject may already
            // be gone by the time the row is read. No foreign keys in either
            // direction, on purpose — an audit row must outlive what it describes.
            $table->nullableMorphs('performer');
            $table->nullableMorphs('auditable');

            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();

            // Written once, never updated.
            $table->timestamp('created_at')->useCurrent();
        });

        /* ── Craft taxonomy ──────────────────────────────────────────────── */

        /**
         * `kraf_bidangs` HOLDS TWO LISTS IN ONE TABLE, and telling them apart is
         * the single most common mistake in this area:
         *
         *   `code` IS NULL     — a craft CATEGORY (TEKSTIL, HASIL RIMBA, …), the
         *                        top of the hierarchy a company picks from.
         *   `code` IS NOT NULL — a craft CODE (TBPS, RKP, …) from the printed
         *                        coding sheet, which a plot is categorised by and
         *                        a draw entry is matched against.
         *
         * They are two lists, two screens, two jobs, and they do not meet: a
         * company's code is its own field (`companies.kraf_code`).
         *
         * Every query against one of them must say which. A listing that forgets
         * shows both, and a delete that forgets removes a row from the list
         * nobody was looking at.
         */
        Schema::create('kraf_bidangs', function (Blueprint $table) {
            $table->id();

            // Display order from the printed coding sheet. NOT alphabetical:
            // sorting A-Z puts ANEKA KRAF above TEKSTIL, a shape nobody working
            // from that sheet recognises.
            $table->integer('bil')->nullable();

            $table->string('name');
            $table->string('pecahan_bidang')->nullable();
            $table->string('pecahan_sub_bidang')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_multiple_sub_bidang')->default(false);
            $table->timestamps();

            $table->index('code');
        });

        Schema::create('kraf_sub_bidangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bidang_id')->constrained('kraf_bidangs')->cascadeOnDelete();

            // The optional link to the coded half of `kraf_bidangs`. Optional is
            // the point: setting a company's craft category must never require a
            // code to exist first.

            $table->string('name');
            $table->timestamps();
        });

        Schema::create('kraf_pengelasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_bidang_id')->constrained('kraf_sub_bidangs')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('kraf_produks', function (Blueprint $table) {
            $table->id();
            // A product sits in a SUB-CATEGORY. `bidang_id` still names its
            // category, kept in step with the sub-category's. `sub_bidang_id` is
            // nullable only for products entered before it existed: those are
            // shown as "not placed yet" and cannot be ticked by a company.
            $table->foreignId('bidang_id')->constrained('kraf_bidangs')->cascadeOnDelete();
            $table->foreignId('sub_bidang_id')->nullable()->constrained('kraf_sub_bidangs')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /* ── Companies ───────────────────────────────────────────────────── */

        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            // RESTRICT, and unique. See the file docblock.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unique('user_id');

            $table->string('name');

            // The office zone this company is administered under — what a state
            // admin is scoped to. There is no address here and no premises table:
            // the draw needs to know which zone a company belongs to, not where
            // its workshop is.
            $table->string('state')->nullable();

            // Read by Company::isExternalEntity(), which is what lets a
            // government agency be given a plot outside any craft category.
            $table->string('entrepreneur_category')->nullable();

            // The company's usual craft code (TBP, RKP…), set by an admin and never
            // shown to the participant. A string like event_registrations.code, and
            // only a SUGGESTION: the draw list copies it in when the company is
            // added, and the code that counts stays the one on the draw list.
            $table->string('kraf_code')->nullable();

            // The company's usual booth size (3x3, 3x6…), the same kind of
            // suggestion: copied to event_registrations.plot_size when the
            // company is added to a draw list, where it can still be changed.
            $table->string('plot_size')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('state');
        });

        Schema::create('company_krafs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // `bidang_id` is the CATEGORY half of kraf_bidangs (`code` IS NULL) —
            // the same half `kraf_sub_bidangs.bidang_id` points at, which is what
            // makes the pair below consistent. A company's craft CODE is not part of
            // its selections — it is `companies.kraf_code`. Both are NOT NULL.
            $table->foreignId('bidang_id')->constrained('kraf_bidangs')->cascadeOnDelete();
            $table->foreignId('sub_bidang_id')->constrained('kraf_sub_bidangs')->cascadeOnDelete();

            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // The classifications a company ticked under one selection's sub-category.
        // Goes with its selection; a classification still ticked here cannot be
        // deleted — the database refuses, and Settings refuses first, by name.
        Schema::create('company_kraf_pengelasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_kraf_id')->constrained('company_krafs')->cascadeOnDelete();
            $table->foreignId('pengelasan_id')->constrained('kraf_pengelasan')->restrictOnDelete();
            $table->unique(['company_kraf_id', 'pengelasan_id']);
        });

        // The products a company ticked under one selection's sub-category, as
        // classifications are. Goes with its selection; a product still ticked
        // here cannot be deleted in Settings.
        Schema::create('company_kraf_produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_kraf_id')->constrained('company_krafs')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('kraf_produks')->restrictOnDelete();
            $table->unique(['company_kraf_id', 'produk_id']);
        });

        /* ── Events ──────────────────────────────────────────────────────── */

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location');
            $table->string('state')->nullable();

            $table->dateTime('start_date');
            $table->dateTime('end_date');

            // The window during which the event is "open". Nothing self-registers
            // here — an admin builds the draw list — but the window still drives
            // `events.status`, which the polling guard and the public listing read.
            $table->dateTime('registration_open_at');
            $table->dateTime('registration_close_at');
            $table->boolean('auto_open')->default(false);
            $table->boolean('auto_close')->default(false);

            // draft | open | closed | completed. Event::STATUSES is the list.
            $table->string('status', 20)->default('draft');

            // draft | polling | closed | completed | cancelled.
            // Event::POLLING_STATUSES is the list.
            $table->string('polling_status', 20)->default('draft');
            $table->timestamp('polling_start_date')->nullable();
            $table->timestamp('polling_deadline')->nullable();
            $table->boolean('auto_polling_open')->default(false);
            $table->boolean('auto_polling_close')->default(false);

            // Computed by PollingController::savePlan() from the plan, never
            // taken from the form.
            $table->integer('total_plots')->nullable();

            $table->boolean('use_preset')->default(false);
            $table->string('draw_animation', 20)->default('splitflap');
            $table->unsignedInteger('max_participants');

            $table->string('banner')->nullable();

            // Calendar tint only.
            $table->string('colour', 7)->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('polling_status');
        });

        /* ── The draw plan ───────────────────────────────────────────────── */

        /**
         * A `plot_sizes` row is a (LOCATION, SIZE) PAIR, and that pair is what a
         * plot carries.
         *
         * The plan form does not type the pair directly: one row on the form is a
         * LOCATION with a count, and each craft allocation nested under it names
         * its OWN size — the same location can hold 3x6 and 3x3 side by side.
         * PollingController::savePlan() resolves those back onto rows here,
         * matching on (location, size) so a surviving pair keeps its id.
         */
        Schema::create('plot_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('size');
            $table->integer('quantity');
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('bidang_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bidang_id')->constrained('kraf_bidangs')->cascadeOnDelete();
            $table->foreignId('sub_bidang_id')->nullable()->constrained('kraf_sub_bidangs')->cascadeOnDelete();
            $table->foreignId('plot_size_id')->constrained('plot_sizes')->cascadeOnDelete();
            $table->integer('plots_count')->default(0);
            $table->timestamps();
        });

        Schema::create('plots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plot_size_id')->constrained('plot_sizes')->cascadeOnDelete();

            // SET NULL, both. A plot that loses its allocatee is still a plot.
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('allocated_to')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('bidang_id')->nullable()->constrained('kraf_bidangs')->cascadeOnDelete();
            $table->foreignId('sub_bidang_id')->nullable()->constrained('kraf_sub_bidangs')->cascadeOnDelete();

            $table->string('plot_code');
            $table->boolean('is_allocated')->default(false);

            // algorithm | preset | manual
            $table->string('allocation_type')->default('algorithm');
            $table->boolean('is_locked')->default(false);
            $table->timestamp('allocated_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'is_allocated']);
        });

        /* ── Who is in the draw ──────────────────────────────────────────── */

        /**
         * THE EXISTENCE OF A ROW IS THE APPROVAL.
         *
         * There is no status flow here. An admin puts a company into an event's
         * draw list, gives it a craft code and a booth size, and that is what
         * being in the draw means. The system this replaces carried a seven-value
         * status column and the workflow around it; none of it is what the draw
         * reads.
         */
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Ownership is `company_id` — that column is NOT NULL and
            // foreign-keyed. `user_id` is kept because the draw itself writes and
            // reads it (plots.allocated_to, polls, preset allocations all key on
            // the account), but it is NOT the ownership predicate and no
            // user-facing path should ask it. Two keys, two questions.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            // The craft code from the coding sheet (TBPS, RKP, …), as a string.
            // It matches against the code on `kraf_bidangs` rows that plots are
            // categorised by. A string rather than a foreign key because that is
            // what the allocator compares, and changing it would mean rewriting
            // the allocator rather than carrying it across intact.
            $table->string('code')->nullable();
            $table->timestamp('code_updated_at')->nullable();

            // A string, defaulting to 3x3, NOT a reference to plot_sizes: the
            // office states a booth size before any plan exists, because the plan
            // is built from those numbers.
            $table->string('plot_size', 20)->nullable()->default('3x3');

            // NO `onDelete` CLAUSE ON PURPOSE — see the file docblock. Without
            // one the database restricts, so a plot cannot be deleted while a
            // draw entry still points at it. A plan rewrite must clear `plot_id`
            // first and be seen to do it, rather than having the result vanish
            // underneath.
            $table->foreignId('plot_id')->nullable()->constrained('plots');

            $table->boolean('confirmed_participation')->default(false);
            $table->timestamp('confirmed_at')->nullable();

            // Stamped when the close reminder is QUEUED, not when it is delivered.
            $table->timestamp('reminded_at')->nullable();

            $table->timestamps();

            // One entry per company per event: one draw per event, and a company
            // cannot be in the same draw twice.
            $table->unique(['event_id', 'company_id']);
        });

        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50)->nullable();
            $table->timestamps();

            // "Has this account drawn yet?" is asked here and nowhere else.
            $table->unique(['user_id', 'event_id']);
        });

        Schema::create('preset_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bidang_id')->constrained('kraf_bidangs')->cascadeOnDelete();
            $table->foreignId('sub_bidang_id')->nullable()->constrained('kraf_sub_bidangs')->cascadeOnDelete();
            $table->foreignId('plot_size_id')->constrained('plot_sizes')->cascadeOnDelete();
            $table->string('plot_number');
            $table->string('plot_size')->nullable();
            $table->string('plot_code')->nullable();
            $table->string('code', 50)->nullable();
            $table->integer('priority_rank');
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();
        });

        /* ── Notifications ───────────────────────────────────────────────── */

        /**
         * Laravel's own notifications table, written out here rather than
         * published, so the whole schema stays in one file.
         *
         * It is NOT optional even before any announcement screen exists:
         * AppServiceProvider's `notifications.unread-count` binding calls
         * `unreadNotifications()` for any signed-in user, so a missing table is a
         * 500 on every portal page rather than a feature that is merely absent.
         */
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        /* ── Announcements ───────────────────────────────────────────────── */

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');

            // news | alert | system | message
            $table->string('type', 20);

            // Not a foreign key, and not a join table: "all_users", "all_admins",
            // or "admin_<id>". Announcement::recipientsFor() reads it.
            $table->string('sent_to')->nullable();

            $table->string('banner')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });

        /* ── Public site content ─────────────────────────────────────────── */

        Schema::create('footer_menus', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // Rich text, sanitised through mews/purifier on the way in.
            $table->longText('content');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('url');
            // bootstrap | image
            $table->string('icon_type', 20)->default('bootstrap');
            $table->string('icon_class')->nullable();
            $table->string('icon_image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Reverse creation order, so a foreign key never outlives what it points at.
        foreach ([
            'social_links',
            'footer_menus',
            'announcements',
            'notifications',
            'preset_allocations',
            'polls',
            'event_registrations',
            'plots',
            'bidang_allocations',
            'plot_sizes',
            'events',
            'company_kraf_produk',
            'company_kraf_pengelasan',
            'company_krafs',
            'companies',
            'kraf_produks',
            'kraf_pengelasan',
            'kraf_sub_bidangs',
            'kraf_bidangs',
            'audit_trails',
            'settings',
            'admins',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
