# TODO.md

Apa yang masih perlu dibuat. Peraturan dan keputusan ada dalam `CLAUDE.md`.

Sumber utama untuk disalin: **`f:\herd\daftar`**. Baca
`CLAUDE.md` › *"Bila menyalin daripada `daftar`"* dahulu — empat perkara di situ
menggigit setiap kali.

---

## Siap

**Fasa 1 — rangka yang berjalan.** `141d023`

- Laravel 12.69.3, PHP `^8.4`, tiada langkah bina
- Skema penuh dalam satu migration, 27 jadual, lulus pada MySQL **dan** SQLite
- Dua guard, kedua-dua log masuk, lupa/reset kata laluan
- Dua layout dengan nav dipacu data
- `Admin`, `User`, `Company`, `Setting`, `AuditTrail`, `FooterMenu`, `SocialLink`
- `AdminSeeder` (kata laluan dari `.env`, tiada default)
- Pest: **11 lulus**

---

## Fasa 2 — data cabutan

*Siap apabila:* admin boleh cipta acara, cipta syarikat dengan akaun penggunanya,
pilih kategori kraf, dan letak syarikat ke dalam senarai cabutan dengan kod dan
saiz gerai.

### 2.1 Support yang disalin dahulu

Salin dari `daftar/app/Support/` — semuanya hampir kendiri:

- [x] `EventVisibility.php` — **gerbang skop negeri**. Diperlukan oleh setiap
      controller admin di bawah. Ujiannya menunggu model `Event` (2.2/2.4)
- [x] `AdminZones.php` — zon pejabat. `ZonesCarryTheirRowsWhenRenamedTest`
      menahan `rename()` dan `isInUse()`
- [x] `PlotSizes.php` — senarai saiz gerai
- [x] `KrafPicker.php` — pemilih hierarki kraf. Tapisan senarai kategori ditukar
      kepada `whereNull('code')`; `daftar` guna `pecahan_bidang`, yang betul
      hanya kerana datanya kebetulan sejajar
- [x] `partials/zone-options.blade.php` — pasangan view untuk `AdminZones`

**Tidak disalin — tiada lajur untuk disuap dalam skema ini:**

- `RegistrationStates.php` + `partials/state-options.blade.php` — senarai
  geografi. Penggunanya dalam `daftar` ialah `company_premises.state` (jadual
  itu tiada di sini) dan `events.state` (di sini ia **zon**, salah satu daripada
  tiga lajur yang `AdminZones::rename()` tulis). Salin bila ada lajur tempat
  sebenar
- `RejectionReasons.php` + `partials/rejection-reason-options.blade.php` — tiada
  aliran tolak di sini: kewujudan baris pendaftaran ialah kelulusannya, dan
  tiada lajur `notes`/`reason` dalam skema

### 2.2 Model

- [x] `Event` — `use Auditable`. Const `STATUSES` dan `POLLING_STATUSES`
      (**bukan** enum lajur). Relasi: `registrations`, `plotSizes`,
      `bidangAllocations`, `plots`, `polls`, `presetAllocations`.
      `collectDeletionCounts()` guna query builder, bukan relasi, supaya ia
      berfungsi sebelum model Fasa 3 wujud
- [x] `EventRegistration` — `use Auditable`. Tiada `polls()`, tiada `status`,
      tiada hook: kewujudan baris ialah kelulusannya
- [x] `KrafBidang`, `KrafSubBidang`, `KrafPengelasan`, `KrafProduk`, `CompanyKraf`.
      `KrafBidang::categories()` / `::codes()` ialah cara menyatakan separuh mana
- [x] Tambah pada `Company`: relasi **`eventRegistrations()`** dan
      **`krafSelections()`**
- [x] Factory untuk setiap satu, **menamakan setiap lajur** — termasuk
      `CompanyFactory` yang sebelum ini tiada
- [x] Ujian: `TheTwoCraftListsStayApartTest`, `AnEventDeleteSaysWhatTheCascadeTookTest`

**Dibetulkan sambil lalu:**

- Komen `company_krafs.bidang_id` dalam migration baseline berkata ia memegang
  separuh **berkod**. Ia salah: `bidang_id` ialah separuh **kategori** (sama
  dengan `kraf_sub_bidangs.bidang_id`, yang membuat pasangan itu konsisten).
  Dalam `daftar` 277/277 baris mengesahkannya. Komen dibetulkan
- `KrafPicker` dipangkas kepada dua aras. `daftar` menyimpan pengelasan dan
  produk per pilihan melalui dua jadual pivot (`company_kraf_pengelasan`,
  `company_kraf_produk`); kedua-duanya tiada dalam skema ini

**Perangkap yang ditemui, dan bagaimana ia ditutup:**

- Memadam acara mencetuskan **dua** cascade (`events` → `event_registrations`,
  `events` → `plots`) sedangkan `event_registrations.plot_id` ialah `NO ACTION`.
  Diuji 2026-10-14: **kedua-dua** MySQL dan SQLite memadam dengan bersih tanpa
  pengawal — tiada perlanggaran hari ini. `EventController::destroy()` tetap
  mengosongkan `plot_id` dahulu kerana tiada pemacu menjanjikan susunan. Tiada
  ujian untuknya: ujian yang tidak boleh gagal pada mana-mana pemacu bukan ujian
- Memadam baris `kraf_bidangs` / `kraf_sub_bidangs` cascade tanpa baris audit.
  Ditutup dengan **menolak**, bukan dengan `auditDeletionSummary()`:
  `SettingController` memadam hanya bila tiada apa menunjuk kepadanya (setiap FK
  hidup diperiksa terhadap `information_schema`). `kraf_pengelasan` tertinggal
  daripada semakan sub-kategori — ditambah. `CraftRowsInUseRefuseToDeleteTest`
- Padam baris kraf yang **tidak** digunakan masih tidak diaudit (`KrafBidang`
  tiada `Auditable`). Tambah bila seseorang bertanya siapa memadam sesuatu kod

### 2.3 Skrin admin

- [x] **Acara** — `Admin/EventController`, tanpa kalendar buat masa ini.
      Senarai, cipta, sunting, padam
- [x] **Syarikat** — `Admin/CompanyController`. Senarai, cipta, sunting, padam
  - [x] **Cipta akaun pengguna dari borang yang sama** — satu syarikat satu
        pengguna. Borang itu juga memegang toggle aktif/tidak dan reset kata
        laluan *(keputusan 29b)*
  - [x] ~~`Rule::unique` pada `user_id`~~ — tiada borang menghantar `user_id`;
        akaun sentiasa dicipta baharu. Mesej boleh baca datang daripada
        `Rule::unique('users', 'email')`
  - [x] Bahagian pemilihan kategori kraf —
        `admin/companies/partials/kraf-section.blade.php`
- [x] **Senarai cabutan acara** — dari `Admin/EntRegistrationController`.
      Tambah/buang syarikat, tetapkan kod kraf, saiz gerai, operasi pukal, undo
- [x] **Settings › Dropdown Lists** — kod kraf, hierarki kraf, saiz gerai,
      zon (negeri tidak — lihat 2.1). Setiap query mesti menyatakan separuh mana `kraf_bidangs` yang
      dimaksudkan
- [x] Tambah entri ke `$nav` dalam `layouts/admin.blade.php` — Senarai cabutan
      dicapai dari halaman acara, bukan nav
- **Skop negeri diubah 2026-10-14:** peraturan own-state `daftar` dibuang daripada
  `EventVisibility`. Admin negeri melihat setiap acara bukan draf, kerana pejabat
  yang mengisi senarai cabutan; baris di dalamnya kekal ditapis mengikut zon

### 2.4 Ujian Fasa 2

- [x] Skop negeri: admin negeri melihat syarikat negerinya sahaja, dan **tidak
      boleh** mencapai yang lain melalui URL yang ditaip
- [x] Satu syarikat satu pengguna, dikuatkuasakan pada kedua-dua lapisan
- [x] Saringan dua senarai `kraf_bidangs` — kategori tidak muncul dalam senarai
      kod dan sebaliknya
- [x] `Company` yang memegang lot menolak padam

---

## Fasa 3 — cabutan itu sendiri

*Siap apabila:* satu cabutan penuh berjalan hujung ke hujung.

- [x] Salin `app/Services/LotAllocator.php` dan `PlotAllocationAlgorithm.php`
      — **ingat ia dalam `Services/`, bukan `Support/`**
- [x] Salin `app/Exceptions/LotUnavailable.php` — mod kegagalan kongsi kedua-dua
- [x] Salin `app/Support/PollingGuard.php`
- [x] Model `Plot`, `PlotSize`, `BidangAllocation`, `PresetAllocation`, `Poll`.
      Factory untuk `Plot` dan `PlotSize`; yang lain bila ujian memerlukannya
- [x] Ujian: `OneRuleSeatsEveryoneTest`, `ADrawOpensOnlyIfItCanFinishTest` —
      setiap peraturan disahkan gagal bila dibuang

**Berbeza daripada `daftar`, dengan sengaja:**

- Pemadanan ialah **string**: `event_registrations.code` = `kraf_bidangs.code`
  pada plot. `daftar` telah beralih ke `kraf_bidang_id`; skema ini tidak
- Tiada tapisan `status = 'committee_approved'` — setiap baris ialah peserta
- `LotUnavailable::noAccount()` dan semakan guard `missingAccounts` — baharu.
  Syarikat boleh berada dalam cabutan sebelum akaunnya wujud, dan
  `polls.user_id` NOT NULL akan menggagalkan seluruh larian pukal
- Pembantu `PlotSize` yang tiada pemanggil (`isFullyAllocated`,
  `remaining_quantity`, `display_name`) tidak disalin
- [x] `Admin/PollingController` — pembina pelan (`savePlan()`, pemadanan
      (lokasi, saiz)), jana plot, buka/tutup polling, keputusan, assign unvoted.
      Partial pelan disalin; `show` ditulis semula ringkas (empat tab, bukan
      1,714 baris). Ujian: `TheLotPlanBuildsWhatItSaysTest`
  - [x] Bawa tiga penulisan `AuditTrail` tangan: `plan_rewrite`, `plan_cleared`,
        `plots_generated`
  - Berbeza daripada `daftar`: `validateBidangIds()` menerima **kod** sahaja
    (id kategori membina plot yang tiada pendaftaran boleh padan); `update()`
    tidak lagi menulis tajuk acara; cabang "pulihkan preset" dalam `update()`
    dibuang — `planIsFrozen()` sudah menolak sebarang lot dipegang, jadi ia
    tidak pernah berjalan; `completed` ditolak selagi ada yang belum duduk;
    dropdown status pada borang sunting (yang `update()` abaikan) diganti
    paparan baca sahaja
- [x] `Admin/PlotPainterController` — `admin.polling.paint`, dipaut dari tab Plan.
      Kod sahaja boleh dicat (sama seperti pelan bertaip); petak dan cip kini
      `<button>` supaya papan kekunci boleh mencapainya. Ujian:
      `PaintingCodesOntoPlotsTest`
- [x] Penetapan awal (preset assign) — dalam `PollingController`, dengan
      pengesahan pelayar bila merentas kod
- [x] Sisi pengguna — `LotDrawController` (`show`, `submit`), bukan
      `EventRegistrationController`: tiada pendaftaran kendiri di sini, jadi
      tiada apa lagi untuk controller itu pegang. Nama route `zvote.*` dikekalkan.
      `zvote/vote.blade.php` dan `lot-reveal` disalin; butang slip PDF dibuang
      sehingga PDF wujud (Fasa 4), pautan `profile` diganti ayat
- [x] ~~Entri `$apNav`~~ — dashboard peserta menyenaraikan setiap cabutan dan
      memaut ke lotnya; halaman lot menyalakan entri Dashboard. `zvote.dashboard`
      `daftar` tidak disalin

### Ujian Fasa 3

- [ ] Dua pengguna menarik serentak tidak boleh mendapat lot yang sama —
      **belum diuji**. `submit()` mengunci baris pendaftaran dan
      `inRandomOrder()->lockForUpdate()` mengunci plot, tetapi SQLite dalam satu
      proses tidak boleh menghasilkan dua permintaan bertindih. Perlu ujian pada
      MySQL dengan dua sambungan
- [x] Pengguna tidak boleh menarik dua kali — `AParticipantConfirmsTheirLotTest`
- [x] ~~Penulisan semula pelan mengosongkan `plot_id`~~ — tidak berlaku: penulisan
      semula ditolak sebaik satu lot dipegang (`planIsFrozen()`), jadi tiada
      `plot_id` untuk dikosongkan
- [x] Plot preset yang hilang memberi ralat yang boleh dibaca —
      `OneRuleSeatsEveryoneTest` (`preset_missing`)
- [ ] Penetapan merentas kategori bertanya dahulu — `confirm()` dalam pelayar;
      menunggu suite Playwright
- [x] Satu cabutan penuh hujung ke hujung melalui skrin —
      `AParticipantConfirmsTheirLotTest`

---

## Fasa 4 — sekeliling

- [x] PDF keputusan lot — `pdf/lot-result.blade.php`, `zvote.slip`. Seksyen imej
      produk, sijil dan OKU dibuang (tiada data itu di sini). Font subsetting
      dihidupkan: satu halaman 880 KB → 30 KB
- [ ] Skrin audit trail — `Admin/AuditTrailController`
- [ ] Pengumuman + `VotingCloseReminder` + queue worker
- [ ] Scheduler: `events:update-statuses`, `voting:send-reminder`
- [ ] Laporan & Eksport — `Admin/ReportController`.
      **Buang lajur City** (ia membaca premis, yang tidak dibawa)
- [ ] Kalendar acara — ~280 baris **di dalam** `EventController` dan
      `ZeventController` `daftar`, bukan fail berasingan.
      Route kalendar mesti berada **di atas** `Route::resource('events')`
- [ ] Halaman awam — senarai acara, menu footer, pautan sosial,
      `layouts/public.blade.php`
- [x] `/auto` — `DevAutoLoginController`, environment `local`/`testing` sahaja.
      Pintu ber-flag `daftar` untuk pelayan dev tidak dibawa
- [ ] Suite Playwright — `tests/e2e/serve.js`, `helpers.js`, `fixtures/run.php`,
      `fixtures/seed.js`, `playwright.config.js`. Bawa `acceptDialog()` /
      `strayAlerts()` **dari hari pertama**

---

## Sebelum pejabat menyentuhnya

- [ ] **Jalankan satu cabutan penuh dengan tangan**: cipta acara → cipta 3
      syarikat dengan akaun dan kod kraf berbeza → bina pelan dua lokasi dua
      saiz → buka polling → log masuk sebagai tiga pengguna dan tarik → tutup →
      keputusan dan PDF sepadan
- [ ] Jalankan baseline migration pada MySQL **dan** SQLite dan bandingkan
      jenis lajur

---

## Deploy

Belum dibuat. Rancangan:

- Folder baharu `/var/www/html/undilot/undilot` pada devundilot
  (`192.168.2.101`). **`daftar` pada `/var/www/html/undilot/daftar` tidak
  disentuh**
- DB `undilot`
- `git pull` terus pada kotak; tiada bina, tiada artifak
- Artisan berjalan sebagai `www-data`; git dan composer di bawah `sudo` biasa
  (pokok kerja milik root)
- `/etc/cron.d/undilot` untuk scheduler, `undilot-queue.service` untuk worker
  — kedua-duanya hanya selepas Fasa 4
- **Jangan** tambah entri crontab `www-data`: crontab itu milik `dsi`, aplikasi
  lain pada kotak yang sama, dan pendua itu tidak kelihatan kepada `crontab -l`

Prosedur penuh `daftar` ada dalam `f:\herd\daftar\DEPLOY.md` §5.7.

---

## Keputusan yang sudah dibuat — jangan buka semula tanpa bertanya

| | |
|---|---|
| Pendaftaran | Tiada aliran status. Kewujudan baris ialah kelulusan |
| Alamat syarikat | `companies.state` sahaja. Tiada jadual premis |
| Cabutan per acara | Satu |
| Akaun usahawan | Admin cipta dari skrin Syarikat. Tiada pendaftaran sendiri |
| PDF | Satu sahaja — keputusan lot |
| Auto buka/tutup polling | **Dikehendaki.** `daftar` mematikannya 2026-08-13; undilot mahukannya |
| Hierarki kraf | Dibawa penuh, termasuk pilihan kraf syarikat |
| Audit trail | Dibawa |

### Ditinggalkan daripada `daftar`, dengan sengaja

Imej produk + moderasi · Katalog awam · Dokumen pendaftaran · Bayaran dan
pengecualian · Premis syarikat · Profil syarikat pengguna (sijil, staf, OKU,
anugerah, media sosial, bank) · Survey · FAQ · PDF pendaftaran

Semuanya masih ada dalam `daftar` kalau diperlukan kemudian.
