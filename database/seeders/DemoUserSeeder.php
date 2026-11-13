<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyKraf;
use App\Models\KrafSubBidang;
use App\Models\User;
use App\Support\AdminZones;
use App\Support\EntrepreneurCategories;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DEMO: one participant per zone (14), each owning one company in that zone,
 * with a craft code and one craft selection (its first classification and
 * product ticked) when DemoHierarchySeeder has run first.
 *
 * Emails are `user.<zone>@demo.test`, random passwords — sign in via /auto.
 * Re-runnable: matched on email.
 */
class DemoUserSeeder extends Seeder
{
    /** [company name, craft code], one per zone in AdminZones order. */
    private const COMPANIES = [
        ['Batik Warisan Johor', 'TBPS'],
        ['Tenun Melaka Enterprise', 'TTN'],
        ['Ukiran Kayu Seri Menanti', 'RKH'],
        ['Seramik Klang Studio', 'TTK'],
        ['Labu Sayong Perak', 'TTK'],
        ['Songket Pulau Pinang', 'TST'],
        ['Anyaman Mengkuang Kedah', 'RMH'],
        ['Mutiara Langkawi Craft', 'AMH'],
        ['Tekat Perlis Indah', 'TTH'],
        ['Perak Tembaga Kelantan', 'LEH'],
        ['Rotan Pahang Jaya', 'RBH'],
        ['Batik Lukis Terengganu', 'TBPS'],
        ['Manik Sabah Heritage', 'AMH'],
        ['Pua Kumbu Sarawak', 'TTN'],
    ];

    public function run(): void
    {
        $subs = KrafSubBidang::with(['pengelasan', 'produks'])->orderBy('id')->get();

        foreach (AdminZones::DEFAULTS as $i => $zone) {
            [$name, $code] = self::COMPANIES[$i];

            $user = User::firstOrCreate(['email' => 'user.'.Str::slug($zone).'@demo.test'], [
                'name' => "Owner {$name}",
                'password' => Str::random(40),
                'phone' => '01'.random_int(10000000, 99999999),
                'ic_number' => null,
                'community_id' => sprintf('KOM-%04d', $i + 1),
                'is_active' => true,
            ]);

            $company = Company::firstOrCreate(['user_id' => $user->id], [
                'name' => $name,
                'state' => $zone,
                'entrepreneur_category' => EntrepreneurCategories::DEFAULTS[1],
                'is_active' => true,
            ]);
            $company->kraf_code = $code; // not fillable: admin only
            $company->plot_size = $code === 'RKH' ? '3x6' : '3x3';
            $company->save();

            if ($subs->isNotEmpty() && ! $company->krafSelections()->exists()) {
                $sub = $subs[$i % $subs->count()];
                $selection = CompanyKraf::create([
                    'company_id' => $company->id,
                    'bidang_id' => $sub->bidang_id,
                    'sub_bidang_id' => $sub->id,
                    'is_primary' => true,
                ]);
                $selection->pengelasan()->attach($sub->pengelasan->take(1)->pluck('id'));
                $selection->produks()->attach($sub->produks->take(1)->pluck('id'));
            }
        }
    }
}
