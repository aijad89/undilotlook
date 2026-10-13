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

- [ ] `EventVisibility.php` — **gerbang skop negeri**. Diperlukan oleh setiap
      controller admin di bawah
- [ ] `RegistrationStates.php` — senarai negeri (extends `ManagedList`)
- [ ] `AdminZones.php` — zon pejabat
- [ ] `PlotSizes.php` — senarai saiz gerai
- [ ] `KrafPicker.php` — pemilih hierarki kraf
- [ ] `RejectionReasons.php` — dibaca oleh `partials/rejection-reason-options`
- [ ] `partials/state-options.blade.php`, `partials/rejection-reason-options.blade.php`

### 2.2 Model

- [ ] `Event` — `use Auditable`. Const `STATUSES` dan `POLLING_STATUSES`
      (**bukan** enum lajur). Relasi: `registrations`, `plotSizes`,
      `bidangAllocations`, `plots`, `polls`, `presetAllocations`
- [ ] `EventRegistration` — `use Auditable`. **Jangan** salin relasi `polls()`:
      ia rosak dalam `daftar`, memadan pada `polls.company_id` yang tidak wujud
- [ ] `KrafBidang`, `KrafSubBidang`, `KrafPengelasan`, `KrafProduk`, `CompanyKraf`
- [ ] Tambah pada `Company`: relasi **`eventRegistrations()`** —
      **bukan** `registrations()`. Tiada method bernama `registrations()` pada
      model itu dalam `daftar`, jadi nama yang salah memulangkan null secara
      senyap
- [ ] Factory untuk setiap satu, **menamakan setiap lajur**

### 2.3 Skrin admin

- [ ] **Acara** — `Admin/EventController`, tanpa kalendar buat masa ini.
      Senarai, cipta, sunting, padam
- [ ] **Syarikat** — `Admin/CompanyController`. Senarai, cipta, sunting, padam
  - [ ] **Cipta akaun pengguna dari borang yang sama** — satu syarikat satu
        pengguna. Borang itu juga memegang toggle aktif/tidak dan reset kata
        laluan *(keputusan 29b)*
  - [ ] `CompanyController::store()` mesti mengesahkan `user_id` dengan
        `Rule::unique` **selain** indeks unik — mesej yang boleh dibaca, bukan
        ralat pangkalan data
  - [ ] Bahagian pemilihan kategori kraf —
        `admin/companies/partials/kraf-section.blade.php`
- [ ] **Senarai cabutan acara** — dari `Admin/EntRegistrationController`.
      Tambah/buang syarikat, tetapkan kod kraf, saiz gerai, operasi pukal, undo
- [ ] **Settings › Dropdown Lists** — kod kraf, hierarki kraf, saiz gerai, negeri,
      zon. Setiap query mesti menyatakan separuh mana `kraf_bidangs` yang
      dimaksudkan
- [ ] Tambah empat entri ke `$nav` dalam `layouts/admin.blade.php`

### 2.4 Ujian Fasa 2

- [ ] Skop negeri: admin negeri melihat syarikat negerinya sahaja, dan **tidak
      boleh** mencapai yang lain melalui URL yang ditaip
- [ ] Satu syarikat satu pengguna, dikuatkuasakan pada kedua-dua lapisan
- [ ] Saringan dua senarai `kraf_bidangs` — kategori tidak muncul dalam senarai
      kod dan sebaliknya
- [ ] `Company` yang memegang lot menolak padam

---

## Fasa 3 — cabutan itu sendiri

*Siap apabila:* satu cabutan penuh berjalan hujung ke hujung.

- [ ] Salin `app/Services/LotAllocator.php` dan `PlotAllocationAlgorithm.php`
      — **ingat ia dalam `Services/`, bukan `Support/`**
- [ ] Salin `app/Exceptions/LotUnavailable.php` — mod kegagalan kongsi kedua-dua
- [ ] Salin `app/Support/PollingGuard.php`
- [ ] Model `Plot`, `PlotSize`, `BidangAllocation`, `PresetAllocation`, `Poll`
- [ ] `Admin/PollingController` — pembina pelan (`savePlan()`, pemadanan
      (lokasi, saiz)), jana plot, buka/tutup polling, keputusan, assign unvoted
  - [ ] Bawa tiga penulisan `AuditTrail` tangan: `plan_rewrite`, `plan_cleared`,
        `plots_generated`
- [ ] `Admin/PlotPainterController` (`polling2`) — susun atur plot visual
- [ ] Penetapan awal (preset assign) — sudah dalam `PollingController`
- [ ] Sisi pengguna: `EventRegistrationController::showVote()` / `submitVote()`,
      `zvote/vote.blade.php`, `zvote/partials/lot-reveal.blade.php`
- [ ] Tambah entri `$apNav` untuk cabutan

### Ujian Fasa 3

- [ ] Dua pengguna menarik serentak tidak boleh mendapat lot yang sama
- [ ] Pengguna tidak boleh menarik dua kali (indeks unik `polls`)
- [ ] Penulisan semula pelan mengosongkan `plot_id` sebelum memadam plot
- [ ] Plot preset yang hilang memberi ralat yang boleh dibaca, bukan 500
- [ ] Penetapan merentas kategori bertanya dahulu, dan menyimpan pada OK

---

## Fasa 4 — sekeliling

- [ ] PDF keputusan lot — `pdf/lot-result.blade.php`.
      **Buang seksyen imej produk (`:319-330` dalam `daftar`)** semasa menyalin
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
- [ ] `/auto` — `DevAutoLoginController`, environment `local`/`testing` sahaja
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
