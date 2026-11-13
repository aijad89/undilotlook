<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\BidangAllocation;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\EventRegistration;
use App\Models\KrafBidang;
use App\Models\Plot;
use App\Models\PlotSize;
use App\Services\LotAllocator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * DEMO DATA for the manual test sheet (docs/testing/undilot-test-manual.xlsx)
 * — never on the live server: DemoSeeder calls it, below DatabaseSeeder's
 * isProduction() guard.
 *
 * One event per state the sheet's preconditions ask for, titled "Uji: …" and
 * naming its test cases in the description, so a tester finds the right one
 * from the event list. Plans are built the way the plan form builds them
 * (PlotSize rows, BidangAllocation per code, plots coded per Plot::codeFor),
 * and lots are drawn through LotAllocator, the same door Confirm uses.
 *
 * Re-runnable: an event that already exists is left as it is.
 */
class DemoScenarioSeeder extends Seeder
{
    private Admin $hq;

    private Admin $johorAdmin;

    /** @var array<string, Company> keyed by short name */
    private array $co = [];

    public function run(): void
    {
        $this->hq = Admin::where('email', 'hq@demo.test')->firstOrFail();
        $this->johorAdmin = Admin::where('email', 'admin.johor@demo.test')->firstOrFail();
        $this->companies();

        $this->noPlanYet();
        $this->planReady();
        $this->planWithPreset();
        $this->drawOpen();
        $this->drawOpenNoLot();
        $this->drawClosed();
        $this->drawClosedShort();
        $this->drawCompleted();
        $this->drawList();
        $this->maxTwo();
        $this->unpublished();
        $this->autoClosed();
        $this->draftExpoRows();
        $this->registrationOver();
        $this->autoOpen();
        $this->toDelete();
        $this->fairExtras();
    }

    /* ── Companies ────────────────────────────────────────────────────────── */

    private function companies(): void
    {
        $johor = Company::whereHas('user', fn ($q) => $q->where('email', 'user.johor@demo.test'))->firstOrFail();
        $this->co = [
            'johor' => $johor,
            'selangor' => $this->demoCompany('user.selangor-kuala-lumpur-dan-putrajaya@demo.test'),
            'kedah' => $this->demoCompany('user.kedah@demo.test'),
            'n9' => $this->demoCompany('user.negeri-sembilan@demo.test'),
        ];

        // Johor companies at TBPS 3x3: the draw cases need several in one zone.
        foreach (['johor2' => 'Batik Tulis Muar', 'johor3' => 'Batik Kukus Batu Pahat', 'johor4' => 'Batik Pelangi Kluang', 'johor5' => 'Batik Moden Segamat'] as $key => $name) {
            $this->co[$key] = $this->company("user.{$key}@demo.test", $name, 'Johor', 'TBPS');
        }

        // One company per job from here down, so a case that edits one never
        // reaches into another case's draw list (a booth change carries to
        // every draft list the company is on).
        $one = fn (string $key, string $email, string $name, ?string $code = 'TBPS') => $this->co[$key] = $this->company($email, $name, 'Johor', $code);

        $one('baru', 'user.johor-baru@demo.test', 'Kraf Baru Pontian', null);          // TC-089 → 090, 111, 096, 156
        $one('kosong', 'user.johor-kosong@demo.test', 'Kraf Kosong Kota Tinggi', null); // TC-041–044
        $one('sunyi', 'user.johor-sunyi@demo.test', 'Kraf Sunyi Kulai', null);          // TC-152: never touched
        $one('ganti', 'user.johor-ganti@demo.test', 'Kraf Ganti Mersing');              // TC-037, TC-047
        $one('padam', 'user.johor-padam@demo.test', 'Kraf Padam Pengerang');            // TC-040
        $one('saiz', 'user.johor-saiz@demo.test', 'Kraf Saiz Desaru');                  // TC-045/046
        $one('saiz2', 'user.johor-saiz2@demo.test', 'Kraf Saiz Kulai');                 // TC-099
        $one('saiz3', 'user.johor-saiz3@demo.test', 'Kraf Saiz Tangkak');               // TC-100

        // Uji: Senarai Cabutan, one company per row kind.
        $one('verified1', 'user.verified1@demo.test', 'Tenun Senai');                   // TC-104 → 106 → 154, 158
        $one('verified2', 'user.verified2@demo.test', 'Ukiran Pasir Gudang');           // TC-098 → 097
        $one('waiting1', 'user.waiting1@demo.test', 'Anyaman Benut');                   // TC-091 → 093
        $one('waiting2', 'user.waiting2@demo.test', 'Tembikar Yong Peng');              // TC-105
        $one('waiting3', 'user.waiting3@demo.test', 'Songket Labis');                   // TC-153
        $one('returned', 'user.returned@demo.test', 'Manik Rengit');                    // TC-094 → 095 → 097
        $one('pending1', 'user.pending1@demo.test', 'Rotan Simpang Renggam');           // TC-086/087, 160 → 101
        $one('pending2', 'user.pending2@demo.test', 'Batik Air Hitam');                 // TC-102 → 157

        // A name a spreadsheet reads as a formula: TC-162; verified, TC-107.
        $this->co['formula'] = $this->company('user.formula@demo.test', '=SUM(1+1) Kraf', 'Johor', 'TBPS');
    }

    private function demoCompany(string $email): Company
    {
        return Company::whereHas('user', fn ($q) => $q->where('email', $email))->firstOrFail();
    }

    private function company(string $email, string $name, string $zone, ?string $code): Company
    {
        $user = \App\Models\User::firstOrCreate(['email' => $email], [
            'name' => "Owner {$name}",
            'password' => \Illuminate\Support\Str::random(40),
            'phone' => '01'.random_int(10000000, 99999999),
            'ic_number' => null,
            'community_id' => null,
            'is_active' => true,
        ]);

        $company = Company::firstOrCreate(['user_id' => $user->id], [
            'name' => $name,
            'state' => $zone,
            'entrepreneur_category' => null,
            'is_active' => true,
        ]);
        $company->kraf_code = $code; // not fillable: admin only
        $company->plot_size = '3x3';
        $company->save();

        return $company;
    }

    /* ── Scenarios ────────────────────────────────────────────────────────── */

    /** TC-114–119, TC-122/123: verified Johor TBPS rows, no plan yet. */
    private function noPlanYet(): void
    {
        $e = $this->event('Uji: Cabutan Belum Ada Pelan', 'TC-114 to TC-119, TC-122, TC-123: build the lot plan here.', [
            'polling_status' => 'draft',
        ]);
        if (! $e) {
            return;
        }
        foreach (['johor', 'johor2', 'johor3', 'selangor', 'kedah'] as $k) {
            $this->row($e, $k);
        }
    }

    /** TC-120/121, 139, 144, 145, 147/148, 150: a plan, all verified, no lot held. */
    private function planReady(): void
    {
        $e = $this->event('Uji: Pelan Sedia', 'TC-120, TC-121, TC-139, TC-144, TC-145, TC-148, TC-150: plan built, every row verified, no lot held.', [
            'polling_status' => 'draft',
            'polling_start_date' => now()->addWeek()->setTime(9, 0),
            'polling_deadline' => now()->addWeeks(2)->setTime(17, 0),
        ]);
        if (! $e) {
            return;
        }
        foreach (['johor2', 'johor3', 'selangor', 'n9'] as $k) {
            $this->row($e, $k);
        }
        $this->plan($e, 'Dewan A', ['3x3' => 6, '3x6' => 2], ['TBPS' => ['3x3', 3], 'TTK' => ['3x3', 2], 'RKH' => ['3x6', 1]]);
    }

    /** TC-131–133, TC-137: draft draw with an unconfirmed preset for a Johor company. */
    private function planWithPreset(): void
    {
        $e = $this->event('Uji: Pelan dengan Simpanan', 'TC-131, TC-132, TC-133, TC-137: draft draw; Batik Tulis Muar has a plot set aside.', [
            'polling_status' => 'draft',
            'polling_start_date' => now()->addWeek()->setTime(9, 0),
            'polling_deadline' => now()->addWeeks(2)->setTime(17, 0),
        ]);
        if (! $e) {
            return;
        }
        foreach (['johor2', 'johor3', 'johor4', 'selangor'] as $k) {
            $this->row($e, $k);
        }
        $this->plan($e, 'Dewan B', ['3x3' => 7], ['TBPS' => ['3x3', 4], 'TTK' => ['3x3', 2]]);
        $this->preset($e, 'johor2');
    }

    /** TC-108/109, 125–130, 134, 135, 140, 146, 149, 151, TC-038: draw Open, some lots held. */
    private function drawOpen(): void
    {
        $e = $this->event('Uji: Cabutan Dibuka', 'TC-125 to TC-130, TC-134, TC-135, TC-140, TC-146, TC-151: draw Open. Batik Warisan Johor (user.johor) has not drawn; Batik Tulis Muar and Seramik Klang have; Batik Kukus Batu Pahat has a plot set aside.', [
            'polling_status' => 'draft',
            'polling_start_date' => now()->subDay()->setTime(9, 0),
            'polling_deadline' => now()->addDays(5)->setTime(17, 0),
        ]);
        if (! $e) {
            return;
        }
        foreach (['johor', 'johor2', 'johor3', 'johor4', 'selangor', 'kedah'] as $k) {
            $this->row($e, $k);
        }
        $this->plan($e, 'Dewan C', ['3x3' => 10], ['TBPS' => ['3x3', 6], 'TTK' => ['3x3', 2], 'RMH' => ['3x3', 1]]);
        $this->preset($e, 'johor3');
        $e->update(['polling_status' => 'polling']);
        $this->draw($e, 'johor2');
        $this->draw($e, 'selangor');
    }

    /** TC-124 (K05): draw Open, nobody has drawn. */
    private function drawOpenNoLot(): void
    {
        $e = $this->event('Uji: Cabutan Dibuka Tanpa Lot', 'TC-124: draw Open, no lot held yet.', [
            'polling_status' => 'draft',
            'polling_start_date' => now()->subDay()->setTime(9, 0),
            'polling_deadline' => now()->addDays(5)->setTime(17, 0),
        ]);
        if (! $e) {
            return;
        }
        foreach (['johor4', 'johor5', 'selangor'] as $k) {
            $this->row($e, $k);
        }
        $this->plan($e, 'Dewan D', ['3x3' => 4], ['TBPS' => ['3x3', 2], 'TTK' => ['3x3', 1]]);
        $e->update(['polling_status' => 'polling']);
    }

    /** TC-142, TC-143, TC-136 (K04): Closed after the deadline, two unseated, plots to spare. */
    private function drawClosed(): void
    {
        $e = $this->event('Uji: Cabutan Ditutup', 'TC-136, TC-142, TC-143: draw Closed after its deadline; two companies have no lot yet and there are plots to spare.', [
            'polling_status' => 'draft',
            'polling_start_date' => now()->subWeek()->setTime(9, 0),
            'polling_deadline' => now()->subDay()->setTime(17, 0),
        ]);
        if (! $e) {
            return;
        }
        foreach (['johor2', 'johor3', 'johor4', 'selangor'] as $k) {
            $this->row($e, $k);
        }
        $this->plan($e, 'Dewan E', ['3x3' => 6], ['TBPS' => ['3x3', 4], 'TTK' => ['3x3', 1]]);
        $e->update(['polling_status' => 'polling']);
        $this->draw($e, 'johor2');
        $this->draw($e, 'selangor');
        $e->update(['polling_status' => 'closed']);
    }

    /** TC-141: Closed, fewer free plots of the size than unseated companies. */
    private function drawClosedShort(): void
    {
        $e = $this->event('Uji: Cabutan Ditutup Plot Kurang', 'TC-141: draw Closed; three companies without a lot but only one free 3x3 plot.', [
            'polling_status' => 'draft',
            'polling_start_date' => now()->subWeek()->setTime(9, 0),
            'polling_deadline' => now()->subDay()->setTime(17, 0),
        ]);
        if (! $e) {
            return;
        }
        foreach (['johor2', 'johor3', 'johor4', 'johor5'] as $k) {
            $this->row($e, $k);
        }
        $this->plan($e, 'Dewan F', ['3x3' => 2], ['TBPS' => ['3x3', 2]]);
        $e->update(['polling_status' => 'polling']);
        $this->draw($e, 'johor2');
        $e->update(['polling_status' => 'closed']);
    }

    /** TC-136, TC-138: a completed draw on an event still to come. */
    private function drawCompleted(): void
    {
        $e = $this->event('Uji: Cabutan Selesai', 'TC-136, TC-138: draw completed, every company holds a lot.', [
            'polling_status' => 'draft',
            'polling_start_date' => now()->subWeeks(2)->setTime(9, 0),
            'polling_deadline' => now()->subWeek()->setTime(17, 0),
        ]);
        if (! $e) {
            return;
        }
        foreach (['johor2', 'johor3', 'selangor'] as $k) {
            $this->row($e, $k);
        }
        $this->plan($e, 'Dewan G', ['3x3' => 4], ['TBPS' => ['3x3', 2], 'TTK' => ['3x3', 1]]);
        $e->update(['polling_status' => 'polling']);
        foreach (['johor2', 'johor3', 'selangor'] as $k) {
            $this->draw($e, $k);
        }
        $e->update(['polling_status' => 'completed']);
    }

    /**
     * TC-087, TC-090–107, TC-110, TC-153, TC-160, TC-162: every kind of row the
     * two-step check knows.
     */
    private function drawList(): void
    {
        $e = $this->event('Uji: Senarai Cabutan', 'TC-090 to TC-107, TC-110, TC-153, TC-160: verified, waiting-for-HQ and returned rows; two pending Johor requests (approve one, reject one); one rejected by Kedah this week.', [
            'polling_status' => 'draft',
        ]);
        if (! $e) {
            return;
        }

        // Verified, company told.
        foreach (['verified1', 'verified2', 'formula', 'saiz2', 'saiz3'] as $k) {
            $this->row($e, $k);
        }
        // Added by the zone, waiting for HQ.
        $this->row($e, 'waiting1', verified: false);
        $this->row($e, 'waiting2', verified: false);
        $this->row($e, 'selangor', verified: false);
        // Asked to join, zone approved, HQ not yet: TC-153.
        $this->request($e, 'waiting3', 'approved', $this->johorAdmin);
        $this->row($e, 'waiting3', verified: false);
        // Returned by HQ with a note.
        $this->row($e, 'returned', verified: false, hqNote: 'Resit bayaran tidak jelas. Sila muat naik semula.');
        // Pending requests in Johor, with a receipt.
        $this->request($e, 'pending1', 'pending');
        $this->request($e, 'pending2', 'pending');
        $this->request($e, 'kedah', 'rejected', Admin::where('email', 'admin.kedah@demo.test')->first(), 'Kod kraf tidak sepadan dengan produk.');
    }

    /** TC-065: Maximum participants 2, already 2 on the list. */
    private function maxTwo(): void
    {
        $e = $this->event('Uji: Had Peserta 2', 'TC-065: maximum participants 2, two companies already on the list.', [
            'polling_status' => 'draft',
            'max_participants' => 2,
        ]);
        if (! $e) {
            return;
        }
        $this->row($e, 'johor2');
        $this->row($e, 'johor3');
    }

    /** TC-068, TC-071: Unpublished. */
    private function unpublished(): void
    {
        $this->event('Uji: Tidak Diterbitkan', 'TC-068, TC-071: status Unpublished.', [
            'status' => 'closed',
            'polling_status' => 'draft',
        ]);
    }

    /** TC-088: unpublished by auto-close, with a request sent before close. */
    private function autoClosed(): void
    {
        $e = $this->event('Uji: Tutup Automatik', 'TC-088: registration closed yesterday and auto-close unpublished it; a Johor request sent before the close is still pending.', [
            'status' => 'closed',
            'auto_close' => true,
            'registration_open_at' => now()->subWeeks(3),
            'registration_close_at' => now()->subDay(),
            'polling_status' => 'draft',
        ]);
        if ($e) {
            $this->request($e, 'johor5', 'pending', at: now()->subDays(3));
        }
    }

    /** TC-161: the Draft event carries a draw list in several zones; TC-147: and a plan. */
    private function draftExpoRows(): void
    {
        $e = Event::where('title', 'Demo Draft Expo')->first();
        if ($e && ! $e->registrations()->exists()) {
            foreach (['johor2', 'selangor', 'kedah'] as $k) {
                $this->row($e, $k);
            }
            $e->update(['polling_start_date' => now()->addMonths(3), 'polling_deadline' => now()->addMonths(3)->addWeek()]);
            $this->plan($e, 'Dewan Draf', ['3x3' => 4], ['TBPS' => ['3x3', 1], 'TTK' => ['3x3', 1], 'RMH' => ['3x3', 1]]);
        }
    }

    /** TC-084: registration closed; Kedah's request was rejected and cannot be sent again. */
    private function registrationOver(): void
    {
        $e = $this->event('Uji: Pendaftaran Tamat', 'TC-084: registration closed yesterday; Anyaman Mengkuang Kedah was not approved.', [
            'registration_open_at' => now()->subMonth(),
            'registration_close_at' => now()->subDay(),
            'polling_status' => 'draft',
        ]);
        if ($e) {
            // Ten days back, so it does not join TC-103's "last 7 days".
            $this->request($e, 'kedah', 'rejected', Admin::where('email', 'admin.kedah@demo.test')->first(), 'Dokumen tidak lengkap.');
            $e->joinRequests()->update(['reviewed_at' => now()->subDays(10)]);
        }
    }

    /** TC-150: auto-open is on and its start has passed, but one row waits for HQ. */
    private function autoOpen(): void
    {
        $e = $this->event('Uji: Buka Automatik', 'TC-150: Auto-Open on, start time passed; Batik Moden Segamat waits for HQ, so the draw stays Draft.', [
            'polling_status' => 'draft',
            'auto_polling_open' => true,
            'polling_start_date' => now()->subHour(),
            'polling_deadline' => now()->addWeek()->setTime(17, 0),
        ]);
        if (! $e) {
            return;
        }
        $this->row($e, 'johor2');
        $this->row($e, 'johor5', verified: false);
        $this->plan($e, 'Dewan H', ['3x3' => 3], ['TBPS' => ['3x3', 2]]);
    }

    /** TC-066: an event with a draw list, a plan and lots, made to be deleted. */
    private function toDelete(): void
    {
        $e = $this->event('Uji: Untuk Dipadam', 'TC-066: delete this event; it has a draw list, a plan and lots.', [
            'polling_status' => 'draft',
            'polling_start_date' => now()->subWeek(),
            'polling_deadline' => now()->subDay(),
        ]);
        if (! $e) {
            return;
        }
        $this->row($e, 'johor2');
        $this->row($e, 'johor3');
        $this->plan($e, 'Dewan I', ['3x3' => 2], ['TBPS' => ['3x3', 2]]);
        $e->update(['polling_status' => 'polling']);
        $this->draw($e, 'johor2');
        $this->draw($e, 'johor3');
        $e->update(['polling_status' => 'completed']);
    }

    /**
     * Companies on two draft draw lists, verified, for the booth-size cases:
     * TC-040 (padam), TC-045/046 (saiz: Fair + Perak), TC-099/100 (saiz2/3:
     * Fair + Senarai Cabutan).
     */
    private function fairExtras(): void
    {
        $fair = Event::where('title', 'Demo Kraf Fair 2027')->first();
        $perak = Event::where('title', 'Festival Seni Kraf Perak 2027')->first();
        if (! $fair || $fair->registrations()->where('company_id', $this->co['padam']->id)->exists()) {
            return;
        }
        foreach (['padam', 'saiz', 'saiz2', 'saiz3'] as $k) {
            $this->row($fair, $k);
        }
        $this->row($perak, 'saiz');
    }

    /* ── Builders ─────────────────────────────────────────────────────────── */

    /** A published event two months out, registration open; null if it exists already. */
    private function event(string $title, string $description, array $attributes): ?Event
    {
        $event = Event::firstOrCreate(['title' => $title], $attributes + [
            'description' => $description,
            'location' => 'Dewan Ujian, Johor Bahru',
            'state' => 'Johor',
            'start_date' => now()->addMonths(2)->startOfDay()->setTime(10, 0),
            'end_date' => now()->addMonths(2)->addDays(3)->startOfDay()->setTime(22, 0),
            'registration_open_at' => now()->subWeek(),
            'registration_close_at' => now()->addMonth(),
            'status' => 'open',
            'max_participants' => 100,
        ]);

        return $event->wasRecentlyCreated ? $event : null;
    }

    private function row(Event $event, string $key, bool $verified = true, ?string $hqNote = null): EventRegistration
    {
        $company = $this->co[$key];

        return EventRegistration::create([
            'event_id' => $event->id,
            'company_id' => $company->id,
            'user_id' => $company->user_id,
            'code' => $company->kraf_code,
            'code_updated_at' => now(),
            'plot_size' => $company->plot_size ?? '3x3',
            'verified_by' => $verified ? $this->hq->id : null,
            'verified_at' => $verified ? now() : null,
            'first_verified_at' => $verified ? now() : null,
            'hq_note' => $hqNote,
            'payment_note' => $verified ? null : 'Resit 0042, tunai, '.now()->subDays(2)->format('d/m/Y'),
        ]);
    }

    private function request(Event $event, string $key, string $status, ?Admin $reviewer = null, ?string $reason = null, $at = null): void
    {
        $company = $this->co[$key];
        $path = "join-requests/{$event->id}/resit-{$company->id}.jpg";
        Storage::disk('local')->put($path, Storage::disk('local')->get('products/demo/1.jpg') ?? 'receipt');

        $request = EventJoinRequest::create([
            'event_id' => $event->id,
            'company_id' => $company->id,
            'user_id' => $company->user_id,
            'payment_method' => 'transfer',
            'waiver_category' => null,
            'documents' => [['name' => 'Payment receipt', 'path' => $path, 'original' => 'resit.jpg']],
            'answers' => [
                'recognition' => 'none',
                'classification' => 'no',
                'classification_cert' => null,
                'quality' => null,
                'quality_cert' => null,
                'contact_name' => $company->user->name,
                'contact_phone' => $company->user->phone,
                'product_images' => array_map(fn ($i) => ['path' => "products/demo/{$i}.jpg", 'original' => "produk{$i}.jpg"], range(1, 5)),
            ],
            'status' => $status,
            'reject_reason' => $reason,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => $reviewer ? now()->subDays(2) : null,
        ]);

        if ($at) {
            $request->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
        }
    }

    /**
     * A plan as the form builds it.
     *
     * @param  array<string, int>  $sizes  size => plots at this location
     * @param  array<string, array{string, int}>  $codes  craft code => [size, plots]
     */
    private function plan(Event $event, string $location, array $sizes, array $codes): void
    {
        $rows = [];
        foreach ($sizes as $size => $quantity) {
            $rows[$size] = PlotSize::create(['event_id' => $event->id, 'location' => $location, 'size' => $size, 'quantity' => $quantity]);
        }
        $event->forceFill(['total_plots' => array_sum($sizes)])->save();

        $claimed = [];
        foreach ($codes as $code => [$size, $count]) {
            $bidang = KrafBidang::codes()->where('code', $code)->firstOrFail();
            BidangAllocation::create(['event_id' => $event->id, 'bidang_id' => $bidang->id, 'plot_size_id' => $rows[$size]->id, 'plots_count' => $count]);
            for ($n = 1; $n <= $count; $n++) {
                $this->plot($event, $rows[$size], $bidang->id, Plot::codeFor($location, $code, $n));
            }
            $claimed[$size] = ($claimed[$size] ?? 0) + $count;
        }

        // What no code claims is the open pool at that size.
        foreach ($rows as $size => $row) {
            for ($n = 1; $n <= $row->quantity - ($claimed[$size] ?? 0); $n++) {
                $this->plot($event, $row, null, Plot::codeFor($location, $size, $n));
            }
        }
    }

    private function plot(Event $event, PlotSize $size, ?int $bidangId, string $code): void
    {
        Plot::create([
            'event_id' => $event->id,
            'plot_size_id' => $size->id,
            'bidang_id' => $bidangId,
            'plot_code' => $code,
            'is_allocated' => false,
            'allocation_type' => 'algorithm',
        ]);
    }

    /** As PollingController::presetAssign() does it. */
    private function preset(Event $event, string $key): void
    {
        $registration = $event->registrations()->where('company_id', $this->co[$key]->id)->firstOrFail();
        $plot = $event->plots()->where('is_allocated', false)
            ->whereHas('bidang', fn ($q) => $q->where('code', $registration->code))->firstOrFail();

        $plot->update([
            'company_id' => $registration->company_id,
            'allocated_to' => $registration->user_id,
            'is_allocated' => true,
            'allocation_type' => 'preset',
            'is_locked' => true,
            'allocated_at' => now(),
        ]);
        $registration->update(['plot_id' => $plot->id]);
    }

    /** A lot drawn through the same door as the participant's Confirm. */
    private function draw(Event $event, string $key): void
    {
        $registration = $event->registrations()->where('company_id', $this->co[$key]->id)->firstOrFail();
        (new LotAllocator)->assign($registration);
    }
}
