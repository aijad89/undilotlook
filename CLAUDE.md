# CLAUDE.md

Panduan untuk sesiapa — manusia atau agen — yang bekerja dalam repo ini.

Fail ini memegang **peraturan dan keputusan sahaja**: perkara yang tidak boleh
diterbitkan semula dengan membaca kod. Apa yang masih perlu dibuat ada dalam
`TODO.md`.

---

## Apa ini

**undilot** — sistem cabutan lot untuk acara usahawan kraf. Laravel 12, PHP
`^8.4`, MySQL pada dev dan produksi, SQLite dalam-memori untuk ujian.

Ia dipisahkan daripada **`daftar`** (`f:\herd\daftar`) pada 2026-10-13. `daftar`
membawa 285 route, 156 migration dan 62 entri backlog, dan backlog itu tertumpu
pada modul yang bukan produk. undilot membawa cabutan lot dan apa yang cabutan
perlukan.

**`daftar` kekal sebagai arkib dan rujukan.** Apa pun yang ditinggalkan masih ada
di sana untuk disalin bila diperlukan. Jangan tulis semula sesuatu yang boleh
disalin.

---

## Persekitaran tempatan

| | |
|---|---|
| Laluan | `f:\herd\undilot` |
| URL | `http://undilot.test` (Herd) |
| DB | `undilot` pada `127.0.0.1` |
| PHP CLI | `/c/Users/ALOY/.config/herd/bin/php84.bat` |
| MySQL CLI | `/c/Program Files/MySQL/MySQL Server 9.5/bin/mysql.exe` |
| Admin pertama | dari `.env` — `ADMIN_SEED_EMAIL` / `ADMIN_SEED_PASSWORD` |

`artisan tinker <fail>` **tergantung** pada mesin ini. Guna klien MySQL untuk
menanya pangkalan data.

---

## Peraturan

### Satu baseline migration, dan ia kekal satu

`database/migrations/0001_01_02_000000_create_undilot_schema.php` memegang
keseluruhan skema aplikasi. Selagi belum ada data sebenar, **ubah fail itu dan
jalankan `migrate:fresh`** — jangan tambah migration berperingkat.

Itu berhenti pada hari pejabat memasukkan data sebenar. Selepas itu, migration
baharu seperti biasa.

### Tiada lajur `enum`, selamanya

Perbendaharaan status tinggal dalam const model (`Event::STATUSES`,
`Event::POLLING_STATUSES`), bukan dalam jenis lajur.

Sebabnya diukur: enum adalah serupa pada MySQL dan SQLite sehingga seseorang
guna `ALTER TABLE … MODIFY COLUMN`, yang **SQLite langkau senyap**. Dalam
`daftar` penyimpangan itu tidak disedari selama lapan bulan — sehingga tiada
ujian boleh menetapkan `polling_status = 'closed'`, iaitu keadaan yang modul
cabutan itu sendiri mengenainya.

`tests/Feature/SchemaHoldsTheSameShapeOnBothDriversTest.php` mengunci ini.

### Setiap migration dijalankan pada **dua** pemacu sebelum dipercayai

```
php artisan migrate:fresh --force                 # MySQL, dari .env
vendor/pestphp/pest/bin/pest                      # SQLite dalam-memori
```

### Nav layout ialah senarai, bukan markup

`resources/views/layouts/admin.blade.php` dan `layouts/frontend.blade.php`
masing-masing mengisytiharkan `$nav` / `$apNav` di atas fail. **Tambah modul =
satu entri array.**

Jangan kembali kepada mengeja `route()` dalam markup. `daftar` ada 22 dalam satu
layout, dan satu route yang dibuang di mana-mana membunuh **setiap** skrin admin
sekali gus, dengan trace yang menyalahkan layout.

### Factory menamakan setiap lajur, termasuk yang nullable

`Model::preventAccessingMissingAttributes()` hidup di luar produksi, dan
`actingAs($user)` menyerahkan view **instance factory itu sendiri**. Lajur yang
factory tidak namakan tidak hadir pada instance itu — jadi view campak
`MissingAttributeException` di bawah `actingAs` sambil berfungsi sempurna
selepas log masuk sebenar. Kegagalan itu kelihatan seperti view yang rosak.

`null` ialah nilai. Namakan lajur itu.

### Tiada kata laluan lalai dalam kod

`AdminSeeder` menolak berjalan tanpa `ADMIN_SEED_PASSWORD` dalam `.env`.
**Jangan beri ia fallback.** Kata laluan ber-fallback dalam fail yang dijejaki
ialah kata laluan dalam sejarah git pada hari ia ditulis.

### Semua `.md` dijejaki

Tiada peraturan `.gitignore` mengecualikannya. Keputusan ialah bahagian yang
paling sukar dipulihkan.

---

## Seni bina dan jerangkap

### Dua guard

`web` (`App\Models\User`) dan `admin` (`App\Models\Admin`). Ini keputusan paling
membentuk dalam aplikasi, dan hampir semua kejanggalan auth berpunca daripadanya:
**admin ialah tetamu pada guard `web`**, dan peserta ialah tetamu pada `admin`.

`bootstrap/app.php` menjawabnya dengan dua closure. Pecahkan salah satu dan
kegagalannya bukan 404 — ia gelung redirect, atau admin yang sudah log masuk
merenung borang log masuk **peserta**.

`tests/Feature/BothGuardsSignInTest.php` menahan kedua-duanya.

### Tiga peranan admin

`admin` (berskop satu negeri), `hq`, `super`. Perbendaharaan dan siapa boleh
memberi apa ada dalam `Admin::ROLES` dan `Admin::assignableRolesFor()`.

`assignableRolesFor()` **diterbitkan daripada pemanggil**, bukan senarai tetap.
Peraturan `Rule::in(array_keys(Admin::ROLES))` tidak peduli siapa bertanya —
tidak berbahaya selagi skrin itu super-sahaja, dan membenarkan hq mencetak akaun
super bagi dirinya sendiri sebaik skrin itu diluaskan.

### Skop negeri melalui `EventVisibility`, bukan middleware

Tiada alias middleware yang kelihatan seperti pengawal negeri. Pengawal pada
setiap route admin **membaca** seperti penguatkuasaan dan tidak menguatkuasakan
apa-apa. `TrackAdminLastSeen` ialah penjejakan kehadiran, dan namanya berkata
begitu.

### `kraf_bidangs` memegang **dua** senarai

| `code` | Apa ia | Diurus di |
|---|---|---|
| `NULL` | kategori kraf (TEKSTIL, HASIL RIMBA…) | Craft Hierarchy |
| berisi | kod kraf (TBPS, RKP…) dari helaian pengekodan | Craft Category Code |

Keduanya bertemu pada `kraf_sub_bidangs.kraf_bidang_code_id`, yang **pilihan**.

**Setiap query mesti menyatakan yang mana.** Senarai yang terlupa memaparkan
kedua-duanya; padam yang terlupa membuang baris daripada senarai yang tiada siapa
sedang lihat. Ini punca tiga entri backlog dalam `daftar`.

### Pemilikan ialah `company_id`, bukan `user_id`

`User::registrations()` ialah `hasManyThrough` melalui `Company`. Lajur
`company_id` ialah `NOT NULL` dan ber-foreign-key; `user_id` nullable dan tiada
apa dalam skema menghalang null tiba.

`user_id` **tetap ditulis dan dibaca** — oleh `plots.allocated_to`, `polls`, dan
peruntukan awal. **Dua kunci, dua soalan, dengan sengaja.**

### Kewujudan baris pendaftaran ialah kelulusannya

Tiada lajur `status` pada `event_registrations`. Admin meletakkan syarikat ke
dalam senarai cabutan sesebuah acara dengan kod kraf dan saiz gerai; itulah
maksud masuk cabutan.

### Baris `plot_sizes` ialah pasangan (lokasi, saiz)

Borang tidak menaip pasangan itu terus: satu baris pada borang ialah **lokasi**
dengan kiraan, dan setiap peruntukan kraf yang bersarang di bawahnya menamakan
**saiznya sendiri**. `PollingController::savePlan()` menyelesaikannya kembali
kepada baris, memadan pada (lokasi, saiz) supaya pasangan yang terselamat
mengekalkan id-nya. `total_plots` dikira di sana, tidak pernah diambil daripada
borang.

Saiz gerai sebuah pendaftaran ialah `event_registrations.plot_size` — satu
**string**, lalai `3x3`. Bukan rujukan `plot_sizes`: pejabat menyatakannya
sebelum sebarang pelan wujud, kerana pelan dibina daripada nombor itu.

### `event_registrations.code` ialah string, bukan foreign key

Ia memegang kod kraf (TBPS, RKP) dan dipadankan terhadap `code` pada baris
`kraf_bidangs` yang plot dikategorikan dengannya. Itu ketidakpadanan
string-lawan-FK, dan ia **sengaja dibiarkan**: itulah yang pengalokasi
bandingkan, dan mengubahnya bermakna menulis semula pengalokasi dan bukan
membawanya masuk utuh.

### `event_registrations.plot_id` tiada klausa `ON DELETE`

Jadi pangkalan data menyekat. Plot tidak boleh dipadam selagi satu entri cabutan
masih menunjuk kepadanya — keputusan cabutan **tidak boleh** hilang kerana
seseorang menyunting pelan. Penulisan semula pelan mesti mengosongkan pautan itu
dan dilihat berbuat demikian.

### Audit cabutan sebahagiannya ditulis dengan tangan

Trait `Auditable` menggunakan Eloquent model event, dan **mass delete tidak
mencetuskan satu pun**. `PollingController` memasukkan barisnya sendiri
(`plan_rewrite`, `plan_cleared`, `plots_generated`) untuk menampungnya, dan
`AuditTrail::getChangedFieldsAttribute()` ada cabang khas untuknya.

Begitu juga `ON DELETE CASCADE`: baris yang hilang melaluinya **tidak
meninggalkan satu pun baris audit**. Model yang boleh menyatakan jangkauannya
menutupnya dengan `auditDeletionSummary()`, mengira **sebelum** DELETE.

**Jangan ukur jangkauan cascade daripada fail migration.** Tanya pangkalan data
hidup:

```sql
select rc.REFERENCED_TABLE_NAME, kcu.TABLE_NAME, kcu.COLUMN_NAME, rc.DELETE_RULE
from information_schema.REFERENTIAL_CONSTRAINTS rc
join information_schema.KEY_COLUMN_USAGE kcu
  on kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
 and kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
where rc.CONSTRAINT_SCHEMA = 'undilot' and rc.DELETE_RULE = 'CASCADE';
```

### Tiada langkah bina

Tiada Vite, tiada npm build, tiada Alpine. Setiap stylesheet ialah inline dalam
partial Blade; setiap pustaka datang dari CDN dengan versi dipin. `npm` hanya
untuk Playwright. **Node tidak diperlukan pada produksi.**

### Dua design system, dua prefix

`.mac-` (admin, amber) dan `.ap-` (portal peserta, biru). Tiada halaman sah
menggunakan kedua-duanya. View auth membawa rupa tersendiri — jangan kenakan
kelas `mac`/`ap` padanya.

### `SESSION_DRIVER` mesti `database`

`NewPasswordController::logOutOtherDevices()` mengusir dengan memadam baris
daripada jadual `sessions`. Pada pemacu lain ia memadan **tiada apa**: reset
melaporkan berjaya dan peranti lain kekal log masuk. Tiada ralat, tiada ujian
gagal — hanya satu `Log::warning`.

---

## Bila menyalin daripada `daftar`

Empat perkara menggigit setiap kali:

1. **`LotAllocator` dan `PlotAllocationAlgorithm` ada dalam `app/Services/`**,
   bukan `app/Support/`, dan kedua-duanya dipanggil FQCN *inline* tanpa `use`.
   Grep untuk `use App\Support\LotAllocator` tidak menjumpai apa-apa.
2. **`app/Exceptions/LotUnavailable.php`** ialah mod kegagalan kongsi kedua-dua
   pengalokasi, dan tiada dalam mana-mana senarai `Support/`.
3. **Komen membawa rujukan mati** — "backlog entry 17", "office question 15b",
   dan beberapa ayat yang berakhir dengan "See" tanpa apa-apa selepasnya. Jadikan
   ia berdiri sendiri; rujukan yang tidak membawa ke mana-mana lebih buruk
   daripada tiada rujukan.
4. **Kod mati untuk data lama.** `daftar` membawa cabang untuk rekod yang
   mendahului sesuatu migration. Pangkalan data kosong tidak boleh mengandungi
   satu pun. Periksa sebelum menyalin.

---

## Ujian

```
vendor/pestphp/pest/bin/pest              # SQLite dalam-memori
npx playwright test                        # bila suite pelayar wujud
```

`RefreshDatabase` dikenakan pada `Feature` tetapi **bukan** `Regression`. Ujian
yang perlu menanam keadaan yang skema larang — `plot_id` tergantung, dengan
menogol `PRAGMA foreign_keys` — tidak boleh berbuat demikian di dalam transaksi.

**Satu larian hijau bukan bukti pada suite pelayar.** Ini dipelajari dengan mahal
dalam `daftar`: empat larian penuh pada kod yang sama memberi empat set kegagalan
berbeza. Bila suite Playwright didirikan di sini, bawa `retries: 1` dan
`trace: 'on-first-retry'`, dan **baca kiraan `flaky`**, bukan kiraan `passed`.

Jangan tulis `page.once('dialog', d => d.accept())`. Guna helper yang merekod
jenis dan teks dialog — `alert` yang diterima secara buta ialah tindakan yang
tidak berlaku, dan ujian gagal empat baris kemudian atas sesuatu yang tidak
berkaitan.
