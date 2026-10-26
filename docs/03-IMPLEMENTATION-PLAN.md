# 03 — Roadmap Pengerjaan Baru

## Aturan status

- **DOC READY:** kebutuhan dan urutan kerja terdokumentasi.
- **AUDIT:** kode dan dependensi diperiksa, belum berarti benar.
- **IMPLEMENTED:** kode tersedia di repository.
- **LOCAL TEST PASS:** pengguna telah menjalankan skenario pada PHP Desktop dan melaporkan hasilnya.
- PoC yang pernah PASS hanya membuktikan skenario PoC terkait; tidak otomatis meluluskan fitur produksi.

## Keputusan reset

Tahap 1–4 sebelumnya adalah PoC/fondasi untuk membuktikan PHP Desktop Chrome 130.1 + PHP 8.3, SQLite, audio internal, konfigurasi/backup dasar, dan paket jadwal bisa berjalan. Hasil itu dipertahankan sebagai bukti teknis. Roadmap produk dimulai kembali dari Fase A, tanpa mengulang eksperimen yang sudah terbukti dan tanpa menganggap seluruh UI/fitur lama sudah benar.

## Keputusan arsitektur yang dikunci

- Public area menjadi entry point aplikasi; pemeriksaan teknis bukan beranda.
- Dashboard admin diakses langsung dari public area; tidak ada login/Auth.
- Gunakan PHP native layouts/partial views melalui require, pisahkan layout public dan admin.
- Public punya CSS/JS terpisah jika dibutuhkan.
- Folder aset persisten berisi subfolder audio dan logo. Operator dapat membuka folder dari aplikasi dan copy-paste file; nama file bebas.
- Aplikasi harus menyediakan pemilihan aset yang benar-benar menunjuk file di folder aset, menyimpan referensi persisten di SQLite, dan mendeteksi file yang hilang.
- Native browser file picker tidak boleh dianggap memberi path lokal penuh. Audit bridge PHP Desktop terlebih dahulu; bila tidak tersedia, pilih dari daftar file yang ditemukan di folder aset.
- Pertahankan database dan referensi path lama. Migrasi harus aditif dan tidak menghapus aset lama.

## Roadmap eksekusi yang diperbarui

### Fase A — Audit dan kontrak teknis
1. Audit entry point/runtime config PHP Desktop.
2. Audit seluruh method Database, schema/migrasi, form POST, validasi, backup, dan referensi audio.
3. Audit CSS/JS serta navigasi; catat baseline sebelum redesign.
4. Tetapkan kontrak path aset dan kompatibilitas path audio lama.
**Kriteria selesai:** tidak ada asumsi kritis yang belum diperiksa; risiko dan keputusan tercatat di audit.

### Fase B — Fondasi arsitektur
1. Buat entry point public tanpa menghapus halaman diagnostik.
2. Susun layout public/admin dan partial PHP native.
3. Susun CSS tokens/komponen bersama; hilangkan CSS aditif yang berkonflik secara terukur.
4. Sediakan struktur folder aset persisten dan cara membuka lokasinya dari UI.
**Kriteria selesai:** navigasi, include path, lokasi data, dan kontrak aset konsisten.

### Fase C — Public area
1. Tampilan jadwal publik sederhana dan terbaca.
2. Akses jelas ke Dashboard Admin.
3. Informasi jadwal tidak disamakan dengan status audio real-time.
**Kriteria selesai:** entry point mengarah ke public, tanpa menu admin yang tidak relevan.

### Fase D — Admin shell dan modul
Urutan: D1 app shell/partial; D2 Dashboard; D3 Paket Jadwal; D4 Editor Jadwal; D5 Konfigurasi/aset; D6 Backup/Restore; D7 Diagnostik; D8 polish dan responsivitas.
Setiap modul diselesaikan, direview, dan diuji terpisah sebelum modul berikutnya.

### Fase E — Mesin operasional dan rilis
Resolver kalender Normal/Khusus → scheduler/audio dan log status → restore aman → hardening → paket distribusi dan panduan operator.

### Aturan kerja
- Satu batch memiliki satu hasil yang jelas dan commit terpisah.
- Jangan menambah patch CSS sebelum selector terkait diaudit.
- Jangan menyebut fitur “lulus” hanya karena kodenya tersedia.
- Pengguna menguji runtime PHP Desktop pada Windows; hasilnya dicatat sebagai LOCAL TEST PASS hanya setelah dilaporkan.
- Jangan mereset database, memindahkan AppData, atau menghapus data/file lama demi merapikan struktur.



## Status checkpoint — 2026-10-13

- **Fase A / audit statis:** diperluas dan dicatat di `docs/06-BASELINE-AUDIT.md`. Konfigurasi runtime PHP Desktop yang benar-benar terpasang, pengujian Windows, resolver kalender, dan desain restore masih terbuka.
- **Fase B / D1 Admin shell:** kode layout dan partial bersama sudah diimplementasikan pada commit [`448a0d2`](https://github.com/Al-Faroz/Bel_Madrasah/commit/448a0d275084f57805fefba2024db2b01329c77a).
- **Fase D / Dashboard Admin pilot:** markup dashboard dipisah menjadi view dan menggunakan layout baru. Statusnya **IMPLEMENTED**, belum **LOCAL TEST PASS**.
- Pemeriksaan PHP CLI dan smoke test memakai data simulasi lulus pada lingkungan kerja, tetapi tidak menggantikan pengujian PHP Desktop Windows dengan database SQLite asli.
- **Checkpoint wajib:** uji dashboard pada runtime target terlebih dahulu. Setelah hasil dicatat, lanjutkan migrasi satu modul per batch; modul lain belum dianggap menggunakan layout baru.

## Checkpoint lanjutan — 2026-10-13

- **Navigasi Admin → Publik:** ditemukan satu tautan dashboard yang masih memakai `target="_blank"`; atribut tersebut diubah menjadi `target="_self"` pada commit [`d14c614`](https://github.com/Al-Faroz/Bel_Madrasah/commit/d14c614e10fed10e74ffd52156257e0f3a65d98e). Pemeriksaan pencarian kode tidak menemukan lagi `target="_blank"` pada branch utama. Perilaku runtime PHP Desktop tetap perlu diverifikasi.
- **D3 Paket Jadwal:** controller `packages.php` kini menyiapkan data dan operasi lalu menggunakan layout bersama. Markup dipindahkan ke `views/admin/packages.php`; navigasi dan footer berasal dari partial/layout Admin.
- Perubahan D3 hanya refaktor struktur presentasi. Logika penyimpanan, aktivasi, pembuatan paket, pilihan hari libur, dan data SQLite dipertahankan. Tidak ada operasi penghapusan atau migrasi data.
- Commit D3: [`03f0920`](https://github.com/Al-Faroz/Bel_Madrasah/commit/03f09206c8d98010cb0757927f991350eec49b29) menambahkan view; [`99203a6`](https://github.com/Al-Faroz/Bel_Madrasah/commit/99203a6708f3b8f2e6e0752716c2edde3a612696) memigrasikan controller.
- Status: **IMPLEMENTED**, belum **LOCAL TEST PASS**. Pengguna meminta pengerjaan dilanjutkan tanpa menunggu uji dashboard; karena itu pekerjaan berlanjut, tetapi pengujian pada PHP Desktop Windows tetap tercatat sebagai belum dilakukan.
- **Batch berikutnya:** D4 — migrasi Editor Jadwal ke layout Admin bersama, dengan pemeriksaan khusus agar seluruh alur edit/penyimpanan tetap utuh.

## Checkpoint D4 — Editor Jadwal memakai layout bersama (2026-10-13)

- Controller `schedule-editor.php` sekarang memproses data dan aksi lalu memanggil `layouts/admin.php`.
- Markup halaman dipisahkan ke `views/admin/schedule-editor.php`; header, navigasi, dan footer berasal dari partial Admin bersama.
- Commit view: [`b148b44`](https://github.com/Al-Faroz/Bel_Madrasah/commit/b148b446830ddebcf8449968f836406f6e643147).
- Commit controller/layout: [`fda7c51`](https://github.com/Al-Faroz/Bel_Madrasah/commit/fda7c51a7d46a19c569c81f26e113888dc3c9d6e).
- Pemeriksaan struktur statis memastikan controller Editor Jadwal tidak lagi merender dokumen HTML sendiri dan view tidak lagi membawa header/nav/footer duplikat.
- Status **IMPLEMENTED**, belum **LOCAL TEST PASS**. Belum ada pengujian PHP Desktop Windows atau verifikasi interaksi formulir setelah refaktor.
- **Catatan pekerjaan berikutnya:** Editor Jadwal masih memiliki input upload audio lama. Itu belum diubah dalam refaktor ini; harus diselaraskan dengan keputusan aset persisten (operator copy-paste file ke folder aset lalu memilih file dari daftar, nama file bebas) pada batch Konfigurasi/Aset. Jangan menganggap alur audio baru sudah selesai.
- **Batch berikutnya:** D5 — audit/migrasi Konfigurasi dan Aset, termasuk kontrak folder persisten dan cara memilih audio/logo tanpa menghapus atau mengganti referensi path lama.

## Checkpoint D5 — Konfigurasi dan aset persisten (2026-10-13)

- Halaman Konfigurasi kini menggunakan layout Admin bersama; preferensi sekolah/operator/volume tetap disimpan melalui `app_settings`.
- Folder persisten dibuat di `%APPDATA%/BelMadrasah/assets/audio` dan `%APPDATA%/BelMadrasah/assets/logo` (fallback `%LOCALAPPDATA%`). UI menampilkan lokasi folder, jumlah file yang dikenali, petunjuk copy-paste ke File Explorer, dan tombol salin lokasi.
- Editor Jadwal kini memilih audio dari daftar file di folder aset. Tidak ada lagi input upload HTML pada formulir kegiatan. File audio tidak digandakan; database menyimpan path absolut. Referensi audio lama dipertahankan kecuali operator memilih file aset baru atau menghapus referensinya secara eksplisit.
- Pengaturan logo disimpan sebagai key `school_logo_path` di tabel key-value `app_settings`; tidak diperlukan perubahan skema tabel. Logo yang dipilih ditampilkan di header Admin dan Publik melalui data URI lokal yang dibatasi pada tipe gambar yang dikenal dan ukuran maksimal 5 MB.
- Commit terkait: [`4cbb1b9`](https://github.com/Al-Faroz/Bel_Madrasah/commit/4cbb1b930578793df0a37f159a61bddf3616e0df), [`3bf871d`](https://github.com/Al-Faroz/Bel_Madrasah/commit/3bf871d0def751e589bfa4ba72175e5b2b518827), [`23c61c0`](https://github.com/Al-Faroz/Bel_Madrasah/commit/23c61c0fff18b1f1325e482ca15ba2fc4aa1c0d4), [`bfa3e38`](https://github.com/Al-Faroz/Bel_Madrasah/commit/bfa3e38d998facea9c0f779b654bbd45131bbe1a), [`f74cad0`](https://github.com/Al-Faroz/Bel_Madrasah/commit/f74cad0ef82055a85e878be612a291e62f64eb47), [`389d3a3`](https://github.com/Al-Faroz/Bel_Madrasah/commit/389d3a3b3ca1c97a0cfc9d9a8d76002e767a052f), [`fc8d4ed`](https://github.com/Al-Faroz/Bel_Madrasah/commit/fc8d4ed03e5a83178375fce0819ecbba08eed658), [`24be67c`](https://github.com/Al-Faroz/Bel_Madrasah/commit/24be67cae16a4b987b859d0e0e124cfe0b2e8039), [`f685df1`](https://github.com/Al-Faroz/Bel_Madrasah/commit/f685df138b0c44f35fa7859ff426f3aaadb3143f), [`75ab969`](https://github.com/Al-Faroz/Bel_Madrasah/commit/75ab9693f6270c7a0ca89d4e7cc0f993723988b0), [`29684c8`](https://github.com/Al-Faroz/Bel_Madrasah/commit/29684c8c98fbd8f61ae501c0740c21702c72281b), [`da617c1`](https://github.com/Al-Faroz/Bel_Madrasah/commit/da617c1d54aac07bc75631756e70d6595db54a02), [`1180de9`](https://github.com/Al-Faroz/Bel_Madrasah/commit/1180de9cdc4140e5fd2bb1ab3fcfe8154cb6e62f), [`216e86f`](https://github.com/Al-Faroz/Bel_Madrasah/commit/216e86f8f112cb70833f9d4a2101f2fe019037e9).
- Status **IMPLEMENTED**, belum **LOCAL TEST PASS**. Belum diuji pada PHP Desktop Windows; belum ada klaim bahwa clipboard, rendering logo, atau pemilihan audio sudah lulus runtime.
- Pada checkpoint 2026-10-13, folder dibuka manual melalui salin path karena bridge/native shell belum diverifikasi. Perubahan 2026-10-14 menambahkan tombol pembuka File Explorer berbasis fungsi exec Windows dengan fallback salin path; hasil runtime masih belum diverifikasi.
- **Batch berikutnya:** D6 — audit dan migrasi Backup/Restore. Sebelum restore diimplementasikan, rancang validasi integritas SQLite dan backup pra-pemulihan; jangan menimpa database aktif tanpa pratinjau/konfirmasi.

## Checkpoint D6 — Backup dan Restore (2026-10-13)

- Halaman Backup kini memakai layout Admin bersama dan menampilkan database aktif, lokasi backup, daftar file SQLite, ukuran, serta waktu modifikasi.
- Restore menggunakan dua langkah: **Periksa & pratinjau** (validasi integritas `PRAGMA integrity_check`, tabel wajib, dan versi skema), lalu konfirmasi eksplisit sebelum pemulihan.
- Sebelum mengganti database aktif, `Database::restoreFromBackup()` membuat backup pra-pemulihan. Jika penggantian atau inisialisasi gagal, metode mencoba mengembalikan file pra-pemulihan dan membuka ulang koneksi.
- Daftar restore hanya memuat file `.sqlite` yang berada langsung di folder backup aplikasi. File dari luar dapat disalin ke folder tersebut; file yang bukan database Bel Madrasah akan ditolak pada pemeriksaan.
- Commit: [`10877bc`](https://github.com/Al-Faroz/Bel_Madrasah/commit/10877bc772ef9d2117055f4851a396798662fdf5), [`2be0719`](https://github.com/Al-Faroz/Bel_Madrasah/commit/2be07196c7c4ac99237b09092f9958396e0f815d), [`296f776`](https://github.com/Al-Faroz/Bel_Madrasah/commit/296f7766fd2f75dd90f4b5f5e6057b5cc09ead70), [`4c98edf`](https://github.com/Al-Faroz/Bel_Madrasah/commit/4c98edf2bdec3367872f0c8d271077715762bc6e).
- Status **IMPLEMENTED**, belum **LOCAL TEST PASS**. Pemulihan database adalah operasi berisiko dan belum diuji dengan SQLite asli di PHP Desktop Windows. Sebelum penggunaan produksi, uji backup valid, file rusak, skema tidak cocok, kegagalan copy, dan pemulihan dari backup pra-pemulihan.
- **Batch berikutnya:** D7 — migrasi Diagnostik ke layout bersama, mempertahankan seluruh pengujian teknis yang sudah ada.

## Checkpoint D7 — Diagnostik memakai layout bersama (2026-10-13)

- Controller `diagnostics.php` tetap menjalankan pemeriksaan runtime, SQLite/persistensi, dan uji audio yang sudah ada; dokumen HTML dan navigasi dipindahkan ke `layouts/admin.php` serta `views/admin/diagnostics.php`.
- Layout memakai koneksi `$appDatabase` yang sudah dibuat halaman diagnostik bila tersedia, agar tidak membuka koneksi aplikasi kedua hanya untuk membaca logo.
- Commit: [`611fb9e`](https://github.com/Al-Faroz/Bel_Madrasah/commit/611fb9e9229feacd6051be38858c2623556b298c), [`b6037bd`](https://github.com/Al-Faroz/Bel_Madrasah/commit/b6037bd08b108492eac880d163801763581a533d), [`cdd8612`](https://github.com/Al-Faroz/Bel_Madrasah/commit/cdd8612b59dd57d6af552c252e52148c52540e42).
- Status **IMPLEMENTED**, belum **LOCAL TEST PASS**. Uji diagnostik dan pemutaran audio harus dijalankan kembali pada PHP Desktop Windows setelah salinan `www/` diperbarui.
- **Batch berikutnya:** D8 — audit CSS yang sudah ada dan perapian responsivitas terukur; hindari menambah override tanpa menghapus/menyatukan aturan yang bertumpuk.

## Checkpoint D8 — Audit CSS dan polish awal (2026-10-13)

- Inventarisasi statis `assets/app.css`: 65,279 karakter / 912 baris, 683 blok aturan sederhana yang terdeteksi, 450 selector unik, dan 155 selector yang muncul lebih dari sekali. Angka ini bukan bukti semua duplikasi salah; beberapa aturan berada di media query atau memang override responsif.
- Karena belum ada screenshot regresi dari runtime Windows, selector lama tidak digabung/dihapus secara otomatis. Pendekatan pertama hanya menambahkan gaya untuk selector baru yang sebelumnya tidak ada: panel lokasi aset, daftar backup, pratinjau restore, dan penyesuaian grid untuk layar sempit.
- Commit: [`ba7df73`](https://github.com/Al-Faroz/Bel_Madrasah/commit/ba7df73497227eda98641203bf648a62ce5d8a82).
- Status **IMPLEMENTED — POLISH AWAL**; D8 belum tuntas. Konsolidasi CSS lama dan verifikasi visual masih menunggu screenshot/runtime test agar aturan responsif yang valid tidak terhapus.
- Setelah baseline screenshot tersedia, lanjutkan audit selector per konteks media query, hilangkan override yang benar-benar redundan, lalu lanjut ke Fase E: resolver kalender → scheduler/audio → hardening dan paket distribusi.

## Checkpoint E1 — Resolver kalender Normal/Khusus (2026-10-13)

- Ditambahkan `lib/ScheduleResolver.php` sebagai resolver bersama untuk tanggal lokal: menghitung pasaran Jawa dari anchor Jumat Legi 17 Agustus 1945, menentukan ordinal kemunculan hari dalam bulan, memilih aturan Normal/Khusus, dan menyusun kegiatan aktif berdasarkan waktu.
- Aturan Khusus menggantikan Normal jika tepat satu aturan cocok. Jika lebih dari satu aturan Khusus cocok pada tanggal yang sama, resolver memilih Normal sebagai fallback dan menandai konflik agar UI dapat memperingatkan operator; resolver tidak menggabungkan dua jadwal khusus secara diam-diam.
- Dashboard Admin dan Tampilan Publik kini menggunakan resolver yang sama serta menampilkan pasaran/pola kalender. Ini baru pemilihan jadwal untuk informasi layar; belum ada scheduler atau pemutaran audio otomatis.
- Anchor pasaran diverifikasi pada kalender daring: 17 Agustus 1945 = Jumat Legi ([ALMNK](https://almnk.com/kalender/masehi/tanggal/17-agustus-1945), [Ki-Demang](https://ki-demang.com/almanak_jawa/konversi/masehi/1945/agustus/17)).
- Commit: [`6216207`](https://github.com/Al-Faroz/Bel_Madrasah/commit/62162075ef0c62068c2c268693a31f85410b0ef8), [`6a271c6`](https://github.com/Al-Faroz/Bel_Madrasah/commit/6a271c63b58d6cd0b2f4c33c2f5fe6429a6f2b7a), [`0703c4e`](https://github.com/Al-Faroz/Bel_Madrasah/commit/0703c4e23ca3ed2e68f773f2188ea050bdf8ef8f), [`067278d`](https://github.com/Al-Faroz/Bel_Madrasah/commit/067278d512b82613be32ae1daf3eb6bebd46ef3f), [`a418914`](https://github.com/Al-Faroz/Bel_Madrasah/commit/a41891435a7038d7473e5be7901674628f5254be), [`e78c021`](https://github.com/Al-Faroz/Bel_Madrasah/commit/e78c021ebd35106f9cee8ce76666abd8d1f68210), [`712649e`](https://github.com/Al-Faroz/Bel_Madrasah/commit/712649ebf7a2fb7cfc7a2072f775062bcd23e4ad), [`4093987`](https://github.com/Al-Faroz/Bel_Madrasah/commit/40939878e66d3b2bd9760348dcfc1d4c89b0b71f), [`7eb478f`](https://github.com/Al-Faroz/Bel_Madrasah/commit/7eb478fba9943c029c980c12f2349025f5959f2b).
- Fixture regresi tersedia di `tests/ScheduleResolverTest.php` untuk anchor pasaran, siklus harian, aturan khusus tunggal, konflik fallback Normal, dan kecocokan Minggu ke-5. Fixture ini belum dijalankan terhadap repo/runtime target.
- Status **IMPLEMENTED**, belum **LOCAL TEST PASS**. Perhitungan tanggal dan konflik harus diuji dengan `php tests/ScheduleResolverTest.php` pada PHP CLI yang memiliki PDO SQLite, lalu di PHP Desktop sebelum scheduler memakai hasil resolver.
- **Batch berikutnya:** E2 — scheduler/audio otomatis dan status operasional, dengan penanganan audio yang hilang, keterlambatan tick, dan pemulihan setelah aplikasi minimisasi/restart.

## Checkpoint UI Admin — 2026-10-14

- Referensi RoyalUI resmi diperiksa langsung pada partial sidebar dan navbar.
- **D1 shell baru: IMPLEMENTED** — sidebar navigasi, topbar workspace, indikator menu aktif, SVG lokal, serta stylesheet Admin terpisah dari public.
- **Status visual/runtime: BELUM DIVERIFIKASI.** Perubahan belum diuji di PHP Desktop Chrome 130.1 pada Windows dan belum ada screenshot pascaperubahan.
- **Tidak boleh menganggap D2–D8 selesai.** Langkah berikutnya tetap memeriksa dashboard hasil shell baru dan menguji seluruh link/layout di runtime target sebelum migrasi visual modul berikutnya.
- Detail temuan dan commit ada di docs/06-BASELINE-AUDIT.md.

## Checkpoint UI Admin lanjutan — 2026-10-14

### Perubahan kode yang sudah masuk repository

- **D1 / shell:** sidebar dan topbar bersama; judul topbar kini menunjukkan halaman yang sedang dibuka, bukan mengulang nama aplikasi di semua halaman.
- **D2 / Dashboard:** hierarki ringkasan, metadata, panel, dan ikon lokal dirapikan.
- **D3 / Paket Jadwal:** intro tidak lagi menjadi kartu besar terpisah; ringkasan paket aktif, badge, dan tombol aksi dibuat lebih konsisten.
- **D4 / Editor Jadwal:** panel aturan, tombol simpan/salin, status konfirmasi, dan tabel kegiatan diberi gaya Admin yang lebih padat. Struktur POST dan operasi database tidak sengaja diubah pada batch styling ini.
- **D5 / Konfigurasi aset:** tombol **Buka Folder Audio** dan **Buka Folder Logo** ditambahkan. Helper hanya menerima path folder aset yang diperoleh dari Database, membuka Explorer di Windows jika fungsi exec tersedia, dan mengembalikan fallback salin path jika tidak dapat menjalankannya. Tombol folder memakai aksi POST tersendiri agar tidak ikut menyimpan/mengubah preferensi konfigurasi.
- **D6 / Backup-restore** dan **D7 / Diagnostik:** panel, badge, path panjang, tabel, dan pesan status dirapikan.
- **Public dashboard:** ikon simbol diganti dengan SVG inline lokal. Tidak ada CDN atau font ikon baru.
- **Pembersihan navigasi CSS:** aturan `.main-nav` lama yang membentuk navigasi horizontal di `app.css` dihapus setelah shell bersama dipastikan menjadi satu-satunya sumber markup navigasi. Gaya sidebar kini dikelola oleh `admin.css`; tidak ada aturan `.main-nav` tersisa di `app.css`.
- **Pembersihan duplikasi CSS:** tiga aturan yang identik dalam konteks media yang sama dihapus dari `app.css` setelah parser statis memeriksa selector, konteks, dan deklarasinya. Audit ini tidak menghapus override berbeda yang mungkin memang diperlukan.
- **Catatan Editor Jadwal:** label fase lama yang menyatakan resolver konflik masih akan dibuat diperbarui agar sesuai kode sekarang; dokumentasi view menjelaskan fallback Normal dan bahwa scheduler/audio otomatis belum aktif. Commit: [a8b9ac1](https://github.com/Al-Faroz/Bel_Madrasah/commit/a8b9ac1f384c1a1a7cafb1ca0124483e90d25caf).
- Commit pembersihan duplikasi: [65d6e9c](https://github.com/Al-Faroz/Bel_Madrasah/commit/65d6e9cca771a6d2384f86c3aebc89053d6c1f4c).
- **Pembersihan page heading CSS:** selector `.page-intro` lama dihapus dari `app.css`; tampilan judul modul kini hanya dimiliki `admin.css`, termasuk breakpoint sempit. Commit: [ffe4bee](https://github.com/Al-Faroz/Bel_Madrasah/commit/ffe4bee21238ba0174e9ae760c36d9a81f9ba9d3).
- **Responsivitas:** menu Admin pada lebar sempit tetap dua kolom; padding judul halaman tidak kembali membesar pada breakpoint 480 px.

### Commit penting

- Shell/navigasi: [02a1f26](https://github.com/Al-Faroz/Bel_Madrasah/commit/02a1f2676af8db15adad8e2819ea606af9309a26)
- Judul halaman dan Dashboard: [dec39f3](https://github.com/Al-Faroz/Bel_Madrasah/commit/dec39f356bdffb3eb940893c2b2cb031f9dad48c)
- Paket Jadwal: [d713d36](https://github.com/Al-Faroz/Bel_Madrasah/commit/d713d368e53c08e4ec7aa2ee326c2cf3ee70cfd6)
- Editor Jadwal: [2c5c11b](https://github.com/Al-Faroz/Bel_Madrasah/commit/2c5c11b76ba12d03853d5ded19cf1dd997fb7333)
- Pembuka folder aset: [0d6b771](https://github.com/Al-Faroz/Bel_Madrasah/commit/0d6b771f06d2f587e3978430db9cb4eefb98b9c1)
- Backup/Diagnostik: [54a39f3](https://github.com/Al-Faroz/Bel_Madrasah/commit/54a39f3d87497686b89299d46676ff2e54c4801f)
- Public icons dan responsivitas: [65b411f](https://github.com/Al-Faroz/Bel_Madrasah/commit/65b411f38abb70399f0353cb7cca857e9db63ca6)
- Hapus CSS navigasi horizontal lama: [821e3b3](https://github.com/Al-Faroz/Bel_Madrasah/commit/821e3b38ca5909ab2d346a988da4ad979b6a543f)

### Status dan batas verifikasi

- **IMPLEMENTED:** perubahan kode di atas tersedia di branch main.
- **Pemeriksaan statis terbatas:** kurung blok CSS seimbang pada app.css dan admin.css; file terkait dibaca ulang dari branch setelah perubahan; selector aksi folder dan fallback salin path ada.
- **BELUM DIVERIFIKASI:** sintaks PHP melalui PHP CLI, pemanggilan File Explorer pada PHP Desktop Windows, perubahan tampilan aktual, navigasi klik, perilaku tombol pada semua modul, dan resize 1366/1024/390 px.
- **Belum LOCAL TEST PASS.** Belum ada screenshot pascaperubahan dari pengguna. D8 belum selesai karena CSS lama masih memiliki banyak selector berulang dan konsolidasi harus dilakukan hati-hati dengan baseline visual, bukan penghapusan otomatis.
- Tidak ada perubahan skema SQLite, migrasi database, path database utama, atau data pengguna dalam batch UI ini.

## Checkpoint lanjutan — 2026-10-17

### Editor Jadwal — audit kode dan regresi filter

- Audit sumber menemukan bahwa setelah kegiatan disimpan/dihapus, filter daftar aturan dapat kembali ke Senin karena nilai hari tidak dipertahankan pada POST tindakan kegiatan. Ini berpotensi membuat hasil yang baru disimpan tampak menghilang meskipun data sudah tersimpan.
- Controller kini mempertahankan hari milik aturan terkait untuk tindakan simpan dan hapus kegiatan, termasuk ketika validasi simpan melempar pesan kesalahan. Commit: [2772582](https://github.com/Al-Faroz/Bel_Madrasah/commit/277258299e358d2b04b7ecd6c2a6c84031025c48).
- Pesan pola awal diperbaiki agar mengikuti pengaturan hari libur paket, bukan selalu menyebut Senin–Jumat. Commit: [e215a29](https://github.com/Al-Faroz/Bel_Madrasah/commit/e215a29e725c8738b1ac2cfa8842d79e84ce1f01).
- Checklist regresi filter editor dan pola awal ditambahkan ke rencana pengujian. Commit: [664cd4d](https://github.com/Al-Faroz/Bel_Madrasah/commit/664cd4dfa6904154d659665371b7870a92ce0b8e), [1a3ccaf](https://github.com/Al-Faroz/Bel_Madrasah/commit/1a3ccaf3e3f9024aba8f58a72fb4845ee5f494fd).
- Status batch ini: **PERBAIKAN KODE TERIMPLEMENTASI; belum diuji ulang oleh pengguna di PHP Desktop Windows.** Ini pemeriksaan dan koreksi sumber, bukan klaim uji runtime.

### Backup & Restore

- Pada 2026-10-17, pengguna melaporkan alur Backup & Restore sudah diterapkan dan dicoba di Windows, **FIX tanpa kendala**. Status alur utama dicatat sebagai **LOCAL TEST PASS berdasarkan laporan pengguna**.
- Skenario kegagalan khusus (file rusak/kosong, skema tidak cocok, folder tidak dapat ditulis, dan rollback) tetap belum dinyatakan PASS satu per satu karena belum ada laporan hasil terpisah.

### Scheduler/Audio — fondasi implementasi (2026-10-17)

- Spesifikasi kontrak scheduler ditambahkan di [07-SCHEDULER-SPEC.md](07-SCHEDULER-SPEC.md): waktu lokal Windows, resolver sebagai sumber jadwal, urutan kegiatan, toleransi keterlambatan 30 detik, deduplikasi kejadian, status log, dan batas operasi saat aplikasi ditutup.
- Ditambahkan endpoint polling `scheduler.php`, yang memilih paket aktif, memakai `ScheduleResolver`, melewatkan kegiatan tanpa audio, mencatat file hilang sebagai gagal, dan mengklaim kejadian satu kali melalui `playback_log`.
- Zona waktu PHP ditetapkan eksplisit ke `Asia/Jakarta` agar jam Public, resolver, dan log tidak bergantung pada nilai `date.timezone` yang belum diketahui dari runtime. Komputer Windows target harus dikonfigurasi WIB; verifikasi tetap masuk checklist.
- Ditambahkan `audio-stream.php`, yang hanya menyalurkan file dari referensi kegiatan di database, memeriksa ekstensi/keberadaan file, dan mendukung permintaan byte-range media.
- Ditambahkan `assets/scheduler.js` pada Public dan layout Admin. Script memeriksa jadwal tiap detik, memutar audio melalui elemen media internal, menerapkan preferensi `default_volume`, memperbarui status log, dan merekonsiliasi pemutaran yang terhenti ketika halaman berpindah.
- Status scheduler ditampilkan di header Public dan footer Admin. Urutan kegiatan pada waktu sama kini memakai `sort_order` sebelum ID sebagai pemecah seri. Konfigurasi volume diperbarui agar sesuai dengan integrasi pemutar.
- Ditambahkan fixture regresi untuk urutan kegiatan waktu sama serta [SchedulerPlaybackLogTest.php](../tests/SchedulerPlaybackLogTest.php) untuk deduplikasi log dan transisi status.
- Commit utama: [8d4a4e5](https://github.com/Al-Faroz/Bel_Madrasah/commit/8d4a4e5fa591b85a9724f2d3ade9ea8a5009f7a0), [f3796b6](https://github.com/Al-Faroz/Bel_Madrasah/commit/f3796b63594e310d518b2e8d7d7a9cbf15b70bfc), [55b5988](https://github.com/Al-Faroz/Bel_Madrasah/commit/55b59889ada273fe2701fe25356e14ade465c15a), [533c908](https://github.com/Al-Faroz/Bel_Madrasah/commit/533c9081577144c06099bc5f2d3a47c17d1ecbfe), [092b0bb](https://github.com/Al-Faroz/Bel_Madrasah/commit/092b0bb0c53e9d38ad33463eb80865dcb484c45a).
- Penguatan lanjutan: log pemutaran memakai pesan tanpa ketergantungan ekstensi mbstring ([7a0cb73](https://github.com/Al-Faroz/Bel_Madrasah/commit/7a0cb7366d561b042da3ea34dd0102f8bb7efe61)); endpoint scheduler/audio menolak permintaan lintas situs ([f03400c](https://github.com/Al-Faroz/Bel_Madrasah/commit/f03400cebb8e580ade3acbd0ca3e4fe719f3fed0), [77d9a0e](https://github.com/Al-Faroz/Bel_Madrasah/commit/77d9a0e031b5f605c2bdb3f5ce2373d49d5f443e)); antrean kegiatan dengan waktu sama tetap berurutan jika audio sebelumnya panjang ([d6020ef](https://github.com/Al-Faroz/Bel_Madrasah/commit/d6020ef4e553b0f97855df2aae2c6b921d69b493), [abb2352](https://github.com/Al-Faroz/Bel_Madrasah/commit/abb23521a1572c0672d3e4db320bbbb7a975ef8a)). Uji regresi antrean ditambahkan di [d1f7916](https://github.com/Al-Faroz/Bel_Madrasah/commit/d1f79162277edf4548ad85bd11330556940622cc).
- Inisialisasi scheduler diwajibkan POST dan status riwayat dibatasi ke tanggal hari ini ([95693ad](https://github.com/Al-Faroz/Bel_Madrasah/commit/95693ad52d6a63b8f951720418349faafcdd8626), [551b3d0](https://github.com/Al-Faroz/Bel_Madrasah/commit/551b3d0d2af9e56f2c873f08fbbae648cc6ddc7d), [1dd47f5](https://github.com/Al-Faroz/Bel_Madrasah/commit/1dd47f5c56f29edc6d8bf977f49870976d8397f6), [e27b15b](https://github.com/Al-Faroz/Bel_Madrasah/commit/e27b15b0bf074dc947068ed1c1b50fe80340f992)).
- **Status: IMPLEMENTED — BELUM LOCAL TEST PASS.** Pemeriksaan ulang file dari repository dan pemeriksaan statis dasar dilakukan, tetapi PHP lint, fixture PHP, autoplay, audio aktual, deduplikasi pada runtime, minimize/restore, dan navigasi belum dijalankan di PHP Desktop Windows. Konfigurasi runtime pengguna tidak ada di repository, sehingga kebijakan autoplay belum dapat dipastikan.
- Sebelum penggunaan operasional, tarik perubahan terbaru, jalankan kedua fixture PHP CLI, lalu lakukan uji audio satu menit ke depan di PHP Desktop. Jangan menganggap status `started`/UI sebagai bukti suara fisik terdengar tanpa pemeriksaan langsung.

## Checkpoint Editor Jadwal — kejelasan status dan alur kerja (2026-10-19)

- Menambahkan panduan visual tiga tingkat status: paket aktif, aturan hari aktif, dan kegiatan bel aktif. Panduan menegaskan bahwa ketiganya harus sesuai agar kegiatan tampil di Publik.
- Kartu aturan kini merangkum jumlah kegiatan aktif dibanding total kegiatan, sehingga operator dapat membedakan status aturan dari status setiap bel tanpa membuka satu per satu.
- Label tabel diperjelas menjadi “Nama kegiatan bel”, “File audio”, dan “Status kegiatan”. Teks bantuan membedakan fungsi edit pola (Normal/Khusus, atribut, status aturan) dari edit kegiatan langsung pada tabel.
- CSS Admin ditambahkan untuk hierarki panduan dan ringkasan status, dengan breakpoint untuk jendela lebih sempit.
- Perubahan ini adalah perbaikan keterbacaan/alur editor; tidak mengubah data SQLite, resolver, scheduler, atau status jadwal yang tersimpan.
- Status: **KODE DIPERBARUI — BELUM DIVERIFIKASI SECARA VISUAL DI PHP DESKTOP WINDOWS**. Pemeriksaan runtime perlu memastikan layout tidak tumpang tindih pada 1366 px, 1024 px, dan jendela sempit; cek juga filter hari, penyimpanan tabel, serta status aturan/kegiatan.

## Perbaikan lanjutan — resolver referensi aset (2026-10-26)

- Bug yang dilaporkan: `schedule-editor.php` memanggil `Database::resolveAudioAsset()`, tetapi metode itu tidak ada di kelas `Database`. Akibatnya penyimpanan kegiatan baru gagal ketika operator memilih audio dari folder aset; masalah ini bukan disebabkan hari berjalan atau penghapusan kegiatan sebelumnya.
- `Database::resolveAudioAsset()` dan `Database::resolveLogoAsset()` dipulihkan. Keduanya mencocokkan nama file dengan daftar aset yang benar-benar tersedia, mengembalikan path penuh, dan menolak input path arbitrer.
- Ditambahkan fixture untuk resolver audio/logo dan penolakan path traversal pada `tests/ScheduleEventValidationTest.php`.
- Commit perbaikan: [aa6553f](https://github.com/Al-Faroz/Bel_Madrasah/commit/aa6553f2a9b6a2c79593935bcd6b4900fa61631c), [5dc5716](https://github.com/Al-Faroz/Bel_Madrasah/commit/5dc57166ddd0ddc1e86fbc39e8c7d8eec826becb), dan [569348b](https://github.com/Al-Faroz/Bel_Madrasah/commit/569348b76ad03194150be09d9436c96454725ddb).
- Status: kode dan fixture sudah masuk ke `main`; eksekusi fixture serta verifikasi PHP Desktop Windows masih menunggu.

## Tahap 2 — Editor Jadwal: waktu unik, perapihan UI, dan regresi (2026-10-24)

- Kontrak waktu kegiatan menggunakan format 24 jam `HH:MM`; satu aturan hari hanya boleh memiliki satu kegiatan pada tiap menit. Validasi berada di lapisan Database agar berlaku untuk tambah maupun edit, bukan hanya mengandalkan form HTML.
- Saat mengedit kegiatan, waktu yang sudah dipakai oleh baris itu sendiri tetap boleh disimpan. Waktu yang dipakai kegiatan lain pada aturan yang sama ditolak dengan pesan yang jelas.
- Data lama tidak dihapus atau diubah otomatis. Jika database sudah memiliki duplikasi waktu, Editor perlu menandainya agar operator dapat memperbaiki satu per satu sebelum mengandalkan jadwal tersebut. Scheduler juga tidak boleh memutar kegiatan pada waktu duplikat: kejadian tersebut dicatat `failed` sampai operator memperbaiki pola. Salin Jadwal menolak sumber yang masih memiliki waktu duplikat sebelum menghapus atau mengganti data tujuan.
- Form pola hari bertanggung jawab pada Normal/Khusus, atribut khusus, dan status aturan. Tabel kegiatan bertanggung jawab pada nama, waktu, urutan tampilan, audio, dan status kegiatan; kedua alur ini tetap dipisahkan.
- Daftar aturan tetap menggunakan lebar konten penuh dan tabel kegiatan dapat digulir horizontal pada jendela sempit. Perubahan CSS harus terscope ke Admin agar tampilan Publik tidak berubah.
- Fixture regresi tidak lagi menganggap beberapa kegiatan pada menit yang sama sebagai konfigurasi yang didukung. Tes mencakup penolakan waktu duplikat saat tambah/edit, tetap mengizinkan edit baris dengan waktunya sendiri, dan pemrosesan jadwal berbeda waktu setelah kegagalan.
- Status implementasi dan status uji runtime dicatat terpisah. Pemeriksaan kode tidak menggantikan pengujian PHP Desktop Windows oleh pengguna.
- Implementasi Tahap 2: validasi waktu unik berlaku saat tambah/edit, salin sumber duplikat diblokir sebelum target disentuh, dan scheduler mencatat kegiatan aktif yang duplikat sebagai `failed` tanpa memutar audio. Jalur antrean menit-sama lama dihapus dari runtime dan fixture regresi.

### Hasil implementasi Tahap 2 — 2026-10-24

- Commit validasi Database, peringatan Editor, dan fixture awal: [ce62008](https://github.com/Al-Faroz/Bel_Madrasah/commit/ce62008b898beadfbec78fc348f9c0e52dd4645d).
- Commit proteksi Salin Jadwal dan pratinjau sumber duplikat: [e9fddb9](https://github.com/Al-Faroz/Bel_Madrasah/commit/e9fddb9385d68f115060f69ce40f32435eca3949).
- Commit deteksi waktu duplikat pada resolver/scheduler dan penghapusan jalur antrean menit-sama: [7ef01dd](https://github.com/Al-Faroz/Bel_Madrasah/commit/7ef01dd75004018f63be2318b9813a44144f3c01).
- Fixture `tests/ScheduleEventValidationTest.php` memeriksa penolakan duplikat saat tambah/edit, tetap mengizinkan waktu sama pada pola Normal/Khusus yang berbeda, dan memastikan salin dari sumber lama yang duplikat tidak mengubah hari tujuan.
- Pemeriksaan statis setelah commit memastikan referensi jalur antrean lama sudah tidak ada pada file yang diperiksa dan kurung kurawal CSS seimbang. **PHP lint dan fixture belum dijalankan**: lingkungan pemeriksaan ini tidak menyediakan PDO SQLite, dan runtime PHP Desktop Windows pengguna tidak dapat diuji dari sini.
- Sebelum status batch dinyatakan PASS, jalankan `php tests/ScheduleEventValidationTest.php`, `php tests/ScheduleResolverTest.php`, dan `php tests/SchedulerPlaybackLogTest.php` dengan PHP CLI yang memiliki PDO SQLite; kemudian uji Editor, Salin Jadwal, serta status scheduler di PHP Desktop Windows. Status penerimaan pengguna tetap belum PASS sampai hasil aktual dilaporkan.

## Perbaikan lanjutan — deteksi stream audio terpotong (2026-10-26)

- Audit penanganan kegagalan audio menemukan celah kecil pada `audio-stream.php`: jika `fread()` mengembalikan chunk terakhir yang tidak kosong sambil mencapai EOF, loop dapat berhenti saat masih ada byte yang diharapkan tanpa melewati cabang empty-read.
- Setelah loop, endpoint kini memeriksa jumlah byte tersisa. Jika belum seluruh byte terkirim dan klien tidak memutus koneksi, log run ditutup sebagai `failed` dengan alasan dan jumlah byte yang tersisa. Pemutusan koneksi klien tetap mengikuti lifecycle player.
- Commit kode: [1dd128c](https://github.com/Al-Faroz/Bel_Madrasah/commit/1dd128ced9151b458fd808f94e4cf917a74a3ada). Spesifikasi diperbarui pada [af2b89a](https://github.com/Al-Faroz/Bel_Madrasah/commit/af2b89a8e1416f09d24ed81bf360e7778c1960ed).
- Status: **IMPLEMENTED; belum diuji dengan file yang sengaja terpotong atau runtime PHP Desktop Windows**. Setelah pull, uji file valid MP3/WAV, file kosong, file hilang, dan file yang berubah/terpotong ketika sedang dibaca. Pastikan satu kegagalan tidak menghentikan polling bel berikutnya.

## Perbaikan lanjutan — mempertahankan alasan kegagalan audio di UI (2026-10-26)

- Saat endpoint stream sudah menutup log dengan alasan spesifik (misalnya file tidak terbaca atau stream terpotong), player tetap mengirim aksi `finish`. Sebelumnya respons konflik hanya mengatakan log sudah ditutup, sehingga UI tidak menampilkan alasan yang sudah dicatat.
- Respons `scheduler.php` kini menyertakan status dan pesan log yang tersimpan ketika run sudah ditutup. UI dapat mempertahankan alasan kegagalan asli tanpa menimpa log dengan pesan browser yang lebih umum.
- Commit kode: [fe564d6](https://github.com/Al-Faroz/Bel_Madrasah/commit/fe564d6cec3a6c29756acfe85c4cca3ae84eae41); spesifikasi diperbarui pada [8eaa021](https://github.com/Al-Faroz/Bel_Madrasah/commit/8eaa021bb40f4a13894514e56472526beca374b3).
- Status: **IMPLEMENTED; belum diuji di PHP Desktop Windows**. Perlu uji negatif file hilang, file kosong, format tak didukung, dan stream terputus; pastikan pesan UI sesuai dengan alasan yang tersimpan di riwayat dan scheduler tetap memeriksa jadwal berikutnya.

## Regresi — detail kegagalan playback tidak boleh tertimpa (2026-10-26)

- Fixture `tests/SchedulerPlaybackLogTest.php` kini membuat satu run yang berakhir `failed` dengan alasan spesifik, mencoba menutupnya kembali sebagai `completed`, lalu memverifikasi bahwa status dan pesan kegagalan awal tetap persis tersimpan.
- Urutan fixture dijaga unik agar skenario baru tidak menambah ambiguitas pada daftar kegiatan.
- Commit tes: [53f6ede](https://github.com/Al-Faroz/Bel_Madrasah/commit/53f6edee72fe3af7281e8ba863c8e6a1963a67ae) dan [e482952](https://github.com/Al-Faroz/Bel_Madrasah/commit/e48295201b7329ab5e0dc304c24f0887ca2a0c6f). Kriteria penerimaan ditambahkan ke [rencana tes](https://github.com/Al-Faroz/Bel_Madrasah/blob/main/docs/04-TEST-PLAN.md).
- Status: **TES DITAMBAHKAN; BELUM DIEKSEKUSI**. Jalankan `php tests/SchedulerPlaybackLogTest.php` pada PHP CLI dengan PDO SQLite, lalu tetap lakukan uji kegagalan audio di PHP Desktop Windows.

