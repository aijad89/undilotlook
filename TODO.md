# TODO.md

Apa yang masih perlu dibuat. Peraturan dan keputusan ada dalam `CLAUDE.md`.

Sumber utama untuk disalin: **`f:\herd\daftar`**. Baca
`CLAUDE.md` › *"Bila menyalin daripada `daftar`"* dahulu — empat perkara di situ
menggigit setiap kali.

**Keadaan pada 2026-10-14:** Fasa 1–4 siap. Kod ada di
`https://github.com/aijad89/undilot` (peribadi, cawangan `main`). Belum ada di
pelayan. Pest 113 lulus (SQLite); Playwright 12/12, flaky 0, tiga larian (MySQL).
Senarai semak penuh setiap fasa ada dalam sejarah git fail ini.

---

## Seterusnya — deploy ke devundilot

**Belum diputuskan bagaimana kod sampai ke pelayan.** Pemilik akan memilih cara
sendiri; jangan mula tanpa bertanya. Fakta yang sudah disahkan 2026-10-14:

| | |
|---|---|
| Hos | `devundilot.kraftangan.gov.my` → `192.168.2.101` |
| Yang dihidang sekarang | **`daftar`**, di akar domain, dari `/var/www/html/undilot/daftar` |
| SSH | `administrator`, kata laluan sahaja — kunci mesin ini **tidak** dibenarkan; sudo perlukan kata laluan |
| Nama yang sudah diambil `daftar` | `/etc/cron.d/undilot`, `undilot-queue.service` — undilot perlukan nama lain |
| PHP pada kotak | `/usr/bin/php8.5` (menurut `daftar/DEPLOY.md` §5.7) |
| Repo | peribadi — kotak perlukan kelayakan untuk `git pull` (cadangan: deploy key baca-sahaja) |

**Calon yang dibincang:** undilot di `https://devundilot.kraftangan.gov.my/undilotnew`,
bersebelahan `daftar` yang kekal di akar tanpa diubah. Jika itu dipilih:

- Apache `Alias /undilotnew` ke `public/` undilot, dengan peraturan rewrite dalam
  `<Directory>` vhost (`AllowOverride None`). **`.htaccess` Laravel tiada
  `RewriteBase`**, jadi di bawah Alias ia tidak berfungsi sendiri
- `.env` undilot: `APP_URL` dengan laluan, `SESSION_COOKIE` sendiri,
  `SESSION_PATH=/undilotnew` — atau log masuk ke satu aplikasi melog keluar yang lain
- Kod sudah selamat-subpath: tiada pautan akar keras; ubah hala log masuk/keluar
  menggunakan nama route (`59e1d0c`)
- DB `undilot` + pengguna DB yang hanya menyentuhnya; `migrate --seed` (kod kraf +
  admin pertama dari `ADMIN_SEED_*`, kata laluan ditaip pemilik sendiri)
- Cron dan unit queue dengan nama baharu; **jangan** guna crontab `www-data`
  (milik `dsi`)
- Sandaran DB dan vhost dahulu; `apache2ctl configtest` sebelum reload
- `/auto` **tiada** di pelayan (hanya `local`/`testing`/`e2e`) — penguji perlukan akaun sebenar

Langkah 1 sentiasa baca-sahaja: vhost semasa, modul Apache/PHP, sambungan PHP,
`composer`, nama cron/servis yang ada, cara `daftar` menarik dari GitHub.

Prosedur `daftar` sebagai rujukan: `f:\herd\daftar\DEPLOY.md` §5.7.

---

## Sebelum pejabat menyentuhnya

- [ ] Pejabat menjalankan satu cabutan penuh **sendiri** — agen sudah melakukannya
      dalam pelayar (2026-10-14, "Pameran Ujian Kedua"), tetapi bukan pengguna sebenar
- [ ] Pangkalan data dev `undilot` mengandungi data ujian agen (dua cabutan,
      syarikat `@ujian.test`, satu pengumuman, satu halaman footer, satu pautan
      sosial). Tanya pemilik sebelum `migrate:fresh` — ia juga memadam apa yang
      pemilik tambah sendiri

---

## Diketahui, belum dibuat — dengan sengaja

- Senarai Acara memaparkan kiraan pendaftaran **semua** zon kepada admin negeri.
  Satu baris untuk menskopkannya, jika ditanya
- Padam baris kraf yang **tidak** digunakan tidak diaudit (`KrafBidang` tiada
  `Auditable`). Tambah bila seseorang bertanya siapa memadam sesuatu kod
- Bahasa bercampur: halaman lot dan slip PDF dalam Inggeris (disalin dari
  `daftar`), dashboard dan halaman awam dalam Melayu. Satukan bila pejabat memilih
- Palet kalendar ialah pemalar — tiada skrin untuk mengubahnya
- Projek `mobile-chrome` Playwright `daftar` tidak dibawa; suite hanya Chromium desktop

---

## Siap

| Fasa | Commit | Ringkas |
|---|---|---|
| 1 | `141d023` | Dua guard, skema dalam satu migration, layout dipacu data |
| 2 | `4cb0127`, `9f59709` | Model, acara, syarikat + akaun, senarai cabutan, Settings |
| 3 | `18d482e`–`7fc57ec` | `LotAllocator`, `PollingGuard`, skrin cabutan, Confirm peserta, plot painter |
| 4 | `539467f`–`5f72e6a` | PDF slip, scheduler, pengumuman, audit trail, laporan CSV, kalendar, halaman awam, Playwright |
| Semak | `dfe65b2` | Cabutan penuh dalam pelayar; 242 lajur sama pada MySQL dan SQLite, tiada `enum` |

**Berbeza daripada `daftar`, dengan sengaja** (sebab penuh dalam mesej commit):

- Skop negeri: tiada peraturan *own-state* — admin negeri melihat setiap acara
  bukan draf, kerana pejabat yang mengisi senarai cabutan
- Kod dipadankan sebagai **string** (`event_registrations.code` = `kraf_bidangs.code`)
- Peruntukan dan cat plot menerima **kod** sahaja, bukan kategori
- Setiap lokasi menamakan saiz untuk plot lebihannya (`daftar` membinanya tanpa
  saiz — tidak pernah boleh dimenangi)
- `LotUnavailable::noAccount()` — syarikat tanpa akaun ditolak dengan nama, bukan
  menggagalkan seluruh larian pukal
- `completed` ditolak selagi ada yang belum mendapat lot
- Satu `AnnouncementNotification` menggantikan tiga salinan
- Ikon sosial SVG ditolak; IC 12 digit disimpan bersengkang
- Zon waktu `Asia/Kuala_Lumpur`
- Dashboard peserta menyenaraikan cabutan walaupun tarikhnya belum ditetapkan

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
| `daftar` | Kekal di mesin ini dan di GitHub. Tidak disentuh |

### Ditinggalkan daripada `daftar`, dengan sengaja

Imej produk + moderasi · Katalog awam · Dokumen pendaftaran · Bayaran dan
pengecualian · Premis syarikat · Profil syarikat pengguna (sijil, staf, OKU,
anugerah, media sosial, bank) · Survey · FAQ · PDF pendaftaran · Kalendar peserta
· Pintu `/auto` ber-flag untuk pelayan

Semuanya masih ada dalam `daftar` kalau diperlukan kemudian.
