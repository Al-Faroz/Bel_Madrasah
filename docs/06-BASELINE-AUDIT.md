# Fase A — Audit Baseline

**Status:** audit statis kode utama, navigasi, formulir POST, skema database, dan CSS telah diperluas. Entry point Public-first serta Admin shell/dashboard pilot sudah diimplementasikan di repository, tetapi belum diuji di runtime Windows. Konfigurasi paket PHP Desktop yang benar-benar terpasang tidak disertakan dalam repository, sehingga pemeriksaan pembatasan fungsi runtime belum dapat dituntaskan.  
**Tanggal:** 2026-10-13  
**Metode:** pembacaan file pada default branch repository. Tidak menjalankan PHP Desktop/Windows dari lingkungan ini.

## 1. Hasil audit awal

### Fondasi runtime dan database

- `prototype/phpdesktop/www/lib/Database.php` adalah pusat akses SQLite; database utama ditempatkan di `%APPDATA%/BelMadrasah/data/bel-madrasah.sqlite`, dengan fallback ke `%LOCALAPPDATA%`.
- Kelas database membuat skema dan menjalankan migrasi saat inisialisasi. Perubahan skema harus ditinjau hati-hati karena constructor dipakai oleh banyak halaman.
- Saat audit awal, `index.php` berisi pemeriksaan runtime dan tes persistensi. Pada batch implementasi pertama, isi pemeriksaan dipertahankan di `diagnostics.php` dan `index.php` diarahkan ke `public.php`; perilaku ini masih perlu dikonfirmasi pada runtime PHP Desktop Windows.
- Tidak boleh menghapus database diagnostik maupun database utama hanya untuk merapikan alur.

### Halaman yang teridentifikasi

- `dashboard.php`: dashboard admin dengan ringkasan paket, kegiatan, dan tautan ke modul.
- `packages.php`: daftar, buat/edit, aktivasi paket dan pilihan hari libur.
- `schedule-editor.php`: aturan hari, kegiatan bel, serta alur preview/konfirmasi salin seluruh pola dan kegiatan antarhari.
- `settings.php`: nama sekolah, operator, dan preferensi volume.
- `backup.php`: pembuatan backup database ke folder AppData.
- `public.php`: dashboard baca-saja yang menyusun jadwal berdasarkan aturan Normal; memberi peringatan jika aturan Khusus ada.
- `assets/app.css`: satu stylesheet sekitar 65 KB dengan beberapa blok gaya yang ditambahkan bertahap. Perlu audit selector duplikat, aturan yang saling menimpa, dan konsistensi komponen sebelum redesign.

## 2. Temuan dan risiko yang perlu ditangani

1. **Entry point sudah diubah di kode, belum diuji di runtime.** `index.php` mengarahkan ke `public.php`; pemeriksaan teknis tersedia di `diagnostics.php`. Pastikan redirect relatif bekerja di PHP Desktop Windows.
2. **CSS tumbuh secara aditif.** Gaya dari beberapa batch ditambahkan di bagian bawah stylesheet; hal ini berisiko menghasilkan override dan perbedaan visual antarlaman. Jangan menambah patch kosmetik lagi sebelum inventarisasi selector dan token.
3. **Navigasi tidak seragam.** Urutan item menu berbeda antara beberapa halaman (contoh settings/backup menempatkan Pemeriksaan lebih awal daripada dashboard/packages). Perlu satu partial/template navigasi bersama atau strategi render bersama.
4. **Resolver kalender sudah diimplementasikan, tetapi belum diuji runtime.** `ScheduleResolver.php` menghitung pasaran Jawa dan memilih aturan Normal/Khusus untuk tanggal tertentu. Satu aturan Khusus yang cocok menggantikan Normal; konflik beberapa aturan Khusus memakai fallback Normal dan menghasilkan peringatan. Dashboard dan Publik sudah memakai resolver ini, tetapi scheduler/audio otomatis belum menggunakannya.
5. **Status scheduler/audio belum nyata.** Label yang menjelaskan jadwal tersimpan bukan status pemutaran aktual harus dipertahankan sampai mesin scheduler mengirim status runtime.
6. **Backup/Restore sudah diimplementasikan, tetapi belum diuji runtime.** Halaman menyediakan pratinjau file backup, pemeriksaan integritas SQLite dan struktur tabel, konfirmasi eksplisit, serta backup pra-pemulihan. Mekanisme copy/rollback tetap berisiko sampai diuji pada PHP Desktop Windows dengan database uji.
7. **Aktivasi paket dan edit paket memerlukan regresi data.** Pastikan constraint satu paket aktif dan perilaku hari awal tetap sesuai, tanpa perubahan data diam-diam.
8. **Salin jadwal berisiko overwrite.** Implementasi terbaru mengganti seluruh aturan Normal/Khusus dan kegiatan pada hari tujuan melalui pratinjau dan konfirmasi. Tetap harus diuji dengan data dummy untuk memastikan tidak ada perubahan sebelum konfirmasi, paket/hari lain tetap utuh, dan referensi audio dipertahankan.
9. **Validasi keamanan/ketahanan belum diaudit penuh.** Periksa CSRF untuk form POST, otorisasi lokal yang relevan untuk aplikasi desktop, validasi ID milik paket yang dipilih, penanganan error, dan escaping output pada seluruh halaman.
10. **Tidak ada klaim lulus runtime untuk UI terbaru.** Screenshot pengguna menunjukkan layout tidak sesuai ekspektasi. Semua redesign berikutnya perlu uji screenshot pada PHP Desktop yang sebenarnya.

## 3. Keputusan yang dikunci

- PoC PHP Desktop Chrome 130.1 + PHP 8.3 dan SQLite tidak diulang tanpa alasan teknis.
- Lokasi data AppData, file database, dan data pengguna dipertahankan.
- Fitur lama menjadi kandidat reuse setelah audit, bukan dianggap otomatis final.
- RoyalUI adalah referensi visual, bukan dependensi remote runtime.
- Satu halaman/modul dikerjakan dalam satu batch dan diuji sebelum lanjut.
- Jangan memperbaiki tampilan dengan menambahkan lapisan CSS tanpa menghapus/merapikan aturan lama yang berkonflik.

## 4. Temuan audit: audio dan aset

- `bell_events.audio_path` menyimpan path absolut.
- `Database::getAudioStorageDirectory()` dan `Database::storeUploadedAudio()` masih ada sebagai helper lama, tetapi Editor Jadwal tidak lagi menggunakan input upload HTML.
- Folder aset baru dibuat di `%APPDATA%/BelMadrasah/assets/audio` dan `%APPDATA%/BelMadrasah/assets/logo`, dengan fallback ke LocalAppData. Daftar audio dibatasi ke MP3/WAV/OGG/M4A/AAC; logo menerima PNG/JPG/JPEG/WEBP/GIF/SVG.
- Editor Jadwal menyimpan path absolut hasil resolusi nama file yang dipilih dari daftar aset. Resolusi menolak path traversal dan hanya mengembalikan file yang ditemukan di folder audio aset. Referensi lama tetap dipertahankan jika operator tidak menggantinya; opsi hapus hanya mengosongkan referensi DB dan tidak menghapus file.
- Salin jadwal tetap menyalin referensi `audio_path`, tidak menggandakan file.
- Konfigurasi menyimpan referensi logo pada key `school_logo_path`; header Admin dan Publik membaca gambar lokal melalui `lib/AssetHelpers.php` sebagai data URI. Ini belum diuji pada PHP Desktop Windows.

### Kontrak aset dan status implementasi
- Folder persisten `BelMadrasah/assets/audio` dan `BelMadrasah/assets/logo` telah diimplementasikan dengan nama file bebas dan petunjuk copy-paste di halaman Konfigurasi.
- Karena bridge/native file dialog dari paket PHP Desktop yang terpasang belum diverifikasi, Editor Jadwal memilih audio dari daftar file yang ditemukan di folder aset; tidak menyimpan fakepath dari HTML file picker.
- Referensi audio lama dipertahankan. Perubahan pilihan audio hanya mengubah path pada kegiatan yang disimpan; file sumber tidak dipindah, diganti nama, atau dihapus.
- Lokasi folder dapat disalin dari UI dan ditempel ke File Explorer. Tombol Buka Folder juga sudah diimplementasikan dengan pembuka Explorer berbasis `exec` pada Windows dan fallback salin lokasi; keberhasilannya pada PHP Desktop belum diverifikasi.
- Pemilihan dan tampilan logo kini disimpan melalui `school_logo_path` dan digunakan pada header Admin/Publik. Rendering, clipboard, dan pemilihan audio/logo masih perlu pengujian pada PHP Desktop Windows.

### Audit lanjutan
- [ ] Periksa konfigurasi runtime untuk URL awal dan kemungkinan native file dialog/bridge.
- [ ] Audit seluruh POST action untuk CSRF, validasi ID, escaping, transaksi, dan error handling.
- [ ] Inventarisasi CSS selector duplikat dan navigasi.
- [ ] Audit backup dan rancang restore dengan backup pra-pemulihan.
- [ ] Tetapkan spesifikasi resolver kalender, pasaran Jawa, minggu kalender, scheduler, dan status audio.
- [ ] Siapkan matriks uji runtime Windows dan screenshot baseline.

## 5. Status fase

**Fase A belum dinyatakan selesai sepenuhnya.** Audit awal menemukan area risiko di atas, tetapi pemeriksaan penuh semua method, selector, alur error, dan uji runtime masih harus dilakukan. Langkah berikutnya adalah menuntaskan audit kontrak database dan form/action, lalu mengunci keputusan spesifikasi sebelum membuat ulang app shell.


## 6. Snapshot audit repository — 2026-10-13

### Revisi yang diperiksa

- Branch: `main`
- HEAD saat audit: `6d8f9dc1faf3a9a78f0a715f37a04b9d197163c4` — `docs: update audit status after public-first entry implementation`
- Pohon repository yang diperiksa berisi 25 entri; tidak ditemukan `settings.json`, `php.ini`, executable PHP Desktop, maupun konfigurasi runtime lain. README PoC menjelaskan bahwa paket runtime diunduh terpisah dan isi `www/` disalin ke paket itu.
- File sumber di bawah dibaca dari branch `main`; SHA blob dicatat sebagai baseline untuk perubahan berikutnya.

| File | SHA saat audit |
|---|---|
| `docs/00-PROJECT-OVERVIEW.md` | `751bb769328a050bf52514d1576d7b2332030b25` |
| `docs/03-IMPLEMENTATION-PLAN.md` | `6350d60337212041bf186d50e33b3669c01477b9` |
| `docs/05-UI-UX-MATERIAL-DESIGN.md` | `48d1580c33603f3e2d04224362b161bc587b7159` |
| `prototype/phpdesktop/README.md` | `7aaee54d31d7fdbe53d661516b5942cabdffad6a` |
| `prototype/phpdesktop/www/index.php` | `18ccfb4a081a0384ab2cb5f0d8e7622e352efc0d` |
| `prototype/phpdesktop/www/public.php` | `64d53bf46bddb7a74ae5d7b1c0bcc015913eb984` |
| `prototype/phpdesktop/www/diagnostics.php` | `f10ee6bc0ed25751f6ac383a220e53a68215017d` |
| `prototype/phpdesktop/www/dashboard.php` | `c5ee0d252a32fbdba87327a5c372e33935650ffe` |
| `prototype/phpdesktop/www/packages.php` | `1b435b7b766f90a34b6890ad5b22d77e9d62c1c8` |
| `prototype/phpdesktop/www/schedule-editor.php` | `fdc95bb11a3c5765dcd2b10c9e041ad550673ea0` |
| `prototype/phpdesktop/www/settings.php` | `3d716063fa95b2219736d92fa26399d1e02100bd` |
| `prototype/phpdesktop/www/backup.php` | `bd71d8d4736472ddcd3872c2ec97edfcbbba8f78` |
| `prototype/phpdesktop/www/lib/Database.php` | `1635ba81dadf33458a4ff0b73d3c9092827c669c` |
| `prototype/phpdesktop/www/assets/app.css` | `c0c07c10655b5fdc3258838514504114f1d6cf7d` |

### Entry point dan runtime

- `index.php` berisi redirect HTTP 302 relatif ke `public.php`; ini menghindari URL absolut dan sesuai prinsip offline, tetapi tetap perlu diuji di paket PHP Desktop yang dipakai.
- `public.php` menggunakan `Database.php` dan `assets/app.css`; data publik saat ini mengumpulkan aturan Normal untuk hari ini dan memberi peringatan bila ada aturan Khusus. Ini belum resolver kalender final.
- `dashboard.php` juga membaca aturan Normal untuk ringkasan kegiatan berikutnya; ia dengan benar memberi keterangan bahwa data tersebut bukan bukti audio sedang diputar.
- Semua halaman masih memakai satu stylesheet. Public dan Admin belum dipisahkan secara fisik dari sisi CSS/JS.

### PHP Desktop dan pemilihan file

- Dokumentasi resmi PHP Desktop: [Settings](https://github.com/cztomczak/phpdesktop/wiki/Settings), [Chrome settings](https://github.com/cztomczak/phpdesktop/wiki/Chrome-settings), dan [Javascript API](https://github.com/cztomczak/phpdesktop/wiki/Javascript-API).
- API JavaScript yang didokumentasikan mencakup `GetVersion()`, `ToggleFullscreen()`, dan `IsFullscreen()`; tidak ada API yang didokumentasikan untuk mengembalikan path penuh hasil pemilihan file. Dialog file browser dan nilai `C:\\fakepath\\...` tidak memenuhi kontrak referensi path.
- Karena konfigurasi runtime yang benar-benar dipakai tidak ada di repo, status `disable_functions`, izin menjalankan Explorer, dan konfigurasi awal `www_directory/index_files` belum dapat diverifikasi.
- Keputusan aman untuk implementasi awal: file aset disalin operator ke folder aset persisten lalu dipilih dari daftar hasil pemindaian direktori. Jangan mengandalkan `$_FILES` untuk mendapatkan path sumber asli, dan jangan menyimpan fakepath. Mekanisme membuka folder Explorer perlu menguji kemampuan runtime sebelum dipakai sebagai fitur wajib.

### Database dan kompatibilitas

- `Database::SCHEMA_VERSION` saat ini bernilai 2. Migrasi kolom `schedule_packages.off_days` dilakukan secara aditif; foreign key SQLite diaktifkan dan query data utama memakai prepared statements.
- Lokasi database tetap `%APPDATA%/BelMadrasah/data/bel-madrasah.sqlite` dengan fallback ke LocalAppData. Lokasi ini tidak boleh diubah dalam batch layout.
- `bell_events.audio_path` adalah path absolut. `getAudioStorageDirectory()` menunjuk `%APPDATA%/BelMadrasah/audio`; `storeUploadedAudio()` menyalin unggahan dengan nama token acak, batas 50 MB, dan ekstensi MP3/WAV/OGG/M4A/AAC.
- Ini belum sama dengan workflow aset baru `BelMadrasah/assets/audio` dan `BelMadrasah/assets/logo`. Logo belum memiliki workflow di halaman yang diperiksa. Migrasi aset harus aditif dan mempertahankan semua path lama; jangan memindahkan atau menghapus file audio yang sudah ada.
- Perlu ditinjau pada batch Paket Jadwal: `ensureDefaultSchedulePackage()` tidak mengisi `off_days` secara eksplisit sehingga paket pertama mengikuti default skema `6,7` (Sabtu–Minggu), sedangkan pilihan default form paket baru adalah `7` (Minggu). Perbaikan nantinya harus mengubah default untuk paket baru tanpa mengubah data paket yang sudah tersimpan.
- Form Edit Paket menyajikan `off_days` sebagai hidden/read-only di UI, tetapi backend `saveSchedulePackage()` tetap menerima nilai yang diposting. Validasi server perlu menjaga keputusan produk bahwa pengeditan nama/keterangan tidak diam-diam mengubah hari libur paket.

### Form POST, validasi, dan backup

- Handler POST yang diperiksa: `diagnostics.php` (tes persistensi), `settings.php` (konfigurasi), `packages.php` (simpan/aktifkan paket), `schedule-editor.php` (aturan, kegiatan, preview/konfirmasi salin), dan `backup.php` (buat backup).
- Form POST yang diperiksa belum menggunakan token CSRF. Tidak adanya login memang keputusan produk, tetapi tidak otomatis menghilangkan risiko request lintas situs terhadap server localhost; perlindungan request perlu dirancang sebagai hardening terpisah.
- `schedule-editor.php` dan method terkait di `Database.php` memvalidasi kepemilikan paket/aturan/kegiatan untuk sebagian besar aksi dan salin jadwal menggunakan transaksi. Tetap lakukan uji regresi sumber/tujuan dan kasus ID salah sebelum menganggap aman.
- Output dinamis pada halaman yang diperiksa umumnya di-escape dengan `htmlspecialchars` melalui helper `h()`. Beberapa halaman menampilkan pesan exception mentah yang sudah di-escape; pesan ini dapat mengungkap detail path/driver dan perlu dinilai saat hardening.
- `createBackup()` menggunakan `VACUUM INTO`, menolak tujuan yang sudah ada, lalu hanya memeriksa file ada dan tidak kosong. Belum ada pemeriksaan `PRAGMA integrity_check`, alur restore, maupun backup pra-restore. Jangan menambahkan restore sebelum rancangan pemulihan aman tersedia.

### Audit CSS dan struktur Admin

- `assets/app.css` memiliki 912 baris dan sekitar 65 KB. Pemindaian statis menemukan selector berulang dalam blok tahap lama dan blok UI baru; contoh paling sering adalah `.package-card`, `.app-shell`, `.main-nav a`, `.package-switch-form`, `.event-form-grid`, dan `.copy-schedule-route`. Sebagian pengulangan berada di media query, tetapi urutan override tetap perlu diuji.
- Header, navigasi, dan footer masih ditulis ulang di beberapa halaman. Urutan tautan navigasi tidak konsisten di semua halaman. Public tidak memakai shell Admin, sebagaimana diinginkan.
- Batch berikut yang aman adalah membuat shell Admin dan partial PHP native secara terpisah, lalu memigrasikan `dashboard.php` sebagai halaman pilot. Halaman paket/editor/settings/backup/diagnostik tetap menggunakan markup lama sampai masing-masing dipindahkan dan diregresi satu per satu.

## 7. Status checklist Fase A

- [x] Membaca dokumen acuan terkini dan memverifikasi HEAD branch `main`.
- [x] Memeriksa entry point, Public, diagnostik, dashboard, paket, editor, konfigurasi, backup, Database, dan stylesheet.
- [x] Memeriksa apakah konfigurasi runtime PHP Desktop ada di repository; tidak ditemukan.
- [x] Meninjau schema/migrasi, penyimpanan path audio, validasi ID, handler POST, backup, dan struktur navigasi.
- [x] Menginventarisasi selector CSS yang berulang.
- [ ] Memeriksa `settings.json` dan `php.ini` dari paket PHP Desktop yang benar-benar terpasang pada Windows.
- [ ] Menyelesaikan spesifikasi resolver kalender, pasaran Jawa, minggu kalender, scheduler, dan status pemutaran aktual.
- [ ] Menetapkan desain restore aman beserta backup pra-restore dan pemeriksaan integritas.
- [ ] Melakukan uji runtime Windows dan merekam screenshot pada ukuran jendela yang disepakati.

**Kesimpulan:** audit statis sudah cukup untuk memulai shell Admin secara terisolasi, tetapi Fase A tidak boleh diberi status selesai penuh sampai konfigurasi runtime aktual diperiksa dan kebutuhan resolver/restore ditetapkan. Tidak ada pengujian Windows yang dilakukan dalam audit ini.


## 8. Checkpoint batch D1 — shell Admin dan dashboard pilot

**Commit implementasi:** [`448a0d2`](https://github.com/Al-Faroz/Bel_Madrasah/commit/448a0d275084f57805fefba2024db2b01329c77a) — `refactor: introduce shared admin shell and dashboard view`.

### File yang berubah

- Ditambahkan `prototype/phpdesktop/www/layouts/admin.php` sebagai pemilik dokumen HTML, stylesheet lokal, shell, area main, dan urutan include.
- Ditambahkan `partials/admin-header.php`, `partials/admin-nav.php`, dan `partials/admin-footer.php` untuk komponen bersama. Navigasi memiliki satu daftar tautan dan penanda halaman aktif.
- Ditambahkan `views/admin/dashboard.php`; isi dashboard operasional dipindahkan dari markup halaman lama tanpa mengubah perhitungan paket/kegiatan atau pesan batasan scheduler.
- `dashboard.php` kini bertindak sebagai controller tipis: menghitung data seperti sebelumnya, mengatur metadata halaman, lalu memanggil layout dengan view internal.
- Layout memeriksa bahwa view hasil `realpath()` berada di bawah `views/admin/`; path view tidak diambil dari request pengguna.

### Batas batch

- Tidak mengubah schema SQLite, lokasi AppData, path audio, alur paket/editor, backup, public dashboard, atau diagnostik.
- Tidak mengubah `assets/app.css`; shell menggunakan selector yang sudah ada. Konsolidasi CSS tetap menjadi batch terpisah, bukan ditambal di sini.
- Halaman `packages.php`, `schedule-editor.php`, `settings.php`, `backup.php`, dan `diagnostics.php` belum dimigrasikan ke layout bersama. Migrasikan satu per satu setelah checkpoint ini.

### Pemeriksaan yang dilakukan

- `php -l` pada keenam file PHP yang diubah/ditambahkan: lulus dengan PHP CLI 8.4.24 pada lingkungan kerja ini.
- Smoke test layout memeriksa title, navigasi aktif, pemanggilan view, footer, dan stylesheet: lulus.
- Smoke test dashboard dengan adapter Database palsu memeriksa bahwa controller, layout, dan view terhubung serta merender data contoh: lulus.
- Pemeriksaan di atas **bukan** pengujian runtime PHP Desktop Windows, bukan uji database SQLite asli, dan bukan bukti kompatibilitas visual pada ukuran jendela target.

### Checkpoint berikutnya

Minta pengujian pada paket PHP Desktop Chrome 130.1 + PHP 8.3 yang sebenarnya: buka aplikasi melalui `index.php`, masuk ke Dashboard Admin, periksa tautan navigasi dan jam, lalu pastikan tidak ada halaman kosong/error. Jangan migrasikan modul berikutnya sebelum hasil checkpoint ini dicatat.

## 7. Status implementasi lanjutan — 2026-10-13

- Admin shell, Dashboard, Paket Jadwal, Editor Jadwal, Konfigurasi/Aset, Backup/Restore, dan Diagnostik kini memakai layout bersama pada halaman yang sudah dimigrasikan.
- Asset path: folder Audio/Logo berada di AppData, daftar audio dipakai oleh Editor Jadwal, referensi logo disimpan di `app_settings` dan ditampilkan pada header Admin/Publik. Referensi audio lama tidak dihapus.
- Resolver kalender: `lib/ScheduleResolver.php` kini menjadi sumber bersama bagi Dashboard dan Publik. Anchor 17 Agustus 1945 = Jumat Legi diverifikasi terhadap kalender Jawa daring. Implementasi belum diuji dengan fixture SQLite atau runtime Windows.
- Backup/Restore: restore memvalidasi `PRAGMA integrity_check`, tabel wajib, dan versi skema; membuat backup pra-pemulihan; lalu mengganti file database dan mencoba rollback jika gagal.
- Seluruh perubahan di atas masih berstatus **IMPLEMENTED**. Audit ini tidak menjalankan PHP Desktop, tidak membuka database SQLite milik pengguna, dan tidak membuktikan bahwa operasi restore/copy, clipboard, pemilihan audio, atau logo berhasil pada Windows.
- CSS statis diaudit: 65.279 karakter / 912 baris, 683 blok aturan sederhana, 450 selector unik, 155 selector berulang. Gaya responsif tambahan hanya menyasar komponen baru; konsolidasi selector lama ditahan sampai ada screenshot regresi karena sebagian pengulangan berasal dari media query/override.
- Risiko prioritas: uji pemulihan dengan file valid/rusak, pemeriksaan koneksi SQLite setelah restore, regresi data jadwal/audio, konsistensi navigasi window PHP Desktop, audit keamanan POST/CSRF, dan konsolidasi CSS berdasarkan konteks media query.

## Update audit UI Admin — 2026-10-14

### Hasil pemeriksaan acuan RoyalUI dan perubahan shell

- Template resmi RoyalUI diperiksa pada struktur partial sidebar dan navbar. Pola yang dipakai sebagai acuan adalah navigasi sidebar, topbar terpisah, indikator halaman aktif, serta konten dashboard berbasis panel; dependensi template tidak dipasang dari CDN.
- prototype/phpdesktop/www/layouts/admin.php sekarang memakai grid shell Admin: sidebar navigasi dan workspace terpisah. Layout public tidak diubah.
- prototype/phpdesktop/www/partials/admin-nav.php menyatukan urutan navigasi Admin dan state halaman aktif; ikon navigasi menggunakan SVG inline lokal, tanpa font/icon remote.
- prototype/phpdesktop/www/assets/admin.css ditambahkan sebagai stylesheet khusus Admin dan dimuat setelah app.css. Tujuannya membatasi redesign visual Admin agar tidak mengubah tampilan public.
- Ikon metrik di view Dashboard diganti dari karakter Unicode menjadi SVG inline.
- Commit implementasi: 4829b768, df42b40d, 189e1ef8, 38361830, d181cbbd, 02a1f267, 2d99d002 (status tombol destructive/sekunder).

### Status yang benar

- **IMPLEMENTED (kode repository):** shell sidebar/topbar, navigasi aktif, stylesheet Admin lokal, ikon SVG.
- **BELUM DIVERIFIKASI:** screenshot setelah perubahan, resize 1366/1024/390 px, klik navigasi seluruh halaman, overflow setiap modul, serta kompatibilitas runtime PHP Desktop Chrome 130.1 pada Windows.
- Screenshot pengguna yang sebelumnya memperlihatkan navigasi horizontal adalah baseline sebelum perubahan shell ini; screenshot tersebut tidak membuktikan tampilan terbaru.
- Tidak ada klaim LOCAL TEST PASS untuk perubahan UI ini. Pengujian harus dilakukan pada build PHP Desktop target dan hasilnya dicatat setelah ada bukti aktual.

## Audit UI dan alur aset — 2026-10-14

- Acuan RoyalUI resmi diperiksa pada partial sidebar dan navbar. Implementasi lokal mengambil pola sidebar/topbar/active state, bukan memuat dependensi eksternal.
- Admin sekarang mempunyai stylesheet khusus assets/admin.css; halaman public tetap menggunakan stylesheet aplikasi bersama dengan selector public yang terscope. Ikon navigasi, metrik dashboard, diagnostik audio, dan ringkasan public memakai SVG inline lokal.
- Judul topbar per halaman dibedakan: Dashboard, Paket Jadwal, Editor Jadwal, Konfigurasi, Backup & Restore, dan Pemeriksaan.
- Tombol buka folder aset ditambahkan di Konfigurasi. Path folder berasal dari method Database dan tidak berasal dari nilai POST; hanya nama aksi audio/logo yang diterima. Jika fungsi exec tidak tersedia, UI menampilkan fallback salin lokasi folder.
- Saat Konfigurasi dibuka, getAudioAssetsDirectory() dan getLogoAssetsDirectory() menyiapkan subfolder aset jika belum ada. Daftar audio/logo dibaca dari folder tersebut dan pilihan menyimpan referensi path di SQLite; file tidak disalin oleh aplikasi.
- CSS statis dibaca ulang setelah perubahan. Pemeriksaan keseimbangan kurung CSS memberi hasil seimbang untuk app.css dan admin.css. Ini bukan pengganti PHP lint, rendering browser, atau uji runtime Windows.
- **Belum diuji:** tombol Buka Folder pada PHP Desktop; clipboard; rendering logo; dropdown audio/logo setelah copy-paste file; form paket/editor/backup; resize jendela; dan navigasi penuh.
- **Tidak ada klaim lulus runtime.** Bukti visual berikutnya harus berupa screenshot baru dari PHP Desktop setelah pengguna menarik perubahan terbaru.

## Pemeriksaan lanjutan navigasi CSS — 2026-10-14

- Sebelum shell sidebar, app.css memiliki dua kelompok aturan navigasi horizontal dan beberapa aturan breakpoint untuk selector .main-nav. Setelah navigasi dipusatkan pada partial admin-nav.php dan styling sidebar berada di admin.css, seluruh aturan .main-nav lama di app.css dihapus.
- Pencarian string pada app.css sesudah commit tidak menemukan selector .main-nav. Gaya navigasi Admin sekarang dikelola satu tempat di admin.css, sehingga aturan horizontal lama tidak lagi menumpuk dengan sidebar.
- Ukuran app.css sesudah penghapusan: 65.780 karakter. Ini belum merupakan konsolidasi seluruh CSS; selector lain yang berulang masih perlu diaudit secara kontekstual.
- Commit: [821e3b3](https://github.com/Al-Faroz/Bel_Madrasah/commit/821e3b38ca5909ab2d346a988da4ad979b6a543f).

## Konsolidasi selector judul halaman — 2026-10-14

- Setelah seluruh modul Admin memakai layout bersama, selector .page-intro lama di app.css tidak lagi menjadi sumber gaya yang diperlukan. Aturan tersebut dipindahkan secara efektif ke admin.css yang terscope pada area Admin, termasuk breakpoint sempit.
- Sesudah perubahan, app.css tidak memiliki selector .page-intro. Ini mengurangi benturan antara aturan UI lama dan sistem visual Admin baru.
- Commit: [ffe4bee](https://github.com/Al-Faroz/Bel_Madrasah/commit/ffe4bee21238ba0174e9ae760c36d9a81f9ba9d3).
- Konsolidasi selector lain masih terbuka; jangan menyatukan aturan media-query hanya berdasarkan nama selector tanpa membandingkan konteks dan perilaku.

## Audit duplikasi CSS — 2026-10-14

- Parser statis sederhana mengidentifikasi aturan yang benar-benar identik berdasarkan selector, konteks media/at-rule, dan isi deklarasi.
- Tiga duplikasi identik di app.css dihapus: aturan html dasar, padding app-shell pada breakpoint 760 px, dan ukuran h1 pada breakpoint 460 px.
- Hasil pemeriksaan setelah penghapusan: app.css 65.437 karakter, 682 aturan terdeteksi, 0 kelompok duplikasi identik, 0 selector .main-nav, dan 0 selector .page-intro. admin.css 19.760 karakter, 171 aturan terdeteksi, 0 kelompok duplikasi identik, tanpa URL eksternal pada kedua stylesheet.
- Semua tujuh tujuan href di partial navigasi Admin cocok dengan file PHP yang ada pada branch main.
- Commit pembersihan: [65d6e9c](https://github.com/Al-Faroz/Bel_Madrasah/commit/65d6e9cca771a6d2384f86c3aebc89053d6c1f4c).
- Ini tetap audit statis, bukan lint PHP, render visual, atau uji PHP Desktop Windows. Selector dengan deklarasi berbeda masih memerlukan penilaian kontekstual.

## Koreksi panduan Editor Jadwal — 2026-10-14

- Label “Catatan penting Tahap 5” dan pesan yang menyatakan resolver konflik akan dibuat pada Tahap 6 sudah tidak sesuai keadaan kode, karena ScheduleResolver telah tersedia.
- Teks view diperbarui untuk menjelaskan perilaku saat ini: konflik aturan Khusus ditandai resolver dan memakai fallback Normal; scheduler/pemutaran otomatis belum aktif.
- Perubahan teks saja; tidak mengubah resolver, aturan database, atau operasi jadwal.
- Commit: [a8b9ac1](https://github.com/Al-Faroz/Bel_Madrasah/commit/a8b9ac1f384c1a1a7cafb1ca0124483e90d25caf).


## Audit lanjutan Editor Jadwal — 2026-10-14

- Pemeriksaan statis menemukan bug sintaks pada `prototype/phpdesktop/www/views/admin/settings.php`: pembuka PHP kedua muncul sebelum blok PHP pertama ditutup. Tag pembuka duplikat dihapus pada commit [b008608](https://github.com/Al-Faroz/Bel_Madrasah/commit/b008608b31b026b6e2108750c74e263abf66eaa2). Ini membuktikan bahwa audit sebelumnya belum setara dengan PHP lint; seluruh view tetap perlu lint di runtime/CLI yang sesuai.
- Audit alur **Salin Jadwal** menemukan ketidaksesuaian: UI menyatakan seluruh jadwal tujuan diganti, tetapi implementasi lama hanya mengganti kegiatan pada aturan Normal Selasa dan membiarkan aturan Khusus tetap ada.
- Metode Database kini menyalin seluruh aturan Normal/Khusus dan kegiatan dari hari sumber ke hari tujuan dalam satu transaksi. Semua aturan/kegiatan lama pada hari tujuan diganti; paket, hari sumber, dan hari lain tidak diubah. Referensi path audio disalin sebagai referensi, bukan file fisik. Hari sumber yang belum memiliki aturan ditolak sebelum data tujuan dihapus.
- Pratinjau kini menampilkan jumlah aturan dan kegiatan sumber/tujuan, daftar pola aturan sumber, serta pola pada setiap kegiatan. Peringatan menyebutkan bahwa aturan Khusus pada hari tujuan juga akan diganti.
- Commit implementasi: [e7beb21](https://github.com/Al-Faroz/Bel_Madrasah/commit/e7beb218d8c14b6df0411fd90579e36fa13eff19), [fe63d60](https://github.com/Al-Faroz/Bel_Madrasah/commit/fe63d604e98554fc92fee7848e7d267c52d66c80), [7ea6a06](https://github.com/Al-Faroz/Bel_Madrasah/commit/7ea6a063b552e47705b503368a604884c5f9f19), dan [3e7647b](https://github.com/Al-Faroz/Bel_Madrasah/commit/3e7647bc9b73a51745c8820c35028ed3f3c9b6f3).
- **Status: IMPLEMENTED, belum LOCAL TEST PASS.** Belum dijalankan melalui PHP lint atau PHP Desktop Windows, dan belum ada regresi SQLite yang membuktikan rollback, penggantian aturan Khusus, referensi audio, serta isolasi paket/hari lain. Jangan menganggap operasi salin aman dipakai sebelum uji tersebut lulus.


## Pengelolaan Paket Jadwal — 2026-10-14

- Form Edit Paket kini menampilkan tiga pilihan hari libur mingguan yang sama dengan form Buat Paket: Jumat, Minggu, dan Sabtu–Minggu. Nilai dikirim ke `saveSchedulePackage()` dan disimpan ke kolom `schedule_packages.off_days`.
- Perubahan hari libur pada paket yang sudah memiliki jadwal tidak menghapus atau memindahkan aturan/kegiatan secara otomatis. UI memberi peringatan untuk memeriksa Editor Jadwal sesudah perubahan. Ini sengaja menjaga data jadwal yang telah dikonfigurasi dari penghapusan diam-diam.
- Ditambahkan aksi **Hapus permanen (hard delete)** untuk paket nonaktif. Database menghapus baris paket; foreign key `ON DELETE CASCADE` menghapus aturan hari dan kegiatan bel yang terkait. Riwayat `playback_log` dipertahankan dengan referensi paket/kegiatan menjadi `NULL` sesuai skema.
- Proteksi server menolak hard delete paket aktif, paket terakhir, dan ID yang tidak valid/tidak ditemukan. UI meminta konfirmasi eksplisit dengan istilah HARD DELETE PERMANEN. File audio fisik tidak disentuh oleh operasi ini.
- Implementasi: [05eeb0d](https://github.com/Al-Faroz/Bel_Madrasah/commit/05eeb0df09624bcc3c41af1d97f0f6a32a5b5dbe), [f85803d](https://github.com/Al-Faroz/Bel_Madrasah/commit/f85803dc495ed36c7accd96e52a63f292cb4d298), [3353cd6](https://github.com/Al-Faroz/Bel_Madrasah/commit/3353cd6f906fba70021b148c6a95c91adbeb8369).
- **Status: IMPLEMENTED, belum LOCAL TEST PASS.** Belum diuji dengan database SQLite aktual, foreign-key cascade, proteksi paket aktif/terakhir, duplicate nama, atau runtime PHP Desktop Windows.

## 8. Follow-up — aktivasi paket dan Editor Jadwal (2026-10-15)

Perbaikan kode yang sudah masuk ke branch utama:

- Aktivasi paket kini diverifikasi kembali dari database setelah operasi selesai; dialog konfirmasi JavaScript yang sebelumnya dapat membatalkan submit dihapus karena pergantian paket tidak menghapus data.
- Salin Jadwal kini menyediakan pilihan hari sumber dan tujuan, mengirim konteks paket pada URL dan POST, serta mempertahankan pilihan hari saat pratinjau/konfirmasi. Perintah salin tetap mengganti seluruh aturan dan kegiatan hari tujuan, bukan hanya kegiatan dari aturan Normal.
- Kolom tabel kegiatan diselaraskan: input jam berada di kolom Jam, nama kegiatan di Keterangan bel, urutan di kolom tersendiri, lalu Audio, Status, dan Aksi.
- Daftar Pola yang Tersimpan kini mendapat ruang lebar penuh agar tabel dapat dibaca; pada layar sempit tabel memakai scroll horizontal.
- Tombol Edit Aturan diganti menjadi Ubah Pola Hari, disertai penjelasan bahwa tabel mengedit kegiatan bel, sedangkan formulir pola mengedit Normal/Khusus, atribut khusus, dan status aturan.

### Status verifikasi

**Kode diperbarui dan diperiksa secara statis; belum LOCAL TEST PASS.** Pemeriksaan statis memastikan handler aktivasi memiliki verifikasi pasca-simpan, form salin memiliki pilihan hari, kolom tabel sesuai dengan kontrol input, dan CSS lebar sudah tersedia. Ini bukan bukti bahwa tombol sudah berhasil di PHP Desktop Windows. Masih wajib diuji langsung:

1. Aktifkan paket nonaktif, muat ulang halaman, dan pastikan status aktif berubah serta halaman Public membaca paket baru.
2. Pratinjau salin untuk dua hari berbeda, batalkan, lalu ulangi dan konfirmasi; periksa seluruh aturan/kegiatan hari tujuan dan pastikan hari sumber serta hari lain tidak berubah.
3. Edit jam, nama, urutan, audio, dan status satu kegiatan; simpan dan buka ulang halaman untuk memastikan persistensi.
4. Uji lebar tabel pada jendela desktop normal dan jendela yang dipersempit.
5. Ubah pola Normal/Khusus melalui Ubah Pola Hari, simpan, dan pastikan perubahan tidak mengubah data kegiatan bel yang ada.

### Koreksi Salin Jadwal — hasil tampak kosong setelah salin

Audit lanjutan menemukan bahwa setelah POST konfirmasi, nilai hari pada formulir aturan tetap kembali ke nilai awal (Senin). Skrip filter daftar pola mengikuti nilai formulir tersebut, sehingga hari tujuan yang baru disalin dapat tersembunyi walaupun data telah tersimpan. Controller kini mengarahkan nilai hari formulir/filter ke hari tujuan dan menyebutkan hari tujuan pada pesan hasil.

Fungsi database juga menghitung ulang aturan serta kegiatan pada hari tujuan sebelum commit. Jika jumlah aturan atau kegiatan tidak sama dengan sumber, transaksi dibatalkan agar salinan parsial tidak dianggap berhasil.

**Status:** perubahan sudah masuk ke branch utama dan diperiksa secara statis; tetap belum LOCAL TEST PASS sampai alur salin dijalankan di PHP Desktop Windows.


## Regresi terkonfirmasi: Pemeriksaan memanggil metode database yang tidak ada — 2026-10-26

**Temuan terkonfirmasi:** `prototype/phpdesktop/www/diagnostics.php` memanggil `$appDatabase->getTables()`, tetapi kelas `Database` tidak mendeklarasikan metode publik `getTables()`. Akibatnya, setelah constructor database dan pembacaan versi skema berjalan, pemeriksaan melempar `Call to undefined method Database::getTables()`, menangkapnya sebagai kegagalan database, lalu menampilkan status FAIL. Pesan FAIL ini adalah **negatif palsu dari halaman Pemeriksaan**; pesan tersebut sendiri tidak membuktikan bahwa koneksi SQLite atau skema database gagal.

Metode pemanggilan itu sudah ada sejak halaman `diagnostics.php` ditambahkan pada 2026-10-11; perubahan audio terbaru pada `scheduler.php` dan `audio-stream.php` tidak mengubah halaman Pemeriksaan atau kelas `Database`. Jadi ini adalah cacat kontrak yang sudah tertanam dan baru terungkap saat pemeriksaan tersebut dijalankan, bukan bukti bahwa pengujian audio menghapus atau merusak database. Perbaikan kode kini diterapkan: `Database::getTables()` membaca nama tabel aplikasi dari `sqlite_master` dan mengecualikan tabel internal SQLite. Tes regresi `tests/DatabaseTablesTest.php` ditambahkan untuk memastikan enam tabel inti tersedia. Tes tersebut masih perlu dijalankan pada PHP CLI lokal dan pemeriksaan halaman perlu dikonfirmasi di PHP Desktop Windows.

**Temuan terpisah — autoplay:** `assets/scheduler.js` memang memetakan penolakan `player.play()` oleh kebijakan autoplay menjadi pesan “Browser menolak autoplay”. Ini masalah terpisah dari `getTables()`. Konfigurasi runtime PHP Desktop yang benar-benar dijalankan tidak disimpan dalam repo, sehingga kebijakan autoplay belum dapat dipastikan hanya dari source code. Jangan menyimpulkan kegagalan audio disebabkan file rusak, dan jangan mengubah file audio/database untuk menanganinya.

**Batas bukti:** audit source code tidak dapat memastikan apakah folder `www` yang sedang berjalan di komputer Windows identik dengan `main` repo. Verifikasi runtime dilakukan setelah cacat kontrak di atas diperbaiki; tidak boleh menghapus atau membuat ulang `%APPDATA%\\BelMadrasah` sebagai langkah diagnosis.

## Audit zona waktu aplikasi — 2026-10-26

- Pemeriksaan kode memastikan `prototype/phpdesktop/www/lib/Database.php` menetapkan `date_default_timezone_set('Asia/Jakarta')` saat file dimuat. Entry point `public.php` dan `scheduler.php` memuat file tersebut sebelum membuat `DateTimeImmutable('now')`; karena itu, hipotesis perbedaan zona waktu PHP dan jam WIB tidak terbukti pada source saat ini.
- Tidak ada perubahan perilaku zona waktu yang diperlukan. Jangan menambahkan setelan duplikat atau mengubah tanggal/jam penyimpanan yang sudah ada.
- Celah yang tersisa adalah tidak adanya tes CLI khusus yang mengunci kontrak ini. Tes regresi akan memulai proses dengan zona waktu `UTC`, memuat `Database.php`, lalu memastikan zona waktu aplikasi kembali menjadi `Asia/Jakarta` dan objek waktu baru menggunakan zona tersebut.
- Tes ini hanya mengunci konfigurasi waktu pada source; uji pergantian tanggal, jam WIB, pasaran, dan pemicu bel tetap perlu dilakukan pada runtime PHP Desktop Windows.

## Audit perlindungan formulir POST — 2026-10-26

- Audit statis pada controller dan view `diagnostics.php`, `packages.php`, `schedule-editor.php`, `settings.php`, dan `backup.php` menemukan formulir POST yang belum membawa token CSRF. Tidak adanya login merupakan keputusan produk, tetapi tidak dengan sendirinya melindungi endpoint localhost dari pengiriman formulir lintas situs.
- Batch hardening menambahkan token CSRF berbasis sesi untuk formulir POST pada lima modul tersebut, termasuk aksi diagnostik, aktivasi/hapus paket, edit jadwal, pengaturan aset, serta backup/restore. Ini tidak menambahkan login dan tidak mengubah skema atau lokasi database.
- Endpoint `scheduler.php` dan `audio-stream.php` memiliki pemeriksaan `Sec-Fetch-Site`/Origin tersendiri dan tidak menggunakan formulir HTML. Perlindungan endpoint scheduler/audio tetap menjadi pemeriksaan terpisah; jangan menghapus pemeriksaan same-origin yang sudah ada.
- Kode keamanan dan tes regresinya harus ditinjau bersama seluruh formulir POST, karena satu form yang lupa menyertakan token dapat menyebabkan aksi gagal setelah hardening.

### Checkpoint implementasi CSRF — 2026-10-26

- Ditambahkan `prototype/phpdesktop/www/lib/Security.php` untuk membuat token acak 256-bit di sesi PHP, membandingkan token dengan `hash_equals`, dan menolak POST tanpa token yang benar menggunakan HTTP 403.
- Token dipasang pada semua formulir POST di lima view: Pemeriksaan (1), Paket Jadwal (3), Editor Jadwal (7), Konfigurasi (3), dan Backup & Restore (3). Form GET untuk memilih paket tidak membutuhkan token.
- Lima controller terkait memanggil `enforceCsrfForPost()` sebelum memproses permintaan. Tidak ada login baru, perubahan skema, penghapusan data, atau perubahan lokasi AppData.
- Ditambahkan `tests/CsrfProtectionTest.php` dan `tests/ApplicationTimezoneTest.php`. Pemeriksaan statis mengonfirmasi setiap formulir POST pada view yang diaudit memiliki field token dan setiap controller memanggil helper keamanan.
- **Status: IMPLEMENTED, menunggu tes pada checkout repo dan runtime PHP Desktop Windows.** Uji helper CSRF terisolasi pada PHP CLI 8.4 lulus, tetapi itu bukan pengganti menjalankan file test yang ada di checkout pengguna atau menguji sesi browser PHP Desktop.


## Audit same-origin endpoint scheduler — 2026-10-26

- Pemeriksaan statis menemukan `scheduler.php` sudah menolak `Sec-Fetch-Site: cross-site` dan memeriksa `Origin`, tetapi perbandingan `Origin` hanya memakai hostname.
- Akibatnya, origin dengan hostname sama tetapi port berbeda dapat lolos pemeriksaan, walaupun port berbeda berarti origin berbeda. Pemeriksaan juga perlu menangani permintaan tanpa header Origin secara eksplisit agar hanya permintaan browser same-origin yang diterima oleh endpoint internal.
- Endpoint scheduler bukan sekadar baca: aksi `page_start`/`finish` mengubah log pemutaran dan `tick` dapat mencatat status `missed`, `failed`, atau `skipped`. Karena itu, pemeriksaan same-origin harus ketat tanpa menambahkan autentikasi/login.
- Tindak lanjut: pindahkan pemeriksaan ke helper murni yang bisa diuji, bandingkan skema + host + port, tolak Fetch Metadata selain `same-origin`, dan tambahkan tes regresi. Perilaku browser PHP Desktop Windows tetap perlu smoke test setelah perubahan.


## Hasil tindak lanjut same-origin scheduler — 2026-10-26

- `lib/Security.php` kini menyediakan `isSameOriginRequest()` yang memvalidasi skema, hostname, port, dan Fetch Metadata. Jika Origin tidak tersedia, Fetch Metadata harus secara eksplisit bernilai `same-origin`.
- `scheduler.php` menggunakan helper tersebut dan menolak permintaan yang tidak same-origin dengan HTTP 403. Tidak ada login/autentikasi baru, perubahan skema, atau perubahan data jadwal.
- `tests/SchedulerSameOriginTest.php` mencakup host/port/skema berbeda, Fetch Metadata lintas situs, Origin hilang, dan Origin malformed.
- Audit view menghitung 17 formulir POST pada Pemeriksaan, Paket Jadwal, Editor Jadwal, Konfigurasi, dan Backup & Restore; semuanya memiliki field `_csrf_token`. Form filter GET Editor Jadwal tidak memerlukan token.
- PHP CLI 8.4.24: lint dan tes CSRF serta same-origin **PASS** pada salinan terisolasi dari source yang diperiksa. Checkout repository aktual dan runtime PHP Desktop Windows belum dieksekusi dari lingkungan audit ini; hasil tersebut belum membuktikan smoke test browser.


## Temuan urutan bootstrap timezone — 2026-10-26

- `Database.php` sudah mengunci timezone aplikasi ke `Asia/Jakarta` saat file dimuat. Namun, `diagnostics.php` sebelumnya memuat `Database.php` di tengah alur setelah pemeriksaan persistensi; aksi simpan diagnostik dapat memanggil `date()` lebih dahulu.
- Jika timezone bawaan PHP Desktop bukan `Asia/Jakarta`, timestamp untuk data diagnostik berpotensi menggunakan zona waktu bawaan runtime. Ini bukan perubahan pada data jadwal utama, tetapi tetap inkonsisten dengan kontrak waktu aplikasi.
- Tindak lanjut: muat `Database.php` pada awal `diagnostics.php` sebelum operasi tanggal/waktu dan tambahkan pemeriksaan urutan bootstrap. Jangan mengubah lokasi atau isi database diagnostik.


## Hasil tindak lanjut timezone Diagnostik — 2026-10-26

- `diagnostics.php` sekarang memuat `lib/Database.php` di awal request, sebelum aksi penyimpanan diagnostik memanggil `date()`. Require lama di tengah blok pemeriksaan dihapus agar bootstrap tidak bergantung pada jalur kode yang tercapai.
- `tests/ApplicationTimezoneTest.php` kini memeriksa juga urutan bootstrap tersebut, selain memastikan pemuatan `Database.php` menetapkan `Asia/Jakarta`.
- Pemeriksaan source memastikan include berada sebelum pemanggilan timestamp diagnostik. Tes timezone pada harness terisolasi lulus; eksekusi tes terhadap checkout lengkap dan uji runtime PHP Desktop Windows tetap menjadi checkpoint pengguna.


## Audit keselamatan Backup & Restore — 2026-10-26

- Pemeriksaan statis Database.php menunjukkan backup memakai VACUUM INTO, sumber restore dibatasi ke file .sqlite di folder backup terkelola, dan sumber divalidasi dengan PRAGMA integrity_check, tabel minimum, serta versi skema sebelum restore.
- Restore membuat backup pra-pemulihan sebelum mengganti file database aktif. Jika penggantian/reinisialisasi gagal, kode mencoba mengembalikan salinan pra-pemulihan. Alur ini berisiko tinggi dan perlu tes regresi otomatis, bukan hanya pemeriksaan manual UI.
- Ditambahkan tests/BackupRestoreSafetyTest.php untuk memeriksa integritas backup yang dibuat aplikasi, pemulihan data, isi backup pra-pemulihan, penolakan file rusak, penolakan file di luar folder backup, dan penolakan menimpa nama backup yang sudah ada.
- Tes menggunakan APPDATA sementara dan membersihkan fixture setelah selesai; tidak mengarah ke database Bel Madrasah pengguna.
- Status tes otomatis: belum dijalankan pada checkout lokal pengguna saat perubahan dibuat. Tes harus dijalankan terlebih dahulu sebelum smoke test Restore pada PHP Desktop Windows.
