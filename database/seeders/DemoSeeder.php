<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\KrafBidang;
use App\Models\KrafProduk;
use App\Models\KrafSubBidang;
use Illuminate\Database\Seeder;

/**
 * DEMO DATA — never on the live server (DatabaseSeeder calls it below its
 * isProduction() guard). Plant it on purpose with `db:seed --class=DemoSeeder`.
 *
 * Plants, in order: a sample craft hierarchy, 14 state admins + 1 HQ, 14
 * participants with a company each, and two events — one with its draw list
 * filled from those companies; then DemoEventsSeeder's open, coming and past
 * events; then DemoScenarioSeeder's "Uji: …" events, one per state the manual
 * test sheet needs. Re-runnable: everything is matched by name or email first.
 */
class DemoSeeder extends Seeder
{
    /** category => [sub-category => [[classifications], [products]]] */
    private const HIERARCHY = [
        'TEKSTIL' => [
            'Batik' => [['Batik Canting', 'Batik Blok', 'Batik Skrin'], ['Kain Pasang', 'Baju Kurung', 'Selendang']],
            'Tenun' => [['Tenun Pahang', 'Pua Kumbu'], ['Kain Sampin', 'Alas Meja']],
            'Songket' => [['Songket Tradisional'], ['Kain Songket', 'Tengkolok']],
        ],
        'HASIL RIMBA' => [
            'Ukiran Kayu' => [['Ukiran Timbul', 'Ukiran Tebuk'], ['Panel Dinding', 'Perabot']],
            'Anyaman' => [['Mengkuang', 'Rotan', 'Buluh'], ['Tikar', 'Bakul', 'Tudung Saji']],
        ],
        'HASIL TANAH' => [
            'Seramik' => [['Labu Sayong', 'Tembikar'], ['Pasu', 'Labu Air']],
        ],
        'HASIL LOGAM' => [
            'Tembaga & Perak' => [['Ketukan', 'Larikan'], ['Dulang', 'Kerongsang']],
        ],
        'ANEKA KRAF' => [
            'Manik' => [['Manik Kaca', 'Manik Mutiara'], ['Rantai', 'Gelang']],
        ],
    ];

    public function run(): void
    {
        $this->hierarchy();
        $this->call([DemoAdminSeeder::class, DemoUserSeeder::class]);
        $this->events();
        $this->call(DemoEventsSeeder::class);
        $this->call(DemoScenarioSeeder::class);
    }

    private function hierarchy(): void
    {
        foreach (self::HIERARCHY as $categoryName => $subs) {
            $category = KrafBidang::categories()->where('name', $categoryName)->first();
            if (! $category) {
                continue; // KrafCodeSeeder plants the five categories
            }

            foreach ($subs as $subName => [$classifications, $products]) {
                $sub = KrafSubBidang::firstOrCreate(['bidang_id' => $category->id, 'name' => $subName]);

                foreach ($classifications as $name) {
                    $sub->pengelasan()->firstOrCreate(['name' => $name]);
                }
                foreach ($products as $name) {
                    KrafProduk::firstOrCreate(
                        ['sub_bidang_id' => $sub->id, 'name' => $name],
                        ['bidang_id' => $category->id],
                    );
                }
            }
        }
    }

    private function events(): void
    {
        $fair = Event::firstOrCreate(['title' => 'Demo Kraf Fair 2027'], [
            'description' => 'Demo event with a filled draw list.',
            'location' => 'MATIC, Kuala Lumpur',
            'state' => 'Selangor, Kuala Lumpur dan Putrajaya',
            'start_date' => now()->addMonths(2)->setTime(10, 0),
            'end_date' => now()->addMonths(2)->addDays(4)->setTime(22, 0),
            'registration_open_at' => now()->subWeek(),
            'registration_close_at' => now()->addMonth(),
            'status' => 'open',
            'polling_status' => 'draft',
            'polling_start_date' => now()->addWeeks(3)->setTime(9, 0),
            'polling_deadline' => now()->addWeeks(4)->setTime(17, 0),
            'max_participants' => 100,
        ]);

        // Filled once, when the event is new: a re-run would otherwise add every
        // company planted since, such as DemoScenarioSeeder's Johor extras.
        foreach ($fair->wasRecentlyCreated ? Company::whereNotNull('kraf_code')->get() : [] as $company) {
            EventRegistration::firstOrCreate(
                ['event_id' => $fair->id, 'company_id' => $company->id],
                ['user_id' => $company->user_id, 'code' => $company->kraf_code, 'plot_size' => $company->plot_size ?? '3x3', 'verified_at' => now(), 'first_verified_at' => now()],
            );
        }

        Event::firstOrCreate(['title' => 'Demo Draft Expo'], [
            'description' => 'Demo draft event — hidden from state admins.',
            'location' => 'Dewan Demo, Ipoh',
            'state' => 'Perak',
            'start_date' => now()->addMonths(4)->setTime(10, 0),
            'end_date' => now()->addMonths(4)->addDays(2)->setTime(22, 0),
            'registration_open_at' => now()->addMonth(),
            'registration_close_at' => now()->addMonths(3),
            'status' => 'draft',
            'polling_status' => 'draft',
            'max_participants' => 50,
        ]);
    }
}
