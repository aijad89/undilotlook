<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * SENARAI KODING HKK 2025 — the coding table the office works from, in
 * "BY NEGERI - MICF.csv" rows 356-393.
 *
 * Nineteen codes were on file against the list's twenty-eight. Eleven were
 * missing outright (TBA, TJT, ITSJ, TSN, TSA, IRKH, RBP, IRSH, ITTK, ILEH, AKP)
 * and two on file are no longer on the list (TLB, TSJ).
 *
 * Re-runnable: codes are matched on `code` and updated in place, so this can be
 * run again when the list changes without duplicating anything or disturbing the
 * ids that company_krafs.sub_bidang_id points at.
 *
 * Codes not on the list are DEACTIVATED, never deleted. A code is the thing an
 * old record is explained by, and is_active is already the column meaning "do
 * not offer this any more" — the registrations dropdown and the polling form
 * both honour it. Deleting would also break the sub_bidang_id FK for the sake of
 * two rows.
 *
 * Two readings of the source worth recording, since the CSV is a merged-cell
 * spreadsheet and neither is obvious from the raw rows:
 *
 *   TBA appears twice, under both of BATIK's sub-bidang groups (rows 360 and
 *   361-362). `code` is unique, so it is one row here and the two groups are
 *   joined into its pecahan_sub_bidang — the same joining the existing TBP, TTN
 *   and TST rows already use.
 *
 *   RKP carries two sizes: 3x6 on its own row and 3x3 on the blank row beneath
 *   it (372-373). Both are kept in one string rather than split into two options
 *   that would submit the same code.
 *
 * HASIL LOGAM keeps its name although the list writes LOGAM: it is the name the
 * office already reads on every screen, and a rename is a decision for them.
 *
 * THE FIVE CATEGORIES ARE SEEDED TOO, as the other half of kraf_bidangs (code IS
 * NULL). A company's craft selection is made from them, so an install without
 * them cannot record what any company makes. The levels under them — sub-category,
 * classification, product — are typed in Settings by the office, who know the
 * current list.
 */
class KrafCodeSeeder extends Seeder
{
    /** [bidang, pecahan_bidang, pecahan_sub_bidang, code] in list order. */
    public const CODES = [
        ['TEKSTIL', 'BATIK', 'PAKAIAN SIAP/ KAIN PASANG', 'TBPS'],
        ['TEKSTIL', 'BATIK', 'PAKAIAN SIAP/ KAIN PASANG', 'TBP'],
        ['TEKSTIL', 'BATIK', 'PAKAIAN SIAP/ KAIN PASANG / HIASAN DALAMAN/ PERHIASAN DIRI/ KEGUNAAN', 'TBA'],
        ['TEKSTIL', 'TENUN', 'PAKAIAN SIAP/ KAIN PASANG / HIASAN DALAMAN', 'TTN'],
        ['TEKSTIL', 'SONGKET', 'PAKAIAN SIAP/ KAIN PASANG / HIASAN DALAMAN', 'TST'],
        ['TEKSTIL', 'TEKATAN', 'PAKAIAN SIAP/ KAIN PASANG / HIASAN DALAMAN', 'TTH'],
        ['TEKSTIL', 'JAHITAN TRADISI', 'PAKAIAN SIAP/ KAIN PASANG / HIASAN DALAMAN', 'TJT'],
        ['TEKSTIL', 'JAHITAN TRADISI', 'PAKAIAN SIAP/ KAIN PASANG / HIASAN DALAMAN', 'ITSJ'],
        ['TEKSTIL', 'SULAMAN', 'PAKAIAN/ HIASAN/ KEGUNAAN', 'TSN'],
        ['TEKSTIL', 'SULAMAN', 'PERHIASAN/ HIASAN DALAMAN/ KEGUNAAN', 'TSA'],

        // RKP is listed twice on the sheet, at 3x6 and at 3x3. It is one code here:
        // the size belongs to the draw, not to the category.
        ['HASIL RIMBA', 'KAYU', 'PERABOT KAYU/ WAKAF (LAMAN/TAMAN)', 'RKP'],
        ['HASIL RIMBA', 'KAYU', 'HIASAN DALAMAN - UKIRAN/ KEGUNAAN / ALAT MUZIK/ PERMAINAN', 'RKH'],
        ['HASIL RIMBA', 'KAYU', 'HIASAN DALAMAN - UKIRAN/ KEGUNAAN / ALAT MUZIK/ PERMAINAN', 'IRKH'],
        ['HASIL RIMBA', 'BULUH / ROTAN', 'HIASAN DALAMAN / KEGUNAAN', 'RBH'],
        ['HASIL RIMBA', 'BULUH / ROTAN', 'PERABOT (LAMAN & TAMAN)', 'RBP'],
        ['HASIL RIMBA', 'MENGKUANG/ PANDAN/ RIBU-RIBU/ BEMBAN/ SERDANG/ DAUN SAL/ DAUN SERDANG/ DAUN TAR/ DAUN NIPAH/ RUMBIA/ KERCUT', 'HIASAN DALAMAN / KEGUNAAN', 'RMH'],
        ['HASIL RIMBA', 'SESERAT SEMULAJADI/ TEMPURUNG/ LIDI/ BUNGA/ DAUN/ BUAH KERING', 'HIASAN DALAMAN / KEGUNAAN', 'RSH'],
        ['HASIL RIMBA', 'SESERAT SEMULAJADI/ TEMPURUNG/ LIDI/ BUNGA/ DAUN/ BUAH KERING', 'HIASAN DALAMAN / KEGUNAAN', 'IRSH'],

        ['HASIL TANAH', 'SERAMIK/ BATU / KRISTAL/ KACA', 'HIASAN DALAMAN / KEGUNAAN / SENI ARCA', 'TTK'],
        ['HASIL TANAH', 'SERAMIK/ BATU / KRISTAL/ KACA', 'HIASAN DALAMAN / KEGUNAAN / SENI ARCA', 'ITTK'],
        ['HASIL TANAH', 'SERAMIK/ BATU / KRISTAL/ KACA', 'PERHIASAN DIRI', 'TTP'],

        ['HASIL LOGAM', 'PERAK/ TEMBAGA/ PUITER/ BESI/ ALUMINIUM/ EMAS', 'PERHIASAN DIRI/ KEGUNAAN', 'LEH'],
        ['HASIL LOGAM', 'PERAK/ TEMBAGA/ PUITER/ BESI/ ALUMINIUM/ EMAS', 'PERHIASAN DIRI/ KEGUNAAN', 'ILEH'],
        ['HASIL LOGAM', 'PERAK/ TEMBAGA/ PUITER/ BESI/ ALUMINIUM/ EMAS', 'HIASAN DALAMAN/ KEGUNAAN', 'LTH'],
        ['HASIL LOGAM', 'PERAK/ TEMBAGA/ PUITER/ BESI/ ALUMINIUM/ EMAS', 'SENJATA / PANDAI BESI', 'LTS'],

        ['ANEKA KRAF', 'MANIK / MUTIARA / KARANGAN LAUT', 'PERHIASAN DIRI/ KEGUNAAN', 'AMH'],
        ['ANEKA KRAF', 'KULIT (FAUNA)/ GETAH (LATEX) / POLIMER / PLASTIK/ GUBAHAN', 'HIASAN / KEGUNAAN / PERHIASAN DIRI', 'AKK'],
        ['ANEKA KRAF', 'PLASTIK', 'HIASAN / KEGUNAAN', 'AKP'],
    ];

    /** The top of the craft hierarchy — the category half of kraf_bidangs. */
    public const CATEGORIES = ['TEKSTIL', 'HASIL RIMBA', 'HASIL TANAH', 'HASIL LOGAM', 'ANEKA KRAF'];

    public function run(): void
    {
        $now = now();

        foreach (self::CATEGORIES as $name) {
            $exists = DB::table('kraf_bidangs')->whereNull('code')->where('name', $name)->exists();

            if (! $exists) {
                DB::table('kraf_bidangs')->insert([
                    'name' => $name,
                    'code' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach (self::CODES as $i => [$bidang, $pecahan, $sub, $code]) {
            $values = [
                // bil orders the admin code list, the polling form and the code
                // dropdown, so they read in the order of the printed sheet.
                'bil'                => $i + 1,
                'name'               => $bidang,
                'pecahan_bidang'     => $pecahan,
                'pecahan_sub_bidang' => $sub,
                'is_active'          => true,
                'updated_at'         => $now,
            ];

            $match = DB::table('kraf_bidangs')->where('code', $code);

            if ($match->exists()) {
                $match->update($values);
            } else {
                DB::table('kraf_bidangs')->insert($values + ['code' => $code, 'created_at' => $now]);
            }
        }

        // Retired codes sort to the end. Settings › Craft Category Code lists inactive rows on
        // purpose — it is where they are managed — but at their old bil they
        // would sit interleaved with live ones.
        DB::table('kraf_bidangs')
            ->whereNotNull('code')
            ->whereNotIn('code', array_column(self::CODES, 3))
            ->update([
                'is_active'  => false,
                'bil'        => 900,
                'updated_at' => $now,
            ]);
    }
}
